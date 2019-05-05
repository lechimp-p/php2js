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

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AnnotateFullyQualifiedName extends NodeVisitorAbstract {
    /**
     * @var string[]
     */
    protected $namespaces = [];

    public function beforeTraverse(array $nodes) {
        $this->namespaces = [];
    }

    public function enterNode(Node $n) {
        switch (get_class($n)) {
            case Node\Stmt\Namespace_::class:
                $this->namespaces[] = (string)$n->name;
                break;
            case Node\Stmt\Class_::class:
            case Node\Stmt\Interface_::class:
                if ($n->name !== null) {
                    $n->setAttribute(
                        Compiler::ATTR_FULLY_QUALIFIED_NAME,
                        join("\\",$this->namespaces)."\\".(string)$n->name
                    );
                }
                break;
        }
    }

    public function leaveNode(Node $n) {
        switch (get_class($n)) {
            case Node\Stmt\Namespace_::class:
                if ($n->getAttribute("kind") === Node\Stmt\Namespace_::KIND_BRACED) {
                    array_pop($this->namespaces);
                } 
                break;
        }
    }
}
