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

    /**
     * @var array
     */
    protected $visibilities;

    public function __construct() {
        $this->classes = [];
        $this->visibilities = [];
    }

    public function addClass(Node\Stmt\Class_ $class) : Results {
        if (!$class->hasAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME)) {
            throw new \LogicException(
                "Class for Result should have fully qualified name."
            );
        }
        $fqn = $class->getAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME);

        if (isset($this->classes[$fqn])) {
            throw new Exception(
                "Class '$fqn' defined twice."
            );
        }

        $clone = clone $this;
        $clone->classes[$fqn] = $class;
        return $clone;
    }

    public function getFullyQualifiedClassNames() {
        return array_keys($this->classes);
    }

    public function getClass(string $fully_qualified_name) {
        if (!isset($this->classes[$fully_qualified_name])) {
            throw new \LogicException(
                "Unknown class '$fully_qualified_name'"
            );
        }
        return $this->classes[$fully_qualified_name];
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

    /**
     * @var string One of: Compiler::ATTR_PUBLIC, Compiler::ATTR_PROTECTED, Compiler::ATTR_PRIVATE
     */
    public function getVisibility(string $fully_qualified_class_name, string $method_or_property) : string {
        if (isset($this->visibilities[$fully_qualified_class_name])
        && isset($this->visibilities[$fully_qualified_class_name][$method_or_property])) {
            return $this->visibilities[$fully_qualified_class_name][$method_or_property];
        }

        $class = $this->getClass($fully_qualified_class_name);
        $visibility = null;
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                if ((string)$stmt->name === $method_or_property) {
                    $visibility = Compiler::getVisibilityConst($stmt);
                    break;
                }
            }
            elseif ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $p) {
                    if ((string)$p->name == $method_or_property) {
                        $visibility = Compiler::getVisibilityConst($stmt);
                        break;
                    }
                }
            }
            else {
                throw new \LogicException(
                    "I do not understand this statement in a class: '".get_class($stmt)."'"
                );
            }
        }

        if ($visibility === null) {
            if ($class->extends !== null) {
                $visibility = $this->getVisibility((string)$class->extends, $method_or_property);
                if (in_array($visibility, [Compiler::ATTR_PUBLIC, Compiler::ATTR_PROTECTED])) {
                    return $visibility;
                }
            }

            throw new \LogicException(
                "Could not determine visibility for '$fully_qualified_class_name::$method_or_property'"
            );
        }

        return $visibility;
    }
}
