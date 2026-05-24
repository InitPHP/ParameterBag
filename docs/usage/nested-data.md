# Nested data (multi mode)

When the bag is in multi mode it interprets the configured separator
(`.` by default) inside keys as a path delimiter into a nested
associative array.

## Enabling multi mode

Auto-detection (recommended): if the constructor payload contains any
nested arrays, multi mode is enabled automatically.

```php
$bag = new ParameterBag([
    'database' => ['user' => 'root'],   // nested → multi mode
]);
```

Explicit:

```php
$bag = new ParameterBag([], ['isMulti' => true]);
```

> If you want a flat bag with literal dotted keys, pass
> `['isMulti' => false]` explicitly to override auto-detection.

## Reading nested values

```php
$bag = new ParameterBag([
    'database' => [
        'dsn'      => 'mysql:host=localhost',
        'username' => 'root',
        'password' => 'secret',
    ],
]);

$bag->get('database.username');   // 'root'
$bag->get('database.charset');    // null   (missing path)
$bag->get('database.charset', 'utf8mb4'); // 'utf8mb4'
$bag->has('database.password');   // true
```

## Writing nested values

```php
$bag = new ParameterBag([], ['isMulti' => true]);

$bag->set('cache.driver', 'redis');
$bag->set('cache.host', '127.0.0.1');

$bag->all();
// ['cache' => ['driver' => 'redis', 'host' => '127.0.0.1']]
```

If a scalar sits at a parent path, descending into it silently
replaces it with a fresh subtree:

```php
$bag = new ParameterBag([], ['isMulti' => true]);
$bag->set('db', 'a-string');
$bag->set('db.user', 'root');

$bag->all();
// ['db' => ['user' => 'root']]
```

## Removing nested values

```php
$bag = new ParameterBag(
    ['db' => ['user' => 'root', 'pass' => 'x'], 'cache' => ['ttl' => 60]],
    ['isMulti' => true]
);

$bag->remove('db.pass', 'cache.ttl');

$bag->all();
// ['db' => ['user' => 'root'], 'cache' => []]
```

`remove('db')` deletes the whole subtree.

## Choosing a separator

```php
$bag = new ParameterBag(
    ['user' => ['name' => 'alice']],
    ['isMulti' => true, 'separator' => '|']
);

$bag->get('user|name'); // 'alice'
```

The separator must be a non-empty string; an empty string is rejected
and the previous value is kept.

## Common mistakes

- **Indexing into a scalar leaf**: `set('user', 'alice')` followed by
  `get('user.name')` returns the default (null), not a character of
  `'alice'`. The bag will not probe inside scalars.
- **Auto-detection with all-numeric arrays**: if every element of the
  payload is itself an array (e.g. a list of records), multi mode is
  enabled and the numeric keys participate in dotted lookups. Pass
  `['isMulti' => false]` if you intend a list of opaque rows.
- **Changing the separator at runtime**: the constructor is the only
  supported entry point. Subclass and override
  [`setOptions()`](../api-reference.md#setOptions) if you need
  another flow.
