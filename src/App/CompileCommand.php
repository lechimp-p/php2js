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

use Lechimp\PHP_JS\Compiler;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileCommand extends Command {
    const FILE_ARG = "file";
    const PSR4_OPTION = "psr4";

    /**
     * @var Compiler\Factory
     */
    protected $compiler_factory;

    public function __construct(Compiler\Factory $compiler_factory) {
        parent::__construct();
        $this->compiler_factory = $compiler_factory;
    }

    protected function configure() {
        $this
            ->setName("compile")
            ->setDescription("Compiles a script.")
            ->setHelp("This takes a script in PHP and compiles it to JavaScript.")
            ->addArgument(
                self::FILE_ARG,
                InputArgument::REQUIRED,
                "The file containing the view."
            )
            ->addOption(
                self::PSR4_OPTION,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                "Path to the root of a PSR4-namespace, in the form \$NAMESPACE:\$PATH."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $filename = $input->getArgument(self::FILE_ARG);
        $psr4 = $this->splitPsr4Namespaces($input->getOption(self::PSR4_OPTION));

        $compiler = $this->compiler_factory->buildCompiler($psr4);

        $output->writeln($compiler->compile($filename));
    }

    protected function splitPsr4Namespaces(array $raw) {
        $psr4 = [];
        foreach ($raw as $r) {
            $raw = explode(":", $r);
            if (!count($raw) == 2) {
                throw new Symfoncy\Component\Console\Exception\InvalidOptionException(
                    "psr4-namespaces must be given in the form \$NAMESPACE:\$PATH"
                );
            }
            $psr4[$raw[0]] = $raw[1];
        }
        return $psr4;
    }
}
