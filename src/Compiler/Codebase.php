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
 * Represents the codebase we are working on.
 *
 * Stores the objects in the codebase and informations about them. It is the
 * workspace we are using for different compiler passes.
 */
class Codebase {
    //---------------------------
    // INITIALISATION
    //---------------------------

    /**
     * @var Node\Stmt\Class_[]
     */
    protected $classes;

    /**
     * @var Node\Stmt\Interface_[]
     */
    protected $interfaces;

    /**
     * @var array
     */
    protected $visibilities;

    /**
     * @var string[]
     */
    protected $namespaces;

    public function __construct() {
        $this->classes = [];
        $this->interfaces = [];
        $this->visibilities = [];
        $this->namespaces = [];
    }


    //---------------------------
    // SETTERS
    //---------------------------

    public function addClass(Node\Stmt\Class_ $class) : void {
        $this->addClassOrInterface($class);
    }

    public function addInterface(Node\Stmt\Interface_ $interface) : void{
        $this->addClassOrInterface($interface);
    }

    protected function addClassOrInterface($class_or_interface) : void {
        if (!$class_or_interface->hasAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME)) {
            throw new \LogicException(
                "Class/interface for Codebase should have fully qualified name."
            );
        }
        $fqn = $class_or_interface->getAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME);

        if ($class_or_interface instanceof Node\Stmt\Class_) {
            if (isset($this->classes[$fqn])) {
                throw new Exception(
                    "Class '$fqn' defined twice."
                );
            }
            $this->classes[$fqn] = $class_or_interface;
        }
        else if ($class_or_interface instanceof Node\Stmt\Interface_) {
            if (isset($this->interfaces[$fqn])) {
                throw new Exception(
                    "Interface '$fqn' defined twice."
                );
            }
            $this->interfaces[$fqn] = $class_or_interface;
        }
        else {
            throw new \LogicException(
                "Cannot process '".get_class($class_or_interface)."' here."
            );
        }

        $this->addNamespace($fqn);
    }

    protected function addNamespace(string $fqn) : void {
        $names = explode("\\", $fqn);
        if ($names[0] === "") {
            array_shift($names);
        }
        array_pop($names);
        $cur = &$this->namespaces;
        foreach($names as $n) {
            if (!isset($cur[$n])) {
                $cur[$n] = [];
            }
            $cur = &$cur[$n];
        }
    }


    //---------------------------
    // QUERIES
    //---------------------------

    /**
     * @return string[]
     */
    public function getFullyQualifiedClassNames() {
        return array_keys($this->classes);
    }

    /**
     * @return string[]
     */
    public function getFullyQualifiedInterfaceNames() {
        return array_keys($this->interfaces);
    }

    public function hasClass(string $fully_qualified_name) : bool {
        return isset($this->classes[$fully_qualified_name]);
    }

    public function hasInterface(string $fully_qualified_name) : bool {
        return isset($this->interfaces[$fully_qualified_name]);
    }

    public function getClass(string $fully_qualified_name) : Node\Stmt\Class_ {
        if (!isset($this->classes[$fully_qualified_name])) {
            throw new \LogicException(
                "Unknown class '$fully_qualified_name'"
            );
        }
        return $this->classes[$fully_qualified_name];
    }

    public function getInterface(string $fully_qualified_name) : Node\Stmt\Interface_ {
        if (!isset($this->interfaces[$fully_qualified_name])) {
            throw new \LogicException(
                "Unknown interface '$fully_qualified_name'"
            );
        }
        return $this->interfaces[$fully_qualified_name];
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

            return Compiler::ATTR_PUBLIC;
        }

        return $visibility;
    }

    /**
     * @return string[]
     */
    public function getNamespaces() : array {
        return $this->namespaces;
    }
}
