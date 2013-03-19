<?php
class xmlStreamReaderTest extends PHPUnit_Framework_TestCase
{
    public function testReturnValue()
    {
        $file      = fopen(__DIR__.'/test.xml', 'r');
        $xmlParser = new xmlStreamReader();

        $this->assertSame( $xmlParser, $xmlParser->parse($file) );
        $this->assertSame( $xmlParser, $xmlParser->parse($file), 2000 );

        $data = '<xml>Some data</xml>';
        $this->assertSame( $xmlParser, $xmlParser->parse($data) );
        $this->assertSame( $xmlParser, $xmlParser->parse($data), 2000 );
    }

    public function testSingleCallback()
    {
        $passed    = FALSE;
        $file      = fopen(__DIR__.'/test.xml', 'r');
        $xmlParser = new xmlStreamReader();

        $callback = function() use (&$passed) {
            $passed = TRUE;
        };

        $xmlParser->registerCallback('/rss/channel/title', $callback);
        $xmlParser->parse($file);

        $this->assertTrue( $passed );

        $passed = FALSE;
        $xmlParser->registerCallback('/xml/callback', $callback);
        $xmlParser->parse('<xml><callback /></xml>');

        $this->assertTrue( $passed );
    }

    public function testMultipleCalls()
    {
        $called        = 0;
        $expectedItems = 25;
        $file          = fopen(__DIR__.'/test.xml', 'r');
        $xmlParser     = new xmlStreamReader();

        $callback = function() use (&$called) {
            $called++;
        };

        $xmlParser->registerCallback('/rss/channel/item', $callback);
        $xmlParser->parse($file);

        $this->assertSame( $expectedItems, $called );

        $called = 0;
        $xmlParser->registerCallback('/xml/callback', $callback);
        $xmlParser->parse('<xml><callback /></xml>');

        $this->assertSame( 1, $called );
    }

    public function testMultipleCallbacks()
    {
        $called1       = 0;
        $called2       = 0;
        $expectedItems = 50;
        $file          = fopen(__DIR__.'/test.xml', 'r');
        $xmlParser     = new xmlStreamReader();

        $callback = function() use (&$called1) {
            $called1++;
        };

        $callback2 = function() use (&$called2) {
            $called2++;
        };

        $xmlParser->registerCallback('/rss/channel/item', $callback);
        $xmlParser->registerCallback('/rss/channel/item', $callback2);
        $xmlParser->parse($file);

        $this->assertSame( $expectedItems / 2, $called1 );
        $this->assertSame( $expectedItems / 2, $called2 );
        $this->assertSame( $expectedItems, $called1 + $called2 );

        $called1 = 0;
        $called2 = 0;
        $xmlParser->registerCallback('/xml/callback', $callback);
        $xmlParser->registerCallback('/xml/callback', $callback2);
        $xmlParser->parse('<xml><callback /></xml>');

        $this->assertSame( 1, $called1 );
        $this->assertSame( 1, $called2 );
        $this->assertSame( 2, $called1 + $called2 );
    }

    public function testMultipleNamespaces()
    {
        $called1        = 0;
        $called2        = 0;
        $expectedItems  = 25;
        $expectedItems2 = 240;
        $file           = fopen(__DIR__.'/test.xml', 'r');
        $xmlParser      = new xmlStreamReader();

        $callback = function() use (&$called1) {
            $called1++;
        };

        $callback2 = function() use (&$called2) {
            $called2++;
        };

        $xmlParser->registerCallback('/rss/channel/item', $callback);
        $xmlParser->registerCallback('/rss/channel/item/category', $callback2);
        $xmlParser->parse($file);

        $this->assertSame( $expectedItems, $called1 );
        $this->assertSame( $expectedItems2, $called2 );

        $called1 = 0;
        $called2 = 0;
        $xmlParser->registerCallback('/xml/callback', $callback);
        $xmlParser->registerCallback('/xml/anothercallback/title', $callback2);
        $xmlParser->parse('
            <xml>
                <callback />
                <anothercallback>
                    <title>Text</title>
                </anothercallback>
            </xml>');

        $this->assertSame( 1, $called1 );
        $this->assertSame( 1, $called2 );
        $this->assertSame( 2, $called1 + $called2 );
    }

    public function testReturnObjects()
    {
        $expectedObj = new StdClass;
        $passed      = 0;
        $file        = fopen(__DIR__.'/test.xml', 'r');
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
        $xmlParser->parse($file);

        $this->assertGreaterThan( 0, $passed );
/*
        $called1 = 0;
        $called2 = 0;
        $xmlParser->registerCallback('/xml/callback', $callback);
        $xmlParser->registerCallback('/xml/anothercallback/title', $callback2);
        $xmlParser->parse('
            <xml>
                <callback />
                <anothercallback>
                    <title>Text</title>
                </anothercallback>
            </xml>');

        $this->assertSame( 1, $called1 );
        $this->assertSame( 1, $called2 );
        $this->assertSame( 2, $called1 + $called2 );*/
    }
}