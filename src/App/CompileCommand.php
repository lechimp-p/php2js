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

namespace Lechimp\PHP_JS\App;

use Lechimp\PHP_JS\Compiler\Compiler;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompileCommand extends Command {
    const FILE_ARG = "file";

    /**
     * @var Compiler
     */
    protected $compiler;

    public function __construct(Compiler $compiler) {
        parent::__construct();
        $this->compiler = $compiler;
    }

    protected function configure() {
        $this
            ->setName("compile")
            ->setDescription("Compiles a script.")
            ->setHelp("This takes a script in PHP and compiles it to JavaScript.")
            ->addArgument(self::FILE_ARG, InputArgument::REQUIRED, "The file containing the view.");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $filename = $input->getArgument(self::FILE_ARG);

        $output->writeln($this->compiler->compile($filename));
    }
}
