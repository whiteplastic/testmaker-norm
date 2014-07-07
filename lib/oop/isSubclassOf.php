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
 * Determines whether <kbd>$className</kbd> is a subclass of $parentClassName
 *
 * $className can be a string (the name of the class) or an $object.
 * If it is an object, the predefined function is_subclass_of() is used.
 *
 * @param mixed Name of the potential subclass (can also be an object)
 * @param string Name of the potential parent class
 * @return boolean TRUE if $className is an instance of $parentClassName
 */
function isSubclassOf($className, $parentClassName)
{
	if (is_object($className)) {
		return is_subclass_of($className, $parentClassName);
	}

	$className = strtolower($className);
	$parentClassName = strtolower($parentClassName);

	while ($className != "" && $className != $parentClassName) {
		$className = strtolower(get_parent_class($className));
	}

	return $className != "";
}

?>