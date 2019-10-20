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

/**
 * Transforms a class-node into a ClassRegistry. 
 */
class ClassRegistryBuilder {
    public function buildClassRegistryFrom(Node\Stmt\Class_ $n) {
        if (!$n->hasAttribute(Visitor\AnnotateFullyQualifiedName::ATTR)) {
            throw new \LogicException(
                "Expected class to have FQN."
            );
        }

        $registry = new ClassRegistry(
            (string)$n->getAttribute(Visitor\AnnotateFullyQualifiedName::ATTR),
            $n->extends ? (string)$n->extends : null,
            array_map(function($i) {
                if (!($i instanceof Node\Name\FullyQualified)) {
                    throw new \LogicException(
                        "Expected implemented interface to have FQN."
                    );
                }
                return (string)$i;
            }, $n->implements)
        );

        foreach ($n->stmts as $s) {
            if ($s instanceof Node\Stmt\ClassMethod) {
                $registry->addMethod($s);
            }
            elseif ($s instanceof Node\Stmt\Property) {
                $registry->addProperty($s);
            }
            elseif ($s instanceof Node\Stmt\ClassConst) {
                $registry->addConstant($s);
            }
            else {
                throw new \LogicException(
                    "Currently cannot deal with ".get_class($s)
                );
            }
        }

        return $registry;
    }
}
