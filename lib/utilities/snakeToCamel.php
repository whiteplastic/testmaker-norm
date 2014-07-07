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
 * Converts a snake_case string to a camelCase string
 *
 * @param string The snake_case string to convert
 * @param boolean Whether the first letter should be upper case or not
 * @return string The camelCase equivalent
 */
function snakeToCamel($text, $firstUpper = true)
{
	$regex = '/(';
	if ($firstUpper) $regex .= '^|';
	$regex .= '_)([a-z])/e';
	return preg_replace($regex, "strtoupper('\\2')", $text);
}

/**
 * Converts a camelCase string to a snake_case string
 *
 * @param string The camelCase string to convert
 * @return string The snake_case equivalent
 */
function camelToSnake($text)
{
	return trim(preg_replace('/([A-Z])/e', "strtolower('_\\1')", $text), '_');
}

?>
