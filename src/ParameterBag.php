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
use stdClass;

use function array_change_key_case;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_replace_recursive;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;
use function strpos;
use function strtolower;
use function trim;

use const CASE_LOWER;
use const COUNT_RECURSIVE;

/**
 * Default {@see ParameterBagInterface} implementation.
 *
 * Stores parameters in a single array. When `isMulti` is enabled,
 * dotted keys (e.g. `database.user`) are interpreted as paths into a
 * nested associative array; in flat mode the dot is part of the key.
 *
 * The class is intentionally open to extension — protected hooks are
 * documented as safe override points:
 *
 *  - {@see self::getKey()}      — change how caller-supplied keys are
 *                                 normalized (case folding, trimming).
 *  - {@see self::setOptions()}  — react to additional options if the
 *                                 subclass introduces them.
 *
 * @see ParameterBagInterface
 */
class ParameterBag implements ParameterBagInterface
{
    /**
     * The complete set of options recognised by {@see self::setOptions()}.
     * Any other key in the array supplied to the constructor will be
     * rejected with {@see ParameterBagInvalidArgumentException}.
     *
     * @var array<int, string>
     */
    private const KNOWN_OPTIONS = ['isMulti', 'separator', 'caseInsensitive'];

    /**
     * Sentinel returned by {@see self::multiSubParameterGet()} when a
     * dotted path cannot be resolved. Using a private static object
     * (rather than a magic string or null) guarantees the value can
     * never collide with anything a caller might legitimately store.
     *
     * @var stdClass|null
     */
    private static ?stdClass $notFound = null;

    /**
     * The underlying flat or nested storage.
     *
     * @var array<array-key, mixed>
     */
    private array $stack = [];

    /**
     * Whether dotted keys are interpreted as paths into a nested array.
     */
    private bool $isMulti = false;

    /**
     * Delimiter used to split dotted keys when {@see self::$isMulti}
     * is enabled.
     *
     * @var non-empty-string
     */
    private string $separator = '.';

    /**
     * When true every key (constructor payload, get/set/has/remove
     * arguments, merge() input) is folded to lower-case before being
     * stored or compared. Defaults to false in v2; pass
     * `['caseInsensitive' => true]` to the constructor to restore the
     * legacy (v1) behaviour.
     */
    private bool $caseInsensitive = false;

    /**
     * @param array<array-key, mixed> $data    Initial stack. When the
     *                                         `isMulti` option is not
     *                                         supplied explicitly, this
     *                                         is also used to auto-
     *                                         detect nesting.
     * @param array<string, mixed>    $options Recognised keys:
     *                                         - `isMulti`         (bool, default auto)
     *                                         - `separator`       (non-empty string, default ".")
     *                                         - `caseInsensitive` (bool, default false)
     *
     * @throws ParameterBagInvalidArgumentException If $options contains
     *         any unrecognised key.
     */
    public function __construct(array $data = [], array $options = [])
    {
        if (self::$notFound === null) {
            self::$notFound = new stdClass();
        }
        // Options must be applied BEFORE normalizing $data because the
        // normalizer reads $this->isMulti / $this->separator. Auto-
        // detection of isMulti only runs when the caller has not
        // supplied an explicit boolean.
        if (!isset($options['isMulti']) || !is_bool($options['isMulti'])) {
            if (!empty($data)) {
                $options['isMulti'] = (count($data) !== count($data, COUNT_RECURSIVE));
            }
        }
        $this->setOptions($options);
        if (!empty($data)) {
            $this->stack = $this->normalizeKeys($data);
        }
    }

    /**
     * @return array{isMulti: string, separator: string, caseInsensitive: string, data: array<array-key, mixed>}
     */
    public function __debugInfo(): array
    {
        return [
            'isMulti'         => $this->isMulti ? 'yes' : 'no',
            'separator'       => $this->separator,
            'caseInsensitive' => $this->caseInsensitive ? 'yes' : 'no',
            'data'            => $this->all(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->clear();
        $this->isMulti = false;
        $this->separator = '.';
        $this->caseInsensitive = false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->stack = [];
    }

    /**
     * @inheritDoc
     *
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        return $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return $this->stack === [];
    }

    /**
     * @inheritDoc
     *
     * @return array<int, array-key>
     */
    public function keys(): array
    {
        return array_keys($this->stack);
    }

    /**
     * @inheritDoc
     *
     * @return array<int, mixed>
     */
    public function values(): array
    {
        return array_values($this->stack);
    }

    /**
     * @inheritDoc
     *
     * @param array<array-key, mixed> $data
     */
    public function replace(array $data): ParameterBagInterface
    {
        $this->stack = $this->normalizeKeys($data);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $key = $this->getKey($key);
        if ($this->isMulti && strpos($key, $this->separator) !== false) {
            return $this->multiSubParameterGet($key) !== self::$notFound;
        }

        return array_key_exists($key, $this->stack);
    }

    /**
     * @inheritDoc
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $key = $this->getKey($key);
        if ($this->isMulti && strpos($key, $this->separator) !== false) {
            $value = $this->multiSubParameterGet($key);

            return $value !== self::$notFound ? $value : $default;
        }

        return array_key_exists($key, $this->stack) ? $this->stack[$key] : $default;
    }

    /**
     * @inheritDoc
     *
     * @param mixed $value
     */
    public function set(string $key, $value): ParameterBagInterface
    {
        $key = $this->getKey($key);
        if (is_array($value)) {
            $value = $this->normalizeKeys($value);
        }
        if ($this->isMulti && strpos($key, $this->separator) !== false) {
            $split = explode($this->separator, $key);
            $id = $split[0];
            array_shift($split);
            $this->stack[$id] = $this->multiSubParameterSet(
                implode($this->separator, $split),
                $value,
                $this->arrayOrEmpty($this->stack[$id] ?? null)
            );

            return $this;
        }
        $this->stack[$key] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function remove(string ...$keys): ParameterBagInterface
    {
        foreach ($keys as $key) {
            $key = $this->getKey($key);
            if ($this->isMulti && strpos($key, $this->separator) !== false) {
                $split = explode($this->separator, $key);
                $id = $split[0];
                array_shift($split);
                $this->stack[$id] = $this->multiSubParameterRemove(
                    implode($this->separator, $split),
                    $this->arrayOrEmpty($this->stack[$id] ?? null)
                );
                continue;
            }
            if (array_key_exists($key, $this->stack)) {
                unset($this->stack[$key]);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @param array<array-key, mixed>|ParameterBagInterface ...$merge
     */
    public function merge(...$merge): ParameterBagInterface
    {
        $normalized = [];
        foreach ($merge as $data) {
            if ($data instanceof ParameterBagInterface) {
                $data = $data->all();
            }
            if (!is_array($data)) {
                throw new ParameterBagInvalidArgumentException(
                    'Only an array or a ParameterBag object can be combined.'
                );
            }
            if (empty($data)) {
                continue;
            }
            $normalized[] = $this->normalizeKeys($data);
        }
        if ($normalized === []) {
            return $this;
        }
        $this->stack = $this->isMulti
            ? array_replace_recursive($this->stack, ...$normalized)
            : array_merge($this->stack, ...$normalized);

        return $this;
    }

    /**
     * {@see \Countable::count()} — returns the number of TOP-LEVEL
     * entries in the bag (nested arrays are not unwound).
     */
    public function count(): int
    {
        return count($this->stack);
    }

    /**
     * {@see \IteratorAggregate::getIterator()} — yields top-level
     * entries in insertion order.
     *
     * @return \ArrayIterator<array-key, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->stack);
    }

    /**
     * {@see \ArrayAccess::offsetExists()} — delegates to {@see self::has()}.
     *
     * @param array-key $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->has((string) $offset);
    }

    /**
     * {@see \ArrayAccess::offsetGet()} — delegates to {@see self::get()}.
     *
     * @param array-key $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get((string) $offset);
    }

    /**
     * {@see \ArrayAccess::offsetSet()} — delegates to {@see self::set()}.
     * Appending without a key ($bag[] = $v) is rejected because a bag
     * is a string-keyed structure, not a list.
     *
     * @param array-key|null $offset
     * @param mixed          $value
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new ParameterBagInvalidArgumentException(
                'ParameterBag is a string-keyed structure; assignment without a key is not supported.'
            );
        }
        $this->set((string) $offset, $value);
    }

    /**
     * {@see \ArrayAccess::offsetUnset()} — delegates to {@see self::remove()}.
     *
     * @param array-key $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove((string) $offset);
    }

    /**
     * Normalize a caller-supplied key. Override to change the case or
     * trim policy in a subclass.
     */
    protected function getKey(string $key): string
    {
        if ($this->caseInsensitive) {
            $key = strtolower($key);
        }
        if ($this->isMulti) {
            $key = trim($key, $this->separator);
        }

        return $key;
    }

    /**
     * Apply recognised options from $options to this instance.
     *
     * @param array<string, mixed> $options
     */
    protected function setOptions(array $options): void
    {
        if (empty($options)) {
            return;
        }
        $unknown = array_diff(array_keys($options), self::KNOWN_OPTIONS);
        if ($unknown !== []) {
            throw new ParameterBagInvalidArgumentException(sprintf(
                'Unknown ParameterBag option(s): %s. Known options: %s.',
                implode(', ', $unknown),
                implode(', ', self::KNOWN_OPTIONS)
            ));
        }
        if (isset($options['isMulti']) && is_bool($options['isMulti'])) {
            $this->isMulti = $options['isMulti'];
        }
        if (isset($options['separator']) && is_string($options['separator']) && $options['separator'] !== '') {
            $this->separator = $options['separator'];
        }
        if (isset($options['caseInsensitive']) && is_bool($options['caseInsensitive'])) {
            $this->caseInsensitive = $options['caseInsensitive'];
        }
    }

    /**
     * Return $value if it is already an array, otherwise an empty
     * array. Used when descending into a nested path so that a
     * scalar leaf at a non-leaf position is silently replaced by a
     * fresh subtree (rather than raising a TypeError).
     *
     * @param mixed $value
     *
     * @return array<array-key, mixed>
     */
    private function arrayOrEmpty($value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * Normalize $array for storage. In case-insensitive mode every
     * string key is folded to lower-case (recursively). In the default
     * case-sensitive mode the input is returned unchanged. Values are
     * never modified.
     *
     * @param array<array-key, mixed> $array
     * @return array<array-key, mixed>
     */
    private function normalizeKeys(array $array): array
    {
        if (!$this->caseInsensitive) {
            return $array;
        }

        return array_map(function ($row) {
            if (is_array($row)) {
                $row = $this->normalizeKeys($row);
            }

            return $row;
        }, array_change_key_case($array, CASE_LOWER));
    }

    /**
     * Resolve a dotted $key against $this->stack. Returns
     * {@see self::$notFound} (the sentinel) when any segment is
     * missing or a scalar leaf is reached before the final segment.
     *
     * @return mixed
     */
    private function multiSubParameterGet(string $key)
    {
        $res = $this->stack;
        foreach (explode($this->separator, $key) as $segment) {
            if (!is_array($res) || !array_key_exists($segment, $res)) {
                return self::$notFound;
            }
            $res = $res[$segment];
        }

        return $res;
    }

    /**
     * Recursively assign $value at the dotted $key inside $parameters
     * and return the rebuilt subtree.
     *
     * @param mixed                   $value
     * @param array<array-key, mixed> $parameters
     * @return array<array-key, mixed>
     */
    private function multiSubParameterSet(string $key, $value, array $parameters): array
    {
        if (strpos($key, $this->separator) !== false) {
            $keys = explode($this->separator, $key);
            $id = $keys[0];
            array_shift($keys);
            $parameters[$id] = $this->multiSubParameterSet(
                implode($this->separator, $keys),
                $value,
                $this->arrayOrEmpty($parameters[$id] ?? null)
            );

            return $parameters;
        }
        $parameters[$key] = $value;

        return $parameters;
    }

    /**
     * Recursively remove the dotted $key from $parameters and return
     * the rebuilt subtree.
     *
     * @param array<array-key, mixed> $parameters
     * @return array<array-key, mixed>
     */
    private function multiSubParameterRemove(string $key, array $parameters): array
    {
        if (strpos($key, $this->separator) !== false) {
            $keys = explode($this->separator, $key);
            $id = $keys[0];
            array_shift($keys);
            $parameters[$id] = $this->multiSubParameterRemove(
                implode($this->separator, $keys),
                $this->arrayOrEmpty($parameters[$id] ?? null)
            );

            return $parameters;
        }
        if (array_key_exists($key, $parameters)) {
            unset($parameters[$key]);
        }

        return $parameters;
    }
}
