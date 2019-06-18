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

namespace Lechimp\PHP2JS\JS_Tests\Class_;

class A11 {
    protected $i;

    public function __construct(int $i) {
        $this->i = $i; 
    }

    public function foo(A11 $other) {
        return $other->i;
    }
}

class ExtendsGivesAccessToProtectedOfOtherObjectsTest {
    public function name() {
        return "Class_\\ExtendsGivesAccessToProtectedOfOtherObjectsTest";
    }

    public function perform() {
        $a1 = new A11(0);
        $a2 = new A11(23);

        return $a1->foo($a2) === 23;
    }
}
