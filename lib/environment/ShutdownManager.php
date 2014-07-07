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
 * Shutdown Manager
 *
 * The Shutdown Manager is an extension to register_shutdown_function().
 * Normally, all callbacks submitted to register_shutdown_function() are called
 * one after another, in the same order they were submitted during execution.
 * With the Shutdown Manager, you can influence the execution order by assigning
 * priority levels to each callback.
 *
 * @package Library
 */


/**
 * Callbacks that should be called before all other callbacks
 */
define("SHUTDOWN_POSITION_START", 0);
/**
 * For callbacks that do not have a special priority
 */
define("SHUTDOWN_POSITION_BETWEEN", 1);
/**
 * For callbacks that should be called at the very end (e.g. for closing a database connection)
 */
define("SHUTDOWN_POSITION_END", 2);

// Ensure that the Shutdown Manager gets called at the end of the script execution
register_shutdown_function("callShutdownFunctions");

$GLOBALS["SHUTDOWN_MANAGER"] = array(
	SHUTDOWN_POSITION_START => array(),
	SHUTDOWN_POSITION_BETWEEN => array(),
	SHUTDOWN_POSITION_END => array(),
);

/**
 * Adds a callback to the list
 * @param mixed A valid callback (@link http://www.php.net/is_callable)
 * @param int The priority level
 * @param array Arguments to be passed to the callback function
 */
function registerShutdownFunction($callback, $position = SHUTDOWN_POSITION_BETWEEN, $arguments = array())
{
	if (! is_callable($callback)) {
		trigger_error("Invalid callback ".var_export($callback, TRUE), E_USER_ERROR);
	}
	if (! isset($GLOBALS["SHUTDOWN_MANAGER"][$position])) {
		trigger_error("Invalid shutdown position ".htmlentities($position), E_USER_ERROR);
	}
	if (! is_array($arguments)) {
		trigger_error("Function arguments have to be provided as an array", E_USER_ERROR);
	}

	$GLOBALS["SHUTDOWN_MANAGER"][$position][] = array("callback" => $callback, "arguments" => $arguments);
}

/**
 * Executes the callbacks according to their priorities
 */
function callShutdownFunctions()
{
	foreach ($GLOBALS["SHUTDOWN_MANAGER"] as $position => $functions) {
		foreach ($functions as $function) {
			call_user_func_array($function["callback"], $function["arguments"]);
		}
	}
}

?>