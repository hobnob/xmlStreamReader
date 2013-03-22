xmlStreamReader
===============

[![Build Status](https://travis-ci.org/hobnob/xmlStreamReader.png?branch=master)](https://travis-ci.org/hobnob/xmlStreamReader)

##PHP XML Stream Reader

Reads XML from either a string or a stream, allowing the registration of callbacks when an elemnt is found that matches path.

Installation with Composer
-------------

Declare xmlStreamReader as a dependency in your projects `composer.json` file:

``` json
{
    "require": {
      "hobnob/xmlStreamReader": "1.0.0"
    }
}
```

Usage Example
-------------

```php
<?php

$xmlParser = new xmlStreamReader();

$xmlParser->registerCallback(
    '/xml/node/path',
    function( xmlStreamReader $parser, SimpleXMLElement $node ) {
        // do stuff with $node
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
