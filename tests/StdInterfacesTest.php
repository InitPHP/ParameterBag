<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InitPHP\ParameterBag\ParameterBag;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use Traversable;

/**
 * Tests for the standard PHP interfaces ParameterBag exposes in v2:
 * ArrayAccess (with dotted-path support in multi-mode), Countable
 * (top-level cardinality), IteratorAggregate (top-level traversal).
 */
final class StdInterfacesTest extends TestCase
{
    public function testImplementsExpectedInterfaces(): void
    {
        $bag = new ParameterBag();

        self::assertInstanceOf(ArrayAccess::class, $bag);
        self::assertInstanceOf(Countable::class, $bag);
        self::assertInstanceOf(IteratorAggregate::class, $bag);
    }

    public function testArrayAccessReadDelegatesToGet(): void
    {
        $bag = new ParameterBag(['user' => 'alice']);

        self::assertSame('alice', $bag['user']);
        self::assertNull($bag['missing']);
    }

    public function testArrayAccessWriteDelegatesToSet(): void
    {
        $bag = new ParameterBag();
        $bag['user'] = 'alice';

        self::assertSame('alice', $bag->get('user'));
    }

    public function testArrayAccessExistsDelegatesToHas(): void
    {
        $bag = new ParameterBag(['user' => null]);

        self::assertTrue(isset($bag['user']));        // exists, even though null
        self::assertFalse(isset($bag['missing']));
    }

    public function testArrayAccessUnsetDelegatesToRemove(): void
    {
        $bag = new ParameterBag(['user' => 'alice', 'pass' => 'x']);

        unset($bag['user']);

        self::assertFalse($bag->has('user'));
        self::assertTrue($bag->has('pass'));
    }

    public function testArrayAccessSupportsDottedPathInMultiMode(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root']],
            ['isMulti' => true]
        );

        self::assertSame('root', $bag['db.user']);

        $bag['db.pass'] = 'secret';
        self::assertSame('secret', $bag->get('db.pass'));

        unset($bag['db.pass']);
        self::assertFalse($bag->has('db.pass'));
    }

    public function testArrayAccessSetWithNullOffsetThrows(): void
    {
        $bag = new ParameterBag();

        $this->expectException(\InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException::class);
        $bag[] = 'value';
    }

    public function testCountReturnsTopLevelEntryCount(): void
    {
        $bag = new ParameterBag();
        self::assertCount(0, $bag);

        $bag->set('a', 1);
        $bag->set('b', 2);
        self::assertCount(2, $bag);

        $bag->remove('a');
        self::assertCount(1, $bag);
    }

    public function testCountIgnoresNestedDepth(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root', 'pass' => 'x'], 'cache' => ['ttl' => 60]],
            ['isMulti' => true]
        );

        // count() reports top-level entries only.
        self::assertCount(2, $bag);
    }

    public function testGetIteratorYieldsTopLevelEntries(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);

        $iterator = $bag->getIterator();
        self::assertInstanceOf(Traversable::class, $iterator);

        $collected = iterator_to_array($iterator);
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $collected);
    }

    public function testIterationCanBeRepeated(): void
    {
        $bag = new ParameterBag(['x' => 10, 'y' => 20]);

        $first  = [];
        foreach ($bag as $k => $v) {
            $first[$k] = $v;
        }

        $second = [];
        foreach ($bag as $k => $v) {
            $second[$k] = $v;
        }

        self::assertSame($first, $second);
        self::assertSame(['x' => 10, 'y' => 20], $first);
    }

    public function testIteratorIsArrayIterator(): void
    {
        $bag = new ParameterBag(['a' => 1]);

        self::assertInstanceOf(ArrayIterator::class, $bag->getIterator());
    }
}
