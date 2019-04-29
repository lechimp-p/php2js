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

namespace Lechimp\PHP2JS\Test\Compiler\Dependency;

use Lechimp\PHP2JS\Compiler\Dependency\Locator;
use Lechimp\PHP2JS\Compiler\Dependency\DelegatorLocator;

class DelegatorLocatorTest extends \PHPUnit\Framework\TestCase {
    public function setUp() : void {
        $this->inner = $this->createMock(Locator::class);
        $this->delegator = new DelegatorLocator($this->inner);
    }

    public function test_isInternalDependency_delegates() {
        $filename = "FILENAME";
        $expected = false;

        $this->inner
            ->expects($this->once())
            ->method("isInternalDependency")
            ->with($filename)
            ->willReturn($expected);

        $results = $this->delegator->isInternalDependency($filename);

        $this->assertEquals($expected, $results);
    }

    public function test_getFilenameOfDependency_delegates() {
        $filename = "FILENAME";
        $expected = "RESULT";

        $this->inner
            ->expects($this->once())
            ->method("getFilenameOfDependency")
            ->with($filename)
            ->willReturn($expected);

        $results = $this->delegator->getFilenameOfDependency($filename);

        $this->assertEquals($expected, $results);
    }
}
