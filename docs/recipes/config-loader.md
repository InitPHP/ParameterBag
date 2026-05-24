# Recipe: config loader

Goal: load a PHP config file from disk and expose its contents through
a `ParameterBag` so the rest of the application reads it with dotted
paths.

## The config file

`config/app.php`:

```php
<?php

return [
    'app' => [
        'name'  => 'demo',
        'debug' => false,
    ],
    'database' => [
        'dsn'      => 'mysql:host=localhost;dbname=demo',
        'username' => 'root',
        'password' => 'secret',
    ],
];
```

## The loader

```php
use InitPHP\ParameterBag\ParameterBag;

$config = new ParameterBag(require __DIR__ . '/config/app.php');

echo $config->get('app.name');                   // 'demo'
echo $config->get('database.dsn');               // 'mysql:host=localhost;dbname=demo'
$config->get('cache.driver', 'array');           // 'array' (default)
$config->set('app.debug', true);
```

Multi mode is auto-detected from the nested payload — no options are
required.

## Merging environment overrides

```php
$config = new ParameterBag(require __DIR__ . '/config/app.php');
$config->merge(require __DIR__ . '/config/local.php');
// In multi mode, sibling keys at every depth are preserved.
```

## Common pitfalls

- **Numeric-indexed lists**: if a value is a list (e.g. allowed
  hosts), prefer `get('allowed_hosts', [])` rather than dotted access
  into the list. Dotted lookups treat numeric keys the same as string
  keys, but it reads better.
- **Mutation of injected configs**: a `ParameterBag` is mutable. If
  you inject it into many services, document or freeze the contract
  (e.g. wrap reads in a service that only calls `get()`).
