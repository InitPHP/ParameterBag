# Case sensitivity

By default, v2 keys are **case-sensitive** — `User` and `user` are
two different entries. The v1 behaviour (everything lower-cased) is
still available, but it's opt-in.

## Default behaviour (case-sensitive)

```php
use InitPHP\ParameterBag\ParameterBag;

$bag = new ParameterBag();
$bag->set('User', 'alice');

$bag->has('User');     // true
$bag->has('user');     // false
$bag->get('User');     // 'alice'
$bag->get('user');     // null
```

Constructor payloads keep their key case too:

```php
$bag = new ParameterBag([
    'Database' => ['User' => 'root'],
]);

$bag->all();
// ['Database' => ['User' => 'root']]
$bag->get('Database.User'); // 'root'
```

## Opt-in: case-insensitive mode

Pass `caseInsensitive => true` to fold every key (constructor payload,
`get`/`set`/`has`/`remove` arguments, `merge` input) to lower-case
on entry:

```php
$bag = new ParameterBag(
    ['Database' => ['User' => 'root']],
    ['caseInsensitive' => true]
);

$bag->all();
// ['database' => ['user' => 'root']]

$bag->set('Cache.DRIVER', 'redis');
$bag->get('cache.driver');  // 'redis'
$bag->has('CACHE.DRIVER');  // true
```

## Migrating from v1

If you upgraded from v1 and your callers relied on the implicit
lowercasing, add `caseInsensitive => true` to your constructor
calls. The rest of the API is unchanged.

## Common mistakes

- **Mixing modes between bags**: a case-insensitive bag merged into a
  case-sensitive one will land with already-lowercased keys, which
  may not match anything the case-sensitive side stored.
- **Expecting `close()` to keep `caseInsensitive` on**: `close()`
  restores every option to its default, including this one. Use
  `clear()` if you only want to empty the stack.
