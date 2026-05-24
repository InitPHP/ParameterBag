# Configuration options

The `ParameterBag` constructor takes two arguments:

```php
new ParameterBag(array $data = [], array $options = []);
```

`$options` may contain the keys listed below. **Unknown keys raise
`ParameterBagInvalidArgumentException`** — there is no silent
swallowing of typos in v2.

## `isMulti`

| | |
| --- | --- |
| Type | `bool` |
| Default | auto-detected from `$data` |
| Effect | Enables dotted-path semantics (and `array_replace_recursive` merge). |

```php
new ParameterBag([], ['isMulti' => true]);

// Without the flag, auto-detection looks at $data:
//   ['user' => 'a']             → flat   (isMulti = false)
//   ['db' => ['user' => 'a']]   → multi  (isMulti = true)
```

Pass `['isMulti' => false]` to force flat mode even if `$data` is
nested.

## `separator`

| | |
| --- | --- |
| Type | non-empty `string` |
| Default | `'.'` |
| Effect | Delimiter used to split dotted keys in multi mode. |

```php
new ParameterBag(['db' => ['user' => 'root']], ['separator' => '|']);
// $bag->get('db|user'); // 'root'
```

An empty string is rejected silently (the previous value is kept).
The separator must be a non-empty string; multi-character separators
(`'::'`, `'->'`) are allowed.

## `caseInsensitive`

| | |
| --- | --- |
| Type | `bool` |
| Default | `false` |
| Effect | When true, every key (constructor payload, `get`/`set`/`has`/`remove` arguments, `merge` input) is folded to lower-case on entry. Matches the legacy v1 behaviour. |

See [Case sensitivity](usage/case-sensitivity.md) for examples.

## Strict validation

```php
new ParameterBag([], ['is_multi' => true]);
// ParameterBagInvalidArgumentException:
// "Unknown ParameterBag option(s): is_multi. Known options: isMulti, separator, caseInsensitive."
```

This catches every typo at construction time, which is the only place
options are read.

## Resetting options

`close()` resets all three options to their defaults along with
clearing the stack:

```php
$bag = new ParameterBag(
    ['db' => ['user' => 'root']],
    ['isMulti' => true, 'separator' => '|', 'caseInsensitive' => true]
);

$bag->close();
// Now: isMulti=false, separator='.', caseInsensitive=false, stack=[]
```

`clear()` only empties the stack.
