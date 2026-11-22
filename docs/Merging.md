# Merging

`merge()` accepts one or more **arrays** and/or **`ParameterBagInterface`**
instances and folds them into the current bag. Its behaviour depends
on the bag's mode:

| Mode | Strategy | PHP primitive |
| --- | --- | --- |
| Flat (`isMulti = false`) | Shallow merge; later entries win on key collision. | `array_merge` |
| Multi (`isMulti = true`) | Recursive replace; sibling keys at every depth are preserved. | `array_replace_recursive` |

Empty arguments (`[]` or an empty bag) are skipped silently — so it
is safe to spread an optional payload.

## Flat merge

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag(['a' => 1, 'b' => 2]);
$bag->merge(['b' => 20, 'c' => 30]);

$bag->all();
// ['a' => 1, 'b' => 20, 'c' => 30]
```

Flat merge is shallow — if both sides have a key whose value is an
array, the right-hand array **replaces** the left-hand array
wholesale.

## Multi-mode merge preserves siblings

```php
$bag = new ParameterBag(
    ['db' => ['user' => 'root']],
    ['isMulti' => true]
);

$bag->merge(['db' => ['pass' => 'secret']]);

$bag->all();
// ['db' => ['user' => 'root', 'pass' => 'secret']]
```

This was a v1 regression: in v1 the flat `array_merge` would have
wiped `db.user`. v2 dispatches to `array_replace_recursive` whenever
`isMulti` is on.

## Multiple payloads in one call

```php
$bag = new ParameterBag([], ['isMulti' => true]);

$bag->merge(
    ['db'    => ['user' => 'root']],
    ['db'    => ['pass' => 'secret']],
    new ParameterBag(['cache' => ['driver' => 'redis']], ['isMulti' => true])
);

$bag->all();
// [
//   'db'    => ['user' => 'root', 'pass' => 'secret'],
//   'cache' => ['driver' => 'redis'],
// ]
```

Payloads are applied left-to-right; the rightmost value wins on
collision. Bag instances are unwrapped via `->all()` before being
merged, so their own options (e.g. their separator) do not leak
into the destination bag.

## What's allowed as an argument

| Type | Treated as |
| --- | --- |
| `array` | Plain payload |
| `ParameterBagInterface` | Unwrapped via `->all()` then merged as an array |
| Anything else | Rejected — throws `ParameterBagInvalidArgumentException` |

```php
$bag->merge('not an array');   // ParameterBagInvalidArgumentException
$bag->merge(new \stdClass());  // ParameterBagInvalidArgumentException
$bag->merge([]);               // no-op
$bag->merge();                 // no-op (zero args)
```

## Merging across modes

The merge behaviour is determined by the **destination** bag, not by
the payload. A flat-mode bag uses `array_merge` even when fed nested
arrays:

```php
$dest = new ParameterBag(['db' => ['user' => 'root']], ['isMulti' => false]);
$dest->merge(['db' => ['pass' => 'secret']]);

$dest->all();
// ['db' => ['pass' => 'secret']]   // shallow merge wiped 'user'
```

If you need recursive merging, ensure `isMulti` is on at the
destination — either via auto-detection or by passing it
explicitly.

## Case-insensitive merging

If the destination bag is `caseInsensitive`, **every key in every
payload is lower-cased before merging**:

```php
$bag = new ParameterBag([], ['caseInsensitive' => true]);
$bag->merge(['User' => 'alice', 'ROLE' => 'admin']);

$bag->all();
// ['user' => 'alice', 'role' => 'admin']
```

Mixing case-sensitive and case-insensitive bags via `merge()` is a
common source of silent data loss — see
[Case Sensitivity → Common mistakes](Case-Sensitivity.md#common-mistakes).

## Common mistakes

- **Expecting flat-mode merge to deep-merge.** Pass
  `['isMulti' => true]` (or supply nested data so auto-detection
  switches it on).
- **Merging into a `close()`d bag.** `close()` also resets options,
  so a previously multi-mode bag will be back in flat mode for the
  next merge.
- **Relying on numeric-key behaviour from `array_merge`.** In flat
  mode, `array_merge` renumbers integer keys. If you store
  numerically-indexed data and that matters, use multi mode (which
  uses `array_replace_recursive` and preserves keys).
