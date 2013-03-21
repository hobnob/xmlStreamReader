<?php
/**
 * Parses a string or stream of XML, calling back to a function when a
 * specified element is found
 *
 * @author David North
 * @package xmlStreamReader
 * @license http://opensource.org/licenses/mit-license.php
 */
class xmlStreamReader
{
    private $_callbacks        = array();
    private $_currentNamespace = '/';
    private $_namespaceData    = array();
    private $_parse            = FALSE;
    private $_xmlParser        = NULL;

    public function parse( $data, $chunkSize = 1024 )
    {
        if ( !is_string( $data )
            && ( !is_resource( $data ) || get_resource_type($data) !== 'stream' )
        )
        {
            throw new Exception( 'Data must be a string or a stream resource' );
        }

        if ( !is_int( $chunkSize ) )
        {
            throw new Exception( 'Chunk size must be an integer' );
        }

        $this->_init();

        $this->_parse     = TRUE;
        $this->_xmlParser = xml_parser_create();

        xml_set_object( $this->_xmlParser, $this );
        xml_set_element_handler( $this->_xmlParser, '_start', '_end' );
        xml_set_character_data_handler( $this->_xmlParser, '_data' );
        xml_set_default_handler( $this->_xmlParser, '_data' );

        $obj             = new StdClass;
        $obj->data       = '';
        $obj->attributes = array();

        $this->_namespaceData['/'] = $obj;

        if ( is_resource( $data ) )
        {
            fseek( $data, 0 );
            while( $this->_parse && $chunk = fread($data, $chunkSize) )
            {
                $this->_parseString( $chunk, feof($data) );
            }
        }
        else
        {
            $this->_parseString( $data, TRUE );
        }
        
        xml_parser_free( $this->_xmlParser );
        return $this;
    }

    public function registerCallback( $namespace, $callback )
    {
        if ( !is_string( $namespace ) )
        {
            throw new Exception('Namespace must be a string');
        }

        if ( !is_callable( $callback ) )
        {
            throw new Exception('Callback must be callable');
        }

        $namespace = strtoupper($namespace);
        if ( substr($namespace, -1, 1) !== '/' )
        {
            $namespace .= '/';
        }

        if ( !isset( $this->_callbacks[$namespace] ) )
        {
            $this->_callback[$namespace] = array();    
        }

        $this->_callbacks[$namespace][] = array(
            'data'     => new StdClass,
            'callback' => $callback,
        );

        return $this;
    }

    public function stopParsing()
    {
        $this->_parse = FALSE;
    }

    private function _init()
    {
        $this->_currentNamespace = '/';
        $this->_namespaceData    = array();
        $this->_parse            = FALSE;
    }

    protected function _parseString( $data, $isFinal )
    {
        if (!xml_parse($this->_xmlParser, $data, $isFinal))
        {
            throw new Exception(
                xml_error_string( xml_get_error_code( $this->_xmlParser ) )
                .' At line: '.
                xml_get_current_line_number( $this->_xmlParser )
            );
        }
    }

    protected function _start( $parser, $tag, $attributes )
    {
        $this->_currentNamespace .= $tag.'/';

        $obj             = new StdClass;
        $obj->data       = '';
        $obj->attributes = array();

        foreach ( $attributes as $key => $val )
        {
            $obj->attributes[strtolower($key)] = $val;
        }

        $this->_namespaceData[$this->_currentNamespace] = $obj;
    }

    protected function _data( $parser, $data )
    {
        $this->_namespaceData[$this->_currentNamespace]->data .= $data;
    }

    protected function _end( $parser, $tag )
    {
        $namespaceParts = explode(
            '/', trim( strtolower($this->_currentNamespace), '/' ) );

        foreach( $this->_callbacks as $namespace => $callbacks )
        {
            if ( !$this->_parse )
            {
                return;
            }

            if ( strpos( $this->_currentNamespace, $namespace ) !== FALSE )
            {
                foreach ( $callbacks as $key => $callback )
                {
                    $obj = $callback['data'];
                    foreach ( $namespaceParts as $part )
                    {
                        if ( !isset( $obj->nodes ) )
                        {
                            $obj->nodes = array();
                        }

                        if ( !isset( $obj->nodes[$part] ) )
                        {
                            $obj->nodes[$part] = new StdClass;
                        }

                        $obj = $obj->nodes[$part];
                    }

                    $obj->data =
                        trim($this->_namespaceData[$this->_currentNamespace]->data);

                    $obj->attributes =
                        $this->_namespaceData[$this->_currentNamespace]->attributes;

                    if ( $namespace === $this->_currentNamespace )
                    {
                        //Find part of the object that is referenced by the
                        //namespace
                        call_user_func_array(
                            $callback['callback'], array($this, $obj)
                        );

                        $callback['data'] = new StdClass;
                    }
                }
            }
        }

        unset( $this->_namespaceData[$this->_currentNamespace] );
        $this->_currentNamespace = substr(
            $this->_currentNamespace,
            0,
            strlen($this->_currentNamespace) - (strlen($tag) + 1)
        );
    }
}