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
    "hobnob/xmlStreamReader": "dev-master"
  }
}
```

Usage Example
-------------

```php
<?php

// If you aren't using composer, load xmlStreamReader
require_once('classes/xmlStreamReader.php');

$xmlParser = new xmlStreamReader();
$file      = fopen('file.xml', 'r');
$callback  = function( xmlStreamReader $parser, StdClass $node ) {
    print_r( $node );
};

$xmlParser->registerCallback('/xml/node/path', $callback);
$xmlParser->parse($file);
```

Contributors
------------

  * [David North](https://github.com/hobnob)
  * [more](https://github.com/hobnob/xmlStreamReader/contributors)


Licence
-------

© David North

Released under the [The MIT License](http://www.opensource.org/licenses/mit-license.php)
