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

namespace Lechimp\PHP2JS\JS\AST;

/**
 * Represents an try: try { statements... } catch(identifier) { statements... } finally { statements... }
 */
class Try_ extends Node {
    /**
     * @var mixed
     */
    protected $try;

    /**
     * @var mixed
     */
    protected $catch_identifier;

    /**
     * @var mixed
     */
    protected $catch;

    /**
     * @var mixed
     */
    protected $finally;

    public function __construct($try, $catch_identifier, $catch, $finally = null) {
        $this->try = $try;
        $this->catch_identifier = $catch_identifier;
        $this->catch = $catch;
        $this->finally = $finally;
    }

    /**
     * @return Node (specificially the implementing class)
     */
    public function fmap(callable $f) {
        return new Try_($f($this->try), $f($this->catch_identifier), $f($this->catch), $this->finally ? $f($this->finally) : null);
    }

    public function try_() {
        return $this->try;
    }

    public function catchIdentifier() {
        return $this->catch_identifier;
    }

    public function catch_() {
        return $this->catch;
    }

    public function finally_() {
        return $this->finally;
    }
}
