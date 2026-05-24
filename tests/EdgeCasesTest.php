<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use InitPHP\ParameterBag\ParameterBagInterface;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for behaviours that fall between the dedicated test files:
 * close(), __debugInfo(), chainability, empty-string keys, scalar
 * leaves in multi-mode, separator overrides, and a few other small
 * but observable contracts.
 */
final class EdgeCasesTest extends TestCase
{
    public function testCloseResetsStackAndOptionsToDefaults(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root']],
            ['isMulti' => true, 'separator' => '|', 'caseInsensitive' => true]
        );

        $bag->close();

        self::assertSame([], $bag->all());
        // After close() the defaults are flat + dot + case-sensitive,
        // so a key that previously walked into a nested value now
        // becomes a flat literal.
        $bag->set('Db|User', 'verbatim');
        self::assertSame(['Db|User' => 'verbatim'], $bag->all());
    }

    public function testClearKeepsOptions(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true, 'separator' => '/']);
        $bag->set('a/b', 1);

        $bag->clear();

        // Stack is empty …
        self::assertSame([], $bag->all());
        // … but the multi-mode + custom separator are intact.
        $bag->set('x/y', 2);
        self::assertSame(['x' => ['y' => 2]], $bag->all());
    }

    public function testDebugInfoExposesCurrentState(): void
    {
        $bag = new ParameterBag(
            ['db' => ['user' => 'root']],
            ['isMulti' => true, 'separator' => '|']
        );

        self::assertSame(
            [
                'isMulti'         => 'yes',
                'separator'       => '|',
                'caseInsensitive' => 'no',
                'data'            => ['db' => ['user' => 'root']],
            ],
            $bag->__debugInfo()
        );
    }

    public function testSetRemoveAndReplaceAreChainable(): void
    {
        $bag = new ParameterBag();

        $result = $bag
            ->set('a', 1)
            ->set('b', 2)
            ->remove('a')
            ->merge(['c' => 3])
            ->replace(['only' => 'value']);

        self::assertInstanceOf(ParameterBagInterface::class, $result);
        self::assertSame($bag, $result);
        self::assertSame(['only' => 'value'], $bag->all());
    }

    public function testNumericKeysAreSupportedInConstructorData(): void
    {
        $bag = new ParameterBag([0 => 'zero', 1 => 'one', 'two' => 2]);

        self::assertSame('zero', $bag->get('0'));
        self::assertSame('one', $bag->get('1'));
        self::assertSame(2, $bag->get('two'));
        self::assertSame([0 => 'zero', 1 => 'one', 'two' => 2], $bag->all());
    }

    public function testEmptyStringKeyRoundTrips(): void
    {
        $bag = new ParameterBag();
        $bag->set('', 'value-at-empty-key');

        self::assertTrue($bag->has(''));
        self::assertSame('value-at-empty-key', $bag->get(''));
    }

    public function testMultiModeScalarLeafAtNonLeafPathReturnsDefault(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true]);
        $bag->set('user', 'alice'); // scalar at top level

        // Querying through it as if it were nested must NOT probe
        // into the scalar — return the default instead.
        self::assertNull($bag->get('user.name'));
        self::assertFalse($bag->has('user.name'));
        self::assertSame('fallback', $bag->get('user.name', 'fallback'));
    }

    public function testInvalidSeparatorOptionIsIgnored(): void
    {
        // Empty separator must not silently switch the splitter to a
        // no-op; it is rejected and the previous value is kept.
        $bag = new ParameterBag([], ['isMulti' => true, 'separator' => '']);
        $bag->set('a.b', 'c');

        self::assertSame('c', $bag->get('a.b'));
    }

    public function testMultiModeSetOverwritesParentScalarWithNestedValue(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true]);
        $bag->set('db', 'a-string');
        // The path 'db.user' must replace the scalar 'a-string' with
        // a fresh nested array — it should not silently throw or
        // append into the string.
        $bag->set('db.user', 'root');

        self::assertSame(['db' => ['user' => 'root']], $bag->all());
    }

    public function testCaseInsensitiveSetWithArrayValueAlsoLowercasesNestedKeys(): void
    {
        $bag = new ParameterBag([], ['caseInsensitive' => true]);
        $bag->set('Outer', ['Inner' => ['Leaf' => 'v']]);

        self::assertSame(
            ['outer' => ['inner' => ['leaf' => 'v']]],
            $bag->all()
        );
    }

    public function testMergeWithMixedParameterBagAndArrayArguments(): void
    {
        $a = new ParameterBag(['x' => 1]);
        $b = new ParameterBag(['y' => 2]);

        $a->merge($b, ['z' => 3]);

        self::assertSame(['x' => 1, 'y' => 2, 'z' => 3], $a->all());
    }

    public function testGetReturnsNullDefaultWhenNoArgumentSupplied(): void
    {
        $bag = new ParameterBag();

        self::assertNull($bag->get('nope'));
    }

    public function testRemoveWithEmptyVariadicIsNoOp(): void
    {
        $bag = new ParameterBag(['a' => 1, 'b' => 2]);
        $bag->remove();

        self::assertSame(['a' => 1, 'b' => 2], $bag->all());
    }
}
