# InitPHP ParameterBag

A small, dependency-free parameter container for PHP that handles both
flat and nested (dotted-path) data with the same API.

[![Latest Stable Version](https://poser.pugx.org/initphp/parameterbag/v)](https://packagist.org/packages/initphp/parameterbag)
[![Total Downloads](https://poser.pugx.org/initphp/parameterbag/downloads)](https://packagist.org/packages/initphp/parameterbag)
[![CI](https://github.com/InitPHP/ParameterBag/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/ParameterBag/actions/workflows/ci.yml)
[![License](https://poser.pugx.org/initphp/parameterbag/license)](https://packagist.org/packages/initphp/parameterbag)
[![PHP Version Require](https://poser.pugx.org/initphp/parameterbag/require/php)](https://packagist.org/packages/initphp/parameterbag)

![parameterbag](https://initphp.github.io/logos/parameterbag.png)

---

## Features

- Single API for flat and nested data; nesting is auto-detected from
  the constructor payload or toggled explicitly.
- Dotted-path access (`$bag->get('database.user')`) with a
  configurable separator.
- Optional, opt-in case-insensitive key handling.
- Implements PHP's standard collection contracts: `ArrayAccess`,
  `Countable`, `IteratorAggregate`.
- Strict validation of constructor options (typos throw instead of
  being silently ignored).
- Zero runtime dependencies; PHPStan level 8 clean.

## Requirements

- PHP 7.4 or later (including 8.0–8.4)

## Installation

```bash
composer require initphp/parameterbag
```

## Quick start

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag($_GET);

// GET /?user=alice
echo $bag->get('user', 'guest'); // 'alice'

$bag->set('locale', 'en_US')->set('debug', true);
$bag->has('debug');              // true
$bag->remove('debug');
```

### Nested data (multi mode)

Pass a nested array (or set `isMulti => true` explicitly) and the bag
will treat the separator (`.` by default) as a path delimiter:

```php
$config = new ParameterBag([
    'database' => [
        'dsn'      => 'mysql:host=localhost',
        'username' => 'root',
        'password' => 'secret',
    ],
]);

$config->get('database.username');   // 'root'
$config->has('database.charset');    // false
$config->set('database.charset', 'utf8mb4');
$config->remove('database.password');
```

Use a custom separator if dots are part of your keys:

```php
$bag = new ParameterBag($data, ['separator' => '|']);
$bag->get('database|username');
```

### Native PHP idioms

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2]);

count($bag);          // 2
$bag['c'] = 3;        // ArrayAccess write
isset($bag['c']);     // true
foreach ($bag as $key => $value) { /* ... */ }
```

## Public API

| Method | Purpose | Docs |
| --- | --- | --- |
| `get(string $key, mixed $default = null): mixed` | Look up a value (dotted paths in multi mode). | [usage/basic-usage](docs/usage/basic-usage.md) |
| `has(string $key): bool` | Existence check (null values count as present). | [usage/basic-usage](docs/usage/basic-usage.md) |
| `set(string $key, mixed $value): self` | Assign or replace a value. | [usage/basic-usage](docs/usage/basic-usage.md) |
| `remove(string ...$keys): self` | Delete one or more keys. | [usage/basic-usage](docs/usage/basic-usage.md) |
| `merge(array\|ParameterBagInterface ...$payloads): self` | Shallow merge (flat) or recursive replace (multi). | [usage/merging](docs/usage/merging.md) |
| `replace(array $data): self` | Swap the entire stack. | [api-reference](docs/api-reference.md) |
| `all(): array` | Return the current stack as a plain array. | [api-reference](docs/api-reference.md) |
| `keys(): array` / `values(): array` | Top-level keys / values in insertion order. | [api-reference](docs/api-reference.md) |
| `count(): int` | Top-level entry count (also via `count($bag)`). | [usage/iteration-and-counting](docs/usage/iteration-and-counting.md) |
| `getIterator(): ArrayIterator` | Iterates top-level entries. | [usage/iteration-and-counting](docs/usage/iteration-and-counting.md) |
| `isEmpty(): bool` | True when the stack has no entries. | [api-reference](docs/api-reference.md) |
| `clear(): void` | Empty the stack, keep options. | [api-reference](docs/api-reference.md) |
| `close(): void` | Empty the stack and reset options to defaults. | [api-reference](docs/api-reference.md) |

## Configuration options

The constructor accepts a second array of options. Unknown keys raise
`ParameterBagInvalidArgumentException`.

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `isMulti` | `bool` | auto-detected from `$data` | Enables dotted-path semantics. |
| `separator` | `non-empty-string` | `'.'` | Delimiter for dotted paths. Ignored in flat mode. |
| `caseInsensitive` | `bool` | `false` | When true, every key (constructor payload, set/get/has/remove arguments, merge input) is folded to lower-case. Matches the legacy v1 behaviour. |

See [docs/configuration.md](docs/configuration.md) and
[docs/usage/case-sensitivity.md](docs/usage/case-sensitivity.md).

## Exceptions

| Exception | Raised when |
| --- | --- |
| `InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException` | Unknown option key, non-array/non-ParameterBag argument to `merge()`, or `$bag[] = $v` ArrayAccess append. Extends `\InvalidArgumentException`. |

See [docs/exceptions.md](docs/exceptions.md).

## Development

```bash
composer install
composer test         # PHPUnit
composer analyse      # PHPStan (level 8)
composer cs:check     # PHP-CS-Fixer dry-run
composer cs:fix       # PHP-CS-Fixer apply
```

CI runs the matrix across PHP 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4.

## Upgrading from v1

v2 introduces a small set of intentional behaviour changes (cache
removed, `isMulti` auto-detect inverted, value-trim bug fixed,
case-sensitive by default, strict option validation, new methods).
A full migration guide lives at
[docs/upgrading-from-v1.md](docs/upgrading-from-v1.md).

## Contributing & Security

- [Contributing guidelines](https://github.com/InitPHP/.github/blob/main/CONTRIBUTING.md)
- [Code of Conduct](https://github.com/InitPHP/.github/blob/main/CODE_OF_CONDUCT.md)
- [Security policy](https://github.com/InitPHP/.github/blob/main/SECURITY.md)

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Released under the [MIT License](./LICENSE).
