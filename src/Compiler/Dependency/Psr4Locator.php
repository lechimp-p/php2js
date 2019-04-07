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

namespace Lechimp\PHP2JS\Compiler\Dependency;

use Composer\Autoload\ClassLoader;

class Psr4Locator implements Locator {
    /**
     * @var Locator
     */
    protected $inner;

    /**
     * @var ClassLoader
     */
    protected $class_loader;

    public function __construct(Locator $inner, string $namespace, string $path) {
        $this->inner = $inner;
        $this->class_loader = new ClassLoader();
        $this->class_loader->addPsr4($namespace, $path);
    }

    /**
     * @inheritdocs
     */
    public function isInternalDependency(string $name) : bool {
        if ($this->class_loader->findFile($name)) {
            return false;
        }
        return $this->inner->isInternalDependency($name);
    }

    /**
     * @inheritdocs
     */
    public function getFilenameOfDependency(string $name) {
        $filename = $this->class_loader->findFile($name);
        if ($filename) {
            return $filename;
        }
        return $this->inner->getFilenameOfDependency($name);
    }
}

