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
 * Censors variables within variables recursively according to their key.
 * The key is either the name of the object property or the array index.
 *
 * @param mixed The variable to censor
 * @return string The replacement value
 */
function censorKeys(&$variable, $search, $replacement)
{
	$objects = array();
	$arrays = array();

	$queue = array(array(&$variable, NULL));

	while ($queue)
	{
		$entry = array_shift($queue);
		$current = &$entry[0];
		$currentKey = $entry[1];

		if (is_array($current) && ! @$current["_IS_FILTERING_PASSWORD"]) {
			$arrays[] = &$current;
			$current["_IS_FILTERING_PASSWORD"] = TRUE;
			foreach (array_keys($current) as $key) {
				if ($key !== "_IS_FILTERING_PASSWORD") {
					$queue[] = array(&$current[$key], $key);
				}
			}
		}
		elseif (is_object($current) && ! @$current->_IS_FILTERING_PASSWORD) {
			$objects[] = &$current;
			$current->_IS_FILTERING_PASSWORD = TRUE;
			foreach (get_object_vars($current) as $key => $value) {
				if ($key !== "_IS_FILTERING_PASSWORD") {
					$queue[] = array(&$current->$key, $key);
				}
			}
		}
		elseif (is_scalar($current)) {
			if (preg_match($search, $currentKey)) {
				$current = $replacement;
			}
		}
	}

	for ($i = 0; $i < count($objects); $i++) {
		unset($objects[$i]->_IS_FILTERING_PASSWORD);
	}
	for ($i = 0; $i < count($arrays); $i++) {
		unset($arrays[$i]["_IS_FILTERING_PASSWORD"]);
	}
}

?>