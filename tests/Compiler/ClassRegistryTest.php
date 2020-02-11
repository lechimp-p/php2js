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
use Lechimp\PHP2JS\Compiler\FilePass;
use PhpParser\ParserFactory;
use PhpParser\BuilderFactory;
use PhpParser\Node;

class ClassRegistryTest extends \PHPUnit\Framework\TestCase {
    const CLASS_NAME = "MyClass";
    const PARENT_CLASS_NAME = "MyParentClass";
    const IMPLEMENTS1 = "MyInterface1";
    const IMPLEMENTS2 = "MyInterface1";

    public function setUp() : void {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->builder = new BuilderFactory;
        $this->registry = new ClassRegistry(
            self::CLASS_NAME,
            self::PARENT_CLASS_NAME,
            [self::IMPLEMENTS1, self::IMPLEMENTS2]
        );
    }

    public function test_name() {
        $this->assertEquals(self::CLASS_NAME, $this->registry->name());
    }

    public function test_parentName() {
        $this->assertEquals(self::PARENT_CLASS_NAME, $this->registry->parentName());
    }

    public function test_implementNames() {
        $this->assertEquals(
            [self::IMPLEMENTS1, self::IMPLEMENTS2],
            $this->registry->implementsNames()
        );
    }

    public function test_addMethod() {
        $method1 = new Node\Stmt\ClassMethod(
            "method1",
            [],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PUBLIC]
        );
        $this->registry->addMethod($method1);
        $method2 = new Node\Stmt\ClassMethod(
            "method2",
            [],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PROTECTED]
        );
        $this->registry->addMethod($method2);
        $method3 = new Node\Stmt\ClassMethod(
            "method3",
            [],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PRIVATE]
        );
        $this->registry->addMethod($method3);

        $this->assertEquals($method1, $this->registry->getMethod("method1"));
        $this->assertEquals($method2, $this->registry->getMethod("method2"));
        $this->assertEquals($method3, $this->registry->getMethod("method3"));

        $this->assertEquals(["method1", "method2", "method3"], $this->registry->getMethodNames());

        $this->assertEquals(["method1"], $this->registry->getMethodNames(FilePass\AnnotateVisibility::ATTR_PUBLIC));
        $this->assertEquals(["method2"], $this->registry->getMethodNames(FilePass\AnnotateVisibility::ATTR_PROTECTED));
        $this->assertEquals(["method3"], $this->registry->getMethodNames(FilePass\AnnotateVisibility::ATTR_PRIVATE));

        $this->assertEquals(
            ["method1" => $method1, "method2" => $method2, "method3" => $method3],
            $this->registry->getMethods()
        );
    }

    public function test_addProperty() {
        $prop1 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop1")
            ],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PUBLIC]
        );
        $this->registry->addProperty($prop1);
        $prop23 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop2")
            , new Node\Stmt\PropertyProperty("prop3")
            ],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PROTECTED]
        );
        $this->registry->addProperty($prop23);
        $prop4 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop4")
            ],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PRIVATE]
        );
        $this->registry->addProperty($prop4);

        $prop2 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop2")
            ],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PROTECTED]
        );
        $prop3 = new Node\Stmt\Property(
            0,
            [ new Node\Stmt\PropertyProperty("prop3")
            ],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PROTECTED]
        );

        $this->assertEquals($prop1, $this->registry->getProperty("prop1"));
        $this->assertEquals($prop2, $this->registry->getProperty("prop2"));
        $this->assertEquals($prop3, $this->registry->getProperty("prop3"));
        $this->assertEquals($prop4, $this->registry->getProperty("prop4"));

        $this->assertEquals(["prop1", "prop2", "prop3", "prop4"], $this->registry->getPropertyNames());

        $this->assertEquals(["prop1"], $this->registry->getPropertyNames(FilePass\AnnotateVisibility::ATTR_PUBLIC));
        $this->assertEquals(["prop2", "prop3"], $this->registry->getPropertyNames(FilePass\AnnotateVisibility::ATTR_PROTECTED));
        $this->assertEquals(["prop4"], $this->registry->getPropertyNames(FilePass\AnnotateVisibility::ATTR_PRIVATE));

        $this->assertEquals(
            ["prop1" => $prop1, "prop2" => $prop2, "prop3" => $prop3, "prop4" => $prop4],
            $this->registry->getProperties()
        );
    }

    public function test_addMethod_for_constructor() {
        $constructor = new Node\Stmt\ClassMethod(
            "__construct",
            [],
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PUBLIC]
        );
        $this->registry->addMethod($constructor);

        $this->assertEquals([], $this->registry->getMethodNames());

        $this->assertEquals($constructor, $this->registry->getConstructor());
    }

    public function test_addConstant() {
        $const12 = new Node\Stmt\ClassConst(
            [
                new Node\Const_("const1", new Node\Scalar\String_("const1")),
                new Node\Const_("const2", new Node\Scalar\String_("const2"))
            ],
            0,
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PROTECTED]
        );
        $this->registry->addConstant($const12);

        $const1 = new Node\Stmt\ClassConst(
            [
                new Node\Const_("const1", new Node\Scalar\String_("const1"))
            ],
            0,
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PROTECTED]
        );
        $const2 = new Node\Stmt\ClassConst(
            [
                new Node\Const_("const2", new Node\Scalar\String_("const2"))
            ],
            0,
            [FilePass\AnnotateVisibility::ATTR => FilePass\AnnotateVisibility::ATTR_PROTECTED]
        );

        $this->assertEquals($const1, $this->registry->getConstant("const1"));
        $this->assertEquals($const2, $this->registry->getConstant("const2"));

        $this->assertEquals(["const1", "const2"], $this->registry->getConstantNames());
        $this->assertEquals([], $this->registry->getConstantNames(FilePass\AnnotateVisibility::ATTR_PUBLIC));
        $this->assertEquals(["const1", "const2"], $this->registry->getConstantNames(FilePass\AnnotateVisibility::ATTR_PROTECTED));
        $this->assertEquals([], $this->registry->getConstantNames(FilePass\AnnotateVisibility::ATTR_PRIVATE));

        $this->assertEquals(
            ["const1" => $const1, "const2" => $const2],
            $this->registry->getConstants()
        );
    }
}

