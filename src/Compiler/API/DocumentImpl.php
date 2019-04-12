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

use Lechimp\PHP2JS\JS\API\Document;
use Lechimp\PHP2JS\JS\API\Html;

/**
 * ATTENTION: This is not supposed to work in a PHP-environment.
 * This is just a stub that gets compiled to JS to implement the
 * JS\API\Document interface. Do not use it yourself.
 */
class DocumentImpl implements Document {
    /**
     * Get an HTML-Node by its id.
     *
     * @return null|Html\Element
     */
    public function getElementById(string $id) {
        $result = $document->getElementById($id);
        if ($result === null) {
            return null;
        }
        return new \HTML\ElementImpl($result);
    }

    /**
     * Get the body of the document.
     */
    public function getBody() : Html\Element {
        return $document->body;
    }

    /**
     * Create an element in a namespace.
     */
    public function createElementNS(string $ns, string $tag) : Html\Element {
        return new \HTML\ElementImpl($document->createElementNS($ns, $tag));
    }

    /**
     * Create an element.
     */
    public function createElement(string $tag) : Html\Element {
        return new \HTML\ElementImpl($document->createElement($ns, $tag));
    }

    /**
     * Create a text node.
     */
    public function createTextNode(string $content) : Html\TextNode {
        return new \HTML\ElementImpl($document->createTextNode($ns, $tag));
    }
}

