# Iteration & counting

`ParameterBag` implements `\ArrayAccess`, `\Countable`, and
`\IteratorAggregate`, so it behaves like a native PHP collection
wherever those contracts are recognised.

## ArrayAccess

Reads and writes go through the same code path as `get/set/has/remove`,
including dotted-path support in multi mode:

```php
$bag = new ParameterBag(['user' => 'alice']);

$bag['user'];              // 'alice'
$bag['locale'] = 'en_US';  // set
isset($bag['user']);       // true
unset($bag['user']);       // remove
```

In multi mode the offset can be a dotted path:

```php
$bag = new ParameterBag(['db' => ['user' => 'root']], ['isMulti' => true]);

$bag['db.user'];           // 'root'
$bag['db.pass'] = 'secret';
isset($bag['db.user']);    // true
unset($bag['db.pass']);
```

`$bag[] = $value` (append without a key) raises
`ParameterBagInvalidArgumentException` because the bag is string-keyed,
not a numeric list.

## Countable

`count()` reports the number of TOP-LEVEL entries — nested arrays are
not unwound:

```php
$bag = new ParameterBag([
    'db'    => ['user' => 'root', 'pass' => 'x'],
    'cache' => ['ttl' => 60],
]);

count($bag);   // 2

$bag->isEmpty();    // false
```

## Iteration

`getIterator()` yields top-level entries in insertion order via an
`\ArrayIterator`, so iteration is repeatable:

```php
$bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);

foreach ($bag as $key => $value) {
    echo "$key=$value\n";
}
// a=1
// b=2
// c=3
```

To walk nested data yourself, call `all()` and recurse, or pick a
specific subtree with `get()`:

```php
foreach ($bag->get('db', []) as $key => $value) { /* ... */ }
```

## Common mistakes

- **Assuming iteration recurses**: it does not. Use `get('subtree')`
  or `all()` and recurse yourself.
- **Mixing ArrayAccess append with multi mode**: `$bag[] = ...`
  always throws; in multi mode there is no "append to root" concept.
