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
    /**
     * @var JS\AST\Factory
     */
    protected $js_factory;

    /**
     * @var BuildInCompiler
     */
    protected $build_in_compiler;

    public function __construct(
        JS\AST\Factory $js_factory,
        BuildInCompiler $build_in_compiler
    ) {
        $this->js_factory = $js_factory;
        $this->build_in_compiler = $build_in_compiler;
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
                $properties,
                $methods,
                $constructor,
                $constants,
                function ($f) use ($js) {
                    return $js->call(
                        $f,
                        $js->object_([
                            "public" => $js->object_([]),
                            "protected" => $js->object_([])
                        ])
                    );
                }
            );
        }
        else {
            return $this->classDefinition(
                $name,
                $properties,
                $methods,
                $constructor,
                $constants,
                function ($f) use ($js, $n) {
                    return $js->call(
                        $js->propertyOf(
                            $js->identifier(Compiler::normalizeFQN($n->extends->value())),
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

    protected function classDefinition($name, $properties, $methods, $constructor, $constants, $initial_call) {
        $js = $this->js_factory;

        $construct_raw = $js->identifier("construct_raw");
        $extend = $js->identifier("extend");
        $public = $js->identifier("_public");
        $protected = $js->identifier("_protected");
        $private = $js->identifier("_private");
        $create_class = $js->identifier("create_class");
        $parent = $js->identifier("parent");

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

        return $js->assignVar(
            $js->identifier(Compiler::normalizeFQN($name)),
            $initial_call($js->function_([$parent], $js->block(
                $js->assignVar(
                    $construct_raw,
                    $js->function_([], $js->block(
                        $js->assignVar($public, $clone($js->propertyOf($parent, $public))),
                        $js->assignVar($protected, $clone($js->propertyOf($parent, $protected))),
                        $js->assignVar($private, $js->object_([])),
                        $js->block(...$properties),
                        $js->block(...$methods),
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

    public function compile_Scalar_String_(PhpNode $n) {
        return $this->js_factory->literal(strtr ($n->value, self::$string_replacements));
    }

    public function compile_Scalar_LNumber(PhpNode $n) {
        if ($n->getAttribute("kind") != 10) {
            throw new \LogicException("Cannot compile LNumbers with kind != 10");
        }
        return $this->js_factory->literal($n->value);
    }

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
        return $this->js_factory->identifier(join("_", $n->parts));
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

    public function compile_Expr_BinaryOp_Identical(PhpNode $n) {
        return $this->js_factory->identical($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Plus(PhpNode $n) {
        return $this->js_factory->add($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Minus(PhpNode $n) {
        return $this->js_factory->sub($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Mul(PhpNode $n) {
        return $this->js_factory->mul($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Div(PhpNode $n) {
        return $this->js_factory->div($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Mod(PhpNode $n) {
        return $this->js_factory->mod($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Pow(PhpNode $n) {
        return $this->js_factory->pow($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_BitwiseAnd(PhpNode $n) {
        return $this->js_factory->bitAnd($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_BitwiseOr(PhpNode $n) {
        return $this->js_factory->bitOr($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_BitwiseXor(PhpNode $n) {
        return $this->js_factory->bitXor($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_ShiftLeft(PhpNode $n) {
        return $this->js_factory->bitShiftLeft($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_ShiftRight(PhpNode $n) {
        return $this->js_factory->bitShiftRight($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Greater(PhpNode $n) {
        return $this->js_factory->greater($n->left, $n->right);
    }

    public function compile_Expr_BinaryOp_Concat(PhpNode $n) {
        $f = $this->js_factory;
        return $f->call(
            $f->propertyOf($n->left, $f->identifier("concat")),
            $n->right
        );
    }

    public function compile_Expr_BinaryOp_BooleanAnd(PhpNode $n) {
        return $this->js_factory->and_(
            $n->left,
            $n->right
        );
    }

    public function compile_Expr_BinaryOp_BooleanOr(PhpNode $n) {
        return $this->js_factory->or_(
            $n->left,
            $n->right
        );
    }

    public function compile_Expr_BooleanNot(PhpNode $n) {
        return $this->js_factory->not(
            $n->expr
        );
    }

    public function compile_Expr_ClassConstFetch(PhpNode $n) {
        $js = $this->js_factory;
        return $js->propertyOf(
            $js->propertyOf(
                $js->identifier(Compiler::normalizeFQN($n->class->value())),
                $js->identifier("__constants")
            ),
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

        $fun = $f->function_(
            $n->params,
            $f->block(...$n->stmts)
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

    public function compile_Expr_Exit_(PhpNode $n) {
        $f = $this->js_factory;
        return $f->call(
            $f->propertyOf(
                $f->identifier("process"),
                $f->identifier("exit")
            ),
            $n->expr
        );
    }

    public function compile_Expr_FuncCall(PhpNode $n) {
        if ($this->build_in_compiler->isBuildInFunction($n->name->toLowerString())) {
            return $this->build_in_compiler->compile($n);
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

    public function compile_Expr_Isset_(PhpNode $n) {
        $vars = $n->vars;
        $var = array_shift($vars);
        $cur = $this->jsIsset($var);
        foreach ($vars as $var) {
            $cur = $f->and_($cur, $this->jsIsset($var));
        }
        return $cur;
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
                $f->identifier(
                    Compiler::normalizeFQN(
                        $n->class->value()
                    )
                ),
                $f->identifier("__construct")
            ),
            ...$n->args
        );
    }

    public function compile_Expr_Ternary(PhpNode $n) {
        return $this->js_factory->ternary(
            $n->cond,
            $n->if,
            $n->else
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
        if (isset($n->type) || $n->byRef || $n->variadic || isset($n->default)) {
            throw new \LogicException(
                "Cannot compile typed, variadic, pass-by-ref or defaulted parameter."
            );
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

        return [
            $n,
            $js->assign(
                $js->propertyOf(
                    $js->identifier("_".$visibility),
                    $js->identifier($n->name->value())
                ),
                $js->function_(
                    $n->params,
                    $js->block(...$n->stmts)
                )
            )
        ];
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

    public function compile_Stmt_Echo_(PhpNode $n) {
        $f = $this->js_factory;
        return $f->call(
            $f->propertyOf(
                $f->propertyOf(
                    $f->identifier("process"),
                    $f->identifier("stdout")
                ),
                $f->identifier("write")
            ),
            ...$n->exprs
        );
    }

    public function compile_Stmt_Return_(PhpNode $n) {
        return $this->js_factory->return_($n->expr);
    }

    public function compile_Stmt_If_(PhpNode $n) {
        $f = $this->js_factory;
        return $f->if_(
            $n->cond,
            $f->block(...$n->stmts),
            $n->else ? $f->block(...$n->else) : null
        );
    }

    public function compile_Stmt_Else_(PhpNode $n) {
        return $n->stmts;
    }

    public function compile_Stmt_Throw_(PhpNode $n) {
        return $this->js_factory->throw_($n->expr);
    }
}
