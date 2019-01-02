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

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AnnotateVisibility extends NodeVisitorAbstract {
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var string|null
     */
    protected $in_class = null;

    public function __construct(Registry $registry) {
        $this->registry = $registry;
    }

    public function beforeTraverse(array $nodes) {
        $this->in_class = null;
    }

    public function enterNode(Node $n) {
        if ($n instanceof Node\Stmt\Class_) {
            $this->in_class = $n->getAttribute(Compiler::ATTR_FULLY_QUALIFIED_NAME);
        }
        if ($n instanceof Node\Expr\PropertyFetch
        ||  $n instanceof Node\Expr\MethodCall) {
            if ($this->in_class === null) {
                return;
            }
            if ((string)$n->var->name !== "this") {
                return;
            }
            if (!($n->name instanceof Node\Identifier)) {
                throw new \LogicException(
                    "Cannot compile access to this with variable expression."
                );
            }
            $n->setAttribute(
                Compiler::ATTR_VISIBILITY,
                $this->registry->getVisibility($this->in_class, (string)$n->name)
            );
        }
    }

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Stmt\Class_) {
            $this->in_class = null;
        }
    }
}
