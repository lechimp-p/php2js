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

    /**
     * @var JS\Factory
     */
    protected $js_factory;

    /**
     * @var JS\Printer
     */
    protected $js_printer;

    public function __construct(
        Parser $parser,
        JS\Factory $js_factory,
        JS\Printer $js_printer
    ) {
        $this->parser = $parser;
        $this->js_factory = $js_factory;
        $this->js_printer = $js_printer;
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

        return $this->js_printer->print($js_ast);
    }

    public function compileAST(PhpNode ...$from) : JS\Node {
        $prefix_len = strrpos(PhpNode\Node::class, "\\") + 1;
        $stmts = array_map(function(PhpNode $n) use ($prefix_len) {
            return Recursion::cata($n, function(PhpNode $n) use ($prefix_len) {
                $class = str_replace("\\", "_", substr(get_class($n), $prefix_len));
                $method = "compile_$class";
                return $this->$method($n);
            });
        }, $from);
        return $this->js_factory->block(...$stmts);
    }

    public function compile_Scalar_String_(PhpNode $n) {
        return $this->js_factory->literal($n->value);
    }

    public function compile_Stmt_Echo_(PhpNode $n) {
        $f = $this->js_factory;
        return $f->call(
            $f->propertyOf(
                $f->identifier("console"),
                $f->identifier("log")
            ),
            ...$n->exprs
        );
    }
}
