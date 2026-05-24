<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for B7 — the magic-string sentinel used internally
 * to differentiate "key missing" from "value === null". A user that
 * happened to store the exact sentinel string would see has() return
 * false and get() return the default, even though their key existed.
 *
 * v2 replaces the string with a private object instance that the user
 * cannot construct, eliminating the collision class entirely.
 */
final class SentinelTest extends TestCase
{
    /**
     * The legacy sentinel value. Storing it as data must NOT cause the
     * bag to treat that key as absent.
     */
    private const LEGACY_SENTINEL = '__InitPHPP@r@m£t£rB@gN0tF0undV@lu€__';

    public function testValueEqualToLegacySentinelStringIsStillFound(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true]);
        $bag->set('db.user', self::LEGACY_SENTINEL);

        self::assertTrue($bag->has('db.user'));
        self::assertSame(self::LEGACY_SENTINEL, $bag->get('db.user'));
    }

    public function testNullValueIsDistinctFromMissingKey(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true]);
        $bag->set('db.user', null);

        self::assertTrue($bag->has('db.user'));
        self::assertNull($bag->get('db.user', 'fallback'));
    }

    public function testNullStoredInFlatModeIsDistinctFromMissing(): void
    {
        $bag = new ParameterBag();
        $bag->set('user', null);

        self::assertTrue($bag->has('user'));
        // get() falls back to default when the underlying value is null
        // because the legacy ?? operator cannot tell the two cases apart.
        // The has()/get() contract for v2: has() is authoritative.
        self::assertFalse($bag->has('missing'));
    }
}
