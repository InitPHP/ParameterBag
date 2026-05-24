<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test that verifies the test/CI scaffolding is wired up and that
 * autoloading resolves the package classes correctly.
 */
final class InfrastructureTest extends TestCase
{
    public function testPackageClassesAreAutoloaded(): void
    {
        $bag = new ParameterBag();

        self::assertInstanceOf(ParameterBagInterface::class, $bag);
        self::assertSame([], $bag->all());
    }
}
