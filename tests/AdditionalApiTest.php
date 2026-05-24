<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the v2 convenience API: isEmpty(), keys(), values(),
 * replace(). These round out the bag surface and bring it in line
 * with the wider PHP collection ecosystem.
 */
final class AdditionalApiTest extends TestCase
{
    public function testIsEmptyOnFreshBag(): void
    {
        self::assertTrue((new ParameterBag())->isEmpty());
    }

    public function testIsEmptyAfterAdditionAndRemoval(): void
    {
        $bag = new ParameterBag();
        $bag->set('a', 1);
        self::assertFalse($bag->isEmpty());

        $bag->remove('a');
        self::assertTrue($bag->isEmpty());
    }

    public function testKeysReturnsTopLevelKeys(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);

        self::assertSame(['a', 'b', 'c'], $bag->keys());
    }

    public function testKeysOnEmptyBagReturnsEmptyArray(): void
    {
        self::assertSame([], (new ParameterBag())->keys());
    }

    public function testValuesReturnsTopLevelValuesInInsertionOrder(): void
    {
        $bag = new ParameterBag();
        $bag->set('z', 'zzz');
        $bag->set('a', 'aaa');

        self::assertSame(['zzz', 'aaa'], $bag->values());
    }

    public function testReplaceSwapsEntireStack(): void
    {
        $bag = new ParameterBag(['old' => 1, 'kept-only-until-replace' => 2]);
        $bag->replace(['new' => 'value']);

        self::assertSame(['new' => 'value'], $bag->all());
    }

    public function testReplaceHonoursCaseInsensitiveOption(): void
    {
        $bag = new ParameterBag([], ['caseInsensitive' => true]);
        $bag->replace(['Foo' => 'bar']);

        self::assertSame(['foo' => 'bar'], $bag->all());
        self::assertSame('bar', $bag->get('FOO'));
    }

    public function testReplaceHonoursMultiModeNesting(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true]);
        $bag->replace(['db' => ['user' => 'root']]);

        self::assertSame('root', $bag->get('db.user'));
    }

    public function testReplaceReturnsSelfForChaining(): void
    {
        $bag = new ParameterBag();
        $result = $bag->replace(['a' => 1]);

        self::assertInstanceOf(ParameterBagInterface::class, $result);
        self::assertSame($bag, $result);
    }
}
