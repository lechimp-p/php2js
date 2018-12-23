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

use PhpParser\Node as PhpNode;
use PhpParser\Parser;
use PhpParser\NodeDumper;
use Lechimp\PHP_JS\JS;

/**
 * Compile PHP to JS.
 */
class Compiler {
    /**
     * @var Parser
     */
    protected $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
    }

    public function compileFile(string $filename) : string {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(
                "Could not find file '$filename'"
            );
        }

        return $this->compile(file_get_contents($filename));
    }

    public function compile(string $php) : string {
        $php_ast = $this->parser->parse($php);

        $js_ast = $this->compileAST(...$php_ast);

        return $js_ast->cata(function ($v) : string {
            switch (get_class($v)) {
                case JS\StringLiteral::class:
                    return "\"{$v->value()}\"";
                case JS\Identifier::class:
                    return $v->value();
                case JS\PropertyOf::class:
                    return "{$v->object()}[{$v->property()}]";
                case JS\Call::class:
                    $params = join(",", $v->parameters());
                    return "{$v->callee()}($params)";
                case JS\Statement::class:
                    return "{$v->which()};";
                default:
                    throw new \LogicException("Unknown class '".get_class($v)."'");
            }
        });
    }

    public function compileAST(PhpNode ...$from) : JS\Node {
        $f = new JS\Factory();
        $stmts = array_map(function(PhpNode $n) use ($f) {
            return Recursion::cata($n, function(PhpNode $n) use ($f) {
                switch (get_class($n)) {
                    case PhpNode\Scalar\String_::class:
                        return $f->literal($n->value);
                    case PhpNode\Stmt\Echo_::class:
                        return $f->call(
                            $f->propertyOf($f->identifier("console"), $f->literal("log")),
                            ...$n->exprs
                        );
                    default:
                        throw new \LogicException("Unknown class '".get_class($n)."'");
                }
            });
        }, $from);
        return $f->block(...$stmts);
    }
}
