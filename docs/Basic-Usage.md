# Basic Usage

This page covers the every-day, **flat-mode** workflow: a single
namespace of string-keyed values. Dotted-path semantics are
documented separately under [Nested Data](Nested-Data.md).

## Constructing a bag

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag();                               // empty
$bag = new ParameterBag(['locale' => 'en_US']);          // pre-seeded
$bag = new ParameterBag($_GET);                          // from a superglobal
```

The constructor's second argument is the options array — see
[Configuration](Configuration.md). Without options the bag starts in
flat mode and is case-sensitive.

## Reading values: `get`, `has`

```php
$bag = new ParameterBag(['user' => 'alice', 'role' => null]);

$bag->get('user');                  // 'alice'
$bag->get('missing');               // null
$bag->get('missing', 'fallback');   // 'fallback'
$bag->get('role', 'fallback');      // null
```

The last line is important: `get()` returns the **stored value**
whenever the key is present, even when that value is `null`. The
`$default` only fires when the key is genuinely absent.

`has()` is the authoritative existence check. A stored `null`
counts as present:

```php
$bag->has('user');     // true
$bag->has('role');     // true   (null is present)
$bag->has('missing');  // false
```

> In v1, `has()` and `get()` shared a magic-string sentinel, so a
> stored `null` looked identical to a missing key. v2 uses
> `array_key_exists` internally — `null` is now a first-class value.

## Writing values: `set`

```php
$bag = new ParameterBag();

$bag->set('host', 'localhost')
    ->set('port', 5432)
    ->set('flags', ['retry' => true]);
```

Three things to remember:

1. `set()` returns `$this`, so writes chain.
2. Existing keys are overwritten without notice.
3. Array values are normalised through the same code path the
   constructor uses (recursive lower-casing when `caseInsensitive`
   is on; otherwise unchanged).

## Removing values: `remove`

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);

$bag->remove('a');               // removes 'a'
$bag->remove('b', 'c');          // removes both (variadic)
$bag->remove('does-not-exist');  // silent no-op
```

`remove()` returns `$this` and ignores missing keys.

## Listing, counting, dumping

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2]);

$bag->count();    // 2  (also: count($bag), see Countable)
$bag->keys();     // ['a', 'b']
$bag->values();   // [1, 2]
$bag->all();      // ['a' => 1, 'b' => 2]
$bag->isEmpty();  // false
```

`keys()`, `values()`, and `all()` reflect **top-level** entries only.
Nested arrays are returned as-is — they are not unwound.

## Wholesale replacement: `replace`

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2]);
$bag->replace(['only' => 'value']);

$bag->all();
// ['only' => 'value']
```

`replace()` swaps the entire stack but **preserves the active
options** (`isMulti`, `separator`, `caseInsensitive`). The new
payload is run through the same normalisation as the constructor.

## Resetting the bag: `clear` and `close`

| Method | Stack | Options |
| --- | --- | --- |
| `clear()` | emptied | preserved |
| `close()` | emptied | reset to defaults (`isMulti=false`, `separator='.'`, `caseInsensitive=false`) |

```php
$bag = new ParameterBag(
    ['a' => 1],
    ['isMulti' => true, 'separator' => '|', 'caseInsensitive' => true]
);

$bag->clear();
// stack: []
// isMulti=true, separator='|', caseInsensitive=true

$bag->close();
// stack: []
// isMulti=false, separator='.', caseInsensitive=false
```

Reach for `clear()` when reusing a long-lived bag for a new payload
under the same configuration. Reach for `close()` when handing a
recycled bag to unrelated code that should not inherit your
options.

## Common mistakes

- **`$bag->get('foo.bar')` in flat mode**: the literal string `foo.bar`
  is the key. The dot has no special meaning unless `isMulti` is on.
  See [Nested Data](Nested-Data.md).
- **Forgetting that v2 is case-sensitive**: `set('User', 'a')` then
  `get('user')` returns `null`. See [Case Sensitivity](Case-Sensitivity.md).
- **`$bag[] = $value`**: ParameterBag is a string-keyed structure;
  the append form raises
  `ParameterBagInvalidArgumentException`. See [Exceptions](Exceptions.md).
