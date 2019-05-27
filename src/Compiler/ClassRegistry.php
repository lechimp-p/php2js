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
 * Collects results of the compilation of a class.
 */
class ClassRegistry {
    /**
     * @var string
     */
    protected $fqn; 

    /**
     * @var Node\Stmt\ClassMethod[]
     */
    protected $methods;

    public function __construct(string $fqn) {
        $this->fqn = $fqn;
        $this->methods = [];
    }

    public function addMethod(Node\Stmt\ClassMethod $method) {
        if (!$method->hasAttribute(Compiler::ATTR_VISIBILITY)) {
            throw new \LogicException(
                "Expected method to have visibility-attribute."
            );
        }
        $this->methods[(string)$method->name] = $method; 
    }  

    /**
     * @throws \InvalidArgumentException if method with name is not known.
     */
    public function getMethod(string $name) : Node\Stmt\ClassMethod {
        if (!isset($this->methods[$name])) {
            throw new \InvalidArgumentException(
                "Unknown method $name."
            );
        }

        return $this->methods[$name];
    }

    /**
     * @return  string[]
     */
    public function getMethodNames(string $visibility = null) : array{
        if ($visibility === null) {
            return array_keys($this->methods);
        }

        return array_keys(
            array_filter(
                $this->methods,
                function ($m) use ($visibility) {
                    return $m->getAttribute(Compiler::ATTR_VISIBILITY) === $visibility;
                }
            )
        );
    }
}
