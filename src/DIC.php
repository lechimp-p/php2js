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

namespace Lechimp\PHP2JS;

use Pimple\Container;
use PhpParser\ParserFactory;

class DIC extends Container {
    public function __construct() {
        parent::__construct();

        $this["app"] = function($c) {
            return new App\App([
                $c["command.compile"]
            ]);
        };

        $this["command.compile"] = function($c) {
            return new App\CompileCommand(
                $c["compiler.factory"]
            );
        };

        $this["compiler.factory"] = function($c) {
            return new Compiler\Factory(
                $c["compiler.parser"],
                $c["js.factory"],
                $c["js.printer"]
            );
        };

        $this["compiler.parser"] = function($c) {
            return (new ParserFactory)
                ->create(ParserFactory::PREFER_PHP7);
        };

        $this["js.factory"] = function($c) {
            return new JS\AST\Factory();
        };

        $this["js.printer"] = function($c) {
            return new JS\AST\Printer();
        };
    }
}
