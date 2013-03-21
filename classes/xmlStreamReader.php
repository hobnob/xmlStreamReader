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

        $this->_parse = TRUE;
        $parser       = xml_parser_create();

        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, '_start', '_end' );
        xml_set_character_data_handler( $parser, '_addCdata' );
        xml_set_default_handler( $parser, '_addData' );

        if ( is_resource( $data ) )
        {
            fseek( $data, 0 );
            while( $this->_parse && $chunk = fread($data, $chunkSize) )
            {
                $this->_parseString( $parser, $chunk, feof($data) );
            }
        }
        else
        {
            $this->_parseString( $parser, $data, TRUE );
        }
        
        xml_parser_free( $parser );
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

        $namespace = strtolower($namespace);
        if ( substr($namespace, -1, 1) !== '/' )
        {
            $namespace .= '/';
        }

        if ( !isset( $this->_callbacks[$namespace] ) )
        {
            $this->_callback[$namespace] = array();    
        }

        $this->_callbacks[$namespace][] = $callback;
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

    protected function _parseString( $parser, $data, $isFinal )
    {
        if (!xml_parse($parser, $data, $isFinal))
        {
            throw new Exception(
                xml_error_string( xml_get_error_code( $parser ) )
                .' At line: '.
                xml_get_current_line_number( $parser )
            );
        }
    }

    protected function _start( $parser, $tag, $attributes )
    {
        $tag = strtolower($tag);
        $this->_currentNamespace .= $tag.'/';
        foreach( $this->_callbacks as $namespace => $callbacks )
        {
            if ( $namespace === $this->_currentNamespace )
            {
                $this->_namespaceData[ $this->_currentNamespace ] = '';
            }
        }

        $data = '<'.$tag;
        foreach ( $attributes as $key => $val )
        {
            $data .= ' '.strtolower($key).'="'.$val.'"';
        }
        $data .= '>';

        $this->_addData( $parser, $data );
    }

    protected function _addCdata( $parser, $data )
    {
        if ( trim($data) )
        {
            $this->_addData( $parser, '<![CDATA['.$data.']]>');
        }
    }

    protected function _addData( $parser, $data )
    {
        foreach ($this->_namespaceData as $key => $val)
        {
            if ( strpos($this->_currentNamespace, $key) !== FALSE )
            {
                $this->_namespaceData[$key] .= $data;
            }
        }
    }

    protected function _end( $parser, $tag )
    {
        $tag = strtolower($tag);
        $data = '</'.$tag.'>';
        $this->_addData( $parser, $data );

        foreach( $this->_callbacks as $namespace => $callbacks )
        {
            if ( $this->_parse && $this->_currentNamespace === $namespace )
            {
                $data = new SimpleXMLElement(
                    $this->_namespaceData[ $namespace ], LIBXML_NOERROR
                );

                foreach ( $callbacks as $callback )
                {
                    call_user_func_array( $callback, array($this, $data) );

                    if ( !$this->_parse )
                    {
                        return;
                    }
                }
            }
        }

        unset( $this->_namespaceData[ $this->_currentNamespace ] );
        $this->_currentNamespace = substr(
            $this->_currentNamespace,
            0,
            strlen($this->_currentNamespace) - (strlen($tag) + 1)
        );
    }
}