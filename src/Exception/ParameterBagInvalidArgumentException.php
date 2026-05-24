<?php

/**
 * This file is part of the initphp/parameterbag package.
 *
 * (c) Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/InitPHP/ParameterBag
 */

declare(strict_types=1);

namespace InitPHP\ParameterBag\Exception;

/**
 * Thrown when a caller supplies an argument the bag cannot accept:
 *
 *  - an unknown option key in the constructor's `$options` payload;
 *  - a non-array, non-{@see \InitPHP\ParameterBag\ParameterBagInterface}
 *    argument to {@see \InitPHP\ParameterBag\ParameterBagInterface::merge()};
 *  - appending to the bag without a key via ArrayAccess
 *    (`$bag[] = $value`), which is unsupported because the bag is
 *    string-keyed rather than a numeric list.
 *
 * Extends the SPL {@see \InvalidArgumentException} so catch blocks
 * written against either type continue to work.
 */
class ParameterBagInvalidArgumentException extends \InvalidArgumentException
{
}
