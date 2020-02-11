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

        $this->initIdentifiers();
    }

    protected function initIdentifiers() {
        $js = $this->js_factory;

        $this->_instanceof = $js->identifier("__instanceof");
        $this->_prototype = $js->identifier("prototype");
        $this->_object = $js->identifier("Object");
        $this->_create = $js->identifier("create");
        $this->_constructor = $js->identifier("constructor");
        $this->_constants = $js->identifier("__constants");
        $this->_this = $js->identifier("this");
        $this->_proto = $js->identifier("__proto__");
        $this->_call = $js->identifier("call");
        $this->_cls = $js->identifier("cls");
        $this->_identicalTo = $js->identifier("__identicalTo");
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
        $js = $this->js_factory;

        if (!$class->hasAttribute(FilePass\AnnotateFullyQualifiedName::ATTR)) {
            throw new \LogicException(
                "Class should have fully qualified name to be compiled."
            );
        }

        $class_registry = (new ClassRegistryBuilder)->buildClassRegistryFrom($class);

        return $this->buildClass($class_registry);
    }

    protected function buildClass(ClassRegistry $class_registry) {
        $js = $this->js_factory;
        $class_name = $this->compileClassName($class_registry->name());

        $prototype = $js->object_(array_merge(
            array_map(
                [$this, "compileRecursive"],
                $class_registry->getProperties()
            ),
            array_map(
                [$this, "compileRecursive"],
                $class_registry->getMethods()
            ),
            $this->buildStandardMethods($class_registry)
        ));

        if ($class_registry->parentName()) {
            $prototype = $js->call(
                $js->propertyOf($this->_object, $this->_create),
                $js->propertyOf(
                    $this->compileClassName($class_registry->parentName()),
                    $this->_prototype
                ),
                $prototype->fmap(function($v) use ($js) {
                    return $js->object_(["value" => $v]);
                })
            );
        }

        return $js->block(
            $js->assign(
                $class_name,
                $this->buildConstructor($class_registry)
            ),
            $js->assign(
                $js->propertyOf($class_name, $this->_prototype),
                $prototype
            ),
            $class_registry->parentName()
                ? $js->assign(
                    $js->propertyOf($class_name, $this->_prototype, $this->_constructor),
                    $class_name
                )
                : $js->nop(),
            $js->assign(
                $js->propertyOf($class_name, $this->_constants),
                $js->object_(array_map(
                    [$this, "compileRecursive"],
                    $class_registry->getConstants()
                ))
            )
        );
    }

    protected function buildConstructor(ClassRegistry $class_registry) {
        $js = $this->js_factory;

        if ($class_registry->getConstructor() === null) {
            return $js->function_([], $js->block(
                $js->nop()
            ));
        }

        return $this->compileRecursive($class_registry->getConstructor());
    }

    protected function buildStandardMethods(ClassRegistry $class_registry) {
        return [
            "__instanceof" => $this->buildInstanceOf($class_registry)
        ];
    }

    protected function buildInstanceOf(ClassRegistry $class_registry) {
        $js = $this->js_factory;
        return $js->function_([$this->_cls], $js->block(
            $js->return_($js->or_(...array_merge(
                [$js->identical($this->_cls, $this->compileClassName($class_registry->name()))],
                array_map(function($i) use ($js) {
                    return $js->identical($this->_cls, $this->compileClassName($i));
                }, $class_registry->implementsNames()),
                $class_registry->parentName()
                    ? [$this->parentMethodCall($this->_instanceof, [$this->_cls])]
                    : []
            )))
        ));
    }

    protected function compileRecursive(PhpNode $n) {
        $prefix_len = strrpos(PhpNode\Node::class, "\\") + 1;
        return Recursion::cata($n, function(PhpNode $n) use ($prefix_len) {
            $class = str_replace("\\", "_", substr(get_class($n), $prefix_len));
            $method = "compile_$class";
            return $this->$method($n);
        });
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
        return $this->js_factory->propertyOf(
            $this->compileClassName($n->class->value()),
            $this->_constants,
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

    public function parentMethodCall($m, $args) {
        $js = $this->js_factory;
        return $js->call(
            $js->propertyOf(
                $this->_this,
                $this->_proto,
                $this->_proto,
                $m,
                $this->_call
            ),
            $this->_this,
            ...$args
        );
    }

    public function compile_Expr_StaticCall(PhpNode $n) {
        if ((string)$n->class->value() !== "parent") {
            throw new \LogicException(
                "Currently can only compile StaticCalls to parents."
            );
        }

        return $this->parentMethodCall($n->name, $n->args);
    }

    public function compile_Expr_Assign(PhpNode $n) {
        assert($n->hasAttribute(FilePass\AnnotateFirstVariableAssignment::ATTR));
        if ($n->getAttribute(FilePass\AnnotateFirstVariableAssignment::ATTR)) {
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
        $js = $this->js_factory;

        if ($this->startsWith_JS_NATIVE($n->class->value())) {
            return $js->new_(
                $js->identifier($this->remove_JS_NATIVE($n->class->value())),
                ...$n->args
            );
        }

        return $js->new_(
            $this->compileClassName($n->class->value()),
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

        if (($n->isMagic() && $n->name->toLowerString() !== "__construct") || $n->isStatic() || $n->isAbstract()) {
            throw new \LogicException(
                "Cannot compile magic, static or abstract methods."
            );
        }

        list($params, $stmts) = $this->unrollParameters(...$n->params);

        return $js->function_(
            $params,
            $js->block(...array_merge($stmts, $n->stmts))
        );
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
        return $n->value;
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
        if (count($n->props) != 1) {
            throw new \LogicException(
                "Expected Property to be unrolled and only have one sub-property."
            );
        }
        return $n->props[0];
    }

    public function compile_Stmt_PropertyProperty(PhpNode $n) {
        return $n->default === null
            ? $this->js_factory->null_()
            : $n->default;
    }

    public function compile_Expr_PropertyFetch(PhpNode $n) {
        return $this->js_factory->propertyOf(
            $n->var,
            $n->name
        );
    }

    public function compile_Stmt_Return_(PhpNode $n) {
        return $this->js_factory->return_($n->expr);
    }
}
