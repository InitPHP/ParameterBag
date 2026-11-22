# Extending

`ParameterBag` is intentionally **not `final`**. v2 documents two
`protected` hooks as stable override points; everything else
(private properties, private helpers, the static sentinel) is
implementation detail and may change without a major-version bump.

## Stable hooks

| Hook | Purpose |
| --- | --- |
| [`getKey`](#key-normalisation) | Customise how caller-supplied keys are normalised (case fold + separator trim by default). |
| [`setOptions`](#adding-new-options) | Recognise new option keys, validate them, then delegate to the parent for built-in handling. |

`getKey()` runs on the caller-supplied key in `get`, `set`, `has`
and `remove` (and therefore through the `ArrayAccess` methods).
`setOptions()` runs from the constructor. Overrides take effect
there without further plumbing; `merge()` and `replace()` do not
call `getKey()`.

## Key normalisation

The default `getKey()` does two things in order:

1. Folds the key to lower-case if `caseInsensitive` is on.
2. Trims leading / trailing separators if `isMulti` is on.

```php
protected function getKey(string $key): string
{
    if ($this->caseInsensitive) {
        $key = strtolower($key);
    }
    if ($this->isMulti) {
        $key = trim($key, $this->separator);
    }
    return $key;
}
```

### Example: snake-case fold

A subclass that accepts `camelCase` and `kebab-case` callers but
stores everything as `snake_case`:

```php
use InitPHP\ParameterBag\ParameterBag;

final class SnakeCaseBag extends ParameterBag
{
    protected function getKey(string $key): string
    {
        $key = parent::getKey($key);

        // camelCase → camel_case, dashes → underscores
        $key = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
        $key = str_replace('-', '_', (string) $key);

        return strtolower($key);
    }
}

$bag = new SnakeCaseBag();
$bag->set('userName', 'alice');
$bag->set('first-name', 'Alice');

$bag->get('user_name');   // 'alice'
$bag->get('first_name');  // 'Alice'
$bag->keys();             // ['user_name', 'first_name']
```

Note: `getKey()` only runs against **caller-supplied** keys; it does
not rewrite the keys of a constructor payload. If you need the
payload itself rewritten, override `replace()` or normalise the
data before construction.

### Example: alias rewriting

```php
final class AliasedBag extends ParameterBag
{
    /** @var array<string, string> */
    private const ALIASES = [
        'user'     => 'username',
        'pwd'      => 'password',
        'database' => 'db',
    ];

    protected function getKey(string $key): string
    {
        $key = parent::getKey($key);

        return self::ALIASES[$key] ?? $key;
    }
}
```

## Adding new options

`setOptions()` is the **only** place options are read, so adding a
new one is a single override. Validate your additions first, then
hand off to the parent for the built-in ones:

```php
use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;

final class ConfigurableBag extends ParameterBag
{
    /** @var int Maximum number of top-level entries; 0 disables the cap. */
    private int $maxEntries = 0;

    protected function setOptions(array $options): void
    {
        if (isset($options['maxEntries'])) {
            if (!is_int($options['maxEntries']) || $options['maxEntries'] < 0) {
                throw new ParameterBagInvalidArgumentException(
                    'maxEntries must be a non-negative integer.'
                );
            }
            $this->maxEntries = $options['maxEntries'];
            unset($options['maxEntries']);   // strip before parent validates
        }

        parent::setOptions($options);        // validates the rest
    }

    public function set(string $key, $value): \InitPHP\ParameterBag\ParameterBagInterface
    {
        if (
            $this->maxEntries > 0
            && !$this->has($key)
            && $this->count() >= $this->maxEntries
        ) {
            throw new ParameterBagInvalidArgumentException(sprintf(
                'Bag is full (maxEntries=%d).',
                $this->maxEntries,
            ));
        }
        return parent::set($key, $value);
    }
}

$bag = new ConfigurableBag([], ['maxEntries' => 2]);
$bag->set('a', 1)->set('b', 2);
$bag->set('c', 3);
// ParameterBagInvalidArgumentException: Bag is full (maxEntries=2).
```

**Key points** of this pattern:

- Strip your option out of `$options` **before** delegating, so
  the parent's strict validation does not reject it.
- Call `parent::setOptions()` last so the built-in options still
  receive their normal validation.
- If you reject the value yourself, throw
  `ParameterBagInvalidArgumentException` (or a subclass) — the
  rest of the library only uses this type.

## What you should NOT override

The following are **not** stable override points; they are private
helpers and may be renamed, inlined, or removed in any minor
release:

- The private property layout (`$stack`, `$isMulti`, `$separator`,
  `$caseInsensitive`).
- The static `$notFound` sentinel.
- `normalizeKeys()`, `multiSubParameterGet()`,
  `multiSubParameterSet()`, `multiSubParameterRemove()`,
  `arrayOrEmpty()`.

If you need to alter behaviour that lives there, prefer wrapping
the bag in a decorator that implements `ParameterBagInterface`
rather than reaching into the implementation.

## Decorator alternative

If your customisation does not need to participate in the bag's
internal state, write a decorator instead:

```php
use InitPHP\ParameterBag\ParameterBagInterface;

final class ReadOnlyBag implements ParameterBagInterface
{
    public function __construct(private ParameterBagInterface $inner) {}

    public function get(string $key, $default = null) { return $this->inner->get($key, $default); }
    public function has(string $key): bool             { return $this->inner->has($key); }
    public function all(): array                       { return $this->inner->all(); }
    public function isEmpty(): bool                    { return $this->inner->isEmpty(); }
    public function keys(): array                      { return $this->inner->keys(); }
    public function values(): array                    { return $this->inner->values(); }
    public function count(): int                       { return $this->inner->count(); }
    public function getIterator(): \Traversable        { return $this->inner->getIterator(); }
    public function offsetExists(mixed $o): bool        { return $this->inner->offsetExists($o); }
    public function offsetGet(mixed $o): mixed        { return $this->inner->offsetGet($o); }

    public function set(string $key, $value): self     { throw new \LogicException('read-only'); }
    public function remove(string ...$keys): self      { throw new \LogicException('read-only'); }
    public function merge(...$merge): self             { throw new \LogicException('read-only'); }
    public function replace(array $data): self         { throw new \LogicException('read-only'); }
    public function clear(): void                      { throw new \LogicException('read-only'); }
    public function close(): void                      { throw new \LogicException('read-only'); }
    public function offsetSet(mixed $o, mixed $v): void { throw new \LogicException('read-only'); }
    public function offsetUnset(mixed $o): void       { throw new \LogicException('read-only'); }
}
```

A decorator is the right choice when you want to compose
behaviours (read-only + logging, freeze-after-boot, etc.) or when
the override would otherwise need to touch implementation
internals.
