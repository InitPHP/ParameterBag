# Documentation

Developer documentation for the `initphp/parameterbag` package. The
project [README](../README.md) gives a one-page overview; this
directory goes deeper.

## Index

- [Getting started](getting-started.md) — install, instantiate, and
  read your first parameter.
- **Usage**
  - [Basic usage](usage/basic-usage.md) — `get`, `set`, `has`,
    `remove` in flat mode.
  - [Nested data](usage/nested-data.md) — multi-mode and dotted paths.
  - [Merging](usage/merging.md) — flat vs. recursive merge.
  - [Iteration & counting](usage/iteration-and-counting.md) —
    `ArrayAccess`, `Countable`, `IteratorAggregate`.
  - [Case sensitivity](usage/case-sensitivity.md) — the
    `caseInsensitive` option.
- [Configuration options](configuration.md) — full options reference.
- [API reference](api-reference.md) — every public method, listed.
- [Exceptions](exceptions.md) — when and why the package throws.
- **Recipes**
  - [Config loader](recipes/config-loader.md) — load a PHP config file
    and expose it as a bag.
  - [Request parameters](recipes/request-parameters.md) — wrap
    `$_GET` / `$_POST`.
  - [Dependency injection](recipes/dependency-injection.md) — inject
    a `ParameterBagInterface` into your services.
- [Upgrading from v1](upgrading-from-v1.md) — BC notes for v2.
- [FAQ](faq.md) — common pitfalls and clarifications.

## How to read these docs

Every page is structured as **Goal → Working example → Expected output
→ Common mistakes**. Snippets are copy-paste ready against the
released package; outputs were generated against the test suite.
