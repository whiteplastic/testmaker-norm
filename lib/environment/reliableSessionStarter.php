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
 * @package Library
 */

/**
 * The session ID is specified as a GET parameter
 */
define("SESSION_SOURCE_GET", 1);

/**
 * The session ID is specified as a POST parameter
 */
define("SESSION_SOURCE_POST", 2);

/**
 * The session ID is specified inside a cookie
 */
define("SESSION_SOURCE_COOKIE", 4);

/**
 * The session ID is specified by GET and/or POST parameters
 */
define("SESSION_SOURCE_QUERY", SESSION_SOURCE_GET | SESSION_SOURCE_POST);

/**
 * Silently handles invalid characters in session IDs by filtering session id before calling session_start().
 */

libLoad("environment::errorRecorder");

function reliableSessionStarter()
{
	if (isset($_COOKIE[session_name()]))
		$_REQUEST[session_name()] = $_COOKIE[session_name()];
	if (getSessionSource() && !(preg_match('/^[a-z0-9]+$/i', @$_REQUEST[session_name()]))) {
		unset($_GET[session_name()]);
		unset($_POST[session_name()]);
		unset($_COOKIE[session_name()]);
		unset($_REQUEST[session_name()]);
	}

	$GLOBALS["RECORDED_ERRORS"] = array();
	$currentErrorHandler = set_error_handler("errorRecorder");
	session_start();
	restore_error_handler();
	$errors = $GLOBALS["RECORDED_ERRORS"];
	unset($GLOBALS["RECORDED_ERRORS"]);
	
	if ($errors) {
		$message = "<p>Session Start failed:</p>\n<ul>\n";
		foreach ($errors as $error)
			$message .= "<li>".$error["message"]." (in <b>".$error["file"]."</b> on line <b>".$error["line"]."</b>, error level ".$error["level"].")</li>\n";
		$message .= "</ul>\n";
		trigger_error($message, E_USER_WARNING);
		return FALSE;
	} else {
		return TRUE;
	}
}

/**
 * Searches for a session ID in <kbd>$_COOKIE</kbd>, <kbd>$_GET</kbd> and <kbd>$_POST</kbd>
 *
 * The session ID needs the be specified by a parameter with the same name as the return value of <kbd>session_name()</kbd>.
 *
 * @return integer the encoded session source
 * @see SESSION_SOURCE_GET
 * @see SESSION_SOURCE_POST
 * @see SESSION_SOURCE_QUERY
 * @see SESSION_SOURCE_COOKIE
 */
function getSessionSource()
{
	$sessionSource = 0;

	if (isset($_COOKIE[session_name()])) {
		$sessionSource = $sessionSource | SESSION_SOURCE_COOKIE;
	}
	if (isset($_GET[session_name()])) {
		$sessionSource = $sessionSource | SESSION_SOURCE_GET;
	}
	if (isset($_POST[session_name()])) {
		$sessionSource = $sessionSource | SESSION_SOURCE_POST;
	}

	return $sessionSource;
}

