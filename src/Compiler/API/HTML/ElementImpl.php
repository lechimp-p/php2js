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

namespace HTML;

use Lechimp\PHP2JS\JS\API\HTML\Element;

/**
 * ATTENTION: This is not supposed to work in a PHP-environment.
 * This is just a stub that gets compiled to JS to implement the
 * JS\API\HTML\Element interface. Do not use it yourself.
 */
class ElementImpl extends NodeImpl implements Element {
    public function getInnerHTML() : string {
        return $this->node->innerHTML;
    }

    public function setInnerHTML(string $innerHTML) : void {
        $this->node->innerHTML = $innerHTML;
    }


    public function setId(string $id) : void {
        $this->node->id = $id;
    }

    public function getId() : string {
        return $this->node->id;
    }

    public function setClassName(string $name) : void {
        $this->node->className = $name;
    }

    public function getClassName() : string {
        return $this->node->className;
    }

    public function setAttribute(string $name, string $value) : void {
        $this->node->setAttribute($name, $value);
    }

    public function getAttribute(string $name) : string {
        return $this->node->getAttribute($name);
    }
}

