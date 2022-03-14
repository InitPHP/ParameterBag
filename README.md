# InitPHP ParameterBag

Single and multi-dimensional parameter bag.

[![Latest Stable Version](http://poser.pugx.org/initphp/parameterbag/v)](https://packagist.org/packages/initphp/parameterbag) [![Total Downloads](http://poser.pugx.org/initphp/parameterbag/downloads)](https://packagist.org/packages/initphp/parameterbag) [![Latest Unstable Version](http://poser.pugx.org/initphp/parameterbag/v/unstable)](https://packagist.org/packages/initphp/parameterbag) [![License](http://poser.pugx.org/initphp/parameterbag/license)](https://packagist.org/packages/initphp/parameterbag) [![PHP Version Require](http://poser.pugx.org/initphp/parameterbag/require/php)](https://packagist.org/packages/initphp/parameterbag)

![parameterbag](https://initphp.github.io/logos/parameterbag.png)

## Installation

```
composer require initphp/parameterbag
```

## Requirements

- PHP 7.4 or higher

## Usage

```php 
require_once "vendor/autoload.php";
use \InitPHP\ParameterBag;

$parameter = new ParameterBag($_GET);

// GET /?user=muhametsafak
echo $parameter->get('user', null); // "muhametsafak"
```

### Methods

#### `has()`

```php 
public function has(string $key): bool;
```

#### `get()`

```php 
public function get(string $key, mixed $default = null): mixed;
```

#### `set()`

```php 
public function set(string $key, mixed $value): \InitPHP\ParameterBag;
```

#### `remove()`

```php 
public function remove(string $key): \InitPHP\ParameterBag;
```

#### `all()`

```php 
public function all(): array;
```

#### `merge()`

```php 
public function merge(array|\InitPHP\ParameterBag $merge): \InitPHP\ParameterBag;
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>> 

## License

Copyright &copy; 2022 - [MIT License](https://initphp.github.io/license.txt)