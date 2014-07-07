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
 * Returns a list of all parent classes
 *
 * @param string The name of the class
 */
function getParentClasses($className)
{
	$parentClass = $className;
	$parentClasses = array();

	do {
		$current = $parentClass;
		$parentClasses[] = $current;
	} while($parentClass = get_parent_class($current));

	return $parentClasses;
}