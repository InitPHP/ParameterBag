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

namespace InitPHP\ParameterBag;

use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;

/**
 * Contract for a string-keyed parameter container that supports both
 * flat (single-level) and nested (dotted-path) access.
 *
 * Implementations also satisfy the standard PHP collection contracts:
 *
 *  - {@see \ArrayAccess}        — `$bag['foo']`, `$bag['foo'] = $v`, etc.
 *  - {@see \Countable}          — top-level entry count via `count($bag)`.
 *  - {@see \IteratorAggregate}  — top-level traversal via `foreach`.
 *
 * Dotted-path semantics (`get('db.user')`, `set('db.pass', '…')`,
 * `has('cache.driver')`, `remove('db.pass')`) are only active when the
 * implementation is configured with `isMulti = true`. In flat mode the
 * dot is part of the key itself.
 *
 * @extends \ArrayAccess<array-key, mixed>
 * @extends \IteratorAggregate<array-key, mixed>
 */
interface ParameterBagInterface extends \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Clear the stack AND restore the implementation's option defaults.
     *
     * Useful when a single instance is being reused across unrelated
     * request lifecycles and the caller wants a guaranteed clean
     * state.
     */
    public function close(): void;

    /**
     * Clear the stack but leave the current options untouched.
     *
     * Equivalent to {@see self::replace([])} except it does not run
     * the data normalizer.
     */
    public function clear(): void;

    /**
     * Return the underlying stack as a plain array, preserving nesting.
     *
     * @return array<array-key, mixed>
     */
    public function all(): array;

    /**
     * True when the stack has no top-level entries.
     */
    public function isEmpty(): bool;

    /**
     * The stack's top-level keys, in insertion order.
     *
     * @return array<int, array-key>
     */
    public function keys(): array;

    /**
     * The stack's top-level values, in insertion order.
     *
     * @return array<int, mixed>
     */
    public function values(): array;

    /**
     * Replace the entire stack with $data. Active options
     * (`isMulti`, `separator`, `caseInsensitive`) are preserved and
     * the incoming payload is normalised through the same code path
     * the constructor uses.
     *
     * @param array<array-key, mixed> $data
     *
     * @return $this
     */
    public function replace(array $data): self;

    /**
     * Whether $key exists in the bag.
     *
     * - In flat mode: tests for a top-level key whose name is exactly
     *   $key (after the optional caseInsensitive fold).
     * - In multi mode: when $key contains the separator, walks the
     *   nested structure; otherwise behaves like the flat mode.
     *
     * A value of `null` stored under $key is still considered present
     * — has() is the authoritative existence check.
     */
    public function has(string $key): bool;

    /**
     * Return the value at $key or $default if the key is absent.
     *
     * Multi-mode behaviour mirrors {@see self::has()}: dotted paths
     * walk the nested structure, single segments behave like flat
     * lookups. A scalar leaf at a non-leaf path is treated as
     * "missing" and yields $default rather than probing inside the
     * scalar.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Assign $value to $key, creating intermediate arrays as needed
     * in multi mode.
     *
     * If $value is an array it is normalised through the same code
     * path the constructor uses (recursive lowercasing when
     * caseInsensitive is on).
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function set(string $key, $value): self;

    /**
     * Remove one or more keys from the bag.
     *
     * Missing keys are a no-op. In multi mode, dotted keys remove a
     * specific nested leaf without touching its siblings.
     *
     * @return $this
     */
    public function remove(string ...$keys): self;

    /**
     * Merge one or more arrays / ParameterBag instances into the
     * stack.
     *
     * - Flat mode: shallow merge (later entries win on key collision).
     * - Multi mode: recursive replace (`array_replace_recursive`);
     *   sibling keys at every depth are preserved.
     *
     * Empty arguments are skipped silently.
     *
     * @param array<array-key, mixed>|ParameterBagInterface ...$merge
     *
     * @return $this
     *
     * @throws ParameterBagInvalidArgumentException If any argument is
     *                                              neither an array nor a {@see ParameterBagInterface}.
     */
    public function merge(...$merge): self;
}
