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
 * Represents an if: if(condition) { statements... }
 */
class If_ extends Node {
    /**
     * @var mixed
     */
    protected $condition;

    /**
     * @var mixed
     */
    protected $block;

    public function __construct($condition, $block) {
        $this->condition = $condition;
        $this->block = $block;
    }

    /**
     * @return Node (specificially the implementing class)
     */
    public function fmap(callable $f) {
        return new If_($f($this->condition), $f($this->block));
    }

    public function condition() {
        return $this->condition;
    }

    public function block() {
        return $this->block;
    }
}
