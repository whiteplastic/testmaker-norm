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
 * Defines the {@link http://www.php.net/array_combine array_combine()} function for PHP 4
 * @access private
 */
if (! function_exists("array_combine"))
{
	function array_combine($first, $second)
	{
		if (count($first) != count($second)) {
			trigger_error("Only array of equal size can be combined", E_USER_ERROR);
			return NULL;
		}

		$array = array();
		for ($i = 0; $i < count($first); $i++) {
			$array[$first[$i]] = $second[$i];
		}
		return $array;
	}
}

?>