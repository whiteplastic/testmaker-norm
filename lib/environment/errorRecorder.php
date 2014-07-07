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
 * Stores all errors in <var>$GLOBALS["RECORDED_ERRORS"]</var>
 *
 * It is recommended to initialize this array before calling errorRecorder() the first time.
 * Also, you should unset the variable when restoring the previous error handler to
 * prevent zombie errors.
 */
function errorRecorder($level, $message, $file = '', $line = 0)
{
	$GLOBALS["RECORDED_ERRORS"][] = array("level" => $level, "message" => $message, "file" => $file, "line" => $line);
}

?>