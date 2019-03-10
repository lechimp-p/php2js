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

namespace Lechimp\PHP_JS\JS\AST;

/**
 * Represents an object construction via new: new ClassName(1,2,3)
 */
class New_ extends Node implements Expression {
    /**
     * @var mixed
     */
    protected $class;

    /**
     * @var array
     */
    protected $parameters;

    public function __construct($class, array $parameters) {
        $this->class = $class;
        $this->parameters = $parameters;
    }

    /**
     * @return Node (specifically the implementing class)
     */
    public function fmap(callable $f) {
        return new New_(
            $f($this->class),
            array_map($f, $this->parameters)
        );
    }

    public function class_() {
        return $this->class;
    }

    public function parameters() {
        return $this->parameters;
    }
}
