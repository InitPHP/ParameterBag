<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Tests for v2 case-sensitivity semantics.
 *
 * v1 always lowercased every key (constructor payload, set(), get(),
 * has(), remove()), which was undocumented and surprising. v2 inverts
 * the default — keys are case-sensitive — and offers an opt-in
 * caseInsensitive=true option that restores the legacy behaviour.
 */
final class CaseSensitivityTest extends TestCase
{
    public function testDefaultIsCaseSensitive(): void
    {
        $bag = new ParameterBag();
        $bag->set('User', 'alice');

        self::assertTrue($bag->has('User'));
        self::assertFalse($bag->has('user'));
        self::assertSame('alice', $bag->get('User'));
        self::assertNull($bag->get('user'));
    }

    public function testConstructorDataPreservesKeyCase(): void
    {
        $bag = new ParameterBag([
            'Database' => ['User' => 'root'],
        ]);

        self::assertSame(
            ['Database' => ['User' => 'root']],
            $bag->all()
        );
        self::assertSame('root', $bag->get('Database.User'));
    }

    public function testCaseInsensitiveOptionLowercasesKeysOnSet(): void
    {
        $bag = new ParameterBag([], ['caseInsensitive' => true]);
        $bag->set('User', 'alice');

        self::assertTrue($bag->has('user'));
        self::assertTrue($bag->has('USER'));
        self::assertSame('alice', $bag->get('User'));
        self::assertSame('alice', $bag->get('uSeR'));
    }

    public function testCaseInsensitiveOptionLowercasesConstructorData(): void
    {
        $bag = new ParameterBag(
            ['Database' => ['User' => 'root']],
            ['caseInsensitive' => true]
        );

        self::assertSame(
            ['database' => ['user' => 'root']],
            $bag->all()
        );
        self::assertSame('root', $bag->get('DATABASE.USER'));
    }

    /**
     * B2 regression: when isMulti and a custom separator and caseInsensitive
     * are all supplied via the constructor, options must be applied BEFORE
     * the payload is normalized — otherwise the first pass uses default
     * options and produces a structurally-wrong stack.
     */
    public function testConstructorAppliesOptionsBeforeNormalizingData(): void
    {
        $bag = new ParameterBag(
            ['DB' => ['Host' => 'localhost']],
            ['isMulti' => true, 'separator' => '|', 'caseInsensitive' => true]
        );

        self::assertSame(
            ['db' => ['host' => 'localhost']],
            $bag->all()
        );
        self::assertSame('localhost', $bag->get('DB|Host'));
    }

    public function testRemoveIsCaseSensitiveByDefault(): void
    {
        $bag = new ParameterBag(['User' => 'a', 'user' => 'b']);

        $bag->remove('user');

        self::assertSame(['User' => 'a'], $bag->all());
    }

    public function testCloseResetsCaseInsensitive(): void
    {
        $bag = new ParameterBag([], ['caseInsensitive' => true]);
        $bag->set('Foo', 'bar');
        self::assertSame('bar', $bag->get('foo'));

        $bag->close();
        $bag->set('Foo', 'baz');

        self::assertSame('baz', $bag->get('Foo'));
        self::assertNull($bag->get('foo'));
    }
}
