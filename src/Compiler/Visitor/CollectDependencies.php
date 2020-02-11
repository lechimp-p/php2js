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

use Lechimp\PHP2JS\Compiler\FilePass\AnnotateScriptDependencies;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class CollectDependencies extends NodeVisitorAbstract {
    /**
     * @var string[]
     */
    protected $dependencies = [];

    public function getDependencies() {
        return array_unique($this->dependencies);
    }

    public function beforeTraverse(array $nodes) {
        $this->dependencies = [];
    }

    public function enterNode(Node $n) {
        switch (get_class($n)) {
            case Node\Stmt\Class_::class:
                if ($n->extends !== null) {
                    $this->dependencies[] = (string)$n->extends;
                }
                foreach ($n->implements as $i) {
                    $this->dependencies[] = (string)$i;
                }
                if ($n->hasAttribute(AnnotateScriptDependencies::ATTR)) {
                    foreach ($n->getAttribute(AnnotateScriptDependencies::ATTR) as $d) {
                        $this->dependencies[] = $d; 
                    }
                }
                break;
            case Node\Stmt\ClassMethod::class:
                if ($n->returnType instanceof Node\Name) {
                    $this->dependencies[] = (string)$n->returnType;
                }
                elseif($n->returnType instanceof Node\NullableType && $n->returnType->type instanceof Node\Name) {
                    $this->dependencies[] = (string)$n->returnType->type;
                }
                break;
            case Node\Param::class:
                if ($n->type instanceof Node\Name) {
                    $this->dependencies[] = (string)$n->type;
                }
                elseif($n->type instanceof Node\NullableType && $n->type->type instanceof Node\Name) {
                    $this->dependencies[] = (string)$n->type->type;
                }
                break;
            case Node\Expr\FuncCall::class:
                if ($n->name instanceof Node\Name) {
                    $this->dependencies[] = (string)$n->name;
                }
                break;
            case Node\Expr\New_::class:
            case Node\Expr\StaticCall::class:
            case Node\Expr\StaticPropertyFetch::class:
                if ($n->class instanceof Node\Name) {
                    $this->dependencies[] = (string)$n->class;
                }
                break;
        }
    }
}
