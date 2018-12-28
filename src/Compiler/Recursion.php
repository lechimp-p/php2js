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
        switch (get_class($n)) {
            case Node\Scalar\String_::class:
            case Node\Identifier::class:
            case Node\Name::class:
            case Node\Name\FullyQualified::class:
            case Node\Name\Relative::class:
                break;
            case Node\Stmt\Echo_::class:
                $n = clone $n;
                $n->exprs = array_map(function($e) use ($f) {
                    return $f($e);
                }, $n->exprs);
                break;
            case Node\Stmt\Class_::class:
                $n = clone $n;
                $n->name = $f($n->name);
                if ($n->extends !== null) {
                    $n->extends = $f($n->extends);
                }
                $n->implements = array_map(function($i) use ($f) {
                    return $f($i);
                }, $n->implements);
                $n->stmts = array_map(function($s) use ($f) {
                    return $f($s);
                }, $n->stmts);
                break;
            case Node\Stmt\ClassMethod::class:
                $n = clone $n;
                $n->name = Recursion::fmap($n->name, $f);
                $n->params = array_map(function($p) use ($f) {
                    return $f($p);
                }, $n->params);
                if ($n->returnType !== null) {
                    $n->returnType = $f($n->returnType);
                }
                $n->stmts = array_map(function($s) use ($f) {
                    return $f($s);
                }, $n->stmts);
                break;
            default:
                throw new \LogicException("Unknown class '".get_class($n)."'");
        }
        return $n;
    }
}

