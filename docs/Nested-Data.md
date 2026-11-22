# Nested Data (Multi Mode)

When the bag is in **multi mode** it treats the configured separator
(`.` by default) inside keys as a path delimiter into a nested
associative array. This page covers everything that changes once
multi mode is on; the flat-mode workflow is documented in
[Basic Usage](Basic-Usage.md).

## Turning multi mode on

### Auto-detection (recommended)

If you don't pass `isMulti` explicitly and the constructor payload
contains **any** nested array, multi mode is enabled:

```php
$bag = new ParameterBag([
    'database' => ['user' => 'root'],   // nested → isMulti = true
]);

$bag->get('database.user');  // 'root'
```

A purely flat payload leaves the bag in flat mode:

```php
$bag = new ParameterBag(['user' => 'root']); // isMulti = false
$bag->get('user');  // 'root'  (the dot has no meaning here)
```

### Explicit override

To force multi mode on an initially empty bag, or to force flat
mode on nested input:

```php
$bag = new ParameterBag([], ['isMulti' => true]);
$bag = new ParameterBag($nested, ['isMulti' => false]); // dot is literal
```

Auto-detection only runs when `isMulti` is omitted (or supplied as
a non-boolean). An explicit `true` / `false` always wins.

## Reading nested values

```php
$config = new ParameterBag([
    'database' => [
        'dsn'      => 'mysql:host=localhost',
        'username' => 'root',
        'password' => 'secret',
    ],
]);

$config->get('database.username');           // 'root'
$config->get('database.charset');            // null (missing path)
$config->get('database.charset', 'utf8mb4'); // 'utf8mb4'
$config->has('database.password');           // true
$config->has('database.charset');            // false
```

**`get()` does not probe inside scalars.** If a scalar sits where the
path expects an array, the lookup is treated as "missing" and yields
the default:

```php
$bag = new ParameterBag([], ['isMulti' => true]);
$bag->set('user', 'alice');
$bag->get('user.name');         // null  (string is not walked)
$bag->get('user.name', 'def');  // 'def'
```

## Writing nested values

```php
$bag = new ParameterBag([], ['isMulti' => true]);

$bag->set('cache.driver', 'redis');
$bag->set('cache.host',   '127.0.0.1');

$bag->all();
// ['cache' => ['driver' => 'redis', 'host' => '127.0.0.1']]
```

Intermediate arrays are created automatically. If a **scalar** sits
on a parent path, descending into it silently replaces it with a
fresh subtree — this is intentional to keep `set()` total:

```php
$bag = new ParameterBag([], ['isMulti' => true]);
$bag->set('db', 'a-string');
$bag->set('db.user', 'root');

$bag->all();
// ['db' => ['user' => 'root']]   // the string was discarded
```

If you need to detect this, check with `has()` / `get()` before
writing.

## Removing nested values

```php
$bag = new ParameterBag(
    [
        'db'    => ['user' => 'root', 'pass' => 'x'],
        'cache' => ['ttl'  => 60],
    ],
    ['isMulti' => true]
);

$bag->remove('db.pass', 'cache.ttl');

$bag->all();
// ['db' => ['user' => 'root'], 'cache' => []]
```

`remove('db')` deletes the whole subtree. `remove()` silently
ignores keys that do not exist; it never throws on a missing path.

> Note: removing the last leaf inside a subtree leaves an empty
> array (`[]`) at that slot; the parent key itself is **not**
> pruned. If you need pruning, do it explicitly via
> `$bag->get($parent) === [] && $bag->remove($parent)`.

## Custom separators

```php
$bag = new ParameterBag(
    ['user' => ['name' => 'alice']],
    ['isMulti' => true, 'separator' => '|']
);

$bag->get('user|name');  // 'alice'
```

The separator must be a **non-empty string**. An empty string is
silently rejected (the previous value is kept). Multi-character
separators (`'::'`, `'->'`) are allowed.

A separator may also be longer than one character, for cases where
your keys contain every common single-character delimiter:

```php
new ParameterBag([], ['isMulti' => true, 'separator' => '::']);
```

## Leading / trailing separators are trimmed

Caller-supplied keys have leading and trailing separators stripped
in multi mode, so the following are equivalent:

```php
$bag->get('.database.user.');
$bag->get('database.user.');
$bag->get('.database.user');
$bag->get('database.user');
```

This makes it safe to pass keys built by string concatenation
without worrying about a dangling delimiter.

## How merge changes in multi mode

| Mode | Strategy | PHP equivalent |
| --- | --- | --- |
| Flat | Shallow merge | `array_merge` |
| Multi | Recursive replace | `array_replace_recursive` |

Sibling keys at every depth are preserved in multi mode — see
[Merging](Merging.md) for examples and edge cases.

## Common mistakes

- **All-numeric "lists" trigger multi-mode auto-detection.** If
  every element of the payload is itself an array (e.g. a list of
  records), the bag enters multi mode and numeric indices
  participate in dotted lookups. If you wanted a list of opaque
  rows, pass `['isMulti' => false]` explicitly.
- **Changing the separator after construction.** The constructor is
  the only supported entry point. If you need runtime reconfiguration,
  subclass and override [`setOptions`](Extending.md#adding-new-options).
- **Indexing into a scalar.** `set('user', 'alice')` then
  `get('user.name')` always returns the default. The bag never
  probes inside strings, integers, or floats.
- **Keys that contain the separator literally.** If your keys
  legitimately need to contain a `.`, choose another separator
  (or stay in flat mode).
