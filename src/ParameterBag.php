<?php
/**
 * ParameterBag.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\ParameterBag;

use const COUNT_RECURSIVE;
use const CASE_LOWER;

use function count;
use function strtolower;
use function strpos;
use function is_array;
use function trim;
use function explode;
use function array_shift;
use function implode;
use function array_merge;
use function is_string;
use function is_bool;
use function array_map;
use function array_change_key_case;

final class ParameterBag
{

    /** @var array */
    protected array $PB_Attributes = [];

    /** @var bool */
    protected bool $isMulti = false;

    /** @var string */
    protected string $separator = '.';

    public function __construct(array $data = [], array $options = [])
    {
        if(!empty($data)){
            $this->isMulti = (count($data) === count($data, COUNT_RECURSIVE));
            $this->PB_Attributes = $this->arrayChangeKeyCaseLower($data);
        }
        $this->setOptions($options);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __debugInfo()
    {
        return [
            'isMulti'   => ($this->isMulti ? 'yes' : 'no'),
            'data'      => $this->all(),
        ];
    }

    /**
     * Verileri siler.
     *
     * @return void
     */
    public function close()
    {
        $this->isMulti = false;
        $this->separator = '.';
        unset($this->PB_Attributes);
    }

    /**
     * Parametre çantasında olan tüm veriyi verir.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->PB_Attributes ?? [];
    }

    /**
     * Belirtilen anahtara ait parametrenin varlığını kontrol eder.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $key = strtolower($key);
        if($this->isMulti !== FALSE && strpos($key, $this->separator) !== FALSE){
            return ($this->multiSubParameterGet($key) !== '_InitPHPParameterBagNotFoundValue');
        }
        return isset($this->PB_Attributes[$key]);
    }

    /**
     * Belirtilen anahtarın değerini döndürür.
     *
     * @param string $key
     * @param mixed $default
     * @return array|mixed|string|null
     */
    public function get(string $key, $default = null)
    {
        $key = strtolower($key);
        if($this->isMulti !== FALSE && strpos($key, $this->separator) !== FALSE){
            $value = $this->multiSubParameterGet($key);
            return ($value !== '_InitPHPParameterBagNotFoundValue') ? $value : $default;
        }
        return $this->PB_Attributes[$key] ?? $default;
    }

    /**
     * Bir parametre ekler ya da değerini değiştirir.
     *
     * @param string $key
     * @param string|int|float|bool $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $key = strtolower($key);
        if($this->isMulti !== FALSE){
            if(is_array($value)){
                $value = $this->arrayChangeKeyCaseLower($value);
            }
            $key = trim($key, $this->separator);
            if(strpos($key, $this->separator) !== FALSE){
                $keys = explode($this->separator, $key);
                $id = $keys[0];
                array_shift($keys);
                $this->PB_Attributes[$id] = $this->multiSubParameterSet(implode($this->separator, $keys), $value, ($this->PB_Attributes[$id] ?? []));
                return $this;
            }
        }
        $this->PB_Attributes[$key] = $value;
        return $this;
    }

    /**
     * Belirtilen parametreyi kaldırır/siler.
     *
     * @param string $key
     * @return $this
     */
    public function remove(string $key): self
    {
        $key = strtolower($key);
        if($this->isMulti !== FALSE && strpos($key, $this->separator) !== FALSE){
            $keys = explode($this->separator, $key);
            $id = $keys[0];
            array_shift($keys);
            $this->PB_Attributes[$id] = $this->multiSubParameterRemove(implode($this->separator, $keys), ($this->PB_Attributes[$id] ?? []));
            return $this;
        }
        if(isset($this->PB_Attributes[$key])){
            unset($this->PB_Attributes[$key]);
        }
        return $this;
    }

    /**
     * Bir diziyi ya da ParameterBag nesnesinin içeriği parametre çantasıyla birleştirir.
     *
     * @param array|ParameterBag $merge
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function merge($merge): self
    {
        if($merge instanceof ParameterBag){
            $merge = $merge->all();
        }
        if(!is_array($merge)){
            throw new \InvalidArgumentException('Only an array or a ParameterBag object can be combined.');
        }
        $merge = $this->arrayChangeKeyCaseLower($merge);
        if(empty($this->PB_Attributes)){
            $this->PB_Attributes = $merge;
            return $this;
        }
        $this->PB_Attributes = array_merge($this->PB_Attributes, $merge);
        return $this;
    }

    private function setOptions(array $options): void
    {
        if(empty($options)){
            return;
        }
        if(isset($options['isMulti']) && is_bool($options['isMulti'])){
            $this->isMulti = $options['isMulti'];
        }
        if(isset($options['separator']) && is_string($options['separator'])){
            $this->separator = $options['separator'];
        }
    }

    private function arrayChangeKeyCaseLower(array $array): array
    {
        return array_map(function($row) {
            if(is_array($row)){
                $row = $this->arrayChangeKeyCaseLower($row);
            }
            return $row;
        }, array_change_key_case($array, CASE_LOWER));
    }

    private function multiSubParameterGet(string $key)
    {
        $keys = explode($this->separator, $key);
        $res = $this->PB_Attributes ?? [];
        foreach ($keys as $key) {
            if(!isset($res[$key])){
                return '_InitPHPParameterBagNotFoundValue';
            }
            $res = $res[$key];
        }
        return $res;
    }

    private function multiSubParameterSet($key, $value, $parameters): array
    {
        if(strpos($key, $this->separator) !== FALSE){
            $keys = explode($this->separator, $key);
            $id = $keys[0];
            array_shift($keys);
            $parameters[$id] = $this->multiSubParameterSet(implode($this->separator, $keys), $value, ($parameters[$id] ?? []));
            return $parameters;
        }
        $parameters[$key] = $value;
        return $parameters;
    }

    private function multiSubParameterRemove($key, $parameters): array
    {
        if(strpos($key, $this->separator) !== FALSE){
            $keys = explode($this->separator, $key);
            $id = $keys[0];
            array_shift($keys);
            $parameters[$id] = $this->multiSubParameterRemove(implode($this->separator, $keys), ($parameters[$id] ?? []));
            return $parameters;
        }
        if(isset($parameters[$key])){
            unset($parameters[$key]);
        }
        return $parameters;
    }

}
