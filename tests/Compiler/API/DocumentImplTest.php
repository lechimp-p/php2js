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

namespace Lechimp\PHP_JS\Test\Compiler\API;

use Lechimp\PHP_JS\JS\API\Document;
use Lechimp\PHP_JS\Compiler\ClassCompiler;
use Lechimp\PHP_JS\Compiler\Compiler;
use Lechimp\PHP_JS\Compiler\AnnotateFullyQualifiedName;
use Lechimp\PHP_JS\Compiler\RemoveTypeHints;
use Lechimp\PHP_JS\JS;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class DocumentImplTest extends \PHPUnit\Framework\TestCase {
    const LOCATION = __DIR__."/../../../src/Compiler/API/DocumentImpl.php";

    public function test_smoke() {
        require_once(self::LOCATION);

        $impl = new \DocumentImpl();

        $this->assertInstanceOf(Document::class, $impl);
    }

    public function test_compile() {
        $js = new JS\AST\Factory();
        $compiler = new ClassCompiler($js);

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $t = new NodeTraverser();
        $t->addVisitor(new AnnotateFullyQualifiedName());
        $t->addVisitor(new RemoveTypeHints());
        $ast = $t->traverse($parser->parse(file_get_contents(self::LOCATION)));
        $ast[2]->setAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME, "\DocumentImpl");

        $result = $compiler->compile($ast[2]);

        $this->assertInstanceOf(JS\AST\Node::class, $result);
    }
}
