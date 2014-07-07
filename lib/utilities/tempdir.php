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
 * Race-condition free temporary directory creator.
 *
 * @param string Directory where the new directory should be created.
 * @param string Prefix for the new directory.
 * @param int File mode of the new directory.
 * @return string Name of the new directory
 */

function tempdir($dir = TM_TMP_DIR, $prefix = "tmp", $mode = 0700)
{
	if (substr($dir, -1) != '/')
		$dir .= '/';

	if (!is_dir($dir) || !is_writable($dir))
		return NULL;

	do
	{
		$path = $dir . $prefix . mt_rand(0, 9999999);
	} while (!mkdir($path, $mode));
	return $path;
}

