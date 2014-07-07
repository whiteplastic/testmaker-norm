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


/*
// Test code
$original = array();
$original["a"] = array();
$original["b"] = array();
$original["a"]["b"] = &$original["b"];
$original["b"]["a"] = &$original["a"];

$original["c"] = array();
$original["c"]["d"] = array();
$original["c"]["e"] = array();
$original["c"]["d"]["e"] = &$original["c"]["e"];
$original["c"]["e"]["e"] = &$original["c"]["e"];

$copy = cleanCopy($original);
/*
*/

/**
 * Creates a copy of a variable that does not contain any references any more.
 *
 * @param mixed A reference to the source variable
 */
function cleanCopy(&$source)
{
	// The first recursion ID
	$id = 1;

	// Recursion IDs to keep
	$markIds = array();

	// Objects that have been changed and need to be cleaned
	$clean = array();

	// The result variable
	$result = NULL;

	// Objects to copy
	$queue = array(array(&$source, &$result));

	while($queue)
	{
		$current = array_shift($queue);
		$source = &$current[0];
		$target = &$current[1];

		if (is_array($source))
		{
			// This is a reference -> remember to mark it
			if (isset($source["__REFERENCE_ID"])) {
				$markIds[$source["__REFERENCE_ID"]][] = &$target;
			}
			// Not a reference -> copy the elements
			else
			{
				$target = array();
				foreach (array_keys($source) as $key) {
					$queue[] = array(&$source[$key], &$target[$key]);
				}
				$source["__REFERENCE_ID"] = $id++;
				$clean[] = $current;
			}
		}
		elseif (is_object($source))
		{
			// This is a reference -> remember to mark it
			if (isset($source->__REFERENCE_ID)) {
				$markIds[$source->__REFERENCE_ID][] = &$target;
			}
			// Not a reference -> copy the elements
			else
			{
				// Evil trick to really copy an object
				$target = unserialize(serialize($source));

				$keys = array_keys(get_object_vars($source));
				foreach ($keys as $key) {
					$queue[] = array(&$source->$key, &$target->$key);
				}
				$source->__REFERENCE_ID = $id++;
				$clean[] = $current;
			}
		}
		else
		{
			$target = $source;
		}
	}

	// Translate the IDs to make them successive
	$translateIds = array();

	while ($clean)
	{
		$current = array_shift($clean);
		$source = &$current[0];
		$target = &$current[1];

		$markId = NULL;

		// Clean the object and determine its recursion ID
		if (is_array($source)) {
			$markId = $source["__REFERENCE_ID"];
			unset($source["__REFERENCE_ID"]);
		}
		elseif (is_object($source)) {
			$markId = $source->__REFERENCE_ID;
			unset($source->__REFERENCE_ID);
		}

		// Decide whether to mark the copy and replace the would be references to it
		if (isset($markIds[$markId]))
		{
			if (! isset($translateIds[$markId])) {
				$translateIds[$markId] = count($translateIds)+1;
			}

			if (is_array($source)) {
				$target["__REFERENCE_ID"] = $translateIds[$markId];
			}
			elseif (is_object($source)) {
				$target->__REFERENCE_ID = $translateIds[$markId];
			}

			for ($i = 0; $i < count($markIds[$markId]); $i++) {
				$markIds[$markId][$i] = "*** REFERENCE TO ".$translateIds[$markId]." ***";
			}
		}
	}

	return $result;
}

?>