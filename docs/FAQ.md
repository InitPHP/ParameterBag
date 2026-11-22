# FAQ

Quick answers to questions that came up while writing or reviewing
the package. If your question is not here, please
[open an issue](https://github.com/InitPHP/ParameterBag/issues/new) —
documentation fixes are reviewed eagerly.

## General

### What is ParameterBag for?

A small, dependency-free container that exposes a uniform API
(`get` / `set` / `has` / `remove` + ArrayAccess + iteration) over
both flat and nested data. Typical uses: config trees, request
parameter wrappers, session payloads, ad-hoc DTOs.

### What is it NOT for?

- **Validation.** The bag is a transport; values flow through
  unchanged. Validate at the consumer boundary.
- **Strongly-typed config.** If typos in config keys should be
  caught at parse time, reach for a schema-validated config DTO.
- **Immutability.** ParameterBag mutates in place. Wrap it in a
  [decorator](Extending.md#decorator-alternative) if you only want
  reads.

### Does it have any runtime dependencies?

No. Dev tools (PHPUnit, PHPStan, PHP-CS-Fixer) live under
`require-dev`. The runtime requires PHP only.

## Usage

### Why is my dotted key stored verbatim?

You are in flat mode. Either supply nested data so multi mode is
auto-detected, or pass `['isMulti' => true]` explicitly. See
[Nested Data](Nested-Data.md).

### Why does `get('user')` return `null` even though I set it as `User`?

v2 keys are case-sensitive by default. Either store and retrieve
with consistent casing, or opt into the legacy behaviour:

```php
new ParameterBag($data, ['caseInsensitive' => true]);
```

See [Case Sensitivity](Case-Sensitivity.md).

### Why did my second `merge()` wipe values from the first?

You are likely in flat mode (or running v1). Flat merge is shallow
(`array_merge`); multi-mode merge is recursive
(`array_replace_recursive`). See [Merging](Merging.md).

### How do I iterate nested data?

`foreach` over the bag yields **top-level** entries. To walk a
specific subtree, pull it out with `get()` and recurse:

```php
foreach ($bag->get('database', []) as $key => $value) { /* ... */ }
```

Or call `all()` and recurse over the plain array yourself.

### How do I append a value without specifying a key?

You cannot — `$bag[] = $value` raises
`ParameterBagInvalidArgumentException` because the bag is a
string-keyed structure, not a numeric list. Use a numerically-keyed
array value instead:

```php
$bag->set('items', [...$bag->get('items', []), $newItem]);
```

### Is the bag immutable?

No. Every `set` / `remove` / `merge` / `replace` mutates in place.
If you need immutability, wrap reads in a service that only
exposes `get` / `has` / `count` / iteration, or clone the bag's
data via `all()` before passing it on. See
[Extending → Decorator alternative](Extending.md#decorator-alternative).

### Does it serialise / deserialise?

Yes — internally it is just `array` properties, so PHP's
`serialize()` / `unserialize()` work out of the box. The package
does not ship a JSON serializer; convert via
`$bag->all()` and `json_encode()`:

```php
echo json_encode($bag->all(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
```

### Can I store closures / objects / resources?

Yes. The bag is value-agnostic: it stores whatever you put in.
Closures and objects survive round-trips through `set` / `get`;
resources too, with the usual PHP caveat that you cannot serialise
them.

## Extending

### How do I subclass safely?

Override the documented `protected` hooks:

- `getKey(string $key): string` — change key normalisation
  (e.g. snake-case fold, alias rewriting, custom trim).
- `setOptions(array $options): void` — recognise new option keys.
  Validate your additions, strip them from `$options`, then call
  `parent::setOptions()` to handle the built-in ones.

Anything else (private properties, private helpers, the static
sentinel) is internal and may change without notice. See
[Extending](Extending.md) for full examples.

### How do I add a new option without breaking validation?

Strip your option out of `$options` **before** delegating to the
parent:

```php
protected function setOptions(array $options): void
{
    if (isset($options['maxEntries'])) {
        // validate, store, then strip
        $this->maxEntries = $options['maxEntries'];
        unset($options['maxEntries']);
    }
    parent::setOptions($options);
}
```

The parent's strict validator only sees keys it recognises.

## PHP / tooling

### Which PHP versions are supported?

PHP 8.2 and later. `composer.json` requires `^8.2`, and CI runs the
test matrix on 8.2, 8.3, 8.4 and 8.5. Releases up to and including
2.0.0 also supported PHP 7.4 and 8.0 – 8.1.

### What's the PHPStan level?

Level 8, configured in `phpstan.neon.dist`, which analyses both
`src` and `tests`.

### Where are the tests?

Under `tests/` in the source repo. The suite covers the constructor,
case sensitivity, infrastructure (ArrayAccess / Countable /
Iterator), merge, remove, sentinel handling, value integrity, edge
cases, and the standard interfaces.

## Versioning

### How are breaking changes versioned?

The project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Anything that breaks the public contract (class, interface,
protected hooks, exception types, minimum PHP version) belongs in a
major release. Private helpers and the static sentinel are
implementation detail and may change in minor releases. The
[CHANGELOG](https://github.com/InitPHP/ParameterBag/blob/main/CHANGELOG.md)
lists what is pending under *Unreleased*.

## Other

### Where do I report a security issue?

See [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md)
in the InitPHP org profile. Please do not file public issues for
vulnerabilities.

### Where do I open feature requests or discussions?

[InitPHP Discussions](https://github.com/orgs/InitPHP/discussions)
is the right place for open-ended threads. Concrete bug reports
and feature proposals belong in
[the issue tracker](https://github.com/InitPHP/ParameterBag/issues).
