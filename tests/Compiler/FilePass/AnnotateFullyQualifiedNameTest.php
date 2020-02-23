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

namespace Lechimp\PHP2JS\Test\Compiler\FilePass;

use Lechimp\PHP2JS\Compiler\FilePass;
use Lechimp\PHP2JS\Test\Compiler\FilePassTest;
use PhpParser\Node;

class AnnotateFullyQualifiedNameTest extends FilePassTest {
    protected function getFilePass() : FilePass {
        return new FilePass\AnnotateFullyQualifiedName();
    }

    public function test_annotates_class_name_at_declaration() {
        $stmts = $this->applyFilePassTo(<<<'PHP'
<?php

namespace Foo;

class Bar {
}

PHP
        );

        $node_finder = $this->getNodeFinder();
        $classes = $node_finder->findInstanceOf($stmts, Node\Stmt\Class_::class);

        $this->assertCount(1, $classes);
        list($class) = $classes;

        $this->assertTrue($class->hasAttribute(FilePass\AnnotateFullyQualifiedName::ATTR));
        $this->assertEquals("Foo\\Bar", $class->getAttribute(FilePass\AnnotateFullyQualifiedName::ATTR));
    }

    public function test_annotates_interface_name_at_declaration() {
        $stmts = $this->applyFilePassTo(<<<'PHP'
<?php

namespace Foo;

interface Bar {
}

PHP
        );

        $node_finder = $this->getNodeFinder();
        $classes = $node_finder->findInstanceOf($stmts, Node\Stmt\Interface_::class);

        $this->assertCount(1, $classes);
        list($class) = $classes;

        $this->assertTrue($class->hasAttribute(FilePass\AnnotateFullyQualifiedName::ATTR));
        $this->assertEquals("Foo\\Bar", $class->getAttribute(FilePass\AnnotateFullyQualifiedName::ATTR));
    }
}

