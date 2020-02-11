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

use Lechimp\PHP2JS\Compiler\FilePass;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AnnotateFirstVariableAssignment extends NodeVisitorAbstract implements FilePass {
    const ATTR = "first_var_assignment";

    /**
     * @var string[]
     */
    protected $assignments = [];

    /**
     * @var string[][]
     */
    protected $stack = [];

    public function runsAlone() : bool {
        return false;
    }

    public function beforeTraverse(array $nodes) {
        $this->assignments = [];
        $this->stack = [];
    }

    public function enterNode(Node $n) {
        if ($n instanceof Node\Stmt\ClassMethod
        ||  $n instanceof Node\Stmt\Function_
        ||  $n instanceof Node\Expr\Closure
        ) {
            array_push($this->stack, $this->assignments);
            $this->assignments = [];
        }

        if ($n instanceof Node\Expr\Assign) {
            if (!$n->hasAttribute(self::ATTR)) {
                $n->setAttribute(
                    self::ATTR,
                    $n->var instanceof Node\Expr\Variable
                    && !array_key_exists($n->var->name, $this->assignments)
                );
            }
            $this->assignments[(string)$n->var->name] = true;
        }

        if ($n instanceof Node\Expr\ClosureUse
        ||  $n instanceof Node\Param) {
            $this->assignments[(string)$n->var->name] = true;
        }
    }

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Stmt\ClassMethod
        ||  $n instanceof Node\Stmt\Function_
        ||  $n instanceof Node\Stmt\Closure
        ) {
            $this->assignments = array_pop($this->stack);
        }
    }
}
