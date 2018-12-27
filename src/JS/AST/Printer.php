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

namespace Lechimp\PHP_JS\JS\AST;

/**
 * Print the javascript AST.
 */
class Printer {
    public function print(Node $node) {
        $prefix_len = strrpos(self::class, "\\") + 1;
        return $node->para(function(Node $original, Node $n) use ($prefix_len) {
            $class = substr(get_class($n), $prefix_len);
            $method = "print_$class";
            return $this->$method($original, $n);
        });
    }

    protected function print_StringLiteral(Node $original, Node $n) {
        return "\"{$n->value()}\"";
    }

    protected function print_Identifier(Node $original, Node $n) {
        return $n->value();
    }

    protected function print_PropertyOf(Node $original, Node $n) {
        $which = $original->property();
        if ($which instanceof Identifier) {
            return "{$n->object()}.{$n->property()}";
        }
        return "{$n->object()}[{$n->property()}]";
    }

    protected function print_Call(Node $original, Node $n) {
        $params = join(", ", $n->parameters());
        if ($original->callee() instanceof Function_) {
            return "({$n->callee()})($params)";
        }
        return "{$n->callee()}($params)";
    }

    protected function print_Statement(Node $original, Node $n) {
        return "{$n->which()};";
    }

    protected function print_Block(Node $original, Node $n) {
        return join("\n", $n->which());
    }

    protected function print_Function_(Node $original, Node $n) {
        $params = join(", ", $n->parameters());
        $block = str_replace("\n", "\n    ", $n->block());
        return "function($params) {\n    $block\n}";
    }
}
