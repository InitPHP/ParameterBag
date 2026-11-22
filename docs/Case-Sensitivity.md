# Case Sensitivity

By default, v2 keys are **case-sensitive** — `User` and `user` are
two different entries. The v1 behaviour (every key folded to
lower-case) is still available, but it has to be opted into
explicitly with `caseInsensitive => true`.

## Default behaviour (case-sensitive)

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag();
$bag->set('User', 'alice');

$bag->has('User');   // true
$bag->has('user');   // false
$bag->get('User');   // 'alice'
$bag->get('user');   // null
```

Constructor payloads keep their key case too:

```php
$bag = new ParameterBag([
    'Database' => ['User' => 'root'],
]);

$bag->all();                  // ['Database' => ['User' => 'root']]
$bag->get('Database.User');   // 'root'
$bag->get('database.user');   // null
```

## Opt-in: case-insensitive mode

Pass `caseInsensitive => true` to fold **every** key — constructor
payload, `get`/`set`/`has`/`remove` arguments, and merge input — to
lower-case on entry:

```php
$bag = new ParameterBag(
    ['Database' => ['User' => 'root']],
    ['caseInsensitive' => true]
);

$bag->all();
// ['database' => ['user' => 'root']]   // stored already-lowercased

$bag->set('Cache.DRIVER', 'redis');
$bag->get('cache.driver');   // 'redis'
$bag->has('CACHE.DRIVER');   // true
$bag->keys();                // ['database', 'cache']
```

Folding happens once, at the boundary. There is no per-call
overhead beyond a single `strtolower()` on the incoming key, and a
single `array_change_key_case()` walk on incoming array values.

## A realistic example: HTTP headers

`$_SERVER` HTTP headers are case-insensitive in the spec but
case-preserving in PHP. Wrap them in a case-insensitive bag and
lookups become forgiving:

```php
$headers = new ParameterBag(
    array_filter(
        $_SERVER,
        static fn (string $name) => str_starts_with($name, 'HTTP_'),
        ARRAY_FILTER_USE_KEY
    ),
    ['caseInsensitive' => true]
);

$headers->get('HTTP_AUTHORIZATION');
$headers->get('http_authorization');  // same value
$headers->get('Http_Authorization');  // same value
```

## Migrating from v1

If you upgraded from v1 and your callers relied on the implicit
lower-casing, add `caseInsensitive => true` to your constructor
calls. The rest of the API is unchanged.

A small shim that keeps the v1 default while still benefitting
from every v2 bug fix:

```php
use InitPHP\ParameterBag\ParameterBag;

function legacyParameterBag(array $data = [], array $options = []): ParameterBag
{
    return new ParameterBag(
        $data,
        $options + ['caseInsensitive' => true]
    );
}
```

See the [Migration Guide](Migration-Guide.md) for the full v1 → v2 list.

## Interaction with other options

- **`isMulti`**: case-insensitive mode lower-cases keys at every
  depth, including the segments of a dotted path. `get('FOO.BAR')`
  still resolves against `['foo' => ['bar' => …]]`.
- **`separator`**: unaffected. The separator string itself is not
  lowercased; only the surrounding key characters are.
- **`close()`**: resets `caseInsensitive` back to `false`. If you
  only want to empty the stack, use `clear()`.

## Common mistakes

- **Mixing modes across bags.** Merging a case-insensitive bag
  (whose keys are already lower-cased) into a case-sensitive one
  lands lower-cased keys, which may not match the case-sensitive
  side's existing entries.
- **Expecting case folding to apply to values.** Only keys are
  folded. `set('locale', 'EN_us')` stores `'EN_us'` verbatim.
- **Round-tripping mixed-case data through a case-insensitive bag.**
  The original casing is lost once `caseInsensitive => true` runs
  over a payload. Store the original elsewhere if you need it.
