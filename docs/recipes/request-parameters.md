# Recipe: request parameters

Goal: wrap PHP's superglobals (`$_GET`, `$_POST`, `$_SERVER`) in a
`ParameterBag` so request data is read through a consistent,
default-aware API.

## Wrapping `$_GET`

```php
use InitPHP\ParameterBag\ParameterBag;

$query = new ParameterBag($_GET);

$query->get('page', 1);
$query->get('q', '');
$query->has('debug');
```

## Wrapping `$_POST`

```php
$body = new ParameterBag($_POST);

$email    = $body->get('email');
$password = $body->get('password');
```

## Wrapping `$_SERVER` with case-insensitive header lookup

HTTP header names are case-insensitive. If you intend to read them
through the bag, enable `caseInsensitive`:

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
$headers->get('http_authorization'); // same value
```

## Combining into a single request bag

```php
$request = new ParameterBag([
    'query'  => $_GET,
    'body'   => $_POST,
    'server' => $_SERVER,
]);

$request->get('query.page');
$request->get('body.email');
$request->get('server.REMOTE_ADDR');
```

The nested constructor payload auto-enables multi mode.

## Common pitfalls

- **Trusting input directly**: ParameterBag does not validate or
  sanitise values. Treat what comes out of it the same way you would
  treat the raw superglobal.
- **`$_FILES`**: file uploads have a peculiar structure (`name`,
  `tmp_name`, `error`, `size`, `type`). A bag can carry them, but
  prefer a dedicated upload handler for parsing.
