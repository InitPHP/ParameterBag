# Recipe: Dependency Injection

**Goal:** register a single `ParameterBagInterface` in your container
so services can declare it as a constructor dependency without
coupling to the concrete `ParameterBag` class.

## Why the interface?

`ParameterBagInterface` is the contract that every consumer should
declare. Depending on the interface (not the implementation) lets
you:

- Substitute a read-only decorator after boot without touching
  consumers.
- Inject a fake bag in unit tests (an in-memory implementation
  backed by an array).
- Switch to a different concrete bag (a logged variant, a
  metric-emitting variant) without rewriting the consumers.

## PSR-11 container

```php
use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;

// PSR-11 container example (pseudocode — adapt to your container's API).
$container->set(ParameterBagInterface::class, function () {
    return new ParameterBag(require __DIR__ . '/../config/app.php');
});
```

Consumers depend on the interface:

```php
final class MailerFactory
{
    public function __construct(
        private readonly ParameterBagInterface $config
    ) {}

    public function create(): Mailer
    {
        return new Mailer(
            $this->config->get('mailer.dsn'),
            $this->config->get('mailer.from'),
            $this->config->get('mailer.timeout', 30),
        );
    }
}
```

## Multi-bag layouts

Some applications want a separate bag per concern (config,
request, session) so each can be injected independently. Register
each under its own service key:

```php
$container->set('bag.config',  fn () => new ParameterBag(require __DIR__ . '/../config/app.php'));
$container->set('bag.request', fn () => new ParameterBag($_REQUEST));
$container->set('bag.session', fn () => new ParameterBag($_SESSION));
```

Then either alias one of them to `ParameterBagInterface::class` (the
"default" bag), or autowire by service key in your framework.

## Symfony service definition

```yaml
# config/services.yaml
services:
    InitPHP\ParameterBag\ParameterBagInterface:
        class: InitPHP\ParameterBag\ParameterBag
        arguments:
            - '%kernel.project_dir%/config/app.php'

    InitPHP\ParameterBag\ParameterBag:
        alias: InitPHP\ParameterBag\ParameterBagInterface
```

For a `require`-based factory, use a factory service instead:

```yaml
services:
    config_bag.factory:
        class: App\Config\ConfigBagFactory

    InitPHP\ParameterBag\ParameterBagInterface:
        factory: ['@config_bag.factory', 'create']
```

## Laravel service container

```php
// app/Providers/AppServiceProvider.php

use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;

public function register(): void
{
    $this->app->singleton(ParameterBagInterface::class, function ($app) {
        return new ParameterBag(
            $app->make('config')->all(),
        );
    });
}
```

A consumer can now type-hint `ParameterBagInterface` and have it
resolved automatically:

```php
public function __construct(ParameterBagInterface $config) { /* ... */ }
```

## PHP-DI (autowired)

```php
use DI\ContainerBuilder;
use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    ParameterBagInterface::class => function () {
        return new ParameterBag(require __DIR__ . '/../config/app.php');
    },
]);
```

## Testing with a fake bag

Because the contract is small, an in-memory test double is
straightforward. The library already gives you one — the concrete
`ParameterBag` itself, fed test data:

```php
final class MailerFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testItReadsTheTimeout(): void
    {
        $config = new ParameterBag([
            'mailer' => ['dsn' => 'smtp://x', 'from' => 'a@b', 'timeout' => 5],
        ]);

        $mailer = (new MailerFactory($config))->create();

        self::assertSame(5, $mailer->timeout());
    }
}
```

For tests where you want to assert specific calls, write a small
spy that implements `ParameterBagInterface` and records arguments;
or wrap the real bag in a logging decorator (see
[Extending → Decorator alternative](Extending.md#decorator-alternative)).

## Common pitfalls

- **Mutable shared state.** A single bag injected into many
  services is shared mutable state. Either freeze the contract
  (read-only decorator after boot) or document that consumers must
  not call `set()` / `remove()`.
- **Compiled containers.** If your container compiles definitions
  (Symfony's compiled container, PHP-DI's compiled mode, etc.),
  the factory closure must be serialisable or replaceable with a
  service-definition class. A trivial factory class fixes both.
- **Picking the wrong type-hint.** Type-hint
  `ParameterBagInterface` in consumers, not `ParameterBag`. The
  concrete class should appear in service definitions and factories
  only.
