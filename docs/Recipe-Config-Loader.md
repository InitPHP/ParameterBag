# Recipe: Config Loader

**Goal:** load a PHP config file (or files) from disk and expose
its contents through a `ParameterBag` so the rest of the application
reads it with dotted paths and forgiving defaults.

## The config files

`config/app.php` — committed defaults:

```php
<?php

return [
    'app' => [
        'name'  => 'demo',
        'debug' => false,
        'env'   => 'production',
    ],
    'database' => [
        'dsn'      => 'mysql:host=localhost;dbname=demo',
        'username' => 'root',
        'password' => 'secret',
        'options'  => [
            'charset' => 'utf8mb4',
            'timeout' => 5,
        ],
    ],
    'cache' => [
        'driver' => 'redis',
        'ttl'    => 3600,
    ],
];
```

`config/local.php` — gitignored, environment-specific:

```php
<?php

return [
    'app' => [
        'debug' => true,
        'env'   => 'local',
    ],
    'database' => [
        'password' => 'dev-password',
    ],
];
```

## The loader

```php
use InitPHP\ParameterBag\ParameterBag;

$base = require __DIR__ . '/config/app.php';

$config = new ParameterBag($base);
// Nested payload → multi mode is auto-detected.

if (is_file(__DIR__ . '/config/local.php')) {
    $config->merge(require __DIR__ . '/config/local.php');
}
```

Because the bag is in multi mode, `merge()` uses
`array_replace_recursive`, so the override only touches the keys
that actually differ — `database.username`, `database.options.*`,
and `cache.*` survive the merge unchanged.

```php
$config->get('app.name');                 // 'demo'
$config->get('app.debug');                // true        (overridden)
$config->get('database.password');        // 'dev-password' (overridden)
$config->get('database.options.charset'); // 'utf8mb4'   (preserved)
$config->get('cache.driver');             // 'redis'     (preserved)
$config->get('mail.driver', 'log');       // 'log'       (default)
```

## Promoting environment variables

If you also want to fold `$_ENV` (or `getenv()`) into the same bag,
keep a separate "env" subtree to avoid namespace collisions with
the file-based config:

```php
$config->merge([
    'env' => array_filter($_ENV, static fn ($v) => $v !== false),
]);

$config->get('env.DATABASE_URL', $config->get('database.dsn'));
```

## A reusable loader class

When the same pattern shows up in multiple projects, wrap it:

```php
use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;

final class ConfigLoader
{
    public static function load(string $baseFile, ?string $overrideFile = null): ParameterBagInterface
    {
        $config = new ParameterBag(require $baseFile);

        if ($overrideFile !== null && is_file($overrideFile)) {
            $config->merge(require $overrideFile);
        }

        return $config;
    }
}

$config = ConfigLoader::load(
    __DIR__ . '/config/app.php',
    __DIR__ . '/config/local.php',
);
```

The factory returns the **interface**, so consumers do not couple
to the concrete class. See [Dependency Injection](Recipe-Dependency-Injection.md)
for wiring this into a PSR-11 container.

## Freezing the config after boot

If your boot phase is the only place that should mutate config,
wrap the bag in a read-only decorator before handing it to
consumers. See
[Extending → Decorator alternative](Extending.md#decorator-alternative)
for the boilerplate.

## Common pitfalls

- **Numeric-indexed lists in nested config.** Auto-detection
  treats `[ ['host' => 'a'], ['host' => 'b'] ]` as multi-mode
  payload, which means `get('0.host')` works. If you wanted an
  opaque list, pull it out as a single value
  (`$config->get('allowed_hosts')`) rather than dotting into
  individual rows.
- **Mutation of injected configs.** A single bag injected into many
  services is shared mutable state. Either freeze the contract
  (see above) or document that consumers should not call
  `set()` / `remove()`.
- **Hot-reload.** ParameterBag has no built-in file watcher. If you
  need that, watch the file yourself and call `replace()` with the
  freshly-required payload.
