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

    public function compile(string $filename) : string {
        $base_ast = $this->preprocessAST(
            ...$this->parseFile($filename)
        );
        $dependencies = $this->getDependencies(...$base_ast);
        $result = $this->getResults(...$base_ast);

        $compiled_dependencies = [];
        while(count($dependencies) > 0) {
            $dep = array_shift($dependencies);
            if (array_key_exists($dep, $compiled_dependencies)) {
                continue;
            }
            $filename = $this->getDependencySourceFile($dep);
            $dep_ast = $this->preprocessAST(
                $this->parseFile($filename)
            );
            $result = $result->append($this->getResults($dep_ast));
            $dependencies = array_merge(
                $dependencies,
                $this->getDependencies($dep_ast)
            );
            $compiled_dependencies[$dep] = true;
        }

        return $this->compileResult($result);
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
        return $this->checkAST(
            ...$this->simplifyAST(...$nodes)
        );
    }

    protected function simplifyAST(PhpNode ...$nodes) : array {
        $name_resolver = new NodeTraverser();
        $name_resolver->addVisitor(new NodeVisitor\NameResolver);

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
        // Check if new with variable class name is called.
        // Check if static call or var fetch with variable class name is used.
        // Check if variable function is called.
        return $nodes;
    }

    protected function getDependencies(PhpNode ...$nodes) : array {
        $collector = new CollectDependencies();
        $t = new NodeTraverser();
        $t->addVisitor($collector);
        $t->traverse($nodes);
        return $collector->getDependencies();
    }

    protected function getResults(PhpNode ...$nodes) : Results {
        return new Results();
    }
}
