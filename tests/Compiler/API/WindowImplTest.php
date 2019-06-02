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

namespace Lechimp\PHP2JS\Test\Compiler\API;

use Lechimp\PHP2JS\JS\API\Window;
use Lechimp\PHP2JS\Compiler\BuildInFunctionsCompiler;
use Lechimp\PHP2JS\Compiler\ClassCompiler;
use Lechimp\PHP2JS\Compiler\Compiler;
use Lechimp\PHP2JS\Compiler\AnnotateFullyQualifiedName;
use Lechimp\PHP2JS\Compiler\AnnotateFirstVariableAssignment;
use Lechimp\PHP2JS\Compiler\AnnotateVisibility;
use Lechimp\PHP2JS\Compiler\RemoveTypeHints;
use Lechimp\PHP2JS\JS;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class WindowImplTest extends \PHPUnit\Framework\TestCase {
    const LOCATION = __DIR__."/../../../src/Compiler/API/WindowImpl.php";

    public function test_smoke() {
        require_once(self::LOCATION);

        $impl = new \WindowImpl();

        $this->assertInstanceOf(Window::class, $impl);
    }


    public function compiled() {
        $js = new JS\AST\Factory();
        $build_in_compiler = new BuildInFunctionsCompiler($js);
        $compiler = new ClassCompiler($js, $build_in_compiler);

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $t = new NodeTraverser();
        $t->addVisitor(new AnnotateVisibility());
        $t->addVisitor(new AnnotateFullyQualifiedName());
        $t->addVisitor(new AnnotateFirstVariableAssignment());
        $t->addVisitor(new RemoveTypeHints());
        $t->addVisitor(new NameResolver());
        $ast = $t->traverse($parser->parse(file_get_contents(self::LOCATION)));
        $ast[2]->setAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME, "\WindowImpl");

        return $compiler->compile($ast[2]);
    }

    public function test_compile() {
        $result = $this->compiled();

        $this->assertInstanceOf(JS\AST\Node::class, $result);
    }

    public function test_use_native_document() {
        $result = (new JS\AST\Printer)->print($this->compiled());

        $this->assertNotRegExp("/.*\\\$window.*/", $result);
        $this->assertRegExp("/.*\\s+window.*/", $result);
    }
}
