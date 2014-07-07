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
 * Provides a utility function to output localized floating-point strings (using the correct decimal separator).
 *
 * @package Library
 */

/**
 * Converts the given floating-point number into a localized string.
 * @param float The number to convert
 * @param integer Precision
 * @param boolean Whether to strip '.00' suffices
 * @return string
 */
function floatToString($value, $prec = 2, $stripZeros = true)
{
	$res = sprintf("%0.{$prec}f", $value);
	if ($stripZeros) $res = str_replace('.00', '', $res);
	return str_replace('.', T('utilities.decimal_separator'), $res);
}

