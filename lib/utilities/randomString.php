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
 * Generates a random string
 *
 * Only characters (a-z, lower and upper case) and digits (0-9) are used to compose the string.
 * If the tan parameter is set to true, the character l and the digits 1 and 0 are omitted.
 *
 * @param integer The length of the string to generate
 * @return string A random string
 */
function randomString($length, $forTan = false)
{
	if ($forTan)
		$chars = array(2,3,4,5,6,7,8,9,'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
	else
		$chars = array(0,1,2,3,4,5,6,7,8,9,'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
	$string = '';
	for ($i = 0; $i < $length; $i++) {
		$string .= $chars[mt_rand(0, sizeof($chars) - 1)];
	}
	return $string;
}

?>