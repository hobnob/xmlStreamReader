<?php
class xmlStreamReaderTest extends PHPUnit_Framework_TestCase
{

    public function getData()
    {
        return array(
            array(fopen(__DIR__.'/test.xml', 'r')),
            array(file_get_contents(__DIR__.'/test.xml')),
        );
    }

    public function testNonStringParse()
    {
        $xmlParser = new xmlStreamReader();
        $this->setExpectedException(
            'Exception', 'Data must be a string or a stream resource'
        );

        $xmlParser->parse(1);
    }

    public function testInvalidResourceParse()
    {
        $resource  = xml_parser_create();
        $xmlParser = new xmlStreamReader();
        $this->setExpectedException(
            'Exception', 'Data must be a string or a stream resource'
        );

        $xmlParser->parse($resource);
    }

    public function testInvalidChunkSize()
    {
        $xmlParser = new xmlStreamReader();
        $this->setExpectedException(
            'Exception', 'Chunk size must be an integer'
        );

        $xmlParser->parse('<xml>data</xml>', '1024');
    }

    public function testInvalidXml()
    {
        $xmlParser = new xmlStreamReader();
        $this->setExpectedException(
            'Exception', 'Mismatched tag'
        );

        $xmlParser->parse('<xml><unclosedTag>data</xml>');
    }

    public function testInvalidNamespace()
    {
        $xmlParser = new xmlStreamReader();
        $this->setExpectedException(
            'Exception', 'Namespace must be a string'
        );

        $xmlParser->registerCallback(1234, function() {});
    }

    public function testInvalidFunction()
    {
        $xmlParser = new xmlStreamReader();
        $this->setExpectedException(
            'Exception', 'Callback must be callable'
        );

        $xmlParser->registerCallback('/', 'someUndefinedMethod');
    }

    /**
     * @dataProvider getData
     */
    public function testReturnValue( $data )
    {
        $xmlParser = new xmlStreamReader();

        $this->assertSame( $xmlParser, $xmlParser->parse($data) );
        $this->assertSame( $xmlParser, $xmlParser->parse($data), 2000 );
    }

    /**
     * @dataProvider getData
     */
    public function testSingleCallback( $data )
    {
        $passed    = FALSE;
        $xmlParser = new xmlStreamReader();

        $callback = function() use (&$passed) {
            $passed = TRUE;
        };

        $xmlParser->registerCallback('/rss/channel/title', $callback);
        $xmlParser->parse($data);

        $this->assertTrue( $passed );
    }

    /**
     * @dataProvider getData
     */
    public function testMultipleCalls( $data )
    {
        $called        = 0;
        $expectedItems = 25;
        $xmlParser     = new xmlStreamReader();

        $callback = function() use (&$called) {
            $called++;
        };

        $xmlParser->registerCallback('/rss/channel/item', $callback);
        $xmlParser->parse($data);

        $this->assertSame( $expectedItems, $called );
    }

    /**
     * @dataProvider getData
     */
    public function testMultipleCallbacks( $data )
    {
        $called1       = 0;
        $called2       = 0;
        $expectedItems = 50;
        $xmlParser     = new xmlStreamReader();

        $callback = function() use (&$called1) {
            $called1++;
        };

        $callback2 = function() use (&$called2) {
            $called2++;
        };

        $xmlParser->registerCallback('/rss/channel/item', $callback);
        $xmlParser->registerCallback('/rss/channel/item', $callback2);
        $xmlParser->parse($data);

        $this->assertSame( $expectedItems / 2, $called1 );
        $this->assertSame( $expectedItems / 2, $called2 );
        $this->assertSame( $expectedItems, $called1 + $called2 );
    }

    /**
     * @dataProvider getData
     */
    public function testMultipleNamespaces( $data )
    {
        $called1        = 0;
        $called2        = 0;
        $expectedItems  = 25;
        $expectedItems2 = 240;
        $xmlParser      = new xmlStreamReader();

        $callback = function() use (&$called1) {
            $called1++;
        };

        $callback2 = function() use (&$called2) {
            $called2++;
        };

        $xmlParser->registerCallback('/rss/channel/item', $callback);
        $xmlParser->registerCallback('/rss/channel/item/category', $callback2);
        $xmlParser->parse($data);

        $this->assertSame( $expectedItems, $called1 );
        $this->assertSame( $expectedItems2, $called2 );
    }

    /**
     * @dataProvider getData
     */
    public function testStopParsing( $data )
    {
        $called1        = 0;
        $called2        = 0;
        $expectedItems  = 1;
        $expectedItems2 = 8;
        $xmlParser      = new xmlStreamReader();

        $callback = function($parser) use (&$called1) {
            $called1++;
            $parser->stopParsing();
        };

        $callback2 = function() use (&$called2) {
            $called2++;
        };

        $xmlParser->registerCallback('/rss/channel/item', $callback);
        $xmlParser->registerCallback('/rss/channel/item/category', $callback2);
        $xmlParser->parse($data);

        $this->assertSame( $expectedItems, $called1 );
        $this->assertSame( $expectedItems2, $called2 );
    }

    /**
     * @dataProvider getData
     */
    public function testReturnObjects($data)
    {
        $passed      = 0;
        $xmlParser   = new xmlStreamReader();


        $expectedObj = new SimpleXmlElement("
            <image>
                <title>Technology news, comment and analysis | guardian.co.uk</title>
                <url>http://image.guardian.co.uk/sitecrumbs/Guardian.gif</url>
                <link>http://www.guardian.co.uk/technology</link>
            </image>
        ");

        $test     = $this;
        $callback = function( $parser, $actualObj )
                        use (&$passed, $expectedObj, $test) {
            $test->assertEquals( $expectedObj, $actualObj );
            $passed++;
        };

        $xmlParser->registerCallback('/rss/channel/image', $callback);
        $xmlParser->parse($data);

        $this->assertGreaterThan( 0, $passed );
    }
}