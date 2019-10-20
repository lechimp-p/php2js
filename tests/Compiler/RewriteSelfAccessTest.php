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

use Lechimp\PHP2JS\Compiler\Visitor\RewriteSelfAccess;
use PhpParser\ParserFactory;
use PhpParser\BuilderFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeDumper;

class RewriteSelfAccessTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->builder = new BuilderFactory;
        $this->dumper = new NodeDumper;
    }

    public function test_rewrites_self_constant_access() {
        $before = $this->parser->parse(<<<'PHP'
<?php
class Foo {
    public function bar() {
        self::MY_CONSTANT;
    }
}
PHP
        );
        $expected = $this->parser->parse(<<<'PHP'
<?php
class Foo {
    public function bar() {
        Foo::MY_CONSTANT;
    }
}
PHP
        );

        $t = new NodeTraverser();
        $t->addVisitor(new NodeVisitor\NameResolver);
        $after = $t->traverse($before);

        $t = new NodeTraverser();
        $t->addVisitor(new RewriteSelfAccess);
        $after = $t->traverse($after);

        $this->assertEquals($this->dumper->dump($expected), $this->dumper->dump($after));
    }
}
