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
 * Initializes Library and Core
 * @package Portal
 */

header("Content-type: text/html; charset=ISO-8859-15");

/**
 * Represents the root directory of the project
 */
define('ROOT', str_replace('\\', '/', dirname(__FILE__)).'/');

/**
 * Represents the root directory of the external library
 */
define('EXTERNAL', ROOT.'external/');

/**
 * Checks the PHP settings
 */
require_once(ROOT."phpsettings.php");

/**
 * Initializes Library
 */
start("Initializing Library");
require_once(ROOT.'lib/init.php');
stop();

/**
 * Initializes Installer
 */
start("Initializing Installer");
if (!file_exists(dirname(__FILE__)."/installer_off"))
require_once(ROOT.'installer/init.php');
stop();

/**
 * Initializes Core
 */
start("Initializing Core");
require_once(ROOT.'core/init.php');
stop();

?>