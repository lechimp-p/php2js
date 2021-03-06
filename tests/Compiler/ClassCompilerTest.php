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

use Lechimp\PHP2JS\Compiler\BuildInFunctionsCompiler;
use Lechimp\PHP2JS\Compiler\ClassCompiler;
use Lechimp\PHP2JS\JS;
use PhpParser\BuilderFactory;
use PhpParser\ParserFactory;
use PhpParser\Node as PhpNode;

class ClassCompilerForTest extends ClassCompiler {
    public function _compileRecursive($n) {
        return $this->compileRecursive($n);
    }
}

class ClassCompilerTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
        $this->builder = new BuilderFactory;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $this->js = new JS\AST\Factory();
        $build_in_compiler = new BuildInFunctionsCompiler($this->js);
        $this->compiler = new ClassCompilerForTest($this->js, $build_in_compiler);
    }

    public function test_compile_null() {
        $node = $this->parser->parse("<?php null;")[0];

        $result = $this->compiler->_compileRecursive($node);

        $this->assertEquals($this->js->identifier("null"),  $result);
    }
}
