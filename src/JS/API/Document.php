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

namespace Lechimp\PHP2JS\JS\API;

/**
 * The browsers document API.
 */
interface Document {
    /**
     * Get an HTML-Node by its id.
     *
     * @return null|Html\Element
     */
    public function getElementById(string $id);

    /**
     * Get the body of the document.
     */
    public function getBody() : Html\Element;

    /**
     * Create an element in a namespace.
     */
    public function createElementNS(string $ns, string $tag) : Html\Element;

    /**
     * Create an element.
     */
    public function createElement(string $tag) : Html\Element;
}
