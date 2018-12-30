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

namespace Lechimp\PHP_JS\Test\Compiler;

use Lechimp\PHP_JS\Compiler\Results;
use Lechimp\PHP_JS\Compiler\Compiler;
use PhpParser\ParserFactory;

class ResultsTest extends \PHPUnit\Framework\TestCase {
    public function setUp() {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->results = new Results();
    }

    public function test_getVisibility() {
        $ast = $this->parser->parse(<<<'PHP'
<?php

class MyClass {
    var $var_public_property;
    public $public_property;
    protected $protected_property;
    private $private_property;

    function implicit_public_method() {}
    public function public_method() {}
    protected function protected_method() {}
    private function private_method() {}
}

PHP
        );

        $class = array_shift($ast);
        $class->setAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME, "MyClass");

        $r = $this->results->addClass($class);
        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyClass", "var_public_property"));
        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyClass", "public_property"));
        $this->assertEquals(Compiler::ATTR_PROTECTED, $r->getVisibility("MyClass", "protected_property"));
        $this->assertEquals(Compiler::ATTR_PRIVATE, $r->getVisibility("MyClass", "private_property"));

        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyClass", "implicit_public_method"));
        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyClass", "public_method"));
        $this->assertEquals(Compiler::ATTR_PROTECTED, $r->getVisibility("MyClass", "protected_method"));
        $this->assertEquals(Compiler::ATTR_PRIVATE, $r->getVisibility("MyClass", "private_method"));
    }


    public function test_getVisibility_from_extended_class() {
        $ast = $this->parser->parse(<<<'PHP'
<?php

class MyClass {
    var $var_public_property;
    public $public_property;
    protected $protected_property;
    private $private_property;

    function implicit_public_method() {}
    public function public_method() {}
    protected function protected_method() {}
    private function private_method() {}
}

PHP
        );

        $class = array_shift($ast);
        $class->setAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME, "MyClass");
        $r = $this->results->addClass($class);

        $ast = $this->parser->parse(<<<'PHP'
<?php

class MyExtendedClass extends MyClass {
}

PHP
        );

        $class = array_shift($ast);
        $class->setAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME, "MyExtendedClass");
        $r = $r->addClass($class);


        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyExtendedClass", "var_public_property"));
        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyExtendedClass", "public_property"));
        $this->assertEquals(Compiler::ATTR_PROTECTED, $r->getVisibility("MyExtendedClass", "protected_property"));

        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyExtendedClass", "implicit_public_method"));
        $this->assertEquals(Compiler::ATTR_PUBLIC, $r->getVisibility("MyExtendedClass", "public_method"));
        $this->assertEquals(Compiler::ATTR_PROTECTED, $r->getVisibility("MyExtendedClass", "protected_method"));


        try {
            $this->assertEquals(Compiler::ATTR_PRIVATE, $r->getVisibility("\\MyClass", "private_property"));
            $this->assertTrue(false);
        }
        catch(\LogicException $e) {
        }

        try {
            $this->assertEquals(Compiler::ATTR_PRIVATE, $r->getVisibility("\\MyClass", "private_method"));
            $this->assertTrue(false);
        }
        catch(\LogicException $e) {
        }
    }
}
