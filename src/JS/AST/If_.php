<?php
/**********************************************************************
 * php2js runs PHP code on the client side using javascript.
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

namespace Lechimp\PHP2JS\JS\AST;

/**
 * Represents an if: if(condition) { statements... }
 */
class If_ extends Node {
    /**
     * @var mixed
     */
    protected $if;

    /**
     * @var mixed
     */
    protected $then;

    /**
     * @var mixed
     */
    protected $else;

    public function __construct($if, $then, $else = null) {
        $this->if = $if;
        $this->then = $then;
        $this->else = $else;
    }

    /**
     * @return Node (specificially the implementing class)
     */
    public function fmap(callable $f) {
        return new If_($f($this->if), $f($this->then), $this->else ? $f($this->else) : null);
    }

    public function if_() {
        return $this->if;
    }

    public function then() {
        return $this->then;
    }

    public function else_() {
        return $this->else;
    }
}
