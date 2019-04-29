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

namespace Lechimp\PHP2JS\Test\JS\AST;

use Lechimp\PHP2JS\JS\AST\Factory;
use Lechimp\PHP2JS\JS\AST\Printer;

class PrinterTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
        $this->factory = new Factory();
        $this->printer = new Printer();
    }

    public function test_print_string() {
        $ast = $this->factory->literal("foo");

        $result = $this->printer->print($ast);

        $this->assertEquals("\"foo\"", $result); 
    }

    public function test_print_int() {
        $ast = $this->factory->literal(1);

        $result = $this->printer->print($ast);

        $this->assertEquals(1, $result);
    }

    public function test_print_identifier() {
        $ast = $this->factory->identifier("foo");

        $result = $this->printer->print($ast);

        $this->assertEquals("foo", $result); 
    }

    public function test_print_propertyOf_identifier() {
        $ast = $this->factory->propertyOf(
            $this->factory->identifier("foo"),
            $this->factory->identifier("bar")
        );

        $result = $this->printer->print($ast);

        $this->assertEquals("foo.bar", $result); 
    }

    public function test_print_propertyOf_string() {
        $ast = $this->factory->propertyOf(
            $this->factory->identifier("foo"),
            $this->factory->literal("bar")
        );

        $result = $this->printer->print($ast);

        $this->assertEquals("foo[\"bar\"]", $result); 
    }

    public function test_print_call_no_param() {
        $ast = $this->factory->call(
            $this->factory->identifier("foo")
        );

        $result = $this->printer->print($ast);

        $this->assertEquals("foo()", $result); 
    }

    public function test_print_call_two_params() {
        $ast = $this->factory->call(
            $this->factory->identifier("foo"),
            $this->factory->identifier("bar"),
            $this->factory->identifier("baz")
        );

        $result = $this->printer->print($ast);

        $this->assertEquals("foo(bar, baz)", $result); 
    }

    public function test_print_function() {
        $f = $this->factory;
        $ast = $f->function_(
            [$f->identifier("a"), $f->identifier("b")],
            $f->block(
                $this->factory->call(
                    $this->factory->identifier("foo"),
                    $this->factory->identifier("a")
                ),
                $this->factory->call(
                    $this->factory->identifier("bar"),
                    $this->factory->identifier("b")
                )
            )
        );

        $result = $this->printer->print($ast);

        $expected = <<<JS
function(a, b) {
    foo(a);
    bar(b);
}
JS;
        $this->assertEquals($expected, $result);
    }

    public function test_print_directly_called_function() {
        $f = $this->factory;
        $ast = $f->call(
            $f->function_(
                [],
                $f->block(
                    $this->factory->call(
                        $this->factory->identifier("foo"),
                        $this->factory->identifier("a")
                    )
                )
            )
        );

        $result = $this->printer->print($ast);

        $expected = <<<JS
(function() {
    foo(a);
})()
JS;
        $this->assertEquals($expected, $result);
    }

    public function test_print_assign_var() {
        $f = $this->factory;

        $ast = $f->assignVar($f->identifier("foo"), $f->literal("bar"));

        $result = $this->printer->print($ast);
        $expected = "var foo = \"bar\"";

        $this->assertEquals($expected, $result);
    }

    public function test_print_assign() {
        $f = $this->factory;

        $ast = $f->assign(
            $f->propertyOf($f->identifier("foo"), $f->identifier("bar")),
            $f->literal("baz")
        );

        $result = $this->printer->print($ast);
        $expected = "foo.bar = \"baz\"";

        $this->assertEquals($expected, $result);
    }

    public function test_print_object() {
        $f = $this->factory;

        $ast = $f->object_([
            "foo" => $f->function_(
                [$f->identifier("a"), $f->identifier("b")],
                $f->block(
                    $this->factory->call(
                        $this->factory->identifier("foo"),
                        $this->factory->identifier("a")
                    ),
                    $this->factory->call(
                        $this->factory->identifier("bar"),
                        $this->factory->identifier("b")
                    )
                )
            ),
            "bar" => $f->literal("bar")
        ]);

        $result = $this->printer->print($ast);

        $expected = <<<JS
{
    "foo" : function(a, b) {
        foo(a);
        bar(b);
    },
    "bar" : "bar"
}
JS;
        $this->assertEquals($expected, $result);
    }

    public function test_print_return() {
        $f = $this->factory;

        $ast = $f->return_($f->literal("foobar"));

        $result = $this->printer->print($ast);
        $expected = "return \"foobar\"";

        $this->assertEquals($expected, $result);
    }

    public function test_print_undefined() {
        $f = $this->factory;

        $ast = $f->undefined();

        $result = $this->printer->print($ast);
        $expected = "undefined";

        $this->assertEquals($expected, $result);
    }

    public function test_print_null() {
        $f = $this->factory;

        $ast = $f->null_();

        $result = $this->printer->print($ast);
        $expected = "null";

        $this->assertEquals($expected, $result);
    }

    public function test_print_identical() {
        $f = $this->factory;

        $ast = $f->identical($f->null_(), $f->undefined());

        $result = $this->printer->print($ast);
        $expected = "(null) === (undefined)";

        $this->assertEquals($expected, $result);
    }

    public function test_print_if() {
        $f = $this->factory;

        $ast = $f->if_($f->null_(), $f->block(
            $f->call($f->identifier("foo"))
        ));

        $result = $this->printer->print($ast);
        $expected = "if (null) {\n    foo();\n}";

        $this->assertEquals($expected, $result);
    }

    public function test_print_ternary_operator() {
        $f = $this->factory;

        $ast = $f->ternary(
            $f->identifier("true"),
            $f->literal("true"),
            $f->literal("false")
        );

        $result = $this->printer->print($ast);
        $expected = "(true) ? (\"true\") : (\"false\")";

        $this->assertEquals($expected, $result);
    }

    public function test_print_not() {
        $f = $this->factory;

        $ast = $f->not(
            $f->identifier("true")
        );

        $result = $this->printer->print($ast);
        $expected = "!(true)";

        $this->assertEquals($expected, $result);
    }

    public function test_print_new() {
        $f = $this->factory;

        $ast = $f->new_(
            $f->identifier("ClassName"),
            $f->identifier("a"),
            $f->identifier("b")
        );

        $result = $this->printer->print($ast);
        $expected = "new ClassName(a, b)";

        $this->assertEquals($expected, $result);
    }

    public function test_while() {
        $f = $this->factory;

        $ast = $f->while_(
            $f->literal(1),
            $f->block(
                $f->call($f->identifier("foo"))
            )
        );

        $result = $this->printer->print($ast);
        $expected  = "while (1) {\n    foo();\n}";

        $this->assertEquals($expected, $result);
    }
}
