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

namespace Lechimp\PHP_JS\Test\JS\AST;

use Lechimp\PHP_JS\JS\AST\Factory;
use Lechimp\PHP_JS\JS\AST\Printer;

class PrinterTest extends \PHPUnit\Framework\TestCase {
    public function setUp() {
        $this->factory = new Factory();
        $this->printer = new Printer();
    }

    public function test_print_string() {
        $ast = $this->factory->literal("foo");

        $result = $this->printer->print($ast);

        $this->assertEquals("\"foo\"", $result); 
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
}
