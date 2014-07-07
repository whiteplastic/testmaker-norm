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
 * Flattens an array to be imploded into a query string
 *
 * @param mixed Array of query parameters
 * @param string
 * @return string
 */
function flattenQueryArray($query, $prefix = NULL)
{
	$pairs = array();
	foreach ($query as $name => $value)
	{
		if (isset($prefix)) {
			$name = $prefix."[".$name."]";
		}

		if (is_scalar($value)) {
			$pairs[$name] = $value;
		}
		elseif (is_array($value)) {
			$pairs = array_merge($pairs, flattenQueryArray($value, $name));
		}
	}

	return $pairs;
}

?>