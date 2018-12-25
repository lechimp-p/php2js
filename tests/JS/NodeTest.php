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

namespace Lechimp\PHP_JS\Test\JS;

use Lechimp\PHP_JS\JS;

class LNil extends JS\Node {
    public function fmap(callable $f) {
        return $this;
    }
}

class LCon extends JS\Node {
    public $value = null;
    public $next = null;
    function __construct($v, $n) {
        $this->value = $v;
        $this->next = $n;
    }

    public function fmap(callable $f) {
        return new LCon(
            $this->value,
            $f($this->next)
        );
    }
}

class NodeTest extends \PHPUnit\Framework\TestCase {
    public function setUp() {
        $this->list = new LCon(
            3,
            new LCon(
                7,
                new LNil
            )
        );
    }

    public function test_cata() {
        $result = $this->list->cata(function($v) {
            if ($v instanceof LNil) {
                return 1;
            }

            return $v->value * $v->next;
        }); 

        $this->assertEquals(21, $result);
    }
}
