# Quick Start

A five-minute tour of the public API. Every snippet runs as-is.

## A flat bag

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag([
    'app_name' => 'demo',
    'debug'    => true,
]);

$bag->get('app_name');              // 'demo'
$bag->get('missing');               // null
$bag->get('missing', 'fallback');   // 'fallback'

$bag->set('app_name', 'production')
    ->set('locale', 'en_US')
    ->remove('debug');

$bag->all();
// ['app_name' => 'production', 'locale' => 'en_US']
```

`set()` / `remove()` / `merge()` / `replace()` all return `$this` so
writes chain naturally.

## A nested bag (auto-detected)

If the constructor payload contains nested arrays, the bag switches
into **multi mode** and treats the configured separator (`.` by
default) as a path delimiter:

```php
$config = new ParameterBag([
    'database' => [
        'dsn'      => 'mysql:host=localhost',
        'username' => 'root',
        'password' => 'secret',
    ],
]);

$config->get('database.username');           // 'root'
$config->has('database.charset');            // false
$config->get('database.charset', 'utf8mb4'); // 'utf8mb4'

$config->set('database.charset', 'utf8mb4');
$config->remove('database.password');
```

To force multi mode on an initially empty bag, pass it explicitly:

```php
$bag = new ParameterBag([], ['isMulti' => true]);
$bag->set('cache.driver', 'redis');
```

## A custom separator

```php
$bag = new ParameterBag(
    ['db' => ['user' => 'root']],
    ['separator' => '|']
);

$bag->get('db|user');   // 'root'
```

## Case-insensitive lookups (opt-in)

By default keys are case-sensitive (`User` ≠ `user`). Opt back into
the legacy v1 behaviour with `caseInsensitive => true`:

```php
$bag = new ParameterBag(
    ['User' => 'alice'],
    ['caseInsensitive' => true]
);

$bag->get('USER');  // 'alice'
```

## Native PHP idioms

The bag implements `ArrayAccess`, `Countable`, and `IteratorAggregate`,
so it works wherever PHP's standard collection contracts are
recognised:

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2]);

count($bag);          // 2
$bag['c'] = 3;        // set via ArrayAccess
isset($bag['c']);     // true
unset($bag['c']);

foreach ($bag as $key => $value) {
    echo "$key=$value\n";
}
```

In multi mode the ArrayAccess offset can be a dotted path:

```php
$bag = new ParameterBag(['db' => ['user' => 'root']]);
$bag['db.user'];  // 'root'
```

## Where to go next

- [Basic Usage](Basic-Usage.md) — the every-day `get`/`set`/`has`/`remove` API.
- [Nested Data](Nested-Data.md) — multi mode in depth.
- [Configuration](Configuration.md) — every option, with examples.
- [API Reference](API-Reference.md) — exhaustive method list.
