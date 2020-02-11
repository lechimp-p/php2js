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
use PhpParser\NodeTraverser;

class RewriteTypeHints extends NodeVisitorAbstract implements FilePass {
    /**
     * @var array
     */
    protected $type_checked_vars_stack = [];

    /**
     * @var array
     */
    protected $type_checked_vars = [];

    public function runsAlone() : bool {
        return true;
    }

    public function enterNode(Node $n) {
        if ($n instanceof Node\Stmt\Class_) {
            if (count($n->implements) > 0) {
                $names = array_map(function($n) { return$n->toLowerString(); }, $n->implements);
                if (in_array(strtolower(JS\Script::class), $names)) {
                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }
            }
        }

        if ($n instanceof Node\Stmt\ClassMethod
        || $n instanceof Node\Expr\Closure) {
            $this->type_checked_vars_stack[] = $this->type_checked_vars;
            $this->type_checked_vars = [];
        }
    }

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Stmt\ClassMethod
        || $n instanceof Node\Expr\Closure) {
            $checks = array_map(function($p) {
                list($type, $var, $might_be_null) = $p;
                $not_instanceof = new Node\Expr\BooleanNot(
                    new Node\Expr\Instanceof_($var, $type)
                );
                $_isset = new Node\Expr\Isset_([$var]);

                if ($might_be_null) {
                    $condition = new Node\Expr\BinaryOp\BooleanAnd(
                        $_isset,
                        $not_instanceof
                    );
                }
                else {
                    $condition = new Node\Expr\BinaryOp\BooleanOr(
                        new Node\Expr\BooleanNot($_isset),
                        $not_instanceof
                    );
                }

                $throw = new Node\Stmt\Throw_(
                    new Node\Expr\New_(
                        new Node\Name(
                            \TypeError::class
                        ),
                        [new Node\Scalar\String_(
                            "Expected ".$type
                        )]
                    )
                );

                return new Node\Stmt\If_(
                    $condition,
                    ["stmts" => [$throw]]
                );
                
            }, $this->type_checked_vars);
            $n->stmts = array_merge($checks, $n->stmts);

            $this->type_checked_vars = array_pop($this->type_checked_vars_stack);
        }

        if ($n instanceof Node\Param) {
            if ($n->type) {
                $this->type_checked_vars[] = [$n->type, $n->var, !is_null($n->default)];
            }
            $n->type = null;
        }
    }
}
