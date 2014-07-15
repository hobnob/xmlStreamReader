xmlStreamReader
===============

[![Build Status](https://travis-ci.org/hobnob/xmlStreamReader.png?branch=master)](https://travis-ci.org/hobnob/xmlStreamReader)
[![Dependency Status](https://www.versioneye.com/user/projects/530f5fcfec1375418400061f/badge.png)](https://www.versioneye.com/user/projects/530f5fcfec1375418400061f)
[![Latest Stable Version](https://poser.pugx.org/hobnob/xml-stream-reader/v/stable.png)](https://packagist.org/packages/hobnob/xml-stream-reader)
[![Montly Downloads](https://poser.pugx.org/hobnob/xml-stream-reader/d/monthly.png)](https://packagist.org/packages/hobnob/xml-stream-reader)

[![Code Coverage](https://scrutinizer-ci.com/g/hobnob/xmlStreamReader/badges/coverage.png?s=e7125d974c335f061eda9d95358be4a7eaf9e3ac)](https://scrutinizer-ci.com/g/hobnob/xmlStreamReader/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/hobnob/xmlStreamReader/badges/quality-score.png?s=877ea5bc73f1d974aaf25f2c145cf2cea739e2ea)](https://scrutinizer-ci.com/g/hobnob/xmlStreamReader/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/065c2cd4-76f4-4a36-b6e5-cd5368942daf/mini.png)](https://insight.sensiolabs.com/projects/065c2cd4-76f4-4a36-b6e5-cd5368942daf)


##PHP XML Stream Reader

Reads XML from either a string or a stream, allowing the registration of callbacks when an elemnt is found that matches path.

Installation with Composer
-------------

Declare xmlStreamReader as a dependency in your projects `composer.json` file:

``` json
{
    "require": {
      "hobnob/xml-stream-reader": "1.0.*"
    }
}
```

Usage Example
-------------

```php
<?php

$xmlParser = new \Hobnob\XmlStreamReader\Parser();

$xmlParser->registerCallback(
    '/xml/node/path',
    function( \Hobnob\XmlStreamReader\Parser $parser, \SimpleXMLElement $node ) {
        // do stuff with $node
    }
);

$xmlParser->registerCallback(
    '/xml/node/@attr',
    function( \Hobnob\XmlStreamReader\Parser $parser, $attrValue ) {
        // do stuff with $attrValue
    }
);

$xmlParser->parse(fopen('file.xml', 'r'));
```

Contributors
------------

  * [David North](https://github.com/hobnob)
  * [more](https://github.com/hobnob/xmlStreamReader/contributors)


Licence
-------

© David North

Released under the [The MIT License](http://www.opensource.org/licenses/mit-license.php)
