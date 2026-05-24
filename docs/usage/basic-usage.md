# Basic usage

Flat-mode workflows: a single namespace of string-keyed values.

## Reading values

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag(['user' => 'alice', 'role' => null]);

$bag->get('user');                  // 'alice'
$bag->get('missing');               // null
$bag->get('missing', 'fallback');   // 'fallback'
$bag->get('role', 'fallback');      // null — the stored value wins,
                                    //        even when it is null,
                                    //        because the key exists.
```

`has()` is the authoritative existence check — it returns `true` for
keys that were explicitly set to `null`:

```php
$bag->has('user');     // true
$bag->has('role');     // true  (null value counts as present)
$bag->has('missing');  // false
```

## Writing values

```php
$bag = new ParameterBag();

$bag->set('host', 'localhost')
    ->set('port', 5432);
```

`set()` returns the bag, so writes chain naturally. Setting an array
also normalises it through the constructor's code path:

```php
$bag->set('headers', ['Accept' => 'application/json']);
$bag->get('headers'); // ['Accept' => 'application/json']
```

## Removing values

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);

$bag->remove('a');             // removes 'a'
$bag->remove('b', 'c');        // removes both
$bag->remove('does-not-exist'); // silent no-op
```

`remove()` is variadic, returns the bag, and ignores missing keys.

## Listing and counting

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2]);

count($bag);     // 2  (also $bag->count())
$bag->keys();    // ['a', 'b']
$bag->values();  // [1, 2]
$bag->all();     // ['a' => 1, 'b' => 2]
$bag->isEmpty(); // false
```

## Clearing the bag

```php
$bag = new ParameterBag(['a' => 1], ['caseInsensitive' => true]);

$bag->clear();   // empties the stack, keeps options
$bag->close();   // empties the stack AND resets options to defaults
```

## Common mistakes

- **`$bag->get('foo.bar')` in flat mode**: the literal string
  `'foo.bar'` is the key; the dot has no special meaning unless
  `isMulti` is enabled. See [Nested data](nested-data.md).
- **Forgetting that v2 is case-sensitive**: `set('User', 'a')`
  followed by `get('user')` returns `null`. Opt into the legacy
  behaviour with `['caseInsensitive' => true]` if you need it. See
  [Case sensitivity](case-sensitivity.md).
- **`$bag[]` append**: ParameterBag is string-keyed, so
  `$bag[] = $value` raises
  `ParameterBagInvalidArgumentException`.
