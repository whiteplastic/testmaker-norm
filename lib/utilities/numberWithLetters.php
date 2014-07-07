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
 * Displays $number with letters instead of numbers (0=a, 1=b, ...)
 * @param int The number to convert
 * @param int The minimum length of the string (the letter "a" will be prepended´)
 * @return string
 */
function numberWithLetters($number, $length = 0)
{
	if ($number < 0) {
		return "";
	}

	if ($number == 0) {
		$string = "a";
	}
	else
	{
		for ($e = 0; pow(26, $e) <= $number; $e++) {}

		$string = "";
		for ($i = $e-1; $i >= 0; $i--)
		{
			$x = ($i > 0) ? floor($number / pow(26, $i)) : $number;
			$number -= $x * pow(26, $i);
			$string .= chr(ord('a')+$x);
		}
	}

	while (strlen($string) < $length) {
		$string = "a".$string;
	}

	return $string;
}

?>