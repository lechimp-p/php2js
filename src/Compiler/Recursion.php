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
 * Recursion schemes on PHP-AST.
 */
class Recursion {
    /**
     * @return mixed
     */
    static public function cata(Node $n, callable $f) {
        return $f(self::fmap($n, function($v) use ($f) {
            return self::cata($v, $f);
        }));
    }

    /**
     * @return mixed
     */
    static public function para(Node $n, callable $f) {
        return $f($n, self::fmap($n, function($v) use ($f) {
            return self::para($v, $f);
        }));
    }

    /**
     * @return Node of the same type that was passed in.
     */
    static public function fmap(Node $n, callable $f) {
        $n = clone $n;
        foreach ($n->getSubNodeNames() as $name) {
            $sub = $n->$name;
            if ($sub instanceof Node) {
                $n->$name = $f($sub);
            }
            elseif(is_array($sub)) {
                $n->$name = array_map(function($s) use ($f) {
                    if (!($s instanceof Node)) {
                        return $s;
                    }
                    return $f($s);
                }, $sub);
            }
        }
        return $n;
    }
}

