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

namespace Lechimp\PHP2JS\Compiler;

use PhpParser\Node as PhpNode;

/**
 * Compiles control structures to JS
 */
trait ScalarCompiler {
    public function compile_Scalar_String_(PhpNode $n) {
        return $this->js_factory->literal(strtr ($n->value, self::$string_replacements));
    }

    public function compile_Scalar_LNumber(PhpNode $n) {
        if ($n->getAttribute("kind") != 10) {
            throw new \LogicException("Cannot compile LNumbers with kind != 10");
        }
        return $this->js_factory->literal($n->value);
    }
}
