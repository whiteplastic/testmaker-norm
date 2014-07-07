<?php

/* This file is part of testMaker.

testMaker is free software; you can redistribute it and/or modify
it under the terms of version 2 of the GNU General Public License as
published by the Free Software Foundation.

testMaker is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */


/**
 * Entry page for the installer. Redirects to the 'installer' page.
 * @package Installer
 */

$dir = dirname($_SERVER['SCRIPT_NAME']);
$dir = substr($dir, 0, strrpos($dir, '/'));
$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
$link = ($https ? "https://" : "http://").$_SERVER['HTTP_HOST'].$dir.'/index.php?page=installer';

header("Location: $link");

?>
