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

/**
 * Collects results of the compilation.
 */
class Results {
    /**
     * @var Node\Stmt\Class_[]
     */
    protected $classes;

    public function __construct() {
        $this->classes = [];
    }

    public function addClass(Node\Stmt\Class_ $class) : Results {
        if (!$class->hasAttribute("fully_qualified_name")) {
            throw new \LogicException(
                "Class for Result should have fully qualified name."
            );
        }
        $fqn = $class->getAttribute("fully_qualified_name");

        if (isset($this->classes[$fqn])) {
            throw new Exception(
                "Class '$fqn' defined twice."
            );
        }

        $clone = clone $this;
        $clone->classes[$fqn] = $class;
        return $clone;
    }

    /**
     * @return Node\Stmt\Class_[]
     */
    public function getClassesThatImplement(string $fully_qualified_name) : array {
        return array_filter(
            $this->classes,
            function(Node\Stmt\Class_ $class) use ($fully_qualified_name) {
                foreach ($class->implements as $i) {
                    if ((string)$i === $fully_qualified_name) {
                        return true;
                    }
                }
                return false;
            }
        );
    }
}
