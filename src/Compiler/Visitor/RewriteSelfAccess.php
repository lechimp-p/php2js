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

use Lechimp\PHP2JS\JS;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeTraverser;

class RewriteSelfAccess extends NodeVisitorAbstract {
    /**
     * @var Node\Name|null
     */
    protected $in_class = null;

    public function enterNode(Node $n) {
        if ($n instanceof Node\Stmt\Class_) {
            if (!isset($n->namespacedName)) {
                throw new \LogicException(
                    "Expected class to have a namespaced name."
                );
            }
            $this->in_class = $n->namespacedName; 
        } 
    }

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Stmt\Class_) {
            $this->in_class = null;
        }
        if ($n instanceof Node\Expr\ClassConstFetch) {
            if ((string)$n->class === "self") {
                if ($this->in_class === null) {
                    throw \LogicException(
                        "Expected in_class to be set."
                    );
                }
                $n->class = $this->in_class;
            }
        }
    }
}
