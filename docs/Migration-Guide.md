# Migration Guide (v1 → v2)

v2 is a major release. It fixes bugs that changed observable
behaviour, removes a problematic feature (the internal cache), and
modernises the API surface. Most users only need to add **one option**
to keep their old behaviour; some need to update assumptions about
keys, merge semantics, and option validation.

## TL;DR

If you upgraded from v1 and want the closest possible drop-in
behaviour, add `caseInsensitive => true` to your constructor call:

```php
new ParameterBag($data, ['caseInsensitive' => true]);
```

The rest of v2's changes are bug fixes — they make the bag behave
the way the v1 documentation already claimed it did. Read on for the
specifics so you can audit your call sites.

## Behavioural changes

### Keys are case-sensitive by default

v1 silently lowercased every key. v2 preserves case unless you opt
in.

```php
// v1 behaviour
$bag = new ParameterBag(['User' => 'alice']);
$bag->get('user'); // 'alice'

// v2 default
$bag = new ParameterBag(['User' => 'alice']);
$bag->get('user'); // null   — case-sensitive
$bag->get('User'); // 'alice'

// v2 legacy mode
$bag = new ParameterBag(['User' => 'alice'], ['caseInsensitive' => true]);
$bag->get('user'); // 'alice'
```

See [Case Sensitivity](Case-Sensitivity.md) for the full mode.

### `isMulti` auto-detection is corrected

v1 inverted the comparison and set `isMulti = true` for **flat**
arrays. The bug was masked whenever callers supplied the option
explicitly. v2 auto-detects correctly: nested → multi, flat → flat.

If you wrote `new ParameterBag(['user' => 'a'])` and relied on the
broken auto-detection to give you multi-mode dotted writes,
supply `['isMulti' => true]` explicitly.

### Multi-mode `merge()` is recursive

v1's `merge()` always used `array_merge`, which is shallow. v2
dispatches to `array_replace_recursive` when `isMulti` is on, so
sibling keys at every depth are preserved.

```php
$bag = new ParameterBag(['db' => ['user' => 'root']], ['isMulti' => true]);
$bag->merge(['db' => ['pass' => 'secret']]);

$bag->all();
// v1: ['db' => ['pass' => 'secret']]            (user was wiped)
// v2: ['db' => ['user' => 'root', 'pass' => 'secret']]
```

If you wanted the v1 shallow behaviour even with nested data, run
the bag in flat mode (`['isMulti' => false]`).

### `clear()` (and `close()`) now actually clear

v1 kept an internal cache that survived `clear()`, so a subsequent
`get()` could return a value that no longer existed in the stack.
v2 removed the cache entirely; `clear()` and `close()` are
authoritative.

### `null` is distinguishable from "missing"

v1 used the same magic-string sentinel for both, so storing `null`
made `has()` return false and `get()` return the default. v2's
`has()` uses `array_key_exists` and `get()` returns the stored
value, so `null` is a first-class value:

```php
$bag = new ParameterBag(['role' => null]);

// v1
$bag->has('role');                 // false
$bag->get('role', 'fallback');     // 'fallback'

// v2
$bag->has('role');                 // true
$bag->get('role', 'fallback');     // null
```

### Storing the legacy sentinel string works

v1 used `'__InitPHPP@r@m£t£rB@gN0tF0undV@lu€__'` as an internal
"not found" sentinel. Storing **that exact string** as data broke
`has()` and `get()`. v2 uses a private object sentinel that callers
cannot construct, so any string — including the legacy one — can be
stored safely.

### Values are no longer trimmed with the separator

v1's normaliser ran `trim($value, $separator)` on every string
**leaf** in multi mode, silently corrupting data like
`'.example.com.'` → `'example.com'`. v2 only trims keys.

### `remove()` correctly targets each key

v1's `remove()` used the first argument as the parent slot for
every iteration in the multi-mode branch, so
`remove('db.pass', 'cache.ttl')` either deleted the wrong slot or
created a phantom one. v2 derives the parent slot from the current
iteration.

## API additions

| Method | Purpose |
| --- | --- |
| `isEmpty(): bool` | Cheap top-level emptiness check. |
| `keys(): array` | Top-level keys in insertion order. |
| `values(): array` | Top-level values in insertion order. |
| `replace(array $data): self` | Swap the stack, preserve options. |
| `\ArrayAccess`, `\Countable`, `\IteratorAggregate` | Now implemented on the bag **and** declared on `ParameterBagInterface`. |

See [Iteration & Counting](Iteration-And-Counting.md) and the full
[API Reference](API-Reference.md).

## API removals & renames

| Item | Status | Notes |
| --- | --- | --- |
| `_PBStack`, `_PBOptions`, `_PBCache` | Removed | These were private in v1 too. If you accessed them via reflection, switch to the new typed properties (`$stack`, `$isMulti`, `$separator`, `$caseInsensitive`). |
| `__destruct()` | Removed | PHP's GC reclaims memory automatically. If you relied on it to reset options, call `close()` explicitly. |

## Stricter option validation

```php
// v1 — silently ignored
new ParameterBag([], ['is_multi' => true]);

// v2 — throws
new ParameterBag([], ['is_multi' => true]);
// ParameterBagInvalidArgumentException:
//   "Unknown ParameterBag option(s): is_multi.
//    Known options: isMulti, separator, caseInsensitive."
```

The exception message lists every accepted key — useful for
quickly finding the right name in IDEs and logs.

## PHP version

| | v1 | v2.0.0 | After 2.0.0 (unreleased) |
| --- | --- | --- | --- |
| Minimum PHP (`composer.json`) | `>=7.2` | `^7.4 \|\| ^8.0` | `^8.2` |
| CI matrix | n/a | 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 | 8.2, 8.3, 8.4, 8.5 |

The `^8.2` minimum
is recorded in the CHANGELOG as a breaking change that has not been
released yet.

## Drop-in compatibility shim

If you cannot make the full migration immediately, the following
factory mimics v1's defaults while still benefitting from every
v2 bug fix:

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

Only the case-sensitivity default is rolled back. The `isMulti`
auto-detection, recursive multi-mode merge, value-trim fix,
distinguishable `null`, and strict option validation are all kept.

## Audit checklist

When upgrading a non-trivial project, walk through this list:

- [ ] All `new ParameterBag(...)` call sites: do any rely on the
      old case-insensitive default? Add `caseInsensitive => true`.
- [ ] Any `set('User.foo', ...)` writes followed by `get('user.foo')`
      reads? Pick one case consistently or opt into case-insensitive.
- [ ] Any `merge()` calls with nested payloads that previously
      "happened to work" because `isMulti` was wrong? Verify the
      bag is in the mode you expected.
- [ ] Any code that relied on `has(null-value)` returning false?
      Switch to `get($key) === null` for that specific check.
- [ ] Any code that previously caught surprises from the cache?
      The cache is gone; the bug should be gone too. Remove the
      workaround.
- [ ] Any option keys spelled differently in different files?
      v2 will throw at construction; fix the typos before
      deploying.
