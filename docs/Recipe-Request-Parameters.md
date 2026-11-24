# Recipe: Request Parameters

**Goal:** wrap PHP's superglobals (`$_GET`, `$_POST`, `$_SERVER`) in
a `ParameterBag` so request data is read through a consistent,
default-aware API — and so HTTP header lookups become
case-insensitive without sprinkling `strtoupper()` calls everywhere.

## Wrapping `$_GET` (query string)

```php
use InitPHP\ParameterBag\ParameterBag;

$query = new ParameterBag($_GET);

$query->get('page', 1);
$query->get('q', '');
$query->has('debug');
```

`$_GET` is typically flat, so the bag stays in flat mode and the
dot in `get('page.number')` would be a literal character. If your
framework feeds you query data as a nested array, multi mode kicks
in automatically.

## Wrapping `$_POST` (request body)

```php
$body = new ParameterBag($_POST);

$email    = $body->get('email');
$password = $body->get('password');
$remember = (bool) $body->get('remember', false);
```

ParameterBag does not validate or sanitise values — treat what
comes out of it the same way you would treat the raw superglobal.
Add a validation layer at the call site (or in a service that
consumes the bag).

## Wrapping `$_SERVER` with case-insensitive headers

HTTP header names are case-insensitive in the spec but
case-preserving in PHP. Filter the `HTTP_*` slots, wrap them in a
case-insensitive bag, and lookups become forgiving:

```php
$headers = new ParameterBag(
    array_filter(
        $_SERVER,
        static fn (string $name) => str_starts_with($name, 'HTTP_'),
        ARRAY_FILTER_USE_KEY,
    ),
    ['caseInsensitive' => true],
);

$headers->get('HTTP_AUTHORIZATION');
$headers->get('http_authorization');  // same value
$headers->get('Http_Authorization');  // same value
```

If you'd rather expose headers under their HTTP names (without the
`HTTP_` prefix and with dashes), normalise as you build the bag:

```php
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (!str_starts_with($key, 'HTTP_')) {
        continue;
    }
    $name = strtr(substr($key, 5), '_', '-');
    $headers[$name] = $value;
}

$headers = new ParameterBag($headers, ['caseInsensitive' => true]);

$headers->get('authorization');
$headers->get('content-type');
```

## Combining query, body, and server into one bag

```php
$request = new ParameterBag([
    'query'  => $_GET,
    'body'   => $_POST,
    'server' => $_SERVER,
]);

$request->get('query.page', 1);
$request->get('body.email');
$request->get('server.REMOTE_ADDR');
```

The top-level payload is nested, so multi-mode auto-detection
takes over. Use this when you want a single bag travelling through
your stack instead of three separate ones.

## A reusable request factory

```php
use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;

final class Request
{
    private ParameterBagInterface $query;
    private ParameterBagInterface $body;
    private ParameterBagInterface $headers;

    public function __construct(array $get, array $post, array $server)
    {
        $this->query   = new ParameterBag($get);
        $this->body    = new ParameterBag($post);
        $this->headers = new ParameterBag(
            self::extractHeaders($server),
            ['caseInsensitive' => true],
        );
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function query(): ParameterBagInterface   { return $this->query; }
    public function body(): ParameterBagInterface    { return $this->body; }
    public function headers(): ParameterBagInterface { return $this->headers; }

    /** @param array<string, mixed> $server */
    private static function extractHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'HTTP_')) {
                $name = strtr(substr($key, 5), '_', '-');
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}

$request = Request::fromGlobals();
$request->query()->get('page', 1);
$request->headers()->get('authorization');
```

## Common pitfalls

- **Trusting input directly.** The bag is a transport, not a
  validator or an escaper. Cast / validate at the boundary that consumes the
  value, not at the call site, and escape it for the output context (for
  example `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` before it goes into
  HTML). Never `echo` a raw query or form value into a page.
- **`$_FILES`.** File uploads have an unusual structure
  (`name`, `tmp_name`, `error`, `size`, `type`, each themselves
  arrays for `multiple` inputs). A bag can carry the structure,
  but prefer a dedicated upload handler for parsing.
- **JSON request bodies.** `$_POST` is empty for `application/json`
  requests. Read `php://input`, decode it, and feed the result
  into the bag:
  ```php
  $data = json_decode(file_get_contents('php://input') ?: '[]', true);
  $body = new ParameterBag(is_array($data) ? $data : []);
  ```
