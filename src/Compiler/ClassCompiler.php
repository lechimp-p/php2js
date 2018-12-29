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
 * Compiles a class to JS.
 */
class ClassCompiler {
    /**
     * @var JS\AST\Factory
     */
    protected $js_factory;

    public function __construct(
        JS\AST\Factory $js_factory
    ) {
        $this->js_factory = $js_factory;
    }

    public function compile(PhpNode\Stmt\Class_ $class) : JS\AST\Node {
        $prefix_len = strrpos(PhpNode\Node::class, "\\") + 1;
        return Recursion::cata($class, function(PhpNode $n) use ($prefix_len) {
            $class = str_replace("\\", "_", substr(get_class($n), $prefix_len));
            $method = "compile_$class";
            return $this->$method($n);
        });
    }

    public function compile_Stmt_Class_(PhpNode $n) {
        $js = $this->js_factory;

        $name = $n->getAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME);

        $construct_raw = $js->identifier("construct_raw");
        $extend = $js->identifier("extend");
        $public = $js->identifier("public");
        $protected = $js->identifier("protected");
        $private = $js->identifier("private");
        $create_class = $js->identifier("create_class");

        return $js->assignVar(
            $js->identifier($this->normalizeFQN($name)),
            $js->call($js->function_([], $js->block(
                $js->assignVar(
                    $construct_raw,
                    $js->function_([], $js->block(
                        $js->assignVar($public, $js->object_([])),
                        $js->assignVar($protected, $js->object_([])),
                        $js->assignVar($private, $js->object_([])),
                        $js->block(...$n->stmts),
                        $js->return_($js->object_([
                            "construct" => $js->function_([], $js->block(
                                $js->return_($public)
                            )),
                            "public" => $public,
                            "protected" => $protected
                        ]))
                    ))
                ),
                $js->assignVar(
                    $extend,
                    $js->function_([$create_class], $js->block(
                        $js->call($create_class, $construct_raw)
                    ))
                ),
                $js->return_($js->object_([
                    "__extend" => $extend,
                    "__construct" => $js->propertyOf(
                        $js->call($construct_raw),
                        $js->identifier("construct")
                    )
                ]))
            )))
        );
    }

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
        $js = $this->js_factory;
        if ($n->isPublic()) {
            $access = "public";
        }
        elseif ($n->isProtected()) {
            $access = "protected";
        }
        elseif ($n->isPrivate()) {
            $access = "private";
        }
        else {
            throw new \LogicException(
                "Method is neither public, nor protected, nor private."
            );
        }

        if ($n->isMagic() || $n->isStatic() || $n->isAbstract()) {
            throw new \LogicException(
                "Cannot compile magic, static or abstract methods."
            );
        }

        return $js->assign(
            $js->propertyOf(
                $js->identifier($access),
                $js->identifier($this->normalizeMethodName($n->name->value()))
            ),
            $js->function_(
                $n->params,
                $js->block(...$n->stmts)
            )
        );
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

    public function normalizeFQN(string $name) {
        return str_replace("\\", "_", $name);    
    }

    public function normalizeMethodName(string $name) {
        return "m_$name";
    }

    public function normalizePropertyName(string $name) {
        return "p_$name";
    }
}
