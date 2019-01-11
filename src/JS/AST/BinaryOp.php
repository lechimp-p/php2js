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
 * Represents some binary operation.
 */
class BinaryOp extends Node implements Expression {
    protected static $valid = ["==="];

    /**
     * @var string
     */
    protected $which;

    /**
     * @var mixed
     */
    protected $left;

    /**
     * @var mixed
     */
    protected $right;

    public function __construct(string $which, $left, $right) {
        if (!in_array($which, self::$valid)) {
            throw new \InvalidArgumentException(
                "'$which' is not a valid binary expression."
            );
        }

        $this->which = $which;
        $this->left = $left;
        $this->right = $right;
    }

    /**
     * @return Node (specificially the implementing class)
     */
    public function fmap(callable $f) {
        return new BinaryOp($this->which, $f($this->left), $f($this->right));
    }

    public function which() {
        return $this->which;
    }

    public function left() {
        return $this->left;
    }

    public function right() {
        return $this->right;
    }
}
