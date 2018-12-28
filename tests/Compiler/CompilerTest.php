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

use Lechimp\PHP_JS\Compiler;
use Lechimp\PHP_JS\JS;
use PhpParser\BuilderFactory;
use PhpParser\ParserFactory;
use PhpParser\Node as PhpNode;

class CompilerForTest extends Compiler\Compiler {
    public function __construct() {
    }

    public function _getDependencies($nodes) {
        return $this->getDependencies(...$nodes);
    }

    public function _annotateAST($nodes) {
        return $this->annotateAST(...$nodes);
    }

    public function _getResults($nodes) {
        return $this->getResults(...$nodes);
    }
}

class CompilerTest extends \PHPUnit\Framework\TestCase {
    public function setUp() {
        $this->builder = new BuilderFactory;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->compiler = new CompilerForTest();
    }

    public function test_getDependencies() {
        $ast = $this->parser->parse(<<<'PHP'
<?php

class Foo extends Bar implements Baz {
    public function __construct(Dependency $dep) {
        $foo = 0;
        some_function();
        new Foobar;
    }

    public function bla(Blaw $b) : Blub {
        Bleen::grue();
        return Grue::$foo;
    }
}

PHP
        );

        $expected = ["Bar", "Baz", "Dependency", "some_function", "Foobar", "Blub", "Blaw", "Bleen", "Grue"];
        sort($expected);

        $result = $this->compiler->_getDependencies($ast);
        sort($result);

        $this->assertEquals($expected, $result);
    }

    public function test_annotate_class_with_fully_qualified_name() {
        $my_class_name = "CLASS_NAME";
        $ast = $this->builder->class($my_class_name)->getNode();

        list($result) = $this->compiler->_annotateAST([$ast]);

        $this->assertTrue($result->hasAttribute(Compiler\Compiler::ATTR_FULLY_QUALIFIED_NAME));
        $this->assertEquals("\\$my_class_name", $result->getAttribute(Compiler\Compiler::ATTR_FULLY_QUALIFIED_NAME));
    }

    public function test_annotate_class_in_namespace_with_fully_qualified_name() {
        $my_namespace_name = "NAMESPACE_NAME";
        $my_nested_namespace = "NESTED_NAMESPACE";
        $my_class_name = "CLASS_NAME";
        $my_class = $this->builder->class($my_class_name)->getNode();
        $ast = $this->builder
            ->namespace($my_namespace_name)
            ->addStmt($this->builder
                ->namespace($my_nested_namespace)
                ->addStmt($my_class)
                ->getNode()
            )
            ->getNode();

        $this->compiler->_annotateAST([$ast]);

        $this->assertTrue($my_class->hasAttribute(Compiler\Compiler::ATTR_FULLY_QUALIFIED_NAME));
        $this->assertEquals(
            "$my_namespace_name\\$my_nested_namespace\\$my_class_name",
            $my_class->getAttribute(Compiler\Compiler::ATTR_FULLY_QUALIFIED_NAME)
        );
    }

    public function test_getResults() {
        $ast = $this->compiler->_annotateAST(
            $this->parser->parse(<<<'PHP'
<?php

class Foo {
}

PHP
            )
        );


        $result = $this->compiler->_getResults($ast);

        $this->assertEquals(["\\Foo"], $result->getFullyQualifiedClassNames());
    }

}
