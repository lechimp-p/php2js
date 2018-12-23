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

namespace Lechimp\PHP_JS\Compiler;

use PhpParser\Node;

/**
 * Recursion schemas on PHP-AST.
 */
class Recursion {
    /**
     * @return mixed
     */
    static public function cata(Node $n, callable $f) {
        switch (get_class($n)) {
            case Node\Scalar\String_::class:
                break;
            case Node\Stmt\Echo_::class:
                $n = clone $n;
                $n->exprs = array_map(function($e) use ($f) {
                    return Recursion::cata($e, $f);
                }, $n->exprs);
                break;
            default:
                throw new \LogicException("Unknown class '".get_class($n)."'");
        }

        return $f($n); 
    }
}

