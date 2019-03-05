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
            if (!($s instanceof Statement || $s instanceof Block)) {
                return new Statement($s);
            }
            return $s;
        }, $stmts);
        return new Block($stmts);
    }

    public function function_(array $parameters, Block $block) : Node {
        foreach ($parameters as $p) {
            if (!($p instanceof Identifier)) {
                throw new InvalidArgumentException(
                    "Expected Identifiers as parameters"
                );
            }
        }
        return new Function_($parameters, $block);
    }

    public function call(Expression $callee, Expression ...$parameters) : Node {
        return new Call($callee, $parameters);
    }

    public function propertyOf(Expression $object, Expression $property) : Node {
        return new PropertyOf($object, $property);
    }

    public function assignVar(Identifier $name, Expression $value) : Node {
        return new AssignVar($name, $value);
    }

    public function assign(Expression $expr, Expression $value) : Node {
        return new Assign($expr, $value);
    }

    public function object_(array $fields) : Node {
        return new Object_($fields);
    }

    public function return_(Expression $expr) : Node {
        return new Return_($expr);
    }

    public function undefined() : Node {
        return $this->identifier("undefined");
    }

    public function null_() : Node {
        return $this->identifier("null");
    }

    public function nop() : Node {
        return new Nop();
    }

    public function identical(Expression $left, Expression $right) : Node {
        return new BinaryOp("===", $left, $right);
    }

    public function and_(Expression $left, Expression $right) : Node {
        return new BinaryOp("&&", $left, $right);
    }

    public function or_(Expression $left, Expression $right) : Node {
        return new BinaryOp("||", $left, $right);
    }

    public function if_(Expression $condition, Block $block) : Node {
        return new If_($condition, $block);
    }

    public function ternary(Expression $if, Expression $then, Expression $else) : Node {
        return new TernaryOp($if, $then, $else);
    }

    public function not(Expression $other) {
        return new UnaryOp("!", $other);
    }
}
