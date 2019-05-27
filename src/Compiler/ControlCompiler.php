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
trait ControlCompiler {
    public function compile_Stmt_If_(PhpNode $n) {
        $f = $this->js_factory;
        return $f->if_(
            $n->cond,
            $f->block(...$n->stmts),
            $n->else ? $f->block(...$n->else) : null
        );
    }

    public function compile_Stmt_Else_(PhpNode $n) {
        return $n->stmts;
    }

    public function compile_Stmt_TryCatch(PhpNode $n) {
        $js = $this->js_factory;
        $var = $js->identifier("e");
        $catch = $js->block($js->throw_($var));
        while(count($n->catches) > 0) {
            list($p, $b) = array_pop($n->catches);
            $catch = $js->block($js->if_($p, $b, $catch));
        }
        return $js->try_($js->block(...$n->stmts), $var, $catch, $n->finally);
    }

    public function compile_Stmt_Catch_(PhpNode $n) {
        $js = $this->js_factory;
        $var = $js->identifier("e");
        return [
            (count($n->types) > 0)
                ? $js->or_(...array_map(function($t) use ($js, $var) {
                    return $js->call(
                        $js->propertyOf($var, $js->identifier("__instanceof")),
                        $this->compileClassName($t->value())
                    );
                }, $n->types))
                : $js->identifier(true),
            $js->block($js->call($js->function_([$n->var], $js->block(...$n->stmts)), $var))
        ];
    }

    public function compile_Stmt_Finally_(PhpNode $n) {
        return $this->js_factory->block(...$n->stmts);
    }

    public function compile_Stmt_Throw_(PhpNode $n) {
        return $this->js_factory->throw_($n->expr);
    }
}
