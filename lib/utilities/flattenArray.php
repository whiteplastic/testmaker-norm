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
 * Reduces the first sub-level of a nested array into the main level, keeping
 * the nested arrays' keys in a new field in the sub-arrays. Example:
 *
 * <code>
 *   $a = array(
 *     1 => array(array('foo', 'bar')),
 *     17 => array(array('baz' => 'quux'), array(2,3))
 *   );
 *   $b = flattenArray($a, 'id');
 *   # Result:
 *   # array(
 *   #   array('id' => 1, 'foo', 'bar'),
 *   #   array('id' => 17, 'baz' => 'quux'),
 *   #   array('id' => 17, 2, 3)
 *   # )
 * </code>
 *
 * @param mixed[] Array to flatten.
 * @param mixed[] Key to store old intermediate-level keys in (or NULL to just
 *   discard the old keys).
 * @return mixed[]
 */
function flattenArray($source, $targetKey = NULL)
{
	$result = array();
	foreach ($source as $key => $list) {
		foreach ($list as $value) {
			if ($targetKey) {
				$value = array_merge($value, array($targetKey => $key));
			}
			$result[] = $value;
		}
	}
	return $result;
}

