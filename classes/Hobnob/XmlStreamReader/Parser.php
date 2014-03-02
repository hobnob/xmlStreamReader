<?php
/**
 * Parses a string or stream of XML, calling back to a function when a
 * specified element is found
 *
 * @author David North
 * @package Stream
 * @package Stream\Xml
 * @license http://opensource.org/licenses/mit-license.php
 */
namespace Hobnob\XmlStreamReader;

use SimpleXMLElement;
use Exception;

class Parser
{
    /**
     * @var array An array of registered callbacks
     */
    private $callbacks = array();

    /**
     * @var string The current node path being investigated
     */
    private $currentPath = '/';

    /**
     * @var array An array path data for paths that require callbacks
     */
    private $pathData = array();

    /**
     * @var boolean Whether or not the object is currently parsing
     */
    private $parse = FALSE;

    /**
     * @var array A list of namespaces in this XML
     */
    private $namespaces = array();

    /**
     * Parses the XML provided using streaming and callbacks
     *
     * @param mixed $data      Either a stream resource or string containing XML
     * @param int   $chunkSize The size of data to read in at a time. Only
     *                         relevant if $data is a stream
     *
     * @return Parser
     * @throws Exception
     */
    public function parse($data, $chunkSize = 1024)
    {
        //Ensure that the $data var is of the right type
        if (!is_string($data) && (!is_resource($data) || get_resource_type($data) !== 'stream')) {
            throw new Exception('Data must be a string or a stream resource');
        }

        //Ensure $chunkSize is the right type
        if (!is_int($chunkSize)) {
            throw new Exception('Chunk size must be an integer');
        }

        //Initialise the object
        $this->init();

        //Create the parser and set the parsing flag
        $this->parse = TRUE;
        $parser      = xml_parser_create();

        //Set the parser up, ready to stream through the XML
        xml_set_object($parser, $this);

        //Set up the protected methods _start and _end to deal with the start
        //and end tags respectively
        xml_set_element_handler($parser, 'start', 'end');

        //Set up the _addCdata method to parse any CDATA tags
        xml_set_character_data_handler($parser, 'addCdata');

        //For general purpose data, use the _addData method
        xml_set_default_handler($parser, 'addData');

        //If the data is a resource then loop through it, otherwise just parse
        //the string
        if (is_resource($data)) {
            //Not all resources support fseek. For those that don't, suppress
            // /the error
            @fseek($data, 0);

            while ($this->parse && $chunk = fread($data, $chunkSize)) {
                $this->parseString($parser, $chunk, feof($data));
            }
        } else {
            $this->parseString($parser, $data, TRUE);
        }

        //Free up the parser
        xml_parser_free($parser);

        return $this;
    }

    /**
     * Registers a single callback for a specified XML path
     *
     * @param string   $path     The path that the callback is for
     * @param callable $callback The callback mechanism to use
     *
     * @return Parser
     * @throws Exception
     */
    public function registerCallback($path, $callback)
    {
        //Ensure the path is a string
        if (!is_string($path)) {
            throw new Exception('Path must be a string');
        }

        //Ensure that the callback is callable
        if (!is_callable($callback)) {
            throw new Exception('Callback must be callable');
        }

        //All tags and paths are lower cased, for consistency
        $path = strtolower($path);
        if (substr($path, -1, 1) !== '/') {
            $path .= '/';
        }

        //If this is the first callback for this path, initialise the variable
        if (!isset($this->callbacks[$path])) {
            $this->callback[$path] = array();
        }

        //Add the callback
        $this->callbacks[$path][] = $callback;

        return $this;
    }

    /**
     * Registers multiple callbacks for the specified paths, for example
     * <code>
     *  $parser->registerCallbacks(array(
     *      array('/path/to/element', 'callback'),
     *      array('/path/to/another/element', array($this, 'callback')),
     * ));
     * </code>
     *
     * @param Array $pathCallbacks An array of paths and callbacks
     *
     * @return Parser
     * @throws Exception
     */
    public function registerCallbacks(Array $pathCallbacks)
    {
        foreach ($pathCallbacks as $row) {
            if (count($row) != 2) {
                throw new Exception(
                    'Each array element in $pathCallbacks must be an array of'
                    .' 2 elements (the path and the callback)'
                );
            }

            list($path, $callback) = $row;
            $this->registerCallback($path, $callback);
        }

        return $this;
    }

    /**
     * Stops the parser from parsing any more. Because of the nature of
     * streaming there may be more data to read. If this is the case then no
     * further callbacks will be called.
     *
     * @return Parser
     */
    public function stopParsing()
    {
        $this->parse = FALSE;

        return $this;
    }

    /**
     * Initialise the object variables
     *
     * @return NULL
     */
    private function init()
    {
        $this->namespaces  = array();
        $this->currentPath = '/';
        $this->pathData    = array();
        $this->parse       = FALSE;
    }

    /**
     * Parse data using xml_parse
     *
     * @param resource $parser  The XML parser
     * @param string   $data    The data to parse
     * @param boolean  $isFinal Whether or not this is the final part to parse
     *
     * @return NULL
     * @throws Exception
     */
    protected function parseString($parser, $data, $isFinal)
    {
        if (!xml_parse($parser, $data, $isFinal)) {
            throw new Exception(
                xml_error_string(xml_get_error_code($parser))
                .' At line: '.
                xml_get_current_line_number($parser)
            );
        }
    }

    /**
     * Parses the start tag
     *
     * @param resource $parser     The XML parser
     * @param string   $tag        The tag that's being started
     * @param array    $attributes The attributes on this tag
     *
     * @return NULL
     */
    protected function start($parser, $tag, $attributes)
    {
        //Set the tag as lower case, for consistency
        $tag = strtolower($tag);

        //Update the current path
        $this->currentPath .= $tag.'/';

        //Go through each callback and ensure that path data has been
        //started for it
        foreach ($this->callbacks as $path => $callbacks) {
            if ($path === $this->currentPath) {
                $this->pathData[ $this->currentPath ] = '';
            }
        }

        //Generate the tag, with attributes. Attribute names are also lower
        //cased, for consistency
        $data = '<'.$tag;
        foreach ($attributes as $key => $val) {
            $options = ENT_QUOTES;
            if (defined('ENT_XML1')) {
                $options |= ENT_XML1;
            }

            $val   = htmlentities($val, $options, "UTF-8");
            $data .= ' '.strtolower($key).'="'.$val.'"';

            if (stripos($key, 'xmlns:') !== false) {
                $key = strtolower($key);
                $key = str_replace('xmlns:', '', $key);
                $this->namespaces[strtolower($key)] = $val;
            }
        }
        $data .= '>';

        //Add the data to the path data required
        $this->addData($parser, $data);
    }

    /**
     * Adds CDATA to any paths that require it
     *
     * @param resource $parser
     * @param string   $data
     *
     * @return NULL
     */
    protected function addCdata($parser, $data)
    {
        $this->addData($parser, '<![CDATA['.$data.']]>');
    }

    /**
     * Adds data to any paths that require it
     *
     * @param resource $parser
     * @param string   $data
     *
     * @return NULL
     */
    protected function addData($parser, $data)
    {
        //Having a path data entry means at least 1 callback is interested in
        //the data. Loop through each path here and, if inside that path, add
        //the data
        foreach ($this->pathData as $key => $val) {
            if (strpos($this->currentPath, $key) !== FALSE) {
                $this->pathData[$key] .= $data;
            }
        }
    }

    /**
     * Parses the end of a tag
     *
     * @param resource $parser
     * @param string   $tag
     *
     * @return NULL
     */
    protected function end($parser, $tag)
    {
        //Make the tag lower case, for consistency
        $tag = strtolower($tag);

        //Add the data to the paths that require it
        $data = '</'.$tag.'>';
        $this->addData($parser, $data);

        //Loop through each callback and see if the path matches the
        //current path
        foreach ($this->callbacks as $path => $callbacks) {
            //If parsing should continue, and the paths match, then a callback
            //needs to be made
            if ($this->parse && $this->currentPath === $path) {
                if (!$this->fireCallbacks($path, $callbacks)) {
                    break;
                }
            }
        }

        //Unset the path data for this path, as it's no longer needed
        unset($this->pathData[ $this->currentPath ]);

        //Update the path with the new path (effectively moving up a directory)
        $this->currentPath = substr(
            $this->currentPath,
            0,
            strlen($this->currentPath) - (strlen($tag) + 1)
        );
    }

    /**
     * Generates a SimpleXMLElement and passes it to each of the callbacks
     *
     * @param string $path      The path to create the SimpleXMLElement from
     * @param array  $callbacks An array of callbacks to be fired.
     *
     * @return boolean
     */
    protected function fireCallbacks($path, array $callbacks)
    {
        $namespaceStr = '';
        $namespaces   = $this->namespaces;
        $matches      = array();
        $pathData     = $this->pathData[ $path ];
        $regex        = '/xmlns:(?P<namespace>[^=]+)="[^\"]+"/sm';

        // Make sure any namespaces already defined in this element are not
        // defined again
        if (preg_match_all($regex, $pathData, $matches)) {
            foreach ($matches['namespace'] as $key => $value) {
                unset($namespaces[$value]);
            }
        }

        // Define all remaining namespaces on the root element
        foreach ($namespaces as $key => $val) {
            $namespaceStr .= ' xmlns:'.$key.'="'.$val.'"';
        }

        //Build the SimpleXMLElement object. As this is a partial XML
        //document suppress any warnings or errors that might arise
        //from invalid namespaces
        $data = new SimpleXMLElement(
            preg_replace('/^(<[^\s>]+)/', '$1'.$namespaceStr, $pathData),
            LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        //Loop through each callback. If one of them stops the parsing
        //then cease operation immediately
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, array($this, $data));

            if (!$this->parse) {
                return false;
            }
        }

        return true;
    }
}
