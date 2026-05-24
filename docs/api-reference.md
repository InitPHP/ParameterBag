# API reference

Every public method on `InitPHP\ParameterBag\ParameterBag` and
`InitPHP\ParameterBag\ParameterBagInterface`. Behavioural details are
linked into the [Usage](usage/) section.

## Constructor

```php
public function __construct(array $data = [], array $options = []);
```

Initialises the bag with `$data` and applies `$options`. See
[Configuration options](configuration.md). Throws
`ParameterBagInvalidArgumentException` if any unknown option key is
supplied.

## `get`

```php
public function get(string $key, mixed $default = null): mixed;
```

Returns the value at `$key`, or `$default` if the key is absent.
Dotted paths walk nested arrays when `isMulti` is on. A scalar leaf
at a non-leaf path returns `$default` (the bag does not probe into
strings). See [Basic usage](usage/basic-usage.md) and
[Nested data](usage/nested-data.md).

## `has`

```php
public function has(string $key): bool;
```

`true` when the key exists, even when the stored value is `null`.

## `set`

```php
public function set(string $key, mixed $value): self;
```

Assigns `$value` to `$key`. Array values are normalised through the
constructor's code path (recursive lower-casing when
`caseInsensitive` is on). Returns `$this` for chaining.

## `remove`

```php
public function remove(string ...$keys): self;
```

Deletes one or more keys. Missing keys are a no-op. Returns `$this`.

## `merge`

```php
public function merge(array|ParameterBagInterface ...$payloads): self;
```

Flat mode → `array_merge` (shallow). Multi mode →
`array_replace_recursive`. Empty arguments are skipped silently.
Throws `ParameterBagInvalidArgumentException` if any argument is
neither an array nor a `ParameterBagInterface`. See
[Merging](usage/merging.md).

## `replace`

```php
public function replace(array $data): self;
```

Swaps the entire stack. Options are preserved.

## `all`

```php
public function all(): array;
```

Returns the underlying stack as a plain array, preserving nesting.

## `keys` / `values`

```php
public function keys(): array;
public function values(): array;
```

Top-level keys / values in insertion order.

## `isEmpty`

```php
public function isEmpty(): bool;
```

`true` when the stack has no top-level entries.

## `count`

```php
public function count(): int;
```

Top-level entry count. Also available via `count($bag)` (Countable).

## `getIterator`

```php
public function getIterator(): \ArrayIterator;
```

Yields top-level entries. Iteration is repeatable. See
[Iteration & counting](usage/iteration-and-counting.md).

## `offsetGet` / `offsetSet` / `offsetExists` / `offsetUnset`

```php
public function offsetExists(mixed $offset): bool;
public function offsetGet(mixed $offset): mixed;
public function offsetSet(mixed $offset, mixed $value): void;
public function offsetUnset(mixed $offset): void;
```

Delegate to `has`, `get`, `set`, and `remove`. Appending without a
key (`$bag[] = $v`) raises
`ParameterBagInvalidArgumentException`.

## `clear`

```php
public function clear(): void;
```

Empties the stack but leaves options intact.

## `close`

```php
public function close(): void;
```

Empties the stack AND resets every option to its default
(`isMulti=false`, `separator='.'`, `caseInsensitive=false`).

## `__debugInfo`

```php
public function __debugInfo(): array;
```

Returns a `var_dump`-friendly snapshot:

```php
[
    'isMulti'   => 'yes' | 'no',
    'separator' => string,
    'data'      => array,
]
```

## Protected hooks (subclass override points)

### `getKey`

```php
protected function getKey(string $key): string;
```

Normalises a caller-supplied key (case fold + separator trim).
Override to change the normalisation policy in a subclass.

### `setOptions`

```php
protected function setOptions(array $options): void;
```

Applies recognised options. Subclasses that introduce additional
options should override this method, extend the validation, and call
back into `parent::setOptions()`.
