# Exceptions

The package throws a single exception type:
`InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException`.
It extends `\InvalidArgumentException`, so `catch
(\InvalidArgumentException $e)` blocks continue to work.

## When it is raised

| Trigger | Example |
| --- | --- |
| Unknown option key in the constructor | `new ParameterBag([], ['is_multi' => true]);` |
| Non-array, non-ParameterBag argument to `merge()` | `$bag->merge('string');` |
| Array-access append (`$bag[] = $value`) | `$bag[] = 'value';` |

## Catching it

```php
use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;
use InitPHP\ParameterBag\ParameterBag;

try {
    $bag = new ParameterBag([], ['seperator' => '|']);
} catch (ParameterBagInvalidArgumentException $e) {
    // Handle the typo.
}
```

## Hierarchy

```
InvalidArgumentException
└── InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException
```

If you wrap the package in your own service layer and want callers to
catch a single, domain-specific type, re-throw the exception as a
subclass of your own.
