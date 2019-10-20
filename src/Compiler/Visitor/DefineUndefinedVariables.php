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

namespace Lechimp\PHP2JS\Compiler\Visitor;

use Lechimp\PHP2JS\Compiler\Compiler;
use Lechimp\PHP2JS\JS;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeTraverser;

class DefineUndefinedVariables extends NodeVisitorAbstract {
    /**
     * @var string[]
     */
    protected $defined = [];

    /**
     * @var string[]
     */
    protected $used = [];

    /**
     * @var string[][]
     */
    protected $stack = [];

    public function beforeTraverse(array $nodes) {
        $this->defined = [];
        $this->used = [];
        $this->stack = [];
    }

    public function enterNode(Node $n) {
        if ($n instanceof Node\Stmt\ClassMethod
        ||  $n instanceof Node\Stmt\Function_
        ||  $n instanceof Node\Expr\Closure
        ) {
            array_push($this->stack, [$this->defined, $this->used]);
            $this->defined = [];
            $this->used = [];
        }

        if ($n instanceof Node\Param
        ||  $n instanceof Node\Expr\ClosureUse
        ||  $n instanceof Node\Expr\Assign) {
            if (!($n->var instanceof Node\Expr\PropertyFetch)
            &&  !($n->var instanceof Node\Expr\ArrayDimFetch)) {
                $this->defined[] = (string)$n->var->name;
            }
        }

        if ($n instanceof Node\Param
        ||  $n instanceof Node\ClosureUse) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($n instanceof Node\Expr\Variable) {
            $this->used[] = (string)$n->name;
        }
    }

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Stmt\Catch_) {
            $this->used = array_diff($this->used, [(string)$n->var->name]);
        }

        if ($n instanceof Node\Stmt\ClassMethod
        ||  $n instanceof Node\Stmt\Function_
        ||  $n instanceof Node\Expr\Closure
        ) {
            if ($n->hasAttribute(Compiler::ATTR_DONT_DEFINE_UNDEFINED_VARS)) {
                return $n;
            }

            $undefined = array_unique(
                array_diff(
                    $this->used,
                    $this->defined,
                    ["this"],
                    array_map(function($p) {
                        return (string)$p->var->name;
                    }, $n->params)
                )
            );
            list($this->defined, $this->used) = array_pop($this->stack);

            $n->stmts = array_merge(
                array_map(function($v) {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable($v),
                            new Node\Expr\ConstFetch(new Node\Name("null"))
                        )
                    );
                }, $undefined),
                $n->stmts
            );
        }
    }
}
