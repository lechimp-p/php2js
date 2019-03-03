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

namespace Lechimp\PHP_JS\Compiler\Dependency;

class LocateByList extends DelegatorLocator {
    /**
     * @var bool
     */
    protected $is_internal;

    /**
     * @var array<string, string>
     */
    protected $list;

    public function __construct(Locator $inner, bool $is_internal, array $list) {
        parent::__construct($inner);
        $this->is_internal = $is_internal;
        $this->list = $list;
    }

    /**
     * @inheritdocs
     */
    public function isInternalDependency(string $name) : bool {
        if (array_key_exists($name, $this->list)) {
            return $this->is_internal;
        }
        return $this->inner->isInternalDependency($name);
    }

    /**
     * @inheritdocs
     */
    public function getFilenameOfDependency(string $name) {
        if (array_key_exists($name, $this->list)) {
            return $this->list[$name];
        }
        return $this->inner->getFilenameOfDependency($name);
    }
}

