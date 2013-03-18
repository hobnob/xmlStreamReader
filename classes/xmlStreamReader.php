<?php
class xmlStreamReader
{
    private $_callbacks = array();

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
            $this->_parseString( $parser, $chunk, TRUE );
        }
        
        xml_parser_free($xml_parser);
    }

    protected function _parseString( $parser, $data, $isFinal )
    {
        if (!xml_parse($parser, $data, $isFinal))
        {
            throw new Exception(
                xml_error_string( xml_get_error_code( $xml_parser ) )
                .' At line: '.
                xml_get_current_line_number( $xml_parser )
            );
        }
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
            'callback' => $callback;
        );

        return $this;
    }

    protected function _start( $parser, $tag, $attributes )
    {

    }

    protected function _data()
    {

    }

    protected function _end()
    {

    }
}