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

namespace Lechimp\PHP2JS\Compiler\FilePass;

use Lechimp\PHP2JS\JS;
use Lechimp\PHP2JS\Compiler\FilePass;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RewriteOperators extends NodeVisitorAbstract implements FilePass {
    public function runsAlone() : bool {
        return false;
    }

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Expr\AssignOp\Plus) {
            return $this->toAssign($n, Node\Expr\BinaryOp\Plus::class);
        }
        if ($n instanceof Node\Expr\AssignOp\Minus) {
            return $this->toAssign($n, Node\Expr\BinaryOp\Minus::class);
        }
        if ($n instanceof Node\Expr\AssignOp\Mul) {
            return $this->toAssign($n, Node\Expr\BinaryOp\Mul::class);
        }
        if ($n instanceof Node\Expr\AssignOp\Div) {
            return $this->toAssign($n, Node\Expr\BinaryOp\Div::class);
        }
        if ($n instanceof Node\Expr\AssignOp\Mod) {
            return $this->toAssign($n, Node\Expr\BinaryOp\Mod::class);
        }
        if ($n instanceof Node\Expr\AssignOp\BitwiseAnd) {
            return $this->toAssign($n, Node\Expr\BinaryOp\BitwiseAnd::class);
        }
        if ($n instanceof Node\Expr\AssignOp\BitwiseOr) {
            return $this->toAssign($n, Node\Expr\BinaryOp\BitwiseOr::class);
        }
        if ($n instanceof Node\Expr\AssignOp\BitwiseXor) {
            return $this->toAssign($n, Node\Expr\BinaryOp\BitwiseXor::class);
        }
        if ($n instanceof Node\Expr\AssignOp\Pow) {
            return $this->toAssign($n, Node\Expr\BinaryOp\Pow::class);
        }
        if ($n instanceof Node\Expr\AssignOp\ShiftLeft) {
            return $this->toAssign($n, Node\Expr\BinaryOp\ShiftLeft::class);
        }
        if ($n instanceof Node\Expr\AssignOp\ShiftRight) {
            return $this->toAssign($n, Node\Expr\BinaryOp\ShiftRight::class);
        }
        if ($n instanceof Node\Expr\AssignOp\Concat) {
            return $this->toAssign($n, Node\Expr\BinaryOp\Concat::class);
        }
        if ($n instanceof Node\Expr\PostInc) {
            $n->expr = Node\Scalar\LNumber::fromString("1");
            return $this->toAssign($n, Node\Expr\BinaryOp\Plus::class);
        }
    }

    protected function toAssign(Node $n, string $op_class) {
        return new Node\Expr\Assign(
            $n->var,
            new $op_class(
                $n->var,
                $n->expr
            )
        );
    }
}
