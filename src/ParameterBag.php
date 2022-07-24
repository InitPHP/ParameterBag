<?php
/**
 * ParameterBag.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    1.1
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\ParameterBag;

use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;

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

class ParameterBag implements ParameterBagInterface
{

    /** @var array */
    private $_PBStack = [];

    private $_PBOptions = [
        'isMulti'       => false,
        'separator'     => '.',
    ];

    public function __construct(array $data = [], array $options = [])
    {
        if(!empty($data)){
            if(!isset($options['isMulti']) || !is_bool($options['isMulti'])){
                $this->_PBOptions['isMulti'] = (count($data) === count($data, COUNT_RECURSIVE));
            }
            $this->_PBStack = $this->arrayChangeKeyCaseLower($data);
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
            'isMulti'   => ($this->_PBOptions['isMulti'] ? 'yes' : 'no'),
            'separator' => $this->_PBOptions['separator'],
            'data'      => $this->all(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->clear();
        $this->_PBOptions = [
            'isMulti'       => false,
            'separator'     => '.'
        ];
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->_PBStack = [];
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->_PBStack ?? [];
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $key = $this->getKey($key);
        if($this->_PBOptions['isMulti'] && strpos($key, $this->_PBOptions['separator']) !== FALSE){
            return ($this->multiSubParameterGet($key) !== '__InitPHPP@r@m£t£rB@gN0tF0undV@lu€__');
        }
        return isset($this->_PBStack[$key]);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        $key = $this->getKey($key);
        if($this->_PBOptions['isMulti'] !== FALSE && strpos($key, $this->_PBOptions['separator']) !== FALSE){
            $value = $this->multiSubParameterGet($key);
            return ($value !== '__InitPHPP@r@m£t£rB@gN0tF0undV@lu€__') ? $value : $default;
        }
        return $this->_PBStack[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value): ParameterBagInterface
    {
        $key = $this->getKey($key);
        if(is_array($value)){
            $value = $this->arrayChangeKeyCaseLower($value);
        }
        if($this->_PBOptions['isMulti'] !== FALSE && strpos($key, $this->_PBOptions['separator']) !== FALSE){
            $split = explode($this->_PBOptions['separator'], $key);
            $id = $split[0];
            array_shift($split);
            $this->_PBStack[$id] = $this->multiSubParameterSet(implode($this->_PBOptions['separator'], $split), $value, ($this->_PBStack[$id] ?? []));
            return $this;
        }
        $this->_PBStack[$key] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function remove(string ...$keys): ParameterBagInterface
    {
        foreach ($keys as $key) {
            $key = $this->getKey($key);
            if($this->_PBOptions['isMulti'] !== FALSE && strpos($key, $this->_PBOptions['separator']) !== FALSE){
                $split = explode($this->_PBOptions['separator'], $key);
                $id = $keys[0];
                array_shift($split);
                $this->_PBStack[$id] = $this->multiSubParameterRemove(implode($this->_PBOptions['separator'], $split), ($this->_PBStack[$id] ?? []));
                continue;
            }
            if(isset($this->_PBStack[$key])){
                unset($this->_PBStack[$key]);
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function merge(...$merge): ParameterBagInterface
    {
        foreach ($merge as &$data) {
            if($data instanceof ParameterBagInterface){
                $data = $data->all();
            }
            if(!is_array($data)){
                throw new ParameterBagInvalidArgumentException('Only an array or a ParameterBag object can be combined.');
            }
            if(empty($data)){
                continue;
            }
            $data = $this->arrayChangeKeyCaseLower($data);
        }
        $this->_PBStack = array_merge($this->_PBStack, ...$merge);
        return $this;
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getKey(string $key): string
    {
        $key = strtolower($key);
        if($this->_PBOptions['isMulti'] !== FALSE){
            $key = trim($key, $this->_PBOptions['separator']);
        }
        return $key;
    }

    /**
     * Sınıf/Nesne seçeneklerini tanımlar.
     *
     * @param array $options
     * @return void
     */
    protected function setOptions(array $options): void
    {
        if(empty($options)){
            return;
        }
        if(isset($options['isMulti']) && is_bool($options['isMulti'])){
            $this->_PBOptions['isMulti'] = $options['isMulti'];
        }
        if(isset($options['separator']) && is_string($options['separator']) && !empty($options['separator'])){
            $this->_PBOptions['separator'] = $options['separator'];
        }
    }

    /**
     * İlişkisel bir dizinin anahtarlarını küçük karakterli haline çevirerek geriye döndürür.
     *
     * @param array $array
     * @return array
     */
    private function arrayChangeKeyCaseLower(array $array): array
    {
        return array_map(function($row) {
            if(is_array($row)){
                $row = $this->arrayChangeKeyCaseLower($row);
            }
            if($this->_PBOptions['isMulti'] !== FALSE){
                $row = trim($row, $this->_PBOptions['separator']);
            }
            return $row;
        }, array_change_key_case($array, CASE_LOWER));
    }

    /**
     * Çoklu parametre çantası kullanımında belirtilen ayırıcı/separator ile ilgili alt elemanın değerini geririr.
     *
     * @param string $key
     * @return array|mixed|string
     */
    private function multiSubParameterGet(string $key)
    {
        $keys = explode($this->_PBOptions['separator'], $key);
        $res = $this->_PBStack ?? [];
        foreach ($keys as $key) {
            if(!isset($res[$key])){
                return '__InitPHPP@r@m£t£rB@gN0tF0undV@lu€__';
            }
            $res = $res[$key];
        }
        return $res;
    }

    /**
     * Çoklu parametre çantası kullanımında belirtilen ayırıcı/separator ile ilgili anahtara ulaşarak değerini tanımlar ve yeni bir oluşturarak oluştruduğu diziyi geri döndürür.
     *
     * @param string|int $key
     * @param mixed $value
     * @param array $parameters
     * @return array
     */
    private function multiSubParameterSet($key, $value, $parameters): array
    {
        if(strpos($key, $this->_PBOptions['separator']) !== FALSE){
            $keys = explode($this->_PBOptions['separator'], $key);
            $id = $keys[0];
            array_shift($keys);
            $parameters[$id] = $this->multiSubParameterSet(implode($this->_PBOptions['separator'], $keys), $value, ($parameters[$id] ?? []));
            return $parameters;
        }
        $parameters[$key] = $value;
        return $parameters;
    }

    /**
     * Çoklu parametre çantası kullanımında belirtilen ayırıcı/separator ile ilgili anahtara ulaşarak kaldırır/siler ve yeni bir oluşturarak oluştruduğu diziyi geri döndürür.
     *
     * @param string|int $key
     * @param array $parameters
     * @return array
     */
    private function multiSubParameterRemove($key, $parameters): array
    {
        if(strpos($key, $this->_PBOptions['separator']) !== FALSE){
            $keys = explode($this->_PBOptions['separator'], $key);
            $id = $keys[0];
            array_shift($keys);
            $parameters[$id] = $this->multiSubParameterRemove(implode($this->_PBOptions['separator'], $keys), ($parameters[$id] ?? []));
            return $parameters;
        }
        if(isset($parameters[$key])){
            unset($parameters[$key]);
        }
        return $parameters;
    }

}
