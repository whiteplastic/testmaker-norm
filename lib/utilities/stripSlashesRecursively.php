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
 * Strips slashes recursiveley
 *
 * If $value is an array, stripSlashesRecursively() is applied to each element.
 * Useful to remove the slashes added by the magic_quotes "feature" of PHP.
 *
 * @param mixed Array or scalar value which should be unescaped
 */
function stripSlashesRecursively($value)
{
	if (is_array($value)) {
		return array_map("stripSlashesRecursively", $value);
	}
	
	return stripslashes($value);
}

?>