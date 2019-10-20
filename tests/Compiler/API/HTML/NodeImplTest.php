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

namespace Lechimp\PHP2JS\Test\Compiler\API\HTML;

use Lechimp\PHP2JS\JS\API\HTML;
use Lechimp\PHP2JS\JS\API\Document;
use Lechimp\PHP2JS\Compiler\BuildInFunctionsCompiler;
use Lechimp\PHP2JS\Compiler\ClassCompiler;
use Lechimp\PHP2JS\Compiler\Visitor;
use Lechimp\PHP2JS\Compiler\Compiler;
use Lechimp\PHP2JS\Compiler\AnnotateFullyQualifiedName;
use Lechimp\PHP2JS\Compiler\AnnotateFirstVariableAssignment;
use Lechimp\PHP2JS\Compiler\AnnotateUsageVisibility;
use Lechimp\PHP2JS\Compiler\AnnotateVisibility;
use Lechimp\PHP2JS\Compiler\RemoveTypeHints;
use Lechimp\PHP2JS\Compiler\Codebase;
use Lechimp\PHP2JS\JS;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class NodeImplTest extends \PHPUnit\Framework\TestCase {
    const LOCATION = __DIR__."/../../../../src/Compiler/API/HTML/NodeImpl.php";
    const NODE_LOCATION = __DIR__."/../../../../src/Compiler/API/HTML/NodeImpl.php";

    public function test_smoke() {
        require_once(self::NODE_LOCATION);
        require_once(self::LOCATION);

        $impl = new \HTML\NodeImpl(null);

        $this->assertInstanceOf(HTML\Node::class, $impl);
    }

    public function test_compile() {
        $js = new JS\AST\Factory();
        $build_in_compiler = new BuildInFunctionsCompiler($js);
        $compiler = new ClassCompiler($js, $build_in_compiler);
        $codebase = $this->createMock(Codebase::class);

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $t = new NodeTraverser();
        $t->addVisitor(new Visitor\AnnotateVisibility());
        $t->addVisitor(new Visitor\AnnotateFullyQualifiedName());
        $t->addVisitor(new Visitor\AnnotateFirstVariableAssignment());
        $t->addVisitor(new Visitor\RemoveTypeHints());
        $t->addVisitor(new NameResolver());
        $ast = $t->traverse($parser->parse(file_get_contents(self::LOCATION)));
        $ast[1]->stmts[1]->setAttribute(Visitor\AnnotateFullyQualifiedName::ATTR, "\HTML\NodeImpl");

        $result = $compiler->compile($ast[1]->stmts[1]);

        $this->assertInstanceOf(JS\AST\Node::class, $result);
    }
}
