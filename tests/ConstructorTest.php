<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Constructor & isMulti auto-detection tests.
 *
 * The legacy implementation inverted the comparison used to auto-detect
 * whether a payload is nested (B1): it set isMulti=true for flat arrays
 * and isMulti=false for nested arrays. These tests assert the corrected
 * behaviour observable through set()/get()/all().
 */
final class ConstructorTest extends TestCase
{
    public function testFlatArrayIsNotTreatedAsMultiDimensional(): void
    {
        $bag = new ParameterBag(['user' => 'a', 'pass' => 'b']);

        // With a flat payload the bag must operate in flat mode, so a
        // dotted key is stored verbatim rather than split into a nested
        // structure.
        $bag->set('foo.bar', 'baz');

        self::assertSame('baz', $bag->get('foo.bar'));
        self::assertSame(
            ['user' => 'a', 'pass' => 'b', 'foo.bar' => 'baz'],
            $bag->all()
        );
    }

    public function testNestedArrayIsTreatedAsMultiDimensional(): void
    {
        $bag = new ParameterBag([
            'database' => ['user' => 'root', 'pass' => '123'],
        ]);

        self::assertSame('root', $bag->get('database.user'));
        self::assertSame('123', $bag->get('database.pass'));
        self::assertTrue($bag->has('database.user'));
        self::assertFalse($bag->has('database.unknown'));
    }

    public function testExplicitIsMultiTrueOverridesAutoDetection(): void
    {
        $bag = new ParameterBag(
            ['user' => 'a', 'pass' => 'b'],
            ['isMulti' => true]
        );

        $bag->set('database.user', 'root');

        self::assertSame('root', $bag->get('database.user'));
    }

    public function testExplicitIsMultiFalseOverridesAutoDetection(): void
    {
        $bag = new ParameterBag(
            ['database' => ['user' => 'root']],
            ['isMulti' => false]
        );

        // In flat mode 'database.user' is a single key, not a path,
        // so the nested value must NOT be reachable through it.
        self::assertNull($bag->get('database.user'));
        self::assertSame(['user' => 'root'], $bag->get('database'));
    }

    public function testEmptyConstructorDefaultsAreFlatMode(): void
    {
        $bag = new ParameterBag();
        $bag->set('a.b', 'x');

        // Defaults: isMulti=false → dotted key stored verbatim.
        self::assertSame(['a.b' => 'x'], $bag->all());
    }
}
