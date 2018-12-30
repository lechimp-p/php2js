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
        if (!$class->hasAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME)) {
            throw new \LogicException(
                "Class should have fully qualified name to be compiled."
            );
        }
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
            $js->identifier(Compiler::normalizeFQN($name)),
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

    public function compile_VarLikeIdentifier(PhpNode $n) {
        return $this->js_factory->identifier($n->name);
    }

    public function compile_Expr_Variable(PhpNode $n) {
        return $this->js_factory->identifier($n->name);
    }

    public function compile_Name_FullyQualified(PhpNode $n) {
        return $this->js_factory->identifier(join("_", $n->parts));
    }

    public function compile_Stmt_ClassMethod(PhpNode $n) {
        $js = $this->js_factory;
        $visibility = Compiler::getVisibilityConst($n);

        if ($n->isMagic() || $n->isStatic() || $n->isAbstract()) {
            throw new \LogicException(
                "Cannot compile magic, static or abstract methods."
            );
        }

        return $js->assign(
            $js->propertyOf(
                $js->identifier($visibility),
                $js->identifier($n->name->value())
            ),
            $js->function_(
                $n->params,
                $js->block(...$n->stmts)
            )
        );
    }

    public function compile_Stmt_Expression(PhpNode $n) {
        return $n->expr;
    }

    public function compile_Stmt_Property(PhpNode $n) {
        $js = $this->js_factory;
        $visibility = $js->identifier(Compiler::getVisibilityConst($n));
        return $js->block(...array_map(function($p) use ($js, $visibility) {
            return $js->assign(
                $js->propertyOf($visibility, $p->name),
                $js->null_()
            );
        }, $n->props));
    }

    public function compile_Stmt_PropertyProperty(PhpNode $n) {
        if ($n->default !== null) {
            throw new \LogicException(
                "Currently cannot compile default values for properties."
            );
        }
        return $n;
    }

    public function compile_Expr_PropertyFetch(PhpNode $n) {
        $js = $this->js_factory;
        if ($n->var->value() === "this") {
            if (!$n->hasAttribute(Compiler::ATTR_VISIBILITY)) {
                throw new \LogicException(
                    "Property access to \$this should have attribute for visibility."
                );
            }
            $visibility = $n->getAttribute(Compiler::ATTR_VISIBILITY);
            $source = $js->identifier($visibility);
        }
        else {
            $source = $n->var;
        }

        return $js->propertyOf(
            $source,
            $n->name
        );
    }

    public function compile_Expr_MethodCall(PhpNode $n) {
        return $this->js_factory->call(
            $this->compile_Expr_PropertyFetch($n),
            ...$n->args
        );
    }

    public function compile_Expr_Assign(PhpNode $n) {
        return $this->js_factory->assign(
            $n->var,
            $n->expr
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
}
