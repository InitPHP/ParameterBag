<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;
use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ParameterBag::merge().
 *
 * B8 regression: in multi-mode the legacy implementation used a shallow
 * array_merge, so a second merge that touched a nested key wiped any
 * sibling keys the first merge had set. v2 dispatches to
 * array_replace_recursive when isMulti is enabled.
 */
final class MergeTest extends TestCase
{
    public function testFlatMergeOverwritesTopLevelKeys(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2]);
        $bag->merge(['b' => 20, 'c' => 30]);

        self::assertSame(['a' => 1, 'b' => 20, 'c' => 30], $bag->all());
    }

    public function testMergeAcceptsAnotherParameterBag(): void
    {
        $a = new ParameterBag(['x' => 1]);
        $b = new ParameterBag(['y' => 2]);

        $a->merge($b);

        self::assertSame(['x' => 1, 'y' => 2], $a->all());
    }

    public function testMergeWithEmptyArrayIsNoOp(): void
    {
        $bag = new ParameterBag(['a' => 1]);
        $bag->merge([]);

        self::assertSame(['a' => 1], $bag->all());
    }

    public function testMergeRejectsScalarArguments(): void
    {
        $bag = new ParameterBag();

        $this->expectException(ParameterBagInvalidArgumentException::class);
        /** @phpstan-ignore-next-line — intentionally passing wrong type */
        $bag->merge('not an array');
    }

    public function testMergeRejectsObjectsOtherThanParameterBag(): void
    {
        $bag = new ParameterBag();

        $this->expectException(ParameterBagInvalidArgumentException::class);
        /** @phpstan-ignore-next-line — intentionally passing wrong type */
        $bag->merge(new \stdClass());
    }

    /**
     * Regression test for B8: in multi-mode, merging two payloads that
     * share a parent key must preserve siblings on both sides.
     */
    public function testMultiModeMergeIsRecursive(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root']],
            ['isMulti' => true]
        );

        $bag->merge(['db' => ['pass' => 'secret']]);

        self::assertSame(
            ['db' => ['user' => 'root', 'pass' => 'secret']],
            $bag->all()
        );
    }

    public function testMultiModeMergeOverwritesLeafScalars(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root', 'pass' => 'old']],
            ['isMulti' => true]
        );

        $bag->merge(['db' => ['pass' => 'new']]);

        self::assertSame(
            ['db' => ['user' => 'root', 'pass' => 'new']],
            $bag->all()
        );
    }

    public function testMultiModeMergeMultipleArguments(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root']],
            ['isMulti' => true]
        );

        $bag->merge(
            ['db' => ['pass' => 'secret']],
            ['cache' => ['driver' => 'redis']]
        );

        self::assertSame(
            [
                'db'    => ['user' => 'root', 'pass' => 'secret'],
                'cache' => ['driver' => 'redis'],
            ],
            $bag->all()
        );
    }
}
