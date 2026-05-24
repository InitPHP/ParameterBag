# FAQ

## Why is my dotted key stored verbatim?

You are in flat mode. Either supply nested data so multi mode is
auto-detected, or pass `['isMulti' => true]` explicitly. See
[Nested data](usage/nested-data.md).

## Why does `get('user')` return `null` even though I set it as `User`?

v2 keys are case-sensitive by default. Either store and retrieve
with consistent casing, or opt into the legacy behaviour:

```php
new ParameterBag($data, ['caseInsensitive' => true]);
```

See [Case sensitivity](usage/case-sensitivity.md).

## Why did my second `merge()` wipe values from the first?

You are likely in flat mode (or pre-v2). Flat merge is shallow;
multi-mode merge is recursive. See [Merging](usage/merging.md).

## How do I iterate nested data?

`foreach` over the bag yields **top-level** entries. To walk a
specific subtree, pull it out with `get()` and recurse:

```php
foreach ($bag->get('database', []) as $key => $value) { /* ... */ }
```

Or call `all()` and recurse over the plain array.

## How do I append a value without specifying a key?

You cannot — `$bag[] = $value` raises
`ParameterBagInvalidArgumentException` because the bag is a
string-keyed structure. Use a numerically-keyed array value
instead:

```php
$bag->set('items', [...($bag->get('items', [])), $newItem]);
```

## Is the bag immutable?

No. Every `set`/`remove`/`merge`/`replace` mutates in place. If you
need immutability, wrap reads in a service that only exposes
`get`/`has`/`count`/iteration, or clone the bag's data via
`all()` before passing it on.

## Does it serialise / deserialise?

Yes — internally it is just `array` properties. PHP's
`serialize()`/`unserialize()` work, but the package does not provide
a JSON serializer; convert via `$bag->all()` and `json_encode()`.

## How do I subclass safely?

Override the documented `protected` hooks:

- `getKey(string $key): string` — change the key normalisation
  (e.g. snake_case fold, custom trim).
- `setOptions(array $options): void` — add new options. Validate
  them, call `parent::setOptions()` to handle the built-in ones.

Anything else (private properties, private helpers) is internal.

## Does it depend on any libraries?

No. The runtime requirement is PHP only. Dev tools (PHPUnit,
PHPStan, PHP-CS-Fixer) live under `require-dev`.

## Does it work on PHP 7.4 specifically?

Yes — CI runs the matrix across 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4 on
every PR. The `#[\ReturnTypeWillChange]` attribute on `offsetGet()`
parses as a comment on 7.4 and suppresses the PHP 8.1+ deprecation
warning that the standard `ArrayAccess` interface would otherwise
trigger.
