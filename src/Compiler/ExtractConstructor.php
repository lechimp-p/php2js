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

namespace Lechimp\PHP_JS\Compiler;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeTraverser;

class ExtractConstructor extends NodeVisitorAbstract {
    /**
     * @var Node\StmtClass_
     */
    protected $class = null;

    public function beforeTraverse(array $nodes) {
        $this->class = null;
    }

    public function enterNode(Node $n) {
        if ($n instanceof Node\Stmt\Class_) {
            $this->class = $n;
        }
    }

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Stmt\Class_) {
            $this->class = null;
        }
        if ($n instanceof Node\Stmt\ClassMethod) {
            if ((string)$n->name === "__construct") {
                $this->class->constructor = $n;
                return NodeTraverser::REMOVE_NODE; 
            }
        }
    }
}
