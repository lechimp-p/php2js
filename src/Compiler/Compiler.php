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
 * Compile PHP to JS.
 */
class Compiler {
    const ATTR_FULLY_QUALIFIED_NAME = "fully_qualified_name";
    const ATTR_DONT_DEFINE_UNDEFINED_VARS = "dont_define_undefined_vars";
    const ATTR_FIRST_VAR_ASSIGNMENT = "first_assignment";
    const ATTR_VISIBILITY = "visibility";
    const ATTR_PUBLIC = "public";
    const ATTR_PROTECTED = "protected";
    const ATTR_PRIVATE = "private";
    const ATTR_SCRIPT_DEPENDENCIES = "script_dependencies";

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

    /**
     * @var Dependency\Locator
     */
    protected $dependency_locator;

    /**
     * @var ClassCompiler
     */
    protected $class_compiler;

    protected static $internal_dependencies = [
        JS\Script::class => null,
        JS\API\Window::class => __DIR__."/API/WindowImpl.php",
        JS\API\Document::class => __DIR__."/API/DocumentImpl.php",
        JS\API\HTML\Node::class => null,
        JS\API\HTML\Element::class => null,
        JS\API\HTML\TextNode::class => null,
        \HTML\NodeImpl::class => __DIR__."/API/HTML/NodeImpl.php",
        \HTML\ElementImpl::class => __DIR__."/API/HTML/ElementImpl.php",
        \HTML\TextNodeImpl::class => __DIR__."/API/HTML/TextNodeImpl.php",
        \PhpArray::class => __DIR__."/PhpArrayImpl.php",
        \JS_NATIVE_Array::class => null,
        \JS_NATIVE_Object::class => null,
        \InvalidArgumentException::class => __DIR__."/InvalidArgumentExceptionImpl.php",
        \TypeError::class => __DIR__."/TypeErrorImpl.php",
        \ReturnFromLoopClosure::class => __DIR__."/ReturnFromLoopClosureImpl.php",
        "parent" => null,
        // TODO: make this use BuildInCompiler somehow
        "is_string" => null,
        "is_int" => null,
        "gettype" => null
    ];

    public function __construct(
        Parser $parser,
        JS\AST\Factory $js_factory,
        JS\AST\Printer $js_printer,
        Dependency\Locator $locator
    ) {
        $this->parser = $parser;
        $this->js_factory = $js_factory;
        $this->js_printer = $js_printer;
        $this->dependency_locator = new Dependency\LocateByList(
            $locator,
            true,
            self::$internal_dependencies
        );
        $this->class_compiler = new ClassCompiler(
            $this->js_factory,
            new BuildInFunctionsCompiler(
                $this->js_factory
            )
        );
    }

    public function compile(string $filename) : string {
        $this->loadCodebaseToCompile($filename);
        return $this->compileCodebase($this->codebase);
    }


    //---------------------------
    // LOAD CODE
    //---------------------------

    protected function loadCodebaseToCompile($filename) {
        $this->codebase = new Codebase();
        $this->filenames = [$filename];
        $this->discovered_deps = [];

        while(count($this->filenames) > 0) {
            $file = array_shift($this->filenames);
            $ast = $this->parseFile($file);
            $ast = $this->preprocessFileAST(
                in_array($file, self::$internal_dependencies),
                ...$ast
            );
            $this->addToCodebase(...$ast);
            $this->addFilenamesOfDependencies(...$ast);
        }
    }

    protected function addFilenamesOfDependencies(PhpNode ...$nodes) : void {
        foreach ($this->getDependencies(...$nodes) as $dep) {
            if (isset($this->discovered_deps[$dep])
            || $this->codebase->hasClass($dep)
            || $this->codebase->hasInterface($dep)
            ) {
                continue;
            }
            $filename = $this->dependency_locator->getFilenameOfDependency($dep);
            if ($filename) {
                $this->filenames[] = $filename;
            }
            $this->discovered_deps[$dep] = true;
        }
    }

    protected function preprocessFileAST(bool $is_internal, PhpNode ...$nodes) : array {
        $nodes = $this->simplifyAST(...$nodes);
        // TODO: This should probably go somewhere else.
        if ($is_internal) {
            // TODO: inject this
            $collector = new RemoveTypeHints();
            $t = new NodeTraverser();
            $t->addVisitor($collector);
            $ast = $t->traverse($nodes);
        }
        else {
            $this->checkFileAST(...$nodes);
        }
        return $this->annotateAST(...$nodes);
    }

    protected function parseFile(string $filename) : array {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(
                "Could not find file '$filename'"
            );
        }
        try {
            return $this->parser->parse(file_get_contents($filename));
        }
        catch (\PhpParser\Error $e) {
            $e->setRawMessage($e->getRawMessage()." in $filename");
            throw $e;
        }
    }

    protected function simplifyAST(PhpNode ...$nodes) : array {
        $pipeline = [
            new NodeVisitor\NameResolver(),
            new RemoveUseNamespace(),
            new RewriteSelfAccess(),
            new RewriteOperators(),
            new RewriteTypeHints(),
            new RewriteArrayCode(),
            new DefineUndefinedVariables()
        ];

        foreach($pipeline as $p) {
            $t = new NodeTraverser();
            $t->addVisitor($p);
            $nodes = $t->traverse($nodes);
        }
        return $nodes;
    }

    protected function checkFileAST(PhpNode ...$nodes) : array {
        // TODO: These checks should go somewhere else:
        // TODO: Check if new with variable class name is called.
        // TODO: Check if static call or var fetch with variable class name is used.
        // TODO: Check if variable function is called.
        // TODO: Check if anonymous classes are used.
        // TODO: Check if this is accessed with a property expression (instead of a name)
        // TODO: Check if there are methods and properties that have the same name.
        // TODO: Check if only declared variables are used.
        return $nodes;
    }

    protected function annotateAST(PhpNode ...$nodes) : array {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new AnnotateFullyQualifiedName());
        $traverser->addVisitor(new AnnotateScriptDependencies());
        $traverser->addVisitor(new AnnotateFirstVariableAssignment());
        $traverser->addVisitor(new AnnotateVisibility());

        return $traverser->traverse($nodes);
    }

    protected function getDependencies(PhpNode ...$nodes) : array {
        $collector = new CollectDependencies();
        $t = new NodeTraverser();
        $t->addVisitor($collector);
        $t->traverse($nodes);
        return $collector->getDependencies();
    }

    protected function addToCodebase(PhpNode ...$nodes) : void {
        // TODO: this does not need to be created various times...
        $filler = new FillCodebase($this->codebase);
        $t = new NodeTraverser();
        $t->addVisitor($filler);
        $t->traverse($nodes);
    }

    protected function compileCodebase(Codebase $codebase) {
        $js = $this->js_factory;

        // TODO: move this to a single file
        // TODO: maybe find a way to let users add stuff here
        $prelude = <<<JS
// PRELUDE START

Object.prototype.getItemAt = function (key) {
    return this[key];
};
Object.prototype.setItemAt = function (key, value) {
    this[key] = value;
}
Object.prototype.__instanceof = function(cls) {
    return false;
}
Object.prototype.__identicalTo = function(other) {
    return this === other;
}
Array.prototype.toPHPArray = function () {
    var arr = new php2js.PhpArray();
    this.forEach(function(e) {
        arr.push(e);
    });
    return arr;
}
String.prototype.__instanceof = function(cls) {
    return cls === String;
}
Number.prototype.__instanceof = function(cls) {
    return cls === Number;
}
Boolean.prototype.__instanceof = function(cls) {
    return cls === Boolean;
}
function __instanceof(e, cls) {
    return e !== null && e.__instanceof(cls);
}
function __identical(l, r) {
    return (l === r) || ((l instanceof Object) && l.__identicalTo(r));
}

// PRELUDE END


JS;

        $php2js = $js->identifier("php2js");
        $String = $js->identifier("String");
        $string = $js->identifier("string");
        $Number = $js->identifier("Number");
        $int = $js->identifier("int");
        $Bool = $js->identifier("Boolean");
        $bool = $js->identifier("bool");

        $compiled_code = $this->js_printer->print(
            $this->js_factory->block(
                ...array_merge(
                    $this->compileNamespaceCreation($codebase),
                    [ $js->assign($js->propertyOf($php2js, $string), $String)
                    , $js->assign($js->propertyOf($php2js, $int), $Number)
                    , $js->assign($js->propertyOf($php2js, $bool), $Bool)
                    ],
                    $this->compileInterfacesFromCodebase($codebase),
                    $this->compileClassesFromCodebase($codebase),
                    $this->compileScriptInvocationFromCodebase($codebase)
                )
            )
        );

        return $prelude.$compiled_code;
    }

    protected function compileNamespaceCreation(Codebase $codebase) {
        $f = null;
        $f = function ($n) use (&$f) {
            $js = [];
            foreach ($n as $k => $v) {
                $js[$k] = $f($v);
            }
            return $this->js_factory->object_($js);
        };
        return [
            $this->js_factory->assignVar(
                $this->js_factory->identifier("php2js"),
                $f($codebase->getNamespaces())
            )
        ];
    }

    protected function compileInterfacesFromCodebase(Codebase $codebase) {
        $js = $this->js_factory;
        $interfaces = $codebase->getFullyQualifiedInterfaceNames();
        $stmts = [];

        foreach ($interfaces as $i) {
            $stmts[] = $js->assign(
                $this->class_compiler->compileClassName($i),
                $js->object_([])
            );
        }

        return $stmts;
    }

    protected function compileClassesFromCodebase(Codebase $codebase) {
        $classes = $codebase->getFullyQualifiedClassNames();
        $stmts = [];

        // TODO: Check compliance with interfaces.
        // TODO: Check compliance with extended classes.

        foreach ($classes as $cls) {
            $stmts[] = $this->class_compiler->compile(
                $codebase->getClass($cls)
            );
        }

        return $stmts;
    }

    protected function compileScriptInvocationFromCodebase(Codebase $codebase) {
        $js = $this->js_factory;
        $stmts = [];

        $script_classes = $codebase->getClassesThatImplement(JS\Script::class);
        assert(count($script_classes) === 1);
        $php_script_class = array_pop($script_classes);

        $script_class = $this->class_compiler->compileClassName(
            $php_script_class->getAttribute(self::ATTR_FULLY_QUALIFIED_NAME)
        );
        $script = $js->identifier("script");

        if ($php_script_class->hasAttribute(self::ATTR_SCRIPT_DEPENDENCIES)) {
            $dependencies = array_map(function($name) use ($js) {
                $names = explode("\\", $name);
                $name = array_pop($names);
                return $js->new_(
                    $this->class_compiler->compileClassName("{$name}Impl")
                );
            }, $php_script_class->getAttribute(self::ATTR_SCRIPT_DEPENDENCIES));
        }
        else {
            $dependencies = [];
        }
        $stmts[] = $js->assignVar(
            $script,
            $js->new_(
                $script_class,
                ...$dependencies
            )
        );
        $stmts[] = $js->call(
            $js->propertyOf($script, $js->identifier("execute"))
        );

        return $stmts;
    }

    static public function getVisibilityConst(PhpNode $n) {
        if ($n->isPublic()) {
            return self::ATTR_PUBLIC;
        }
        elseif ($n->isProtected()) {
            return self::ATTR_PROTECTED;
        }
        elseif ($n->isPrivate()) {
            return self::ATTR_PRIVATE;
        }
        throw new \LogicException(
            "Method or property is neither public, nor protected, nor private"
        );
    }
}
