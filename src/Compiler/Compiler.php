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
                case JS\Member::class:
                    return "{$v->object()}[{$v->member()}]";
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
        $stmts = array_map(function(PhpNode $n) {
            return Recursion::cata($n, function(PhpNode $n) {
                switch (get_class($n)) {
                    case PhpNode\Scalar\String_::class:
                        return new JS\StringLiteral($n->value);
                    case PhpNode\Stmt\Echo_::class:
                        return new JS\Statement(
                            new JS\Call(
                                new JS\Member(
                                    new JS\Identifier("console"),
                                    new JS\StringLiteral("log")
                                ),
                                $n->exprs
                            )
                        );
                    default:
                        throw new \LogicException("Unknown class '".get_class($n)."'");
                }
            });
        }, $from);

        if (count($stmts) == 0) {
            throw new \LogicException("Expected at least on resulting statement.");
        }
        if (count($stmts) == 1) {
            return $stmts[0];
        }
        return new JS\Block($stmts);
    }
}
