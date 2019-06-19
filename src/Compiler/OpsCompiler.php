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

namespace Lechimp\PHP2JS\Compiler;

use PhpParser\Node as PhpNode;

/**
 * Compiles operators to JS
 */
trait OpsCompiler {
    public function compile_Expr_BinaryOp_Identical(PhpNode $n) {
        $js = $this->js_factory;
        return $js->call(
            $js->identifier("__identical"),
            $n->left,
            $n->right
        );
    }

    public function compile_Expr_BinaryOp_Plus(PhpNode $n) {
        return $this->js_factory->add($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Minus(PhpNode $n) {
        return $this->js_factory->sub($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Mul(PhpNode $n) {
        return $this->js_factory->mul($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Div(PhpNode $n) {
        return $this->js_factory->div($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Mod(PhpNode $n) {
        return $this->js_factory->mod($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Pow(PhpNode $n) {
        return $this->js_factory->pow($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_BitwiseAnd(PhpNode $n) {
        return $this->js_factory->bitAnd($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_BitwiseOr(PhpNode $n) {
        return $this->js_factory->bitOr($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_BitwiseXor(PhpNode $n) {
        return $this->js_factory->bitXor($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_ShiftLeft(PhpNode $n) {
        return $this->js_factory->bitShiftLeft($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_ShiftRight(PhpNode $n) {
        return $this->js_factory->bitShiftRight($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Greater(PhpNode $n) {
        return $this->js_factory->greater($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Concat(PhpNode $n) {
        $f = $this->js_factory;
        return $f->call(
            $f->propertyOf($n->left, $f->identifier("concat")),
            $n->right
        );
    }

    public function compile_Expr_BinaryOp_BooleanAnd(PhpNode $n) {
        return $this->js_factory->and_(
            $n->left,
            $n->right
        );
    }

    public function compile_Expr_BinaryOp_BooleanOr(PhpNode $n) {
        return $this->js_factory->or_(
            $n->left,
            $n->right
        );
    }

    public function compile_Expr_BooleanNot(PhpNode $n) {
        return $this->js_factory->not(
            $n->expr
        );
    }

    public function compile_Expr_Ternary(PhpNode $n) {
        return $this->js_factory->ternary(
            $n->cond,
            $n->if,
            $n->else
        );
    }
}

