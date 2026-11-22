# API Reference

The full surface of `InitPHP\ParameterBag\ParameterBag` and
`InitPHP\ParameterBag\ParameterBagInterface`. Behavioural deep-dives
are linked into the [Core Usage](Basic-Usage.md) section.

> Parameters and return values typed `mixed` or as a union below are
> declared in PHPDoc, not in the native signature; the signatures in
> the source leave them untyped.

---

## `ParameterBagInterface`

Contract implemented by `ParameterBag`. Extends
`\ArrayAccess<array-key, mixed>`, `\Countable`, and
`\IteratorAggregate<array-key, mixed>`.

```php
namespace InitPHP\ParameterBag;

interface ParameterBagInterface
    extends \ArrayAccess, \Countable, \IteratorAggregate
{
    public function close(): void;
    public function clear(): void;
    public function all(): array;
    public function isEmpty(): bool;
    public function keys(): array;
    public function values(): array;
    public function replace(array $data): self;
    public function has(string $key): bool;
    public function get(string $key, $default = null);
    public function set(string $key, $value): self;
    public function remove(string ...$keys): self;
    public function merge(...$merge): self;
}
```

Depend on the interface in service signatures so your callers can
substitute a custom implementation (a frozen wrapper, a tracking
proxy, etc.) without touching consumer code.

---

## `ParameterBag`

The default concrete implementation. Open for extension — see
[Extending](Extending.md).

### Constructor

```php
public function __construct(array $data = [], array $options = []);
```

Initialises the bag with `$data` and applies `$options`. See
[Configuration](Configuration.md) for the option list. Throws
`ParameterBagInvalidArgumentException` if any unknown option key is
supplied.

When `$options['isMulti']` is omitted (or non-boolean), the
constructor auto-detects multi mode from the payload: any nested
array enables it.

```php
new ParameterBag(['db' => ['user' => 'root']]);          // multi (auto)
new ParameterBag(['locale' => 'en_US']);                 // flat (auto)
new ParameterBag([], ['isMulti' => true]);               // multi (explicit)
new ParameterBag($_GET, ['caseInsensitive' => true]);    // flat, lower-case keys
```

### `get`

```php
public function get(string $key, $default = null): mixed;
```

Returns the value at `$key`, or `$default` if the key is absent.

- In flat mode, behaves like `$stack[$key] ?? $default`.
- In multi mode, walks the nested structure using the configured
  separator. A scalar leaf on a non-leaf path is treated as
  "missing" and returns `$default`.

```php
$bag->get('user', 'guest');
$bag->get('db.user', 'root');
```

### `has`

```php
public function has(string $key): bool;
```

`true` when the key exists, **even when the stored value is `null`**.
This is the authoritative existence check — use it instead of
`get($key) !== null` when you need to distinguish "absent" from
"present-but-null".

### `set`

```php
public function set(string $key, $value): self;
```

Assigns `$value` to `$key`. Array values are normalised through the
constructor's code path (recursive lower-casing when
`caseInsensitive` is on). Returns `$this` for chaining.

In multi mode, intermediate arrays are created automatically. A
scalar sitting at a parent path is silently replaced by a fresh
subtree — `set()` is total and never throws on path mismatch.

```php
$bag->set('cache.driver', 'redis')->set('cache.host', '127.0.0.1');
```

### `remove`

```php
public function remove(string ...$keys): self;
```

Deletes one or more keys. Missing keys are a no-op. Returns `$this`.

```php
$bag->remove('db.pass', 'cache.ttl');
```

Removing the last leaf of a subtree leaves an empty array (`[]`) at
the parent slot; the parent is **not** pruned automatically.

### `merge`

```php
public function merge(array|ParameterBagInterface ...$payloads): self;
```

Flat mode → `array_merge` (shallow). Multi mode →
`array_replace_recursive`. Empty arguments are skipped silently.
Throws `ParameterBagInvalidArgumentException` if any argument is
neither an array nor a `ParameterBagInterface`.

Bag arguments are unwrapped via `->all()` before merging — their
options do not influence the destination bag.

See [Merging](Merging.md) for examples.

### `replace`

```php
public function replace(array $data): self;
```

Swaps the entire stack. The new payload is normalised the same way
the constructor normalises it (so a case-insensitive bag will
lower-case keys here too). Options are preserved.

### `all`

```php
public function all(): array;
```

Returns the underlying stack as a plain array, preserving nesting.
Useful for `json_encode`, serialisation, or recursive walks.

### `keys` / `values`

```php
public function keys(): array;
public function values(): array;
```

Top-level keys / values in insertion order. Nested arrays appear
as one value each.

### `isEmpty`

```php
public function isEmpty(): bool;
```

`true` when the stack has no top-level entries. Equivalent to
`$bag->all() === []`.

### `count`

```php
public function count(): int;
```

Top-level entry count. Also available via `count($bag)` (the
`Countable` interface).

### `getIterator`

```php
public function getIterator(): \ArrayIterator;
```

Returns a fresh `\ArrayIterator` over the top-level entries in
insertion order. Iteration is repeatable. See
[Iteration & Counting](Iteration-And-Counting.md).

### `offsetExists` / `offsetGet` / `offsetSet` / `offsetUnset`

```php
public function offsetExists(mixed $offset): bool;
public function offsetGet(mixed $offset): mixed;
public function offsetSet(mixed $offset, mixed $value): void;
public function offsetUnset(mixed $offset): void;
```

Delegate to `has`, `get`, `set`, and `remove` respectively. The
offset is cast to `string` before delegation, so integer offsets
work too (e.g. `$bag[42]`). Appending without a key
(`$bag[] = $value`) raises
`ParameterBagInvalidArgumentException`.

`offsetGet` is marked `#[\ReturnTypeWillChange]` because its
signature leaves the return type untyped; the attribute suppresses the
PHP 8.1+ deprecation notice that the standard `ArrayAccess` interface
would otherwise emit.

### `clear`

```php
public function clear(): void;
```

Empties the stack but leaves options intact. Cheap and idempotent.

### `close`

```php
public function close(): void;
```

Empties the stack **and** resets every option to its default
(`isMulti=false`, `separator='.'`, `caseInsensitive=false`). Use
this when handing a recycled bag to unrelated code.

### `__debugInfo`

```php
public function __debugInfo(): array;
```

Returns a `var_dump`-friendly snapshot:

```php
[
    'isMulti'         => 'yes' | 'no',
    'separator'       => string,
    'caseInsensitive' => 'yes' | 'no',
    'data'            => array,
]
```

The booleans are rendered as `'yes' | 'no'` purely to make the dump
easier to read; they correspond to the real `bool` properties
one-to-one.

---

## Protected hooks

Documented override points for subclasses. Everything else
(private properties, private helpers, the static sentinel) is
implementation detail and may change without a major bump.

### `getKey`

```php
protected function getKey(string $key): string;
```

Normalises a caller-supplied key. The default implementation
lower-cases when `caseInsensitive` is on, then trims leading and
trailing separators when `isMulti` is on.

Override to add policies such as snake-case folding, alias
rewriting, or trimming arbitrary characters. See
[Extending → Key normalisation](Extending.md#key-normalisation).

### `setOptions`

```php
protected function setOptions(array $options): void;
```

Applies recognised options. Subclasses that introduce new options
should override this method, validate the additions, then call
`parent::setOptions()` to delegate the built-in ones. See
[Extending → Adding new options](Extending.md#adding-new-options).

---

## Exception

| Class | Extends | Thrown from |
| --- | --- | --- |
| `InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException` | `\InvalidArgumentException` | Constructor (unknown option), `merge()` (bad argument type), `offsetSet()` (`$bag[] = $v` append). |

See [Exceptions](Exceptions.md).
