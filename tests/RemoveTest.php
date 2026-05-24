<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ParameterBag::remove().
 *
 * The B3 regression test pins down the legacy bug where, in multi-mode
 * with multiple keys passed to remove(), the parent slot for EVERY key
 * was derived from $keys[0] (the first variadic argument) instead of
 * the current iteration's exploded segment. The fix uses $split[0].
 */
final class RemoveTest extends TestCase
{
    public function testRemoveSingleFlatKey(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2]);

        $bag->remove('a');

        self::assertFalse($bag->has('a'));
        self::assertTrue($bag->has('b'));
    }

    public function testRemoveMultipleFlatKeys(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2, 'c' => 3]);

        $bag->remove('a', 'c');

        self::assertSame(['b' => 2], $bag->all());
    }

    public function testRemoveMissingKeyIsNoOp(): void
    {
        $bag = new ParameterBag(['a' => 1]);

        $bag->remove('does-not-exist');

        self::assertSame(['a' => 1], $bag->all());
    }

    public function testRemoveSingleNestedPath(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root', 'pass' => 'x']],
            ['isMulti' => true]
        );

        $bag->remove('db.pass');

        self::assertTrue($bag->has('db.user'));
        self::assertFalse($bag->has('db.pass'));
        self::assertSame(['db' => ['user' => 'root']], $bag->all());
    }

    /**
     * Regression test for B3.
     *
     * In the legacy code, the second iteration of the loop computed
     * $id = $keys[0] (== 'db.pass'), causing the wrong top-level slot
     * to be rewritten. With the fix, the second key 'cache.ttl' must
     * cleanly remove only that nested entry.
     */
    public function testRemoveMultipleNestedPathsB3Regression(): void
    {
        $bag = new ParameterBag(
            [
                'db'    => ['user' => 'root', 'pass' => 'x'],
                'cache' => ['ttl' => 60, 'driver' => 'redis'],
            ],
            ['isMulti' => true]
        );

        $bag->remove('db.pass', 'cache.ttl');

        self::assertSame(
            [
                'db'    => ['user' => 'root'],
                'cache' => ['driver' => 'redis'],
            ],
            $bag->all()
        );
    }
}
