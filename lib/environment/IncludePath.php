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
 * Various methods to assist with setting up the include_path
 *
 * {@link resetIncludePath()} is called immediately after loading.
 *
 * @package Library
 */

resetIncludePath();

/**
 * Resets the include_path to "." (the current directory)
 */
function resetIncludePath()
{
	ini_set("include_path", ".");
}

/**
 * Adds a path to the include_path.
 *
 * Should be called after include_path has been initialized with {@link resetIncludePath()};
 *
 * @param string The path to add to the include_path
 * @return boolean FALSE if $path was already part of the include_path, TRUE otherwise
 */
function addIncludePath($path)
{
	// Windows directory seperator is ";" (to allow for c:\, d:\, etc.)
	// On other systems, ":" ist the directory seperator
	static $directorySeperator;
	if (! isset($directorySeperator)) {
		$directorySeperator = (substr(PHP_OS, 0, 3) == "WIN") ? ";" : ":";
	}

	$includePath = explode($directorySeperator, ini_get("include_path"));

	if (in_array($path, $includePath)) {
		return FALSE;
	}

	$includePath[] = $path;
	$includePath = implode($directorySeperator, $includePath);
	ini_set("include_path", $includePath);

	return TRUE;
}


?>