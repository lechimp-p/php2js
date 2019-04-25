#!/usr/bin/env php
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

$base_dir = __DIR__;

$src_dir = "$base_dir/src";
$vendor_dir = "$base_dir/vendor";
$php2js_path = "$base_dir/php2js.php";

$build_dir = __DIR__;
$phar_name = "php2js.phar";
$phar_path = "$build_dir/$phar_name";

// Remove previously created phar if one exists.
if (file_exists($phar_path)) {
    unlink($phar_path);
}

$phar = new Phar
    ( $phar_path
    , FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME
    , $phar_name
    );

$phar->buildFromIterator((function() use ($src_dir, $vendor_dir, $php2js_path) {
        $src = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src_dir));
        foreach ($src as $path => $info) {
            yield $path => $info;
        }

        $vendor = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($vendor_dir));
        foreach ($vendor as $path => $info) {
            yield $path => $info;
        }

        yield $php2js_path => new \SplFileInfo($php2js_path);
    })(),
    $base_dir
);

$phar->setStub(<<<STUB
#!/usr/bin/env php
<?php
Phar::mapPhar();
include "phar://$phar_name/php2js.php";
__HALT_COMPILER();
STUB
);

chmod($phar_path, 0755);
