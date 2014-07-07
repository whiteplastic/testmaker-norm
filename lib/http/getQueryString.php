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
 * Constructs a query string from a set of parameters
 *
 * @param mixed Array of query parameters
 * @param string
 * @return string
 */
function getQueryString($query, $prefix = NULL)
{
	$pairs = array();
	foreach ($query as $name => $value)
	{
		if (isset($prefix)) {
			$name = $prefix."[".$name."]";
		}

		if (is_scalar($value)) {
			$pairs[] = $name."=".urlencode($value);
		}
		elseif (is_array($value)) {
			$pairs[] = getQueryString($value, $name);
		}
	}

	$query = implode("&", $pairs);
	if (! isset($prefix) && $query != "") {
		$query = "?".$query;
	}

	return $query;
}

?>