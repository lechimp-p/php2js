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

namespace Lechimp\PHP2JS\Compiler\FilePass;

use Lechimp\PHP2JS\Compiler\FilePass;
use Lechimp\PHP2JS\JS;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AnnotateScriptDependencies extends NodeVisitorAbstract implements FilePass {
    const ATTR = "script_dependencies";

    const API_NAMESPACE = JS\API::class;
    protected static $known_apis = ["Window", "Document"];

    /**
     * @var Node\Stmt\Class_|null
     */
    protected $script_class = null;

    /**
     * @var bool
     */
    protected $in_script_constructor = false;

    public function runsAlone() : bool {
        return false;
    }

    public function beforeTraverse(array $nodes) {
        $this->script_dependencies = [];
    }

    public function enterNode(Node $n) {
        switch (get_class($n)) {
            case Node\Stmt\Class_::class:
                foreach ($n->implements as $i) {
                    if ((string)$i === JS\Script::class) {
                        $this->script_class = $n;
                        break;
                    }
                }
                break;
            case Node\Stmt\ClassMethod::class:
                if (is_null($this->script_class) || (string)$n->name !== "__construct") {
                    break;
                }
                $dependencies = array_map(function(Node\Param $p) {
                    if (is_null($p->type)) {
                        throw new Exception(
                            "Script-classes need to typehint their constructor arguments"
                        );
                    }
                    $type = explode("\\", (string)$p->type);
                    $api = array_pop($type);
                    $namespace = join("\\", $type);
    
                    if ($namespace !== self::API_NAMESPACE
                    || !in_array($api, self::$known_apis)
                    ) {
                        throw new Exception(
                            "Script-classes can only have dependencies from ".JS\API::class.
                            ", found '{$p->type}'."
                        );
                    }
                    $p->type = null;
                    return "$namespace\\$api";
                }, $n->params);
                $this->script_class->setAttribute(self::ATTR, $dependencies);
                break;
        }
    }

    public function leaveNode(Node $n) {
        switch (get_class($n)) {
            case Node\Stmt\Class_::class:
                $this->script_class = null;
                break;
        }
    }
}
