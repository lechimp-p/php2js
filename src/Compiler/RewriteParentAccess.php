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

use Lechimp\PHP2JS\JS;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeTraverser;

class RewriteParentAccess extends NodeVisitorAbstract {
    const JS_NATIVE_parent = "JS_NATIVE_parent";

    public function leaveNode(Node $n) {
        if ($n instanceof Node\Expr\StaticCall && $this->isAccessToParent($n)) {
            return new Node\Expr\MethodCall(
                new Node\Expr\Variable(self::JS_NATIVE_parent),
                $n->name,
                $n->args
            );
        }
        if ($n instanceof Node\Expr\StaticPropertyFetch && $this->isAccessToParent($n)) {
            return new Node\Expr\PropertyFetch(
                new Node\Expr\Variable(self::JS_NATIVE_parent),
                new Node\Identifier($n->name->name)
            );
        }
    }

    protected function isAccessToParent(Node $n) {
        return $n->class->toLowerString() === "parent";
    }
}
