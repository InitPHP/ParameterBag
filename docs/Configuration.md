# Configuration

The `ParameterBag` constructor takes two arguments:

```php
new ParameterBag(array $data = [], array $options = []);
```

`$options` may contain the keys documented below. **Unknown keys
raise `ParameterBagInvalidArgumentException`** — there is no silent
swallowing of typos in v2. The exception message lists the accepted
keys, so a typo points you straight at the fix.

## Accepted options

| Key | Type | Default | Purpose |
| --- | --- | --- | --- |
| [`isMulti`](#ismulti) | `bool` | auto-detected from `$data` | Enables dotted-path semantics and recursive merge. |
| [`separator`](#separator) | non-empty `string` | `'.'` | Delimiter used to split dotted keys in multi mode. |
| [`caseInsensitive`](#caseinsensitive) | `bool` | `false` | Fold every key to lower-case on entry. |

## `isMulti`

Switches between **flat** and **multi** (dotted-path) semantics.

```php
new ParameterBag([], ['isMulti' => true]);   // explicit on
new ParameterBag([], ['isMulti' => false]);  // explicit off
```

If you omit the option (or supply a non-boolean), the bag inspects
`$data` and switches multi mode on whenever the payload contains any
nested array:

```php
new ParameterBag(['user' => 'a']);           // flat
new ParameterBag(['db' => ['user' => 'a']]); // multi
new ParameterBag([]);                         // flat (no data to detect from)
```

Auto-detection is conservative: any nested array anywhere in the
top-level payload flips the switch. If your "list of records"
payload is a list of records (every element is itself an array, such as
`['id' => 1]` and `['id' => 2]`), multi mode will be
auto-enabled — pass `['isMulti' => false]` if you want literal
indexing instead.

See [Nested Data](Nested-Data.md) for the full multi-mode behaviour.

## `separator`

The delimiter used to split dotted keys in multi mode. Ignored
when `isMulti` is off.

```php
new ParameterBag(
    ['db' => ['user' => 'root']],
    ['separator' => '|']
);
// $bag->get('db|user'); // 'root'
```

Constraints:

- Must be a **non-empty string**.
- An empty string is silently rejected (the previous value is kept).
- Multi-character separators are allowed (`'::'`, `'->'`, `'/'`).

Use a non-default separator when your keys legitimately contain
dots (URLs, IP addresses, file paths):

```php
new ParameterBag([], ['isMulti' => true, 'separator' => '::']);
```

## `caseInsensitive`

When `true`, **every** key — constructor payload, `get`/`set`/`has`/
`remove` arguments, and merge input — is folded to lower-case on
entry. This is opt-in in v2; v1 always behaved this way.

```php
new ParameterBag(
    ['User' => 'alice'],
    ['caseInsensitive' => true]
);
// $bag->get('USER'); // 'alice'
```

See [Case Sensitivity](Case-Sensitivity.md) for examples and pitfalls.

## Strict validation

Unknown keys are rejected at construction time, with a message that
names both the offending key and the full list of accepted ones:

```php
new ParameterBag([], ['is_multi' => true]);
// InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException:
//   "Unknown ParameterBag option(s): is_multi.
//    Known options: isMulti, separator, caseInsensitive."
```

The validator only runs at construction. There is no public
"set option after the fact" entry point.

## Resetting options

`close()` resets all three options to their defaults along with
clearing the stack:

```php
$bag = new ParameterBag(
    ['db' => ['user' => 'root']],
    ['isMulti' => true, 'separator' => '|', 'caseInsensitive' => true]
);

$bag->close();
// stack: []
// isMulti=false, separator='.', caseInsensitive=false
```

`clear()` only empties the stack; it leaves the options untouched.

## Reading the current configuration

There is no public getter for the option fields by design — the
ID of the bag is the data it holds, not the knobs that shaped how
it accepted that data. For debugging, `var_dump($bag)` reflects the
options:

```php
var_dump($bag);
// object(InitPHP\ParameterBag\ParameterBag) {
//   ["isMulti"]         => "no"
//   ["separator"]       => "."
//   ["caseInsensitive"] => "no"
//   ["data"]            => array(0) {}
// }
```

If you need programmatic access to the option fields, subclass and
expose them yourself — see [Extending](Extending.md).
