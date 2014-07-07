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
 * Provides functions to access POST and GET parameters
 *
 * @package Library
 */

/**
 * Returns a POST parameter if set or a default value otherwise
 *
 * @param string Name of the POST parameter
 * @param string Value to be returned if $_POST[$name] is not set
 */
function post($name, $defaultValue = NULL)
{
	if (isset($_POST[$name])) {
		return $_POST[$name];
	} else {
		return $defaultValue;
	}
}

/**
 * Returns a GET parameter if set or a default value otherwise
 *
 * @param string Name of the GET parameter
 * @param mixed Value to be returned if $_GET[$name] is not set
 */
function get($name, $defaultValue = NULL)
{
	if (isset($_GET[$name])) {
		return $_GET[$name];
	} else {
		return $defaultValue;
	}
}

/**
 * Returns a GET or POST parameter if set, or a default value otherwise.
 *
 * @param string Name of the GET/POST parameter
 * @param mixed Value to be returned if that parameter is not set
 */
function getpost($name, $defaultValue = NULL)
{
	return get($name, post($name, $defaultValue));
}

/**
 * Returns a $_SERVER entry if set or a default value otherwise
 *
 * @param string Name of the SERVER entry
 * @param mixed Value to be returned if $_SERVER[$name] is not set
 */
function server($name, $defaultValue = NULL)
{
	if (isset($_SERVER[$name])) {
		return $_SERVER[$name];
	} else {
		return $defaultValue;
	}
}

/**
 * Sets a GET parameter
 *
 * @param string The name of the GET parameter
 * @param mixed The value of the GET parameter
 */
function setGet($name, $value)
{
	$_GET[$name] = $value;
}

/**
 * Sets a POST parameter
 *
 * @param string The name of the POST parameter
 * @param mixed The value of the POST parameter
 */
function setPost($name, $value)
{
	$_POST[$name] = $value;
}

?>
