# InitPHP ParameterBag — Wiki

Welcome to the official documentation for **`initphp/parameterbag`** — a small,
dependency-free container that gives PHP applications a uniform, default-aware API
over both **flat** key/value data and **nested** (dotted-path) structures.

The package ships two types:

| Type | Purpose |
| --- | --- |
| [`ParameterBag`](API-Reference.md#parameterbag) | The default concrete implementation; open for extension. |
| [`ParameterBagInterface`](API-Reference.md#parameterbaginterface) | The contract — depend on this in your services. |

Both types satisfy PHP's standard collection contracts —
`ArrayAccess`, `Countable`, `IteratorAggregate` — so the bag drops into any
code that consumes those interfaces.

```bash
composer require initphp/parameterbag
```

```php
use InitPHP\ParameterBag\ParameterBag;

$config = new ParameterBag([
    'app' => ['name' => 'demo', 'debug' => false],
    'db'  => ['dsn'  => 'mysql:host=localhost', 'user' => 'root'],
]);

$config->get('app.name');                 // 'demo'
$config->get('db.charset', 'utf8mb4');    // 'utf8mb4' (default)
$config->set('app.debug', true)->merge(['db' => ['pass' => 'secret']]);
```

## Start here

- **New to the package?** Read [Installation](Installation.md), then [Quick Start](Quick-Start.md).
- **Building a config tree?** See [Nested Data](Nested-Data.md) and the
  [Config Loader recipe](Recipe-Config-Loader.md).
- **Wrapping `$_GET` / `$_POST`?** Read the
  [Request Parameters recipe](Recipe-Request-Parameters.md).
- **Injecting into a container?** Read
  [Dependency Injection](Recipe-Dependency-Injection.md).
- **Upgrading from v1?** Read the [Migration Guide](Migration-Guide.md).
- **Looking for a specific method?** The full [API Reference](API-Reference.md)
  lists every class member.

## At a glance — feature matrix

| Capability | Flat mode (`isMulti = false`) | Multi mode (`isMulti = true`) |
| --- | :---: | :---: |
| `get` / `set` / `has` / `remove` of top-level keys | ✅ | ✅ |
| Dotted-path access (`db.user`) | ❌ (dot is part of the key) | ✅ |
| Configurable separator | n/a | ✅ (`'.'` default) |
| Case-insensitive keys (opt-in) | ✅ | ✅ |
| Merge strategy | `array_merge` (shallow) | `array_replace_recursive` (deep) |
| `ArrayAccess` `$bag['x']` | ✅ | ✅ (path supported) |
| `Countable` / `IteratorAggregate` | top-level entries only | top-level entries only |
| Strict option validation (unknown keys throw) | ✅ | ✅ |
| Null values distinguishable from missing | ✅ | ✅ |

## Package metadata

- **License**: MIT
- **Minimum PHP**: 8.2 (tested on 8.2 – 8.5)
- **Runtime dependencies**: none
- **Packagist**: [`initphp/parameterbag`](https://packagist.org/packages/initphp/parameterbag)
- **Source**: [github.com/InitPHP/ParameterBag](https://github.com/InitPHP/ParameterBag)
- **Issues**: [github.com/InitPHP/ParameterBag/issues](https://github.com/InitPHP/ParameterBag/issues)
- **Discussions**: [github.com/orgs/InitPHP/discussions](https://github.com/orgs/InitPHP/discussions)
- **Security**: [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md)

If something in this wiki is unclear, ambiguous, or wrong, please
[open an issue](https://github.com/InitPHP/ParameterBag/issues/new) —
documentation fixes are reviewed eagerly.
