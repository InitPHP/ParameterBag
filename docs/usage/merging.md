# Merging

`merge()` accepts one or more arrays and/or `ParameterBagInterface`
instances. Its behaviour depends on the mode:

| Mode | Strategy | PHP equivalent |
| --- | --- | --- |
| Flat (`isMulti = false`) | Shallow merge; later entries win on collision. | `array_merge` |
| Multi (`isMulti = true`) | Recursive replace; sibling keys at every depth are preserved. | `array_replace_recursive` |

Empty arguments (`[]` or an empty bag) are skipped silently.

## Flat merge

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2]);
$bag->merge(['b' => 20, 'c' => 30]);

$bag->all();
// ['a' => 1, 'b' => 20, 'c' => 30]
```

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

In v1 this would have wiped `db.user` because the merge was shallow.
v2 uses `array_replace_recursive` whenever `isMulti` is on.

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

## Error cases

```php
$bag->merge('not an array');   // ParameterBagInvalidArgumentException
$bag->merge(new \stdClass());  // ParameterBagInvalidArgumentException
```

## Common mistakes

- **Expecting flat-mode merge to deep-merge**: pass
  `['isMulti' => true]` (or supply nested data so auto-detection
  switches it on).
- **Trying to merge into a closed bag**: `close()` resets the options
  too. If you `close()` a bag that was created in multi mode, the
  next `merge()` runs in flat mode.
