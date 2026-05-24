<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for cache-coherence bugs (B4, B5, B11 in the v2 plan).
 *
 * The legacy implementation kept an internal cache keyed by lowercased key
 * strings. The cache was populated by {@see ParameterBag::set()} and read
 * by {@see ParameterBag::get()}, but it was NEVER invalidated by
 * {@see ParameterBag::clear()}, by overwriting a parent path, or by
 * removing a parent path in multi-mode. v2 removes the cache entirely;
 * these tests guard against any future re-introduction of the same class
 * of bug.
 */
final class CacheCoherenceTest extends TestCase
{
    public function testClearForgetsPreviouslyReadValue(): void
    {
        $bag = new ParameterBag();
        $bag->set('foo', 'bar');

        // Prime any internal cache.
        self::assertSame('bar', $bag->get('foo'));

        $bag->clear();

        self::assertSame('default', $bag->get('foo', 'default'));
        self::assertSame([], $bag->all());
    }

    public function testOverwritingParentPathInvalidatesChildLookup(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true]);
        $bag->set('database.pass', 'old');

        // Prime any internal cache via the read path.
        self::assertSame('old', $bag->get('database.pass'));

        // Replace the entire parent — child must no longer surface.
        $bag->set('database', ['user' => 'admin']);

        self::assertNull($bag->get('database.pass'));
        self::assertSame('admin', $bag->get('database.user'));
    }

    public function testRemovingParentPathInvalidatesChildLookup(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true]);
        $bag->set('database.pass', 'secret');

        self::assertSame('secret', $bag->get('database.pass'));

        $bag->remove('database');

        self::assertNull($bag->get('database.pass'));
        self::assertFalse($bag->has('database.pass'));
    }
}
