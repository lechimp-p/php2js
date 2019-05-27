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
}

