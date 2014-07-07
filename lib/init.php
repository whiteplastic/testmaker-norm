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
 * Sets up the library
 *
 * Load library modules by calling the function {@link libLoad()}
 *
 * @package Library
 */

/**
 * Represents the base directory of the library
 */
define("LIB", str_replace("\\", "/", dirname(__FILE__))."/");

/**
 * Loads a library module
 *
 * If the module cannot be found, an E_USER_ERROR is printed.
 *
 * A module name consists of zero or more category names
 * and the name of the module file, all seperated by "::".
 *
 * The module path is expected to be
 * <kbd>LIB/category1/.../categoryN/File.php</kbd>
 * for a module named
 * <kbd>category1::...::categoryN::File</kbd>
 *
 * Example:
 * - Module name: <kbd>database::MySQL</kbd>
 * - Module path: <kbd>mylib/database/MySQL.php</kbd>
 * - LIB constant: <kbd>mylib/</kbd>
 *
 * - Module name: <kbd>PEAR</kbd>
 * - Module path: <kbd>thelib/PEAR.php</kbd>
 * - LIB constant: <kbd>thelib/</kbd>
 *
 * @param string The name of the module to load
 * @return boolean FALSE if the module was already loaded, TRUE otherwise
 */
function libLoad($moduleName)
{
	// Stores the already loaded modules to speed things up a bit
	// $loadedModules is used as an associative array avoid linear searching
	static $loadedModules = array();

	if (isset($loadedModules[$moduleName])) {
		return TRUE;
	}

	if (! preg_match("/^[a-zA-Z0-9]+(::[a-zA-Z0-9]+)*$/", $moduleName)) {
		trigger_error("Invalid module name <b>".htmlentities($moduleName)."</b>, please check the documentation of libLoad()", E_USER_ERROR);
	}

	$modulePath = LIB.str_replace("::", "/", $moduleName).".php";

	if (! file_exists($modulePath)) {
		trigger_error("The library module <b>".htmlentities($moduleName)."</b> could not be loaded, <i>".htmlentities($modulePath)."</i> does not exist", E_USER_ERROR);
	}

	require_once($modulePath);
	$loadedModules[$moduleName] = TRUE;

	return FALSE;
}

libLoad("environment::Translation");
$GLOBALS["TRANSLATION"]->addRepository(LIB."translations/");

?>
