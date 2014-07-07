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
 * Shortens a string by cutting out the middle part and replacing it with a fill string.
 *
 * @param string the text to shorten
 * @param int maximum length of the string
 * @param string text to fill in
 * @return string the shortened text
 */
function shortenString($string, $maxLength, $fillString = "(...)", $stripTags = TRUE)
{
	if ($stripTags) {
		$string = strip_tags($string);
	}
	if (strlen($string) > $maxLength) {
		if (strlen($fillString) > $maxLength) {
			$fillString = shortenString($fillString, $maxLength, "");
		}
		$len1 = floor(($maxLength-strlen($fillString)) / 2);
		$len2 = ceil(($maxLength-strlen($fillString)) / 2);

		$newString = "";
		if ($len1 > 0) {
			$newString .= substr($string, 0, $len1);
		}
		$newString .= $fillString;
		if ($len2 > 0)  {
			$newString .= substr($string, -$len2);
		}
		$string = $newString;
	}

	return $string;
}

?>
