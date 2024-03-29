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

use Lechimp\PHP2JS\Compiler;
use Lechimp\PHP2JS\Compiler\Visitor;
use Lechimp\PHP2JS\Compiler\FilePass;
use Lechimp\PHP2JS\JS;
use PhpParser\BuilderFactory;
use PhpParser\ParserFactory;
use PhpParser\Node as PhpNode;
use PhpParser\NodeVisitor;
use PhpParser\NodeTraverser;

class CompilerForTest extends Compiler\Compiler {
    public function __construct() {
        $this->file_passes = [
            new FilePass\ResolveNames(),
            new FilePass\AnnotateFullyQualifiedName(),
            new FilePass\RewriteSelfAccess(),
            new FilePass\RewriteOperators(),
            new FilePass\RewriteTypeHints(),
            new FilePass\RewriteArrayCode(),
            new FilePass\DefineUndefinedVariables(),
            new FilePass\AnnotateScriptDependencies(),
            new FilePass\AnnotateFirstVariableAssignment(),
            new FilePass\AnnotateVisibility()
        ];
    }

    public function _preprocessFileAST($nodes) {
        return $this->preprocessFileAST(...$nodes);
    }

    public function _getDependencies($nodes) {
        return $this->getDependencies(...$nodes);
    }
}

class CompilerTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
        $this->builder = new BuilderFactory;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->compiler = new CompilerForTest();

        $this->js_factory = new JS\AST\Factory();
        $this->js_printer = new JS\AST\Printer();

        $this->real_compiler = new Compiler\Compiler(
            $this->parser,
            $this->js_factory,
            $this->js_printer,
            new Compiler\Dependency\NullLocator()
        );
    }

//------------------------------------------------------------------------------
// TEST: Smoke
//------------------------------------------------------------------------------
    public function test_smoke() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

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

        $this->assertIsString($result);
        $this->assertRegExp("/.*process\\.stdout\\.write\\(\"Hello World!\"\\);.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Get Dependencies
//------------------------------------------------------------------------------
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

//------------------------------------------------------------------------------
// TEST: Annotate Class with Fully Qualified Name
//------------------------------------------------------------------------------
    public function test_annotate_class_with_fully_qualified_name() {
        $my_class_name = "CLASS_NAME";
        $ast = $this->builder->class($my_class_name)->getNode();

        list($result) = $this->compiler->_preprocessFileAST([$ast]);

        $this->assertTrue($result->hasAttribute(FilePass\AnnotateFullyQualifiedName::ATTR));
        $this->assertEquals("\\$my_class_name", $result->getAttribute(FilePass\AnnotateFullyQualifiedName::ATTR));
    }

//------------------------------------------------------------------------------
// TEST: Annotate Class in Namespace with Fully Qualified Name
//------------------------------------------------------------------------------
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

        $this->compiler->_preprocessFileAST([$ast]);

        $this->assertTrue($my_class->hasAttribute(FilePass\AnnotateFullyQualifiedName::ATTR));
        $this->assertEquals(
            "$my_namespace_name\\$my_nested_namespace\\$my_class_name",
            $my_class->getAttribute(FilePass\AnnotateFullyQualifiedName::ATTR)
        );
    }

//------------------------------------------------------------------------------
// TEST: Compile Properties
//------------------------------------------------------------------------------
    public function test_compile_properties() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $protected_var;
    private $private_var;

    public function execute() {
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*\"protected_var\" : null/ms", $result);
        $this->assertRegExp("/.*\"private_var\" : null*/ms", $result);
    }


//------------------------------------------------------------------------------
// TEST: Use Visibility for Properties
//------------------------------------------------------------------------------
    public function test_use_visibility_for_properties() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

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

        $this->assertRegExp("/.*process\\.stdout\\.write\\(this.protected_var\\);.*/ms", $result);
        $this->assertRegExp("/.*process\\.stdout\\.write\\(this.private_var\\);.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Property with Default
//------------------------------------------------------------------------------
    public function test_property_with_default() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foo = "bar" ;

    public function execute() {
        echo $this->foo;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*\"foo\" : \"bar\".*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Method with Param
//------------------------------------------------------------------------------
    public function test_method_with_param() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

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

        $this->assertRegExp("/.*\"echo\"\\s+:\\s+function\\(\\\$string\\).*/ms", $result);
    }


//------------------------------------------------------------------------------
// TEST: Closure with Param
//------------------------------------------------------------------------------
    public function test_closure_with_param() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function execute() {
        $foo = function ($bar) {
            echo $bar;
        };

        return $foo("bar");
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*return \\\$foo\\(\"bar\"\\).*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Closure with Use
//------------------------------------------------------------------------------
    public function test_closure_with_use() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function execute() {
        return function () use ($foo, &$bar) {
            $baz = "foobar";
            $bar = $baz;
        };
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*return \\(function\\s*\\(\\\$foo\\)\\s*\\{.*/ms", $result);
        $this->assertRegExp("/.*var \\\$baz = \"foobar\";.*/ms", $result);
        $this->assertRegExp("/.*\\\$bar = \\\$baz;.*/ms", $result);
        $this->assertRegExp("/.*\\}\\)\\(\\\$foo\\);.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Use Public Constructor
//------------------------------------------------------------------------------
    public function test_use_public_constructor() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;
use Lechimp\PHP2JS\JS\API\Window;

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

        $this->assertRegExp("/.*this.foo = \\\$foo.*/ms", $result);
        $this->assertRegExp("/.*TestScript\\s+=\\s+function\\(\\\$foo\\)*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Inject Script Dependencies
//------------------------------------------------------------------------------
    public function test_inject_script_dependencies() {

        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;
use Lechimp\PHP2JS\JS\API\Window;

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

        $this->assertRegExp("/.*this.window = \\\$window.*/ms", $result);
        $this->assertRegExp("/.*TestScript\\s+=\\s+function\\(\\\$window\\)*/ms", $result);
        $this->assertRegExp("/.*\\(new php2js.TestScript\\(\\(new php2js.WindowImpl\\(\\)\\)\\)\\);.*/", $result);
        $this->assertRegExp("/.*php2js.WindowImpl = function\\(\\).*/", $result);
    }


//------------------------------------------------------------------------------
// TEST: Annotate Dependencies of Script Class
//------------------------------------------------------------------------------
    public function test_annotate_dependencies_of_script_class() {
        $ast = $this->parser->parse(<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;
use Lechimp\PHP2JS\JS\API\Window;
use Lechimp\PHP2JS\JS\API\Document;

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

        $n = new NodeVisitor\NameResolver();
        $a = new FilePass\AnnotateFullyQualifiedName();
        $s = new FilePass\AnnotateScriptDependencies();
        $t = new NodeTraverser();
        $t->addVisitor($n);
        $t->addVisitor($a);
        $t->addVisitor($s);
        $ast = $t->traverse($ast);

        array_shift($ast); // USE-Statement
        array_shift($ast); // USE-Statement
        array_shift($ast); // USE-Statement
        $my_class = array_shift($ast);

        $this->assertTrue($my_class->hasAttribute(Compiler\FilePass\AnnotateScriptDependencies::ATTR));
        $this->assertEquals(
            [JS\API\Window::class, JS\API\Document::class],
            $my_class->getAttribute(Compiler\FilePass\AnnotateScriptDependencies::ATTR)
        );
    }

//------------------------------------------------------------------------------
// TEST: Compile True and False
//------------------------------------------------------------------------------
    public function test_compile_true_and_false() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foo;
    protected $bar;

    public function __construct() {
    }

    public function execute() {
        $this->foo = true;
        $this->bar = false;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this.foo = true.*/ms", $result);
        $this->assertRegExp("/.*this.bar = false.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile String Concat
//------------------------------------------------------------------------------
    public function test_compile_string_concat() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foobar;

    public function __construct() {
    }

    public function execute() {
        $this->foobar = "foo"."bar"."baz";
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this.foobar = \"foo\".concat\\(\"bar\"\\).concat\\(\"baz\"\\).*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Ternary Operator
//------------------------------------------------------------------------------
    public function test_compile_ternary_operator() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foobar;

    public function __construct() {
    }

    public function execute() {
        $this->foobar = true ? "true" : "false";
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this.foobar = \\(true\\) \\? \\(\"true\"\\) : \\(\"false\"\\).*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Logical Operators
//------------------------------------------------------------------------------
    public function test_compile_logical_operators() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foobar;

    public function __construct() {
    }

    public function execute() {
        $this->foobar = true && false || false;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this.foobar = \\(\\(true\\) [&][&] \\(false\\)\\) [|][|] \\(false\\).*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Foreach
//------------------------------------------------------------------------------
    public function test_compile_foreach() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foobar;

    public function __construct() {
    }

    public function execute() {
        $foo = "foo";
        foreach ($this->foobar as $value) {
            echo $value;
            $foo = "bar";
        }
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this.foobar.foreach\\(function\\(\\\$value\\) \\{\\s+process\\.stdout\\.write\\(\\\$value\\);\\s+.*/ms", $result);
        $this->assertRegExp("/.*^\s+\\\$foo = \"bar\";.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Array with key
//------------------------------------------------------------------------------
    public function test_compile_foreach_with_key() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foobar;

    public function __construct() {
    }

    public function execute() {
        $this->foobar = ["one" => 1];
        $this->foobar["two"] = 2;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*\\(new php2js.PhpArray\\(\\)\\)\\.setItemAt\\(\"one\", 1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.foobar\\.setItemAt\\(\"two\", 2\\);.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Not
//------------------------------------------------------------------------------
    public function test_compile_not() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $foobar;

    public function __construct() {
    }

    public function execute() {
        $this->foobar = !$this->foobar;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this.foobar = [!]\\(this.foobar\\).*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Number
//------------------------------------------------------------------------------
    public function test_compile_number() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function __construct() {
    }

    public function execute() {
        echo 1;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*process\\.stdout\\.write\\(1\\).*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Exit
//------------------------------------------------------------------------------
    public function test_compile_exit() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function __construct() {
    }

    public function execute() {
        exit(1);
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*process.exit\\(1\\).*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile New Array
//------------------------------------------------------------------------------
    public function test_compile_newArray() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $an_array;

    public function __construct() {
    }

    public function execute() {
        $this->an_array = ["one", "two", "three"];
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*\\(new php2js\\.PhpArray\\(\\)\\)\\.push\\(\"one\"\\)\\.push\\(\"two\"\\)\\.push\\(\"three\"\\);.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile escaped characters
//------------------------------------------------------------------------------
    public function test_compile_escaped_chars() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $n;

    public function __construct() {
    }

    public function execute() {
        $this->n = "\n\\\t\"";
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this\\.n = \"\\\\n\\\\\\\\\\\\t\\\\\"\";.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile Assign Operators
//
// This also tests the compilation of (most) binary operators.
//------------------------------------------------------------------------------
    public function test_compile_assign_operators() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    protected $n = 0;

    public function __construct() {
    }

    public function execute() {
        $this->n += 1;
        $this->n -= 1;
        $this->n *= 1;
        $this->n /= 1;
        $this->n %= 1;
        $this->n &= 1;
        $this->n |= 1;
        $this->n ^= 1;
        $this->n **= 1;
        $this->n <<= 1;
        $this->n >>= 1;
        $this->n .= 1;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) \\+ \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) - \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) \\* \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) \\/ \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) % \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) & \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) [|] \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) \\^ \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) \\*\\* \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) << \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = \\(this\\.n\\) >> \\(1\\);.*/ms", $result);
        $this->assertRegExp("/.*this\\.n = this\\.n\\.concat\\(1\\);.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile isset
//------------------------------------------------------------------------------
    public function test_compile_isset() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function __construct() {
    }

    public function execute() {
        isset($this->n);
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*\\(\\(typeof \\(this\\.n\\)\\) !== \\(\"undefined\"\\)\\) && \\(\\(this\\.n\\) !== \\(null\\)\\);.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile class-constants
//------------------------------------------------------------------------------
    public function test_compile_class_constant() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    const CONSTANT = "CONSTANT";
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*php2js\\.TestScript\\.__constants = {.*/ms", $result);
        $this->assertRegExp("/.*\"CONSTANT\" : \"CONSTANT\".*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Compile fetching of class-constants
//------------------------------------------------------------------------------
    public function test_compile_class_constant_fetch() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function foo() {
        return OtherClass::BAR;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*php2js.OtherClass.__constants.BAR.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Use objects as namespace
//------------------------------------------------------------------------------
    public function test_use_objects_as_namespace() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*php2js\\.TestScript = .*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Splat-operator
//------------------------------------------------------------------------------
    public function test_splat_operator() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function method($arg, ...$args) {
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*\\\$args = Array\\.prototype\\.slice\\.call\\(arguments, 1\\).toPHPArray().*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Try/catch/finally
//------------------------------------------------------------------------------
    public function test_try_catch_finally() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function method() {
        try {
            return 0;
        }
        catch (\InvalidArgumentException $e) {
            return 0;
        }
        finally {
            return 0;
        }
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*try \\{.*/ms", $result);
        $this->assertRegExp("/.*if \\(.*__instanceof\\(php2js[.]InvalidArgumentException.*/ms", $result);
        $this->assertRegExp("/.*throw.*/ms", $result);
        $this->assertRegExp("/.finally.*/ms", $result);
    }

//------------------------------------------------------------------------------
// TEST: Return void
//------------------------------------------------------------------------------
    public function test_return_void() {
        $filename = tempnam("/tmp", "php.js");
        file_put_contents($filename,<<<'PHP'
<?php

use Lechimp\PHP2JS\JS\Script;

class TestScript implements Script {
    public function method() {
        return;
    }
}
PHP
        );

        $result = $this->real_compiler->compile($filename);

        $this->assertRegExp("/.*return.*/ms", $result);
    }

}
