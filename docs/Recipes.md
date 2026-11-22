# Recipes

Practical, copy-pasteable patterns built on top of the public API.
Each recipe is self-contained and links back into the reference
material for the methods it uses.

| Recipe | Solves |
| --- | --- |
| [Config Loader](Recipe-Config-Loader.md) | Loading a `config/app.php` tree and merging environment overrides. |
| [Request Parameters](Recipe-Request-Parameters.md) | Wrapping `$_GET`, `$_POST`, `$_SERVER` (with case-insensitive headers). |
| [Dependency Injection](Recipe-Dependency-Injection.md) | Registering `ParameterBagInterface` in a PSR-11 container. |

If you have built something on top of the bag that other users
might benefit from, please
[open an issue](https://github.com/InitPHP/ParameterBag/issues/new)
with a sketch — recipe contributions are welcomed.

## When NOT to use ParameterBag

The bag is intentionally minimal. Reach for a dedicated library when:

- **Strong typing of config matters.** Use a schema-validated config
  object (e.g. `symfony/config`, or a value-object DTO) when typos
  in keys should be caught at the parser, not at the call site.
- **Immutability is required.** ParameterBag mutates in place. Wrap
  it in a [decorator](Extending.md#decorator-alternative) if you only
  want read access.
- **You need observability around writes.** Add logging by wrapping
  the bag in a decorator that forwards calls and emits events on
  `set` / `remove` / `merge`.
- **Cross-cutting validation.** A bag doesn't know what a "valid
  port" or "valid DSN" looks like. Validate at the consumer
  boundary.

## Beyond the bag — pattern sketches

These are not full recipes (yet), but the public API is enough to
implement them in a handful of lines:

### Freeze the bag after boot

```php
$config = new ParameterBag(require __DIR__ . '/config/app.php');
// ... mutate during boot ...
$frozen = new ReadOnlyBag($config);   // see Extending → Decorator alternative
$container->set(ParameterBagInterface::class, $frozen);
```

### Dot-path checks with a default fallback chain

```php
function configWithFallback(ParameterBagInterface $bag, array $paths, $default = null)
{
    foreach ($paths as $path) {
        if ($bag->has($path)) {
            return $bag->get($path);
        }
    }
    return $default;
}

$dsn = configWithFallback(
    $config,
    ['database.dsn', 'db.dsn', 'DATABASE_URL'],
    'sqlite::memory:',
);
```

### Capture / replay a snapshot

```php
$snapshot = $bag->all();
// ... mutations ...
$bag->replace($snapshot);  // restore
```

`replace()` runs the new payload through the same normalisation as
the constructor, so a snapshot taken under `caseInsensitive => true`
restores correctly even after intervening case-sensitive writes
(provided the bag's options have not been changed via `close()`).
