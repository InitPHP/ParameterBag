<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Tests that stored values are returned byte-for-byte unchanged.
 *
 * The B6 regression: arrayChangeKeyCaseLower used to trim every string
 * VALUE with the configured separator, on the mistaken assumption that
 * the separator could only appear inside keys. The fix removes the
 * trim entirely — separator semantics apply to keys only.
 */
final class ValueIntegrityTest extends TestCase
{
    public function testNestedStringValuesAreNotTrimmedWithSeparator(): void
    {
        $bag = new ParameterBag(
            ['db' => ['host' => '.example.com.']],
            ['isMulti' => true, 'separator' => '.']
        );

        self::assertSame('.example.com.', $bag->get('db.host'));
    }

    public function testTopLevelStringValuesAreNotTrimmed(): void
    {
        $bag = new ParameterBag(
            ['greeting' => '.hello.'],
            ['isMulti' => true, 'separator' => '.']
        );

        self::assertSame('.hello.', $bag->get('greeting'));
    }

    public function testSetPreservesStringValuesVerbatim(): void
    {
        $bag = new ParameterBag([], ['isMulti' => true, 'separator' => '.']);
        $bag->set('db.host', '|leading-and-trailing|');

        self::assertSame('|leading-and-trailing|', $bag->get('db.host'));
    }

    public function testCustomSeparatorDoesNotAlterValues(): void
    {
        $bag = new ParameterBag(
            ['db' => ['host' => '|example.com|']],
            ['isMulti' => true, 'separator' => '|']
        );

        self::assertSame('|example.com|', $bag->get('db|host'));
    }
}
