# Exceptions

The package throws a single exception type:

```
\InvalidArgumentException
└── InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException
```

Because it extends the SPL `\InvalidArgumentException`, existing
`catch (\InvalidArgumentException $e)` blocks continue to work.
Most applications will want to catch the more specific type
explicitly though, so the intent is unambiguous.

## When it is raised

| Trigger | Example | Where |
| --- | --- | --- |
| Unknown option key in the constructor | `new ParameterBag([], ['is_multi' => true]);` | Constructor → `setOptions()` |
| Non-array, non-`ParameterBagInterface` argument to `merge()` | `$bag->merge('string');` | `merge()` |
| ArrayAccess append (`$bag[] = $value`) | `$bag[] = 'value';` | `offsetSet()` |

### Unknown option

```php
new ParameterBag([], ['seperator' => '|']);
//                            ^^^^ typo

// ParameterBagInvalidArgumentException:
//   "Unknown ParameterBag option(s): seperator.
//    Known options: isMulti, separator, caseInsensitive."
```

The message lists the accepted keys, which makes the fix obvious
in IDEs and logs alike.

### Bad merge argument

```php
$bag->merge('not an array');
$bag->merge(new \stdClass());

// ParameterBagInvalidArgumentException:
//   "Only an array or a ParameterBag object can be combined."
```

`null`, scalars, generic objects, and even resources all hit the
same branch. Empty arrays and empty bags are silently skipped —
they are **not** an error.

### Array-access append

```php
$bag[] = 'value';

// ParameterBagInvalidArgumentException:
//   "ParameterBag is a string-keyed structure; assignment without a key
//    is not supported."
```

ParameterBag is intentionally not a numeric list. If you genuinely
need an append, store an array value and push to it:

```php
$bag->set('items', [...$bag->get('items', []), $newItem]);
```

## Catching it

```php
use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;
use InitPHP\ParameterBag\ParameterBag;

try {
    $bag = new ParameterBag([], ['seperator' => '|']);
} catch (ParameterBagInvalidArgumentException $e) {
    // Surface the typo to the operator and bail.
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
```

## Re-throwing from your own boundary

If you wrap the package in a service of your own and want callers
to catch a single domain-specific type, re-throw the exception as
your own:

```php
use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;

final class ConfigService
{
    public function __construct(array $raw)
    {
        try {
            $this->bag = new ParameterBag($raw, ['isMulti' => true]);
        } catch (ParameterBagInvalidArgumentException $e) {
            throw new \App\Config\ConfigException(
                'Config could not be parsed: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
```

Preserve the original exception as the `$previous` argument so the
underlying message survives in your logs.

## Things that look like errors but aren't

The bag is deliberately forgiving in places where throwing would
make legitimate workflows clumsy:

| Situation | What happens |
| --- | --- |
| `get('missing')` | Returns `$default` (default: `null`). |
| `remove('missing')` | Silent no-op. |
| `merge([])` / `merge()` with zero args | Silent no-op. |
| `set('user', 'a')` then `set('user.name', 'b')` in multi mode | The scalar `'a'` is silently replaced by `['name' => 'b']`. |
| `new ParameterBag([], ['separator' => ''])` | Empty string is silently rejected; default `'.'` is kept. |

If any of these surprised you, read the [API Reference](API-Reference.md)
entry for the method in question; the behaviour is documented in
each case.
