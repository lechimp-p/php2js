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
trait BuildInCompiler {
    public function compile_Expr_Exit_(PhpNode $n) {
        $f = $this->js_factory;
        return $f->call(
            $f->propertyOf(
                $f->identifier("process"),
                $f->identifier("exit")
            ),
            $n->expr
        );
    }

    public function compile_Expr_InstanceOf_(PhpNode $n) {
        $js = $this->js_factory;
        return $js->call(
            $js->propertyOf(
                $n->expr,
                $js->identifier("__instanceof")
            ),
            $this->compileClassName($n->class->value())
        );
    }

    public function compile_Expr_Isset_(PhpNode $n) {
        $vars = $n->vars;
        $var = array_shift($vars);
        $cur = $this->jsIsset($var);
        foreach ($vars as $var) {
            $cur = $f->and_($cur, $this->jsIsset($var));
        }
        return $cur;
    }

    public function compile_Stmt_Echo_(PhpNode $n) {
        $f = $this->js_factory;
        return $f->call(
            $f->propertyOf(
                $f->propertyOf(
                    $f->identifier("process"),
                    $f->identifier("stdout")
                ),
                $f->identifier("write")
            ),
            ...$n->exprs
        );
    }
}
