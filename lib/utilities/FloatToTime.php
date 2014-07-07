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
 * Provides a utility function to output a time format.
 *
 * @package Library
 */

/**
 * Converts the given floating-point number into a localized string.
 * @param float The number to convert
 * @return string
 */
function floatToTime($value)
{
	$hours = (int) ($value / 60);
	$minutes = $value % 60;
	return sprintf("%02d:%02d", $hours, $minutes);
}

