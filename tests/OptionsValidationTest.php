<?php

declare(strict_types=1);

namespace InitPHP\ParameterBag\Tests;

use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;
use InitPHP\ParameterBag\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Tests for strict validation of the constructor's $options payload.
 *
 * Silently swallowing unknown option keys (the legacy behaviour) hides
 * typos — `'is_multi' => true` would not toggle anything but would
 * also not warn. v2 rejects every key it does not recognise.
 */
final class OptionsValidationTest extends TestCase
{
    public function testKnownOptionsAreAccepted(): void
    {
        $bag = new ParameterBag([], [
            'isMulti'         => true,
            'separator'       => '|',
            'caseInsensitive' => false,
        ]);

        $bag->set('a|b', 'c');
        self::assertSame('c', $bag->get('a|b'));
    }

    public function testUnknownOptionKeyThrows(): void
    {
        $this->expectException(ParameterBagInvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is_multi/');

        new ParameterBag([], ['is_multi' => true]);
    }

    public function testMisspelledKnownOptionStillThrows(): void
    {
        $this->expectException(ParameterBagInvalidArgumentException::class);

        new ParameterBag([], ['seperator' => '|']);
    }

    public function testEmptyOptionsArrayIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();

        new ParameterBag([], []);
    }
}
