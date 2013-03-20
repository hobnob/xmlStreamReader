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
    public function testReturnObjects($data)
    {
        $expectedObj = new StdClass;
        $passed      = 0;
        $xmlParser   = new xmlStreamReader();

        $expectedObj->attributes = array();
        $expectedObj->data       = '';
        $expectedObj->nodes      = array(
            'title' => new StdClass,
            'url'   => new StdClass,
            'link'  => new StdClass,
        );

        $expectedObj->nodes['title']->data = 'Technology news, comment and analysis | guardian.co.uk';
        $expectedObj->nodes['title']->attributes = array();

        $expectedObj->nodes['url']->data = 'http://image.guardian.co.uk/sitecrumbs/Guardian.gif';
        $expectedObj->nodes['url']->attributes = array();

        $expectedObj->nodes['link']->data = 'http://www.guardian.co.uk/technology';
        $expectedObj->nodes['link']->attributes = array();

        $callback = function( $actualObj ) use (&$passed, $expectedObj) {
            $this->assertEquals( $expectedObj, $actualObj );
            $passed++;
        };

        $xmlParser->registerCallback('/rss/channel/image', $callback);
        $xmlParser->parse($data);

        $this->assertGreaterThan( 0, $passed );
    }
}