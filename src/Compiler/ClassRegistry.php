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
    protected $name; 

    /**
     * @var string|null
     */
    protected $parent_name;

    /**
     * @var string[]
     */
    protected $implements_names;

    /**
     * @var Node\Stmt\ClassMethod[]
     */
    protected $methods;

    /**
     * @var Node\Stmt\ClassMethod|null
     */
    protected $constructor;

    /**
     * @var Node\Stmt\Property[]
     */
    protected $properties;

    /**
     * @var Node\Stmt\ClassConst
     */
    protected $constants;


    public function __construct(
        string $name,
        string $parent_name = null,
        array $implements_names = []
    ) {
        $this->name = $name;
        $this->parent_name = $parent_name;
        $this->implements_names = $implements_names;
        $this->methods = [];
        $this->constructor = null;
        $this->properties = [];
        $this->constants = [];
    }

    public function name() : string {
        return $this->name;
    }

    public function parentName() : ?string {
        return $this->parent_name;
    }  

    /**
     * @var array
     */
    public function implementsNames() : array {
        return $this->implements_names;
    }

    /**
     * @return void
     */
    public function addMethod(Node\Stmt\ClassMethod $method) {
        if (!$method->hasAttribute(Compiler::ATTR_VISIBILITY)) {
            throw new \LogicException(
                "Expected method to have visibility-attribute."
            );
        }

        if ((string)$method->name === "__construct") {
            $this->constructor = $method;
            return;
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
    public function getMethodNames(string $visibility = null) : array {
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

    /**
     * @return Node\Stmt\ClassMethod[]
     */
    public function getMethods() : array {
        return $this->methods;
    }

    public function getConstructor() : ?Node\Stmt\ClassMethod {
        return $this->constructor;
    }

    /**
     * @return void
     */
    public function addProperty(Node\Stmt\Property $property) {
        if (!$property->hasAttribute(Compiler::ATTR_VISIBILITY)) {
            throw new \LogicException(
                "Expected property to have visibility-attribute."
            );
        }

        for ($i = 0; $i < count($property->props); $i++) {
            $clone = clone $property;
            $p = $clone->props[$i];
            $clone->props = [$p];
            $this->properties[(string)$p->name] = $clone;
        }
    }

    /**
     * @throws \InvalidArgumentException if method with name is not known.
     */
    public function getProperty(string $name) : Node\Stmt\Property{
        if (!isset($this->properties[$name])) {
            throw new \InvalidArgumentException(
                "Unknown property $name."
            );
        }

        return $this->properties[$name];
    }

    /**
     * @return  string[]
     */
    public function getPropertyNames(string $visibility = null) : array{
        if ($visibility === null) {
            return array_keys($this->properties);
        }

        return array_keys(
            array_filter(
                $this->properties,
                function ($p) use ($visibility) {
                    return $p->getAttribute(Compiler::ATTR_VISIBILITY) === $visibility;
                }
            )
        );
    }

    /**
     * @return Node\Stmt\Property[]
     */
    public function getProperties() : array {
        return $this->properties;
    }

    /**
     * @return void
     */
    public function addConstant(Node\Stmt\ClassConst $const) {
        if (!$const->hasAttribute(Compiler::ATTR_VISIBILITY)) {
            throw new \LogicException(
                "Expected property to have visibility-attribute."
            );
        }

        for ($i = 0; $i < count($const->consts); $i++) {
            $clone = clone $const;
            $c = $clone->consts[$i];
            $clone->consts = [$c];
            $this->constants[(string)$c->name] = $clone;
        }
    }

    public function getConstant(string $name) : Node\Stmt\ClassConst {
        if (!isset($this->constants[$name])) {
            throw new \InvalidArgumentException(
                "Unknown constant $name."
            );
        }

        return $this->constants[$name];
    }

    /**
     * @return  string[]
     */
    public function getConstantNames(string $visibility = null) : array{
        if ($visibility === null) {
            return array_keys($this->constants);
        }

        return array_keys(
            array_filter(
                $this->constants,
                function ($c) use ($visibility) {
                    return $c->getAttribute(Compiler::ATTR_VISIBILITY) === $visibility;
                }
            )
        );
    }

    public function getConstants() {
        return $this->constants;
    }
}
