<?php
/**********************************************************************
 * php.js runs PHP code on the client side using javascript.
 * Copyright (C) 2017 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 **********************************************************************/

declare(strict_types=1);

/**
 * ATTENTION: This is not supposed to work in a PHP-environment.
 * This is just a stub that gets compiled to JS to implement the
 * JS\PhpArray. Do not use it yourself.
 */
class PhpArray { 
    /**
     * @var Array|null from JS
     */
    protected $array;

    /**
     * @var Object|null from JS
     */
    protected $object = null;

    /**
     * @var integer
     */
    protected $max_key = 0;

    /**
     * @var mixed
     */
    protected $key_order;

    /**
     * @var bool
     */
    protected $use_object_mode = false;

    public function __construct() {
        $this->array = new JS_NATIVE_Array();
        $this->key_order = new JS_NATIVE_Array();
    }

    /**
     * Push an element to the array.
     *
     * @param   mixed           $value
     * @return  null
     */
    public function push($value) {
        $this->key_order->push($this->max_key);
        if (!$this->use_object_mode) {
            $this->array->push($value);
            $this->max_key++;
        }
        else {
        }
        return $this;
    }

    /**
     * Get an element from the array.
     *
     * @param   mixed   $key
     * @return  mixed
     */
    public function getItemAt($key) {
        if (!$this->use_object_mode) {
            return $this->array->getItemAt($key);
        }
        else {
            return $this->object->getItemAt("_".$key);
        }
    }

    /**
     * Set an element in the array.
     *
     * @param   mixed   $key
     * @param   mixed   $value
     * @return  mixed
     */
    public function setItemAt($key, $value) {
        if (is_string($key)) {
            if (!$this->use_object_mode) {
                $this->toObjectMode();
            }
        }
        else if (is_int($key)) {
            if ($key > $this->max_key) {
                $this->max_key = $key + 1;
            }
        }
        else {
            throw new \InvalidArgumentException(
                "Unknown key type: ".gettype($key)
            );
        }

        if (!$this->key_order->includes($key)) {
            $this->key_order->push($key);
        }

        if (!$this->use_object_mode) {
            $this->array->setItemAt($key, $value);
        }
        else {
            $this->object->setItemAt("_".$key, $value);
        }
        return $this;
    }

    /**
     * Call the provided function with every element of the array.
     *
     * @param   \Closure    $f
     * @return  null
     */
    public function foreach($foo) {
        if (!$this->use_object_mode) {
            $this->array->forEach($foo);
        }
        else {
            $this->key_order->forEach(function($key) use ($foo) {
                $foo($this->getItemAt($key), $key);
            });
        }
    }

    protected function toObjectMode() {
        $this->use_object_mode = true;
        $this->object = new JS_NATIVE_Object();
        $this->array->forEach(function($v, $k) {
            $this->setItem($k, $v);
        });
    }
}
