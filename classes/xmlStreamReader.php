<?php
class xmlStreamReader
{
    private $_callbacks        = array();
    private $_currentNamespace = '/';
    private $_namespaceData    = array();

    public function parse( $data, $chunkSize = 1024 )
    {
        if ( !is_string( $data )
            && ( !is_resource( $data ) || get_resource_type($data) !== 'stream' )
        )
        {
            throw new Exception( 'Data must be a string or a stream resource' );
        }

        $parser = xml_parser_create();

        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, '_start', '_end' );
        xml_set_character_data_handler( $parser, '_data' ); 

        if ( is_resource( $data ) )
        {
            while( $chunk = fread($data, $chunkSize) )
            {
                $this->_parseString( $parser, $chunk, feof($data) );
            }
        }
        else
        {
            $this->_parseString( $parser, $data, TRUE );
        }
        
        xml_parser_free($parser);
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
        $this->_currentNamespace .= $tag.'/';

        $obj             = new StdClass;
        $obj->data       = '';
        $obj->attributes = $attributes;

        $this->_namespaceData[$this->_currentNamespace] = $obj;
    }

    protected function _data( $parser, $data )
    {
        $this->_namespaceData[$this->_currentNamespace]->data .= $data;
    }

    protected function _end( $parser, $tag )
    {
        $namespaceParts = explode( '/', $this->_currentNamespace );

        foreach( $this->_callbacks as $namespace => $callback )
        {
            if ( strpos( $namespace, $this->_currentNamespace ) !== FALSE )
            {
                $obj = $callback['data'];
                foreach ( $namespaceParts as $part )
                {
                    if ( !isset( $obj->{$part} ) )
                    {
                        $obj->{$part} = new StdClass;
                    }

                    $obj = $obj->{$part};
                }

                $obj->{end($namespaceParts)} =
                    $this->_namespaceData[$this->_currentNamespace];
            }

            if ( $namespace === $this->_currentNamespace )
            {
                call_user_func_array(
                    $callback['callback'], array($callback['data'])
                );

                $callback['data'] = new StdClass;
            }
        }

        unset( $this->_namespaceData[$this->_currentNamespace] );
        $this->_currentNamespace = substr(
            $this->_currentNamespace,
            strlen($this->_currentNamespace),
            strlen(end($namespaceParts)) + 1
        );
    }
}