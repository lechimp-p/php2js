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

use Lechimp\PHP2JS\JS;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeTraverser;

class RewriteArrayCode extends NodeVisitorAbstract {
    public function leaveNode(Node $n) {
        if ($n instanceof Node\Expr\ArrayItem) {
            if ($n->byRef) {
                throw new \LogicException("Cannot rewrite array code using byRef.");
            }
            if ($n->key) {
                return new Node\Expr\FuncCall("setItemAt", [$n->key, $n->value]);
            }
            else {
                return new Node\Expr\FuncCall("push", [$n->value]);
            }
        }
        if ($n instanceof Node\Expr\Array_) {
            $array = new Node\Expr\New_(new Node\Name(\PhpArray::class));
            foreach ($n->items as $item) {
                $array = new Node\Expr\MethodCall($array, $item->name, $item->args);
            }
            return $array;
        }
        if ($n instanceof Node\Expr\ArrayDimFetch) {
            return new Node\Expr\MethodCall(
                $n->var,
                new Node\Name("getItemAt"),
                [$n->dim]
            );
        }
        if ($n instanceof Node\Expr\Assign
        &&  $n->var instanceof Node\Expr\MethodCall
        &&  $n->var->name == "getItemAt") {
            return new Node\Expr\MethodCall(
                $n->var->var,
                new Node\Name("setItemAt"),
                [$n->var->args[0], $n->expr]
            );
        }
        if ($n instanceof Node\Stmt\Foreach_) {
            if ($n->byRef) {
                throw new \LogicException(
                    "Cannot compile foreach with by-ref."
                );
            }
            if ($n->keyVar) {
                $function_vars = [$n->valueVar, $n->keyVar];
            }
            else {
                $function_vars = [$n->valueVar];
            }

            $t = new NodeTraverser;
            $t->addVisitor($this->getMarkVarAssignmentsVisitor());

            return new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    $n->expr,
                    new Node\Name("foreach"),
                    [new Node\Expr\Closure([
                        "params" => array_map(function($v) {
                            return new Node\Param($v);
                        }, $function_vars),
                        "stmts" => $t->traverse($n->stmts)
                    ])]
                )
            );
        }
    }

    protected function getMarkVarAssignmentsVisitor() {
        return new class extends NodeVisitorAbstract {
            public function enterNode(Node $n) {
                if ($n instanceof Node\Stmt\Class_
                ||  $n instanceof Node\Stmt\Function_
                ||  $n instanceof Node\Expr\Closure
                ) {
                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                if ($n instanceof Node\Expr\Assign) {
                    $n->setAttribute(Compiler::ATTR_FIRST_VAR_ASSIGNMENT, false);
                }
            }
        };
    }
}
