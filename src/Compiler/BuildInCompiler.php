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

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Lechimp\PHP2JS\JS;


/**
 * Compiles PHP BuildIns.
 */
class BuildInCompiler {
    /**
     * @var JS\AST\Factory
     */
    protected $js_factory;

    public function __construct(
        JS\AST\Factory $js_factory
    ) {
        $this->js_factory = $js_factory;
    }

    public function isBuildInFunction(string $name) : bool {
        return method_exists($this, "compile_function_$name");
    }

    public function compile(Node\Expr\FuncCall $n) {
        if (!$this->isBuildInFunction($n->name->toLowerString())) {
            throw new \InvalidArgumentException("Unknown function \"{$n->name}\"");
        }

        $name = "compile_function_{$n->name->toLowerString()}";
        return $this->$name($n);
    }

    protected function compile_function_is_string(Node\Expr\FuncCall $n) {
        if (count($n->args) !== 1) {
            throw new \InvalidArgumentException(
                "Expected call to is_string to have exactly one param."
            );
        }

        $f = $this->js_factory;
        return $f->or_(
            $f->identical(
                $f->typeof($n->args[0]),
                $f->literal("string")
            ),
            $f->instanceof_(
                $n->args[0],
                $f->identifier("String")
            )
        );
    }

    protected function compile_function_is_int(Node\Expr\FuncCall $n) {
        if (count($n->args) !== 1) {
            throw new \InvalidArgumentException(
                "Expected call to is_int to have exactly one param."
            );
        }

        $f = $this->js_factory;
        return $f->identical(
            $n->args[0],
            $f->call(
                $f->identifier("parseInt"),
                $n->args[0],
                $f->literal(10)
            )
        );
    }

    protected function compile_function_gettype(Node\Expr\FuncCall $n) {
        if (count($n->args) !== 1) {
            throw new \InvalidArgumentException(
                "Expected call to gettype to have exactly one param."
            );
        }

        $f = $this->js_factory;
        return $f->typeof(
            $n->args[0]
        );
    }
}
