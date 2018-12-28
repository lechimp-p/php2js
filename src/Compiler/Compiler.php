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
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
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
     * @var JS\AST\Factory
     */
    protected $js_factory;

    /**
     * @var JS\AST\Printer
     */
    protected $js_printer;

    public function __construct(
        Parser $parser,
        JS\AST\Factory $js_factory,
        JS\AST\Printer $js_printer
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

        $simplification_pipeline = $this->getSimplificationPipeline();
        foreach ($simplification_pipeline as $t) {
            $php_ast = $t->traverse($php_ast);
        }

        $this->checkScriptAST(...$php_ast);

        $js_ast = $this->compileAST(...$php_ast);

        return $this->js_printer->print($js_ast);
    }

    protected function getSimplificationPipeline() : array {
        $name_resolver = new NodeTraverser();
        $name_resolver->addVisitor(new NodeVisitor\NameResolver);

        $remove_use_namespace = new NodeTraverser();
        $remove_use_namespace->addVisitor(new RemoveUseNamespace());

        return [
            $name_resolver,
            $remove_use_namespace
        ];
    }

    public function compileAST(PhpNode ...$from) : JS\AST\Node {
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

    protected function checkScriptAST(PhpNode ...$php_ast) {
        $class = null;

        foreach ($php_ast as $n) {
            if ($n instanceof PhpNode\Stmt\Class_) {
                $class = $n;
                continue;
            }
            throw new Exception(
                "Found unexpected '".get_class($n)."' on top level."
            );
        }

        if ($class === null) {
            throw new Exception(
                "Did not find expected class on top level"
            );
        }

        if (!$this->implementsScriptInterface($class)) {
            throw new Exception(
                "Did not find expected class that implements '".JS\Script::class."' on top level"
            );
        }
    }

    protected function implementsScriptInterface(PhpNode\Stmt\Class_ $class) {
        foreach ($class->implements as $i) {
            if ((string)$i === JS\Script::class) {
                return true;
            }
        }
        return false;
    }

    // LOW-LEVEL-COMPILATION

    public function compile_Scalar_String_(PhpNode $n) {
        return $this->js_factory->literal($n->value);
    }

    public function compile_Identifier(PhpNode $n) {
        return $this->js_factory->identifier($n->name);
    }

    public function compile_Name_FullyQualified(PhpNode $n) {
        return $this->js_factory->identifier(join("_", $n->parts));
    }

    public function compile_Stmt_ClassMethod(PhpNode $n) {
        return [$n->name, $this->js_factory->function_(
            $n->params,
            $this->js_factory->block(...$n->stmts)
        )];
    }

    public function compile_Stmt_Class_(PhpNode $n) {
        assert($this->implementsScriptInterface($n));
        assert(count($n->stmts) === 0);
        $m = array_shift($n->stmts);
        assert(is_array($m));
        assert(count($m) === 2);
        assert(is_string($m[0]));
        assert($m[0] === "execute");
        assert($m[1] instanceof JS\AST\Function_);
        return $this->js_factory->call($m[1]);
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
