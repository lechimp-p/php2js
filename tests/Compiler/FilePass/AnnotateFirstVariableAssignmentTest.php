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

class AnnotateFirstVariableAssignmentTest extends FilePassTest {
    protected function getFilePass() : FilePass {
        return new FilePass\AnnotateFirstVariableAssignment();
    }

    public function test_annotates_first_assignment_only() {
        $stmts = $this->applyFilePassTo(<<<'PHP'
<?php

function foo() {
    $bar = 1;
    $bar = 2;
}

PHP
        );

        $node_finder = $this->getNodeFinder();
        $assignments = $node_finder->findInstanceOf($stmts, Node\Expr\Assign::class);

        $this->assertCount(2, $assignments);
        list($first, $second) = $assignments;

        $this->assertTrue($first->hasAttribute(FilePass\AnnotateFirstVariableAssignment::ATTR));
        $this->assertTrue($first->getAttribute(FilePass\AnnotateFirstVariableAssignment::ATTR));
        $this->assertTrue($second->hasAttribute(FilePass\AnnotateFirstVariableAssignment::ATTR));
        $this->assertFalse($second->getAttribute(FilePass\AnnotateFirstVariableAssignment::ATTR));
    }
}

