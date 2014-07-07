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
 * Checks and sets PHP settings
 * 
 * Change the php options to meet your needs, respectively the capabilities of your server
 * @package Core
 */

ini_set("display_errors", FALSE);
// Configuration of error reporting
if (defined('E_DEPRECATED'))
ini_set("error_reporting", E_ALL & ~E_NOTICE & ~E_DEPRECATED);
else
ini_set("error_reporting", E_ALL & ~E_NOTICE);

// Configuration of error reporting (debug mode)
if ($GLOBALS["is_dev_machine"]) {
	ini_set("display_errors", TRUE);
	ini_set("error_reporting", E_ALL);
}

ini_set("magic_quotes_runtime", FALSE);
// Deal with cookie path
$urlPath = $_SERVER['SCRIPT_NAME'];
$urlPath = substr($urlPath, 0, strrpos($urlPath, '/'));
if (!$urlPath) $urlPath = '/';
ini_set('session.cookie_path', $urlPath);

?>
