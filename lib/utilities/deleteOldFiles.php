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
 * Deletes old files and directory recursively
 * @param String The directory to scan
 * @param int The timestamp representing "now"
 * @param int The maximum age of files in seconds to avoid deletion
 * @param boolean Whether files and directories starting with a dot (".") should be ignored
 * @param int Nesting level to avoid deletion of $directory
 */
function deleteOldFiles($directory, $time, $ageInSeconds, $ignoreDotFiles = FALSE, $level = 0)
{
	// Ensure trailing slash
	if (substr($directory, -1) != "/") {
		$directory .= "/";
	}

	// Call deleteOldFiles on subdirectories and delete old files in the current directory
	$handle = opendir($directory);
	while ($item = readdir($handle))
	{
		if ($item == "." || $item == ".." || ($ignoreDotFiles && substr($item, 0, 1) == ".")) {
			continue;
		}

		if (is_dir($directory.$item)) {
			deleteOldFiles($directory.$item."/", $time, $ageInSeconds, $ignoreDotFiles, $level + 1);
		}
		elseif (is_file($directory.$item) && filemtime($directory.$item) + $ageInSeconds < $time) {
			unlink($directory.$item);
		}
	}
	closedir($handle);

	// Check if the current directory is to be deleted as well, and do so if it is empty
	// Exception: the directory we started in
	if ($level > 0 && filemtime($directory) + $ageInSeconds < $time)
	{
		$empty = TRUE;
		$handle = opendir($directory);
		while ($item = readdir($handle)) {
			if ($item == "." || $item == "..") {
				continue;
			}
			$empty = FALSE;
			break;
		}
		closedir($handle);

		if ($empty) {
			rmdir($directory);
		}
	}
}

?>