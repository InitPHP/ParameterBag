# Iteration & Counting

`ParameterBag` implements three standard PHP collection contracts:

- `\ArrayAccess` — subscript syntax (`$bag['foo']`)
- `\Countable` — `count($bag)`
- `\IteratorAggregate` — `foreach ($bag as $k => $v)`

The interface (`ParameterBagInterface`) extends all three, so any
consumer that depends on the interface gets the same affordances.

## ArrayAccess

Reads and writes go through the same code path as
`get`/`set`/`has`/`remove`, including dotted-path support in
multi mode:

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag(['user' => 'alice']);

$bag['user'];              // 'alice'  (offsetGet → get)
$bag['locale'] = 'en_US';  // offsetSet → set
isset($bag['user']);       // true     (offsetExists → has)
unset($bag['user']);       // offsetUnset → remove
```

In multi mode, the offset can be a dotted path:

```php
$bag = new ParameterBag(['db' => ['user' => 'root']], ['isMulti' => true]);

$bag['db.user'];           // 'root'
$bag['db.pass'] = 'secret';
isset($bag['db.user']);    // true
unset($bag['db.pass']);
```

### `$bag[] = $value` is intentionally rejected

ParameterBag is a string-keyed structure, not a numeric list, so
the append form raises an exception:

```php
$bag[] = 'value';
// InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException
// "ParameterBag is a string-keyed structure; assignment without a key is not supported."
```

If you genuinely want to append to a list stored **inside** the bag,
read the array out, push to it, write it back:

```php
$bag->set('items', [...$bag->get('items', []), $newItem]);
```

## Countable

`count()` (and `$bag->count()`) report the number of **top-level**
entries. Nested arrays are not unwound:

```php
$bag = new ParameterBag([
    'db'    => ['user' => 'root', 'pass' => 'x'],
    'cache' => ['ttl'  => 60],
]);

count($bag);     // 2     (not 3 — nesting is not counted)
$bag->count();   // 2
$bag->isEmpty(); // false
```

`isEmpty()` is `true` only when the top-level stack is the literal
empty array `[]`. A stack with a single empty-array value
(`['db' => []]`) reports `isEmpty() === false`.

## Iteration

`getIterator()` returns an `\ArrayIterator` over the top-level
entries in **insertion order**, so iteration is repeatable (each
`foreach` constructs a fresh iterator):

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);

foreach ($bag as $key => $value) {
    echo "$key=$value\n";
}
// a=1
// b=2
// c=3
```

To walk **nested** data, either reach into a subtree with `get()`
and iterate that:

```php
foreach ($bag->get('db', []) as $field => $value) {
    /* ... */
}
```

…or pull the whole stack with `all()` and recurse over the plain
array yourself.

## `var_dump` / `__debugInfo`

`__debugInfo()` returns a compact, debug-friendly snapshot rather
than dumping every private property:

```php
$bag = new ParameterBag(
    ['db' => ['user' => 'root']],
    ['isMulti' => true, 'separator' => '|', 'caseInsensitive' => true]
);

var_dump($bag);
// object(InitPHP\ParameterBag\ParameterBag) {
//   ["isMulti"]         => "yes"
//   ["separator"]       => "|"
//   ["caseInsensitive"] => "yes"
//   ["data"]            => array(1) { ["db"] => array(1) { ["user"] => string(4) "root" } }
// }
```

The booleans are rendered as `'yes' | 'no'` purely to make the dump
easier to read — they reflect the real `bool` properties one-to-one.

## Common mistakes

- **Assuming iteration recurses.** It does not. Top-level only.
  Use `get('subtree')` or `all()` and recurse yourself.
- **`$bag[] = $value`.** See above — always throws. Use a typed key.
- **Treating `count($bag)` as a recursive total.** It counts the
  top-level slots; use `count($bag->all(), COUNT_RECURSIVE)` if you
  genuinely want the total node count.
