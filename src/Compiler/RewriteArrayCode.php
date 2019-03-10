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

use Lechimp\PHP_JS\JS;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeTraverser;

class RewriteArrayCode extends NodeVisitorAbstract {
    public function leaveNode(Node $n) {
        if ($n instanceof Node\Expr\ArrayItem) {
            if ($n->byRef) {
                throw new \LogicException("Cannot rewrite array code using byRef.");
            }
            if ($n->key) {
                throw new \LogicException("Cannot rewrite array code using key.");
            }
            return new Node\Expr\FuncCall("push", [$n->value]);
        }
        if ($n instanceof Node\Expr\Array_) {
            $array = new Node\Expr\New_(new Node\Name(\PhpArray::class));
            foreach ($n->items as $item) {
                $array = new Node\Expr\MethodCall($array, $item->name, $item->args);
            }
            return $array;
        }
    }
}
