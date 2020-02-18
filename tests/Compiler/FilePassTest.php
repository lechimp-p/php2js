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

namespace Lechimp\PHP2JS\Test\Compiler;

use Lechimp\PHP2JS\Compiler\FilePass;
use Lechimp\PHP2JS\Test\Compiler\PHPTestHelpers;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;

abstract class FilePassTest extends \PHPUnit\Framework\TestCase {
    use PHPTestHelpers;

    public function setUp() : void {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    abstract protected function getFilePass() : FilePass;

    protected function applyFilePassTo(string $php) : array {
        $ast = $this->parser->parse($php);
        $t = new NodeTraverser();
        $t->addVisitor($this->getFilePass());
        return $t->traverse($ast);
    }
}

