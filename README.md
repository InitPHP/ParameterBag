# InitPHP ParameterBag

A small, dependency-free parameter container for PHP that handles both
flat and nested (dotted-path) data with the same API.

[![Designed & Maintained with Tan](https://muhammetsafak.com/badges/designed-maintained-with-tan.svg)](https://muhammetsafak.com/tan/)
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

- PHP 8.2 or later (including 8.6)

## Installation

```bash
composer require initphp/parameterbag
```

## Quick start

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag($_GET);

// GET /?user=alice
$user = $bag->get('user', 'guest'); // 'alice'

// The bag returns request data as-is (it can be an array, or contain markup).
// Escape it for the context you print it in — here, HTML:
echo htmlspecialchars(is_string($user) ? $user : 'guest', ENT_QUOTES, 'UTF-8');

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
| `get(string $key, mixed $default = null): mixed` | Look up a value (dotted paths in multi mode). | [Basic Usage](docs/Basic-Usage.md) |
| `has(string $key): bool` | Existence check (null values count as present). | [Basic Usage](docs/Basic-Usage.md) |
| `set(string $key, mixed $value): self` | Assign or replace a value. | [Basic Usage](docs/Basic-Usage.md) |
| `remove(string ...$keys): self` | Delete one or more keys. | [Basic Usage](docs/Basic-Usage.md) |
| `merge(array\|ParameterBagInterface ...$payloads): self` | Shallow merge (flat) or recursive replace (multi). | [Merging](docs/Merging.md) |
| `replace(array $data): self` | Swap the entire stack. | [API Reference](docs/API-Reference.md) |
| `all(): array` | Return the current stack as a plain array. | [API Reference](docs/API-Reference.md) |
| `keys(): array` / `values(): array` | Top-level keys / values in insertion order. | [API Reference](docs/API-Reference.md) |
| `count(): int` | Top-level entry count (also via `count($bag)`). | [Iteration & Counting](docs/Iteration-And-Counting.md) |
| `getIterator(): ArrayIterator` | Iterates top-level entries. | [Iteration & Counting](docs/Iteration-And-Counting.md) |
| `isEmpty(): bool` | True when the stack has no entries. | [API Reference](docs/API-Reference.md) |
| `clear(): void` | Empty the stack, keep options. | [API Reference](docs/API-Reference.md) |
| `close(): void` | Empty the stack and reset options to defaults. | [API Reference](docs/API-Reference.md) |

## Configuration options

The constructor accepts a second array of options. Unknown keys raise
`ParameterBagInvalidArgumentException`.

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `isMulti` | `bool` | auto-detected from `$data` | Enables dotted-path semantics. |
| `separator` | `non-empty-string` | `'.'` | Delimiter for dotted paths. Ignored in flat mode. |
| `caseInsensitive` | `bool` | `false` | When true, every key (constructor payload, set/get/has/remove arguments, merge input) is folded to lower-case. Matches the legacy v1 behaviour. |

See [docs/Configuration.md](docs/Configuration.md) and
[docs/Case-Sensitivity.md](docs/Case-Sensitivity.md).

## Exceptions

| Exception | Raised when |
| --- | --- |
| `InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException` | Unknown option key, non-array/non-ParameterBag argument to `merge()`, or `$bag[] = $v` ArrayAccess append. Extends `\InvalidArgumentException`. |

See [docs/Exceptions.md](docs/Exceptions.md).

## Development

```bash
composer install
composer test         # PHPUnit
composer analyse      # PHPStan (level 8)
composer cs:check     # PHP-CS-Fixer dry-run
composer cs:fix       # PHP-CS-Fixer apply
```

CI runs the matrix across PHP 8.2, 8.3, 8.4, 8.5, and 8.6.

## Upgrading from v1

v2 introduces a small set of intentional behaviour changes (cache
removed, `isMulti` auto-detect inverted, value-trim bug fixed,
case-sensitive by default, strict option validation, new methods).
A full migration guide lives at
[docs/Migration-Guide.md](docs/Migration-Guide.md).

## Contributing & Security

- [Contributing guidelines](https://github.com/InitPHP/.github/blob/main/CONTRIBUTING.md)
- [Code of Conduct](https://github.com/InitPHP/.github/blob/main/CODE_OF_CONDUCT.md)
- [Security policy](https://github.com/InitPHP/.github/blob/main/SECURITY.md)

## Credits

- [Muhammet ŞAFAK](https://muhammetsafak.com) <<info@muhammetsafak.com>>

## License

Released under the [MIT License](./LICENSE).
