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
use PhpParser\Parser;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Lechimp\PHP2JS\JS;


/**
 * Compiles a class to JS.
 */
class ClassCompiler {
    use OpsCompiler;
    use ControlCompiler;
    use ScalarCompiler;
    use BuildInCompiler;

    /**
     * @var JS\AST\Factory
     */
    protected $js_factory;

    /**
     * @var BuildInFunctionsCompiler
     */
    protected $build_in_functions_compiler;

    public function __construct(
        JS\AST\Factory $js_factory,
        BuildInFunctionsCompiler $build_in_functions_compiler
    ) {
        $this->js_factory = $js_factory;
        $this->build_in_functions_compiler = $build_in_functions_compiler;
    }

    public function compileClassName(string $name) {
        $js = $this->js_factory;
        $name = explode("\\", $name);
        if ($name[0] === "") {
            array_shift($name);
        }
        $cur = $js->identifier("php2js");
        while(count($name) > 0) {
            $cur = $js->propertyOf(
                $cur,
                $js->identifier(array_shift($name))
            );
        }
        return $cur;
    }

    public function compile(PhpNode\Stmt\Class_ $class) : JS\AST\Node {
        if (!$class->hasAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME)) {
            throw new \LogicException(
                "Class should have fully qualified name to be compiled."
            );
        }
        return $this->compileRecursive($class);
    }

    protected function compileRecursive(PhpNode $n) {
        $prefix_len = strrpos(PhpNode\Node::class, "\\") + 1;
        return Recursion::cata($n, function(PhpNode $n) use ($prefix_len) {
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
        $public = $js->identifier("_public");
        $protected = $js->identifier("_protected");
        $private = $js->identifier("_private");
        $create_class = $js->identifier("create_class");
        $parent = $js->identifier("parent");

        list($constructor, $methods, $properties, $constants) = $this->sortClassBody($n->stmts);

        if ($constructor === null) {
            $constructor = $js->function_([], $js->block(
                $js->return_($public)
            ));
        }
        else {
            $constructor = $js->function_(
                $constructor->parameters(),
                $js->block(
                    $constructor->block(),
                    $js->return_($public)
                )
            );
        }


        if (is_null($n->extends)) {
            return $this->classDefinition(
                $name,
                $n->implements,
                $properties,
                $methods,
                $constructor,
                $constants,
                function ($f) use ($js) {
                    return $js->call(
                        $f,
                        $js->object_([
                            "_public" => $js->object_([
                                "__instanceof" => $js->function_([$js->identifier("cls")], $js->block(
                                    $js->return_($js->identifier("false"))
                                ))
                             ]),
                            "_protected" => $js->object_([])
                        ])
                    );
                }
            );
        }
        else {
            return $this->classDefinition(
                $name,
                $n->implements,
                $properties,
                $methods,
                $constructor,
                $constants,
                function ($f) use ($js, $n) {
                    return $js->call(
                        $js->propertyOf(
                            $this->compileClassName($n->extends->value()),
                            $js->identifier("__extend")
                        ),
                        $f
                    );
                }
            );
        }
    }

    protected function sortClassBody(array $nodes) {
        $js = $this->js_factory;

        $constructor = null;
        $methods = [];
        $properties = [];
        $constants = [];
        foreach ($nodes as $n) {
            list($n, $compiled) = $n;
            if ($n instanceof PhpNode\Stmt\ClassMethod) {
                if ($n->name->toLowerString() === "__construct") {
                    $constructor = $compiled->value();
                }
                else if ($n->name->toLowerString() === "__instanceof") {
                    throw new Exception(
                        "Method cannot be named `__instanceof`, that name is used internally."
                    );
                }
                else {
                    $methods[] = $compiled;
                }
            }
            elseif ($n instanceof PhpNode\Stmt\Property) {
                $properties[] = $compiled;
            }
            elseif ($n instanceof PhpNode\Const_) {
                $constants[$compiled[0]] = $compiled[1];
            }
            else {
                throw new \LogicException(
                    "Cannot process '".get_class($n)."'"
                );
            }
        }
        return [$constructor, $methods, $properties, $constants];
    }

    protected function classDefinition($name, $implements, $properties, $methods, $constructor, $constants, $initial_call) {
        $js = $this->js_factory;

        $construct_raw = $js->identifier("construct_raw");
        $extend = $js->identifier("extend");
        $public = $js->identifier("_public");
        $protected = $js->identifier("_protected");
        $private = $js->identifier("_private");
        $create_class = $js->identifier("create_class");
        $parent = $js->identifier("parent");
        $class = $js->identifier("cls");
        $instanceof = $js->identifier("__instanceof");

        $clone = function ($which) use ($js) {
            return $js->call(
                $js->propertyOf(
                    $js->identifier("Object"),
                    $js->identifier("assign")
                ),
                $js->object_([]),
                $which
            );
        };

        return $js->assign(
            $this->compileClassName($name),
            $initial_call($js->function_([$parent], $js->block(
                $js->assignVar(
                    $construct_raw,
                    $js->function_([], $js->block(
                        $js->assignVar($public, $clone($js->propertyOf($parent, $public))),
                        $js->assignVar($protected, $clone($js->propertyOf($parent, $protected))),
                        $js->assignVar($private, $js->object_([])),
                        $js->block(...$properties),
                        $js->block(...$methods),
                        $js->assign(
                            $js->propertyOf($public, $instanceof),
                            $js->function_([$class], $js->block(
                                $js->return_($js->or_(
                                    $js->identical($class, $this->compileClassName($name)),
                                    $js->call(
                                        $js->propertyOf(
                                            $js->propertyOf($parent, $public),
                                            $instanceof
                                        ),
                                        $class
                                    ),
                                    ...array_map(function($i) use ($class, $js) {
                                        return $js->identical(
                                            $class,
                                            $this->compileClassName($i->value())
                                        );
                                    }, $implements)
                                ))
                            ))
                        ),
                        $js->return_($js->object_([
                            "construct" => $constructor,
                            "_public" => $public,
                            "_protected" => $protected
                        ]))
                    ))
                ),
                $js->assignVar(
                    $extend,
                    $js->function_([$create_class], $js->block(
                        $js->return_(
                            $js->call(
                                $create_class,
                                $js->call($construct_raw)
                            )
                        )
                    ))
                ),
                $js->assignVar(
                    $js->identifier("constants"),
                    $js->object_($constants)
                ),
                $js->return_($js->object_([
                    "__extend" => $extend,
                    "__construct" => $js->propertyOf(
                        $js->call($construct_raw),
                        $js->identifier("construct")
                    ),
                    "__constants" => $js->identifier("constants")
                ]))
            )))
        );
    }


    protected static $string_replacements = [
        "\n" => "\\n",
        "\t" => "\\t",
        "\"" => "\\\"",
        "\\" => "\\\\"
    ];

    public function compile_Identifier(PhpNode $n) {
        return $this->js_factory->identifier($n->name);
    }

    public function compile_Name(PhpNode $n) {
        return $this->js_factory->identifier("$n");
    }

    public function compile_VarLikeIdentifier(PhpNode $n) {
        return $this->js_factory->identifier($n->name);
    }

    public function compile_Expr_Variable(PhpNode $n) {
        $name = $n->name;
        if ($this->startsWith_JS_NATIVE($name)) {
            $name = $this->remove_JS_NATIVE($name);
        }
        else if ($n->name !== "this") {
            $name = '$'.$name;
        }
        return $this->js_factory->identifier($name);
    }

    public function compile_Name_FullyQualified(PhpNode $n) {
        return $this->js_factory->identifier("".$n);
    }

    public function compile_Expr_ConstFetch(PhpNode $n) {
        $js = $this->js_factory;
        if (!in_array($n->name->value(), ["null", "true", "false"])) {
            throw new \LogicException(
                "Can only compile 'null', 'true' or 'false' constants."
            );
        }
        return $n->name;
    }

    public function compile_Expr_ClassConstFetch(PhpNode $n) {
        $js = $this->js_factory;
        if ($n->class->value() === "self") {
            $constants = $js->identifier("constants");
        }
        else {
            $constants = $js->propertyOf(
                $this->compileClassName($n->class->value()),
                $js->identifier("__constants")
            );
        }
        
        return $js->propertyOf(
            $constants,
            $n->name
        );
    }

    public function compile_Expr_Closure(PhpNode $n) {
        if ($n->static || $n->byRef || $n->returnType) {
            throw new \LogicException(
                "Cannot compile Closure with static, byRef or returnType"
            );
        }

        list($uses_by_val, $uses_by_ref) = $this->collectClosureUses($n->uses);

        $f = $this->js_factory;

        list($params, $stmts) = $this->unrollParameters(...$n->params);

        $fun = $f->function_(
            $params,
            $f->block(...array_merge($stmts, $n->stmts))
        );

        if (count($uses_by_val) === 0) {
            return $fun;
        }

        return $f->call(
            $f->function_(
                $uses_by_val,
                $f->block($f->return_($fun))
            ),
            ...$uses_by_val
        );
    }

    protected function collectClosureUses(array $use) {
        $vars_by_val = [];
        $vars_by_ref = [];
        foreach ($use as list($var,$by_ref)) {
            if ($by_ref) {
                $vars_by_ref[] = $var;
            }
            else {
                $vars_by_val[] = $var;
            }
        }
        return [$vars_by_val, $vars_by_ref];
    }

    public function compile_Expr_ClosureUse(PhpNode $n) {
        return [$n->var, $n->byRef];
    }

    public function compile_Expr_FuncCall(PhpNode $n) {
        if ($this->build_in_functions_compiler->isBuildInFunction($n->name->toLowerString())) {
            return $this->build_in_functions_compiler->compile($n);
        }

        return $this->js_factory->call(
            $n->name,
            ...$n->args
        );
    }

    public function compile_Expr_MethodCall(PhpNode $n) {
        return $this->js_factory->call(
            $this->compile_Expr_PropertyFetch($n),
            ...$n->args
        );
    }

    public function compile_Expr_Assign(PhpNode $n) {
        assert($n->hasAttribute(Compiler::ATTR_FIRST_VAR_ASSIGNMENT));
        if ($n->getAttribute(Compiler::ATTR_FIRST_VAR_ASSIGNMENT)) {
            assert($n->var instanceof JS\AST\Identifier);
            return $this->js_factory->assignVar(
                $n->var,
                $n->expr
            );
        }
        return $this->js_factory->assign(
            $n->var,
            $n->expr
        );
    }

    protected function jsIsset(JS\AST\Expression $expr) {
        $f = $this->js_factory;
        return $f->and_(
            $f->not_identical(
                $f->typeof($expr),
                $f->literal("undefined")
            ),
            $f->not_identical(
                $expr,
                $f->null_()
            )
        );
    }

    const JS_NATIVE = "JS_NATIVE_";

    protected function startsWith_JS_NATIVE(string $s) : bool {
        return strpos($s, self::JS_NATIVE) === 0;
    }

    protected function remove_JS_NATIVE(string $s) : string {
        return substr($s, strlen(self::JS_NATIVE));
    }

    public function compile_Expr_New_(PhpNode $n) {
        $f = $this->js_factory;

        if ($this->startsWith_JS_NATIVE($n->class->value())) {
            return $f->new_(
                $f->identifier($this->remove_JS_NATIVE($n->class->value())),
                ...$n->args
            );
        }

        return $f->call(
            $f->propertyOf(
                $this->compileClassName($n->class->value()),
                $f->identifier("__construct")
            ),
            ...$n->args
        );
    }

    public function compile_Arg(PhpNode $n) {
        if ($n->unpack || $n->byRef) {
            throw new \LogicException(
                "Cannot compile unpacked arguments or arguments passed by reference."
            );
        }

        return $n->value;
    }

    public function compile_Param(PhpNode $n) {
        if (isset($n->type) || $n->byRef || isset($n->default)) {
            throw new \LogicException(
                "Cannot compile typed, variadic, pass-by-ref or defaulted parameter."
            );
        }
        if ($n->variadic) {
            return function(int $position) use ($n) {
                $js = $this->js_factory;
                return $js->assignVar(
                    $n->var,
                    $js->call(
                        $js->propertyOf(
                            $js->call(
                                $js->propertyOf(
                                    $js->propertyOf(
                                        $js->propertyOf(
                                            $js->identifier("Array"),
                                            $js->identifier("prototype")
                                        ),
                                        $js->identifier("slice")
                                    ),
                                    $js->identifier("call")
                                ),
                                $js->identifier("arguments"),
                                $js->literal($position)
                            ),
                            $js->identifier("toPHPArray")
                        )
                    )
                );
            };
        }
        return $n->var;
    }

    public function compile_Stmt_ClassMethod(PhpNode $n) {
        $js = $this->js_factory;
        $visibility = Compiler::getVisibilityConst($n);

        if (($n->isMagic() && $n->name->toLowerString() !== "__construct") || $n->isStatic() || $n->isAbstract()) {
            throw new \LogicException(
                "Cannot compile magic, static or abstract methods."
            );
        }

        list($params, $stmts) = $this->unrollParameters(...$n->params);

        return [
            $n,
            $js->assign(
                $js->propertyOf(
                    $js->identifier("_".$visibility),
                    $js->identifier($n->name->value())
                ),
                $js->function_(
                    $params,
                    $js->block(...array_merge($stmts, $n->stmts))
                )
            )
        ];
    }

    protected function unrollParameters(...$params) {
        $unrolled = [];
        for ($i = 0; $i < count($params); $i++) {
            $p = $params[$i];
            if ($p instanceof \Closure) {
                if ($i+1 !== count($params)) {
                    throw new Exception(
                        "Expected splat-operator to be used on last argument."
                    );
                }
                return [$unrolled, [$p($i)]];
            }
            else {
                $unrolled[] = $p;
            }
        }
        return [$unrolled, []];
    }

    public function compile_Const_(PhpNode $n) {
        return [
            $n,
            [ $n->name->value(), $n->value ]
        ];
    }

    public function compile_Stmt_ClassConst(PhpNode $n) {
        if ($n->flags !== 0 || count($n->consts) !== 1) {
            throw new \LogicException(
                "Expected flags = 0 and only one constant in consts."
            );
        }
        return $n->consts[0];
    }

    public function compile_Stmt_Expression(PhpNode $n) {
        return $n->expr;
    }

    public function compile_Stmt_Property(PhpNode $n) {
        $js = $this->js_factory;
        $visibility = $js->identifier("_".Compiler::getVisibilityConst($n));
        return [
            $n,
            $js->block(...array_map(function($p) use ($n, $js, $visibility) {
                return $js->assign(
                    $js->propertyOf($visibility, $p->name),
                    $p->default === null
                        ? $js->null_()
                        : $p->default
                );
            }, $n->props))
        ];
    }

    public function compile_Stmt_PropertyProperty(PhpNode $n) {
        return $n;
    }

    public function compile_Expr_PropertyFetch(PhpNode $n) {
        $js = $this->js_factory;
        if ($n->var instanceof JS\AST\Identifier
        && in_array($n->var->value(), ["this", "parent"])) {
            if (!$n->hasAttribute(Compiler::ATTR_VISIBILITY)) {
                throw new \LogicException(
                    "Property access to \$this should have attribute for visibility."
                );
            }
            $visibility = $n->getAttribute(Compiler::ATTR_VISIBILITY);
            if ($n->var->value() === "this") {
                $source = $js->identifier("_".$visibility);
            }
            else if ($n->var->value() === "parent") {
                $source = $js->propertyOf(
                    $n->var,
                    $js->identifier("_".$visibility)
                );
            }
            else {
                throw new \LogicException(
                    "Expected 'this' or ".RewriteParentAccess::JS_NATIVE_parent
                );
            }
        }
        else {
            $source = $n->var;
        }

        return $js->propertyOf(
            $source,
            $n->name
        );
    }

    public function compile_Stmt_Return_(PhpNode $n) {
        return $this->js_factory->return_($n->expr);
    }
}
