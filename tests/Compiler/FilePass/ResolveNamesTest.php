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

class ResolveNamesTest extends FilePassTest {
    protected function getFilePass() : FilePass {
        return new FilePass\ResolveNames();
    }

    public function test_resolved() {
        $before = <<<'PHP'
<?php

use Foo\Bar\Baz;

class MyClass extends Baz {
    public function foo(Baz $b) : Baz {
        Baz::foo();
        new Baz();
    }
}
PHP;

        $expected = <<<'PHP'
<?php

use Foo\Bar\Baz;

class MyClass extends \Foo\Bar\Baz {
    public function foo(\Foo\Bar\Baz $b) : \Foo\Bar\Baz {
        \Foo\Bar\Baz::foo();
        new \Foo\Bar\Baz();
    }
}
PHP;

        $this->assertPHPEquals(
            $expected,
            $this->applyFilePassTo($before)
        );
    }
}

