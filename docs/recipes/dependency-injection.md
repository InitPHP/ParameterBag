# Recipe: dependency injection

Goal: register a single `ParameterBagInterface` in your container so
services can declare it as a constructor dependency without coupling
to the concrete `ParameterBag` class.

## Service definition

```php
use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;

// PSR-11 container example (pseudocode).
$container->set(ParameterBagInterface::class, function () {
    return new ParameterBag(require __DIR__ . '/../config/app.php');
});
```

## Consumer

```php
final class MailerFactory
{
    public function __construct(
        private readonly ParameterBagInterface $config
    ) {
    }

    public function create(): Mailer
    {
        return new Mailer(
            $this->config->get('mailer.dsn'),
            $this->config->get('mailer.from'),
            $this->config->get('mailer.timeout', 30)
        );
    }
}
```

In PHP 7.4 the syntax is slightly different (no constructor property
promotion, no `readonly`), but the principle is the same: depend on
the **interface**, not the implementation.

## Multi-bag layouts

Some apps want a separate bag per concern (config, request, session).
Register each under its own key:

```php
$container->set('config.bag',  fn () => new ParameterBag(require '...config.php'));
$container->set('request.bag', fn () => new ParameterBag($_REQUEST));
$container->set('session.bag', fn () => new ParameterBag($_SESSION));
```

Consumers then receive whichever bag they were declared against:

```php
public function __construct(
    private readonly ParameterBagInterface $config,
    private readonly ParameterBagInterface $request
) {}
```

## Common pitfalls

- **Mutable shared state**: a single bag injected into many services
  is a shared mutable. Either freeze the contract (`get()` only) or
  reach for an immutable wrapper.
- **Compiling containers**: if your container compiles definitions
  (Symfony's compiled container, php-di's compiled mode, etc.), make
  sure the factory closure can be serialised or replaced by a
  service definition class.
