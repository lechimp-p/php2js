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

namespace Lechimp\PHP_JS\App;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimple\Container;

/**
 * The compiler App.
 */
class App extends Application {
    public function __construct() {
        parent::__construct();
        $this->addCustomCommands();
    }

    protected function addCustomCommands() {
    }

    /**
     * Build the dependency injection container.
     *
     * @param   Config
     * @return  Container
     */
    protected function buildDIC(Config $config) {
        $container = new Container();
        return $container;
    }
}
