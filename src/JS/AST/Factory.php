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
 * Factory for JS-Nodes
 */
class Factory {
    public function literal($value) : Node {
        if (is_string($value)) {
            return new StringLiteral($value);
        }

        throw new \LogicException("Unknown literal type '".gettype($value)."'");
    }

    public function identifier(string $value) : Node {
        return new Identifier($value);
    }

    public function block(Node ...$stmts) : Node {
        $stmts = array_map(function($s) {
            if (!($s instanceof Statement)) {
                return new Statement($s);
            }
            return $s;
        }, $stmts);
        return new Block($stmts);
    }

    public function function_(array $parameters, Block $block) {
        foreach ($parameters as $p) {
            if (!($p instanceof Identifier)) {
                throw new InvalidArgumentException(
                    "Expected Identifiers as parameters"
                );
            }
        }
        return new Function_($parameters, $block);
    }

    public function call(Expression $callee, Expression ...$parameters) {
        return new Call($callee, $parameters);
    }

    public function propertyOf(Expression $object, Expression $property) {
        return new PropertyOf($object, $property);
    }

    public function nop() {
        return new Nop();
    }
}
