# Installation

## Requirements

| | |
| --- | --- |
| PHP | `^8.2` (tested on 8.2 – 8.6) |
| Extensions | None |
| Runtime dependencies | None |

The package has zero runtime dependencies. Development dependencies
(PHPUnit, PHPStan, PHP-CS-Fixer) live under `require-dev` and are not
installed when you depend on `initphp/parameterbag` from another
project.

## Install via Composer

```bash
composer require initphp/parameterbag
```

The classes are autoloaded under the `InitPHP\ParameterBag` namespace
via PSR-4:

```json
{
    "autoload": {
        "psr-4": {
            "InitPHP\\ParameterBag\\": "src/"
        }
    }
}
```

## Verifying the install

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag(['hello' => 'world']);
echo $bag->get('hello'); // world
```

If the autoloader cannot locate the class, double-check that you ran
`composer install` (or `composer dump-autoload`) and that your script
requires `vendor/autoload.php` before instantiating the bag.

## Supported PHP versions in CI

Every PR runs the test matrix against:

- PHP 8.2
- PHP 8.3
- PHP 8.4
- PHP 8.5
- PHP 8.6

If you discover a regression on a supported version, please
[file an issue](https://github.com/InitPHP/ParameterBag/issues/new) and
include the PHP version reported by `php -v`.

## Next steps

- Walk through the everyday API in [Quick Start](Quick-Start.md).
- See the full list of configuration knobs in [Configuration](Configuration.md).
- Upgrading from v1? Read the [Migration Guide](Migration-Guide.md).
