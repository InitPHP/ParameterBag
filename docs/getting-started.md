# Getting started

## Install

```bash
composer require initphp/parameterbag
```

Requires PHP 7.4 or later (tested up to 8.4).

## Your first parameter bag

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag([
    'app_name' => 'demo',
    'debug'    => true,
]);

echo $bag->get('app_name'), PHP_EOL;             // demo
var_export($bag->has('missing'));                 // false
$bag->set('app_name', 'production')->remove('debug');
print_r($bag->all());
```

Expected output:

```
demo
false
Array
(
    [app_name] => production
)
```

## Next steps

- Walk through the everyday methods in [Basic usage](usage/basic-usage.md).
- Learn how the bag handles nested data in [Nested data](usage/nested-data.md).
- See the full [API reference](api-reference.md) for an exhaustive list.
