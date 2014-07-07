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
 * Formats a filesize to make it more comprehendable.
 * Example: 1572864 => 1.5 MB
 *
 * @param integer The size to format
 * @return integer The number of digits after the decimal point
 * @return boolean Whether to do <abbr title="Megabyte">MB</abbr> or just MB
 */
function formatFilesize($size, $precision = 1, $includeAbbr = FALSE)
{
	$units = array("B", "kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
	$explanations = array("Byte", "Kilobyte", "Megabyte", "Gigabyte", "Terabyte", "Petabyte", "Exabyte", "Zettabyte", "Yottabyte");

	$i = 0;
	while ($size > 1024 && ($i+1) < count($units)) {
		$size /= 1024;
		$i++;
	}

	return sprintf("%.".$precision."f", $size)." ".($includeAbbr ? '<abbr title="'.$explanations[$i].'">'.$units[$i].'</abbr>' : $units[$i]);
}

?>