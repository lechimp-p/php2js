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
 * Factory for JS-Nodes
 */
class Factory {
    public function literal($value) : Node {
        if (is_string($value)) {
            return new StringLiteral($value);
        }
        if (is_int($value)) {
            return new IntLiteral($value);
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
                throw new \InvalidArgumentException(
                    "Expected Identifiers as parameters"
                );
            }
        }
        return new Function_($parameters, $block);
    }

    public function call(Expression $callee, Expression ...$parameters) : Node {
        return new Call($callee, $parameters);
    }

    public function new_(Expression $class, Expression ...$parameters) : Node {
        return new New_($class, $parameters);
    }

    public function propertyOf(Expression $object, Expression ...$properties) : Node {
        if (count($properties) > 1) {
            $last = array_pop($properties);
            return new PropertyOf($this->propertyOf($object, ...$properties), $last);
        }
        if (count($properties) !== 1) {
            throw new \LogicException(
                "There must be one property."
            );
        }
        return new PropertyOf($object, array_pop($properties));
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

    public function return_(Expression $expr = null) : Node {
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

    public function not_identical(Expression $left, Expression $right) : Node {
        return new BinaryOp("!==", $left, $right);
    }

    public function and_(Expression ...$operands) : Node {
        return $this->chainOperands($operands, function(Expression $l, Expression $r) {
            return new BinaryOp("&&", $l, $r);
        });
    }

    public function or_(Expression ...$operands) : Node {
        return $this->chainOperands($operands, function(Expression $l, Expression $r) {
            return new BinaryOp("||", $l, $r);
        });
    }

    public function add(Expression $left, Expression $right) : Node {
        return new BinaryOp("+", $left, $right);
    }

    public function sub(Expression $left, Expression $right) : Node {
        return new BinaryOp("-", $left, $right);
    }

    public function mul(Expression $left, Expression $right) : Node {
        return new BinaryOp("*", $left, $right);
    }

    public function div(Expression $left, Expression $right) : Node {
        return new BinaryOp("/", $left, $right);
    }

    public function mod(Expression $left, Expression $right) : Node {
        return new BinaryOp("%", $left, $right);
    }

    public function pow(Expression $left, Expression $right) : Node {
        return new BinaryOp("**", $left, $right);
    }

    public function bitAnd(Expression $left, Expression $right) : Node {
        return new BinaryOp("&", $left, $right);
    }

    public function bitOr(Expression $left, Expression $right) : Node {
        return new BinaryOp("|", $left, $right);
    }

    public function bitXor(Expression $left, Expression $right) : Node {
        return new BinaryOp("^", $left, $right);
    }

    public function bitShiftLeft(Expression $left, Expression $right) : Node {
        return new BinaryOp("<<", $left, $right);
    }

    public function bitShiftRight(Expression $left, Expression $right) : Node {
        return new BinaryOp(">>", $left, $right);
    }

    public function greater(Expression $left, Expression $right) : Node {
        return new BinaryOp(">", $left, $right);
    }

    public function in(Expression $left, Expression $right) : Node {
        return new BinaryOp("in", $left, $right);
    }

    public function instanceof_(Expression $left, Expression $right) : Node {
        return new BinaryOp("instanceof", $left, $right);
    }

    public function if_(Expression $if, Block $then, Block $else = null) : Node {
        return new If_($if, $then, $else);
    }

    public function while_(Expression $while, Block $do) : Node {
        return new While_($while, $do);
    }

    public function ternary(Expression $if, Expression $then, Expression $else) : Node {
        return new TernaryOp($if, $then, $else);
    }

    public function not(Expression $other) {
        return new UnaryOp("!", $other);
    }

    public function typeof(Expression $other) {
        return new UnaryOp("typeof ", $other);
    }

    public function throw_(Expression $other) {
        return new UnaryOp("throw ", $other);
    }

    public function try_(Block $try, Identifier $catch_identifier, Block $catch, Block $finally = null) : Node {
        return new Try_($try, $catch_identifier, $catch, $finally);
    }

    protected function chainOperands(array $operands, \Closure $how) {
        if (count($operands) === 0) {
            throw new \LogicException("Expected at least 1 operand.");
        }
        $o = array_shift($operands);
        while(count($operands) > 0) {
            $o = $how($o, array_shift($operands));
        }
        return $o;
    }
}
