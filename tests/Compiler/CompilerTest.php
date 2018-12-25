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
use PhpParser\ParserFactory;
use PhpParser\BuilderFactory;

class CompilerTest extends \PHPUnit\Framework\TestCase {
    public function setUp() {
        $this->js_factory = new JS\Factory();
        $this->builder = new BuilderFactory;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->compiler = new Compiler\Compiler(
            $this->parser,
            $this->js_factory,
            new JS\Printer
        );
    }

    public function test_smoke() {
        $result = $this->compiler->compile(<<<PHP
<?php
echo "Hello World!";
PHP
);

        $this->assertEquals("console.log(\"Hello World!\");", trim($result));
    }

    public function test_compile_literal_string() {
        $id = uniqid();
        $ast = $this->builder->val($id);

        $result = $this->compiler->compileAST($ast);

        $f = $this->js_factory;
        $expected = $f->block($f->literal($id));
        $this->assertEquals($expected, $result);
    }

    public function test_compile_echo() {
        $id = uniqid();
        $ast = new \PhpParser\Node\Stmt\Echo_([$this->builder->val($id)]);

        $result = $this->compiler->compileAST($ast);

        $f = $this->js_factory;
        $expected = $f->block($f->call(
            $f->propertyOf($f->identifier("console"), $f->identifier("log")),
            $f->literal($id)
        ));
        $this->assertEquals($expected, $result);
    }
}
