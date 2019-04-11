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

namespace Lechimp\PHP2JS\Test\Compiler\Dependency;

use Lechimp\PHP2JS\Compiler\Dependency\Locator;
use Lechimp\PHP2JS\Compiler\Dependency\LocateByList;

class LocateByListTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
        $this->list = [
            "one" => "1",
            "two" => "2"
        ];

        $this->is_internal = false;

        $this->inner = $this->createMock(Locator::class);
        $this->locate_by_list= new LocateByList(
            $this->inner,
            $this->is_internal,
            $this->list
        );
    }

    public function test_isInternalDependency_delegates_unknown() {
        $filename = "FILENAME";
        $expected = false;

        $this->inner
            ->expects($this->once())
            ->method("isInternalDependency")
            ->with($filename)
            ->willReturn($expected);

        $results = $this->locate_by_list->isInternalDependency($filename);

        $this->assertEquals($expected, $results);
    }

    public function test_getFilenameOfDependency_delegates_unknown() {
        $filename = "FILENAME";
        $expected = "RESULT";

        $this->inner
            ->expects($this->once())
            ->method("getFilenameOfDependency")
            ->with($filename)
            ->willReturn($expected);

        $results = $this->locate_by_list->getFilenameOfDependency($filename);

        $this->assertEquals($expected, $results);
    }

    public function test_isInternalDependency_uses_parameters_for_known() {
        $this->assertEquals(
            $this->is_internal,
            $this->locate_by_list->isInternalDependency("one")
        ); 

        $this->assertEquals(
            $this->is_internal,
            $this->locate_by_list->isInternalDependency("two")
        ); 
    }

    public function test_getFilenameOfDependency_uses_list() {
        $this->assertEquals(
            $this->list["one"],
            $this->locate_by_list->getFilenameOfDependency("one")
        ); 

        $this->assertEquals(
            $this->list["two"],
            $this->locate_by_list->getFilenameOfDependency("two")
        ); 
    }
}
