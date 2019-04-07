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

namespace Lechimp\PHP2JS\JS\AST;

/**
 * Represents a function : function() {}
 */
class Function_ extends Node implements Expression {
    /**
     * @var Identifier[]
     */
    protected $parameters;

    /**
     * @var Block
     */
    protected $block;

    public function __construct(array $parameters, $block) {
        $this->parameters = $parameters;
        $this->block = $block;
    }

    /**
     * @return Node (specifically the implementing class)
     */
    public function fmap(callable $f) {
        return new Function_(
            array_map($f, $this->parameters),
            $f($this->block)
        );
    }

    public function parameters() {
        return $this->parameters;
    }

    public function block() {
        return $this->block;
    }
}
