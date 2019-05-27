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

namespace Lechimp\PHP2JS\Test\Compiler;

use Lechimp\PHP2JS\Compiler\ClassRegistry;
use Lechimp\PHP2JS\Compiler\Compiler;
use PhpParser\ParserFactory;
use PhpParser\BuilderFactory;
use PhpParser\Node;

class ClassRegistryTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->builder = new BuilderFactory;
        $this->registry = new ClassRegistry("MyClass");
    }

    public function test_addMethod() {
        $method1 = new Node\Stmt\ClassMethod(
            "method1",
            [],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PUBLIC]
        );
        $this->registry->addMethod($method1);
        $method2 = new Node\Stmt\ClassMethod(
            "method2",
            [],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PROTECTED]
        );
        $this->registry->addMethod($method2);
        $method3 = new Node\Stmt\ClassMethod(
            "method3",
            [],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PRIVATE]
        );
        $this->registry->addMethod($method3);

        $this->assertEquals($method1, $this->registry->getMethod("method1"));
        $this->assertEquals($method2, $this->registry->getMethod("method2"));
        $this->assertEquals($method3, $this->registry->getMethod("method3"));

        $this->assertEquals(["method1", "method2", "method3"], $this->registry->getMethodNames());

        $this->assertEquals(["method1"], $this->registry->getMethodNames(Compiler::ATTR_PUBLIC));
        $this->assertEquals(["method2"], $this->registry->getMethodNames(Compiler::ATTR_PROTECTED));
        $this->assertEquals(["method3"], $this->registry->getMethodNames(Compiler::ATTR_PRIVATE));
    }

    public function test_addProperty() {
        $prop1 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop1")
            ],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PUBLIC]
        );
        $this->registry->addProperty($prop1);
        $prop23 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop2")
            , new Node\Stmt\PropertyProperty("prop3")
            ],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PROTECTED]
        );
        $this->registry->addProperty($prop23);
        $prop4 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop4")
            ],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PRIVATE]
        );
        $this->registry->addProperty($prop4);

        $prop2 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop2")
            ],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PROTECTED]
        );
        $prop3 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop3")
            ],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PROTECTED]
        );

        $this->assertEquals($prop1, $this->registry->getProperty("prop1"));
        $this->assertEquals($prop2, $this->registry->getProperty("prop2"));
        $this->assertEquals($prop3, $this->registry->getProperty("prop3"));
        $this->assertEquals($prop4, $this->registry->getProperty("prop4"));

        $this->assertEquals(["prop1", "prop2", "prop3", "prop4"], $this->registry->getPropertyNames());

        $this->assertEquals(["prop1"], $this->registry->getPropertyNames(Compiler::ATTR_PUBLIC));
        $this->assertEquals(["prop2", "prop3"], $this->registry->getPropertyNames(Compiler::ATTR_PROTECTED));
        $this->assertEquals(["prop4"], $this->registry->getPropertyNames(Compiler::ATTR_PRIVATE));
    }

    public function test_addMethod_for_constructor() {
        $constructor = new Node\Stmt\ClassMethod(
            "__construct",
            [],
            [Compiler::ATTR_VISIBILITY => Compiler::ATTR_PUBLIC]
        );
        $this->registry->addMethod($constructor);

        $this->assertEquals([], $this->registry->getMethodNames());

        $this->assertEquals($constructor, $this->registry->getConstructor());
    }
}

