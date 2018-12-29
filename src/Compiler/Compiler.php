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
    const ATTR_FULLY_QUALIFIED_NAME = "fully_qualified_name";

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

    public function compile(string $filename) : string {
        list($deps, $result) = $this->compileFile($filename);

        // TODO: Check if there is one class implementing Script now.

        $compiled_deps = [];
        while(count($deps) > 0) {
            $dep = array_shift($deps);
            if (array_key_exists($dep, $compiled_deps)) {
                continue;
            }

            if ($this->isInternalDependency($dep)) {
                // TODO: Treat internal dependencies correctly.
            }
            else {
                $filename = $this->getDependencySourceFile($dep);

                list($new_deps, $new_result) = $this->compileFile($filename);
                $result = $result->append($new_result);
                $deps = array_merge($deps, $new_deps);
            };

            $compiled_deps[$dep] = true;
        }

        // TODO: Check if there is still only one class implementing Script now.

        return $this->compileResult($result);
    }

    protected function compileFile(string $filename) : array {
        $ast = $this->preprocessAST(
            ...$this->parseFile($filename)
        );
        return [
            $this->getDependencies(...$ast),
            $this->getResults(...$ast)
        ];
    }

    protected function parseFile(string $filename) : array {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(
                "Could not find file '$filename'"
            );
        }
        return $this->parser->parse(file_get_contents($filename));
    }

    protected function preprocessAST(PhpNode ...$nodes) : array {
        return $this->annotateAST(
            ...$this->checkAST(
                ...$this->simplifyAST(...$nodes)
            )
        );
    }

    protected function simplifyAST(PhpNode ...$nodes) : array {
        $name_resolver = new NodeTraverser();
        $name_resolver->addVisitor(new NodeVisitor\NameResolver());

        $remove_use_namespace = new NodeTraverser();
        $remove_use_namespace->addVisitor(new RemoveUseNamespace());

        $pipeline = [
            $name_resolver,
            $remove_use_namespace
        ];

        foreach($pipeline as $p) {
            $nodes = $p->traverse($nodes);
        }
        return $nodes;
    }

    protected function checkAST(PhpNode ...$nodes) : array {
        // TODO: Check if new with variable class name is called.
        // TODO: Check if static call or var fetch with variable class name is used.
        // TODO: Check if variable function is called.
        // TODO: Check if anonymous classes are used.
        return $nodes;
    }

    protected function annotateAST(PhpNode ...$nodes) : array {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new AnnotateFullyQualifiedName());

        return $traverser->traverse($nodes);
    }

    protected function getDependencies(PhpNode ...$nodes) : array {
        $collector = new CollectDependencies();
        $t = new NodeTraverser();
        $t->addVisitor($collector);
        $t->traverse($nodes);
        return $collector->getDependencies();
    }

    protected function getResults(PhpNode ...$nodes) : Results {
        $collector = new CollectResults();
        $t = new NodeTraverser();
        $t->addVisitor($collector);
        $t->traverse($nodes);
        return $collector->getResults();
    }

    protected function isInternalDependency(string $dep) {
        // TODO: implement me
        return true;
    }

    protected function compileResult(Results $results) {
        $js = $this->js_factory;

        $classes = $results->getFullyQualifiedClassNames();
        $smts = [];

        $class_compiler = new ClassCompiler($this->js_factory); 
        foreach ($classes as $cls) {
            $stmts[] = $class_compiler->compile($results->getClass($cls)); 
        }

        $script_classes = $results->getClassesThatImplement(JS\Script::class);
        assert(count($script_classes) === 1);

        $script_class = $js->identifier(
            $class_compiler->normalizeFQN(
                array_pop($script_classes)->getAttribute(self::ATTR_FULLY_QUALIFIED_NAME)
            )
        );
        $script = $js->identifier("script");
        $stmts[] = $js->assignVar(
            $script,
            $js->call(
                $js->propertyOf($script_class, $js->identifier("__construct"))
            )
        );
        $stmts[] = $js->call(
            $js->propertyOf($script, $js->identifier($class_compiler->normalizeMethodName("execute")))
        );

        return $this->js_printer->print($js->block(...$stmts));
    }
}
