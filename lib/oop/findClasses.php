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
 * Searches for objects within a variables and returns their class names
 *
 * @param string The name of the module to load
 * @return boolean FALSE if the module was already loaded, TRUE otherwise
 */
function findClasses(&$variables)
{
	$classes = array();

	$objects = array();
	$arrays = array();

	$queue = array(array(&$variable));

	while ($queue)
	{
		$entry = array_shift($queue);
		$current = &$entry[0];

		if (is_array($variable) && ! @$variable["_IS_SEARCHING_CLASSES"]) {
			$variable["_IS_SEARCHING_CLASSES"] = TRUE;
			foreach (array_keys($variable) as $key) {
				if ($key !== "_IS_SEARCHING_CLASSES") {
					$queue[] = array(&$variable[$key]);
				}
			}
			unset($variable["_IS_SEARCHING_CLASSES"]);
		}
		elseif (is_object($variable) && ! @$variable->_IS_SEARCHING_CLASSES) {
			$variable->_IS_SEARCHING_CLASSES = TRUE;
			$classes[] = get_class($variable);
			foreach (get_object_vars($variable) as $key => $value) {
				if ($key !== "_IS_SEARCHING_CLASSES") {
					$queue[] = array(&$variable->$key);
				}
			}
			unset($variable->_IS_SEARCHING_CLASSES);
		}
	}

	for ($i = 0; $i < count($objects); $i++) {
		unset($objects[$i]->_IS_SEARCHING_CLASSES);
	}
	for ($i = 0; $i < count($arrays); $i++) {
		unset($arrays[$i]["_IS_SEARCHING_CLASSES"]);
	}

	sort($classes);

	return $classes;
}

?>