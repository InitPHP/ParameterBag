# Upgrading from v1 to v2

v2 is a major release. It fixes bugs that changed observable
behaviour, removes a problematic feature (the internal cache), and
modernises the API surface. Most users only need to add one option
to keep their old behaviour; some need to update assumptions about
keys, merge semantics, and option validation.

## TL;DR

Add `caseInsensitive => true` to your constructor call and you are
likely done:

```php
new ParameterBag($data, ['caseInsensitive' => true]);
```

For everything else, read on.

## Behavioural changes

### Keys are case-sensitive by default

v1 silently lowercased every key. v2 preserves case unless you opt
in with `['caseInsensitive' => true]`.

Migration:

```php
// v1
$bag = new ParameterBag(['User' => 'alice']);
$bag->get('user'); // 'alice'

// v2 (default)
$bag = new ParameterBag(['User' => 'alice']);
$bag->get('user'); // null — case-sensitive
$bag->get('User'); // 'alice'

// v2 (legacy behaviour)
$bag = new ParameterBag(['User' => 'alice'], ['caseInsensitive' => true]);
$bag->get('user'); // 'alice'
```

### `isMulti` auto-detection is corrected

v1 inverted the comparison and set `isMulti = true` for FLAT arrays.
The bug was masked by callers who supplied the option explicitly. v2
auto-detects correctly: nested → multi, flat → flat.

If you wrote `new ParameterBag(['user' => 'a'])` and relied on the
broken auto-detection to give you multi-mode dotted writes, supply
`['isMulti' => true]` explicitly.

### Multi-mode `merge()` is recursive

v1's `merge()` used `array_merge`, which is shallow. v2 dispatches
to `array_replace_recursive` when `isMulti` is on, so sibling keys
at every depth are preserved.

```php
$bag = new ParameterBag(['db' => ['user' => 'root']], ['isMulti' => true]);
$bag->merge(['db' => ['pass' => 'secret']]);
$bag->all();
// v1: ['db' => ['pass' => 'secret']]            (user was wiped)
// v2: ['db' => ['user' => 'root', 'pass' => 'secret']]
```

### `clear()` (and `close()`) now actually clear

v1 kept an internal cache that survived `clear()`, so a subsequent
`get()` could return a value that no longer existed in the stack.
v2 removed the cache entirely; `clear()` is authoritative.

### `null` is distinguishable from "missing"

v1 used the same magic string sentinel for both, so storing `null`
made `has()` return false. v2's `has()` uses `array_key_exists`, so
`null` values are detected as present.

### Storing the legacy sentinel string works

v1 used `'__InitPHPP@r@m£t£rB@gN0tF0undV@lu€__'` as an internal
"not found" sentinel. Storing that exact string as data broke
`has()` and `get()`. v2 uses a private object sentinel that callers
cannot construct, so any string can be stored safely.

### Values are no longer trimmed with the separator

v1's normaliser ran `trim($value, $separator)` on every string leaf
in multi mode, silently corrupting data like `'.example.com.'` →
`'example.com'`. v2 only trims keys.

### `remove()` correctly targets each key

v1's `remove()` used the first argument as the parent slot for every
iteration in the multi-mode branch, so `remove('db.pass', 'cache.ttl')`
either deleted the wrong slot or created a phantom one. v2 derives
the parent slot from the current iteration.

## API additions

- `isEmpty(): bool`
- `keys(): array` / `values(): array`
- `replace(array $data): self`
- `\ArrayAccess`, `\Countable`, `\IteratorAggregate` are now
  implemented on the bag and on `ParameterBagInterface`.

## API removals & renames

- The internal `_PBStack`, `_PBOptions`, `_PBCache` properties were
  private; no public consumer is affected. If you subclassed the
  package and accessed these names through reflection, switch to
  the new typed properties (`$stack`, `$isMulti`, `$separator`,
  `$caseInsensitive`).
- `__destruct()` was removed. PHP's garbage collector reclaims
  memory automatically. If you relied on the destructor to clear
  options, call `close()` explicitly.

## Stricter option validation

```php
// v1: silently ignored
new ParameterBag([], ['is_multi' => true]);

// v2: throws
new ParameterBag([], ['is_multi' => true]);
// ParameterBagInvalidArgumentException
```

The exception message lists every accepted key — useful for quickly
finding the right name.

## PHP version

v2 requires PHP 7.4 or later (including 8.0–8.4). v1 advertised 7.2,
which has been EOL since 2019.

## Drop-in shim

If you cannot make the full migration immediately, the following
factory mimics v1 defaults:

```php
use InitPHP\ParameterBag\ParameterBag;

function legacyParameterBag(array $data = [], array $options = []): ParameterBag
{
    return new ParameterBag(
        $data,
        $options + ['caseInsensitive' => true]
    );
}
```

You still get all the v2 bug fixes; only the case-sensitivity
default is rolled back.
