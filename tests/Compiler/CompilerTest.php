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

    public function _simplifyAST($nodes) {
        return $this->simplifyAST(...$nodes);
    }

    public function _getRegistry($nodes) {
        return $this->getRegistry(...$nodes);
    }
}

class CompilerTest extends \PHPUnit\Framework\TestCase {
    public function setUp() {
        $this->builder = new BuilderFactory;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->compiler = new CompilerForTest();

        $this->js_factory = new JS\AST\Factory();
        $this->js_printer = new JS\AST\Printer();

        $this->real_compiler = new Compiler\Compiler(
            $this->parser,
            $this->js_factory,
            $this->js_printer
        );
    }

    public function test_smoke() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;

class TestScript implements Script {
    protected $foo;

    public function execute() {
        $this->foo = "bar";
        echo "Hello World!";
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertInternalType("string", $result);
        $this->assertRegExp("/.*console.log\\(\"Hello World!\"\\);.*/ms", $result);
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

    public function test_getDependencies_of_script_class() {
        $ast = $this->builder->class("SOME_CLASS")->getNode();
        $ast->setAttribute(Compiler\Compiler::ATTR_SCRIPT_DEPENDENCIES, ["Window"]);

        $expected = ["Window"];
        $result = $this->compiler->_getDependencies([$ast]);

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

    public function test_getRegistry() {
        $ast = $this->compiler->_annotateAST(
            $this->parser->parse(<<<'PHP'
<?php

class Foo {
}

PHP
            )
        );


        $result = $this->compiler->_getRegistry($ast);

        $this->assertEquals(["\\Foo"], $result->getFullyQualifiedClassNames());
    }

    public function test_compile_properties() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;

class TestScript implements Script {
    protected $protected_var;
    private $private_var;

    public function execute() {
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*protected.protected_var = null.*/ms", $result);
        $this->assertRegExp("/.*private.private_var = null.*/ms", $result);
    }


    public function test_use_visibility_for_properties() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;

class TestScript implements Script {
    protected $protected_var;
    private $private_var;

    public function execute() {
        echo $this->protected_var;
        echo $this->private_var;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*console.log\\(protected.protected_var\\);.*/ms", $result);
        $this->assertRegExp("/.*console.log\\(private.private_var\\);.*/ms", $result);
    }

    public function test_use_visibility_for_methods() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;

class TestScript implements Script {
    protected function a_method() {}

    public function execute() {
        $this->a_method();
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/protected.a_method\\(\\).*/ms", $result);
    }

    public function test_property_with_default() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;

class TestScript implements Script {
    protected $foo = "bar" ;

    public function execute() {
        echo $this->foo;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*protected.foo = \"bar\".*/ms", $result);
    }

    public function test_function_with_param() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;

class TestScript implements Script {
    public function execute() {
        $this->echo("Hello World!");
    }

    protected function echo($string) {
        echo $string;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*protected.echo\\s+=\\s+function\\(string\\).*/ms", $result);
    }

    public function test_use_public_constructor() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;
use Lechimp\PHP_JS\JS\API\Window;

class TestScript implements Script {
    protected $foo;

    public function __construct(Window $foo) {
        $this->foo = $foo;
    }

    public function execute() {
        echo $this->foo;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*protected.foo = foo.*/ms", $result);
        $this->assertRegExp("/.*\"construct\"\\s+:\\s+function\\(foo\\)*/ms", $result);
        $this->assertRegExp("/.*return public.*/ms", $result);
    }

    public function test_inject_script_dependencies() {

        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;
use Lechimp\PHP_JS\JS\API\Window;

class TestScript implements Script {
    protected $window;

    public function __construct(Window $window) {
        $this->window = $window;
    }

    public function execute() {
        $this->window->alert("Hello World!");
    }
}
PHP
        );


        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*protected.window = window.*/ms", $result);
        $this->assertRegExp("/.*\"construct\"\\s+:\\s+function\\(window\\)*/ms", $result);
        $this->assertRegExp("/.*TestScript.__construct\\(_WindowImpl.__construct\\(\\)\\);.*/", $result);
        $this->assertRegExp("/.*var _WindowImpl = \\(function\\(\\) {.*/", $result);
    }

    public function test_annotate_dependencies_of_script_class() {
        $ast = $this->parser->parse(<<<'PHP'
<?php

use Lechimp\PHP_JS\JS\Script;
use Lechimp\PHP_JS\JS\API\Window;
use Lechimp\PHP_JS\JS\API\Document;

class TestScript implements Script {
    protected $window;
    protected $document;

    public function __construct(Window $window, Document $document) {
        $this->window = $window;
        $this->document = $document;
    }

    public function execute() {
        $this->window->alert("Hello World!");
    }
}

PHP
        );

        $this->compiler->_simplifyAST($ast);
        $this->compiler->_annotateAST($ast);
        array_shift($ast); // USE-Statement
        array_shift($ast); // USE-Statement
        array_shift($ast); // USE-Statement
        $my_class = array_shift($ast);

        $this->assertTrue($my_class->hasAttribute(Compiler\Compiler::ATTR_SCRIPT_DEPENDENCIES));
        $this->assertEquals(
            [JS\API\Window::class, JS\API\Document::class],
            $my_class->getAttribute(Compiler\Compiler::ATTR_SCRIPT_DEPENDENCIES)
        );
    }
}
