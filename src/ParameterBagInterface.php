<?php
/**
 * ParameterBagInterface.php
 *
 * This file is part of ParameterBag.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    1.1.1
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\ParameterBag;

use InitPHP\ParameterBag\Exception\ParameterBagInvalidArgumentException;

interface ParameterBagInterface
{

    /**
     * Parametre çantasındaki verileri boşaltır ve sınıf varsayılanlarını geri yükler.
     *
     * @return void
     */
    public function close(): void;

    /**
     * Parametre çantasındaki verileri boşaltır/sıfırlar ama sınıf varsayılanlarını geri yüklemez.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Parametre çantasındaki tüm veriyi bir dizi olarak verir.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Belirtilen parametrenin varlığını kontrol eder.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Belirtilen anahtarın değerini döndürür. Parametre yoksa $default döner.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Parametre çantasına bir parametre tanımlar ya da değerini değiştirir.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, $value): self;

    /**
     * Bir yada daha fazla parametreyi çantadan çıkarır. Belirtilen parametreler bulunamazsa bir değişiklik yapılmaz.
     *
     * @param string ...$keys
     * @return $this
     */
    public function remove(string ...$keys): self;

    /**
     * Belirtilen ilişkisel diziyi ya da ParameterBag nesnesinin içeriğini array_merge() işlevini kullanarak parametre çantasının tuttuğu dizi ile birleştirir.
     *
     * @param array|ParameterBagInterface ...$merge
     * @return $this
     * @throws ParameterBagInvalidArgumentException
     */
    public function merge(...$merge): self;

}
