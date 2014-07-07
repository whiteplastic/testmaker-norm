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
 * Checks if a potential UTF-8 multi-byte sequence has a valid suffix part.
 * @access private
 */
function _isUtf8Suffix($str, $offset, $len)
{
	if ($len+$offset > strlen($str)) return false;
	for ($i = 0; $i < $len; $i++) {
		$j = ord($str[$i + $offset]);
		if ($j < 0x80 || $j >= 0xC0) return false;
	}
	return true;
}

/**
 * Checks if the given string contains a UTF-8 multi-byte sequence.
 * @access private
 */
function _isUtf8Sequence($chrs, $offset)
{
	$i = ord($chrs[$offset]);
	if ($i >= 0xFE) return false;
	if ($i >= 0xC0 && $i < 0xE0) return _isUtf8Suffix($chrs, $offset + 1, 1) ? 1 : false;
	if ($i >= 0xE0 && $i < 0xF0) return _isUtf8Suffix($chrs, $offset + 1, 2) ? 2 : false;
	if ($i >= 0xF0 && $i < 0xF8) return _isUtf8Suffix($chrs, $offset + 1, 3) ? 3 : false;
	if ($i >= 0xF8 && $i < 0xFC) return _isUtf8Suffix($chrs, $offset + 1, 4) ? 4 : false;
	if ($i >= 0xFC && $i < 0xFE) return _isUtf8Suffix($chrs, $offset + 1, 5) ? 5 : false;
	return false;
}

/**
 * Converts a UTF-8 string into ISO-8859-1 if necessary/possible.
 *
 * @param string Text to convert
 * @return string Converted text
 */
function deUtf8($string)
{
	$hasUtf8 = false;
	for ($i = 0; $i < strlen($string); $i++) {
		if (ord($string[$i]) < 0x80) continue;
		$res = _isUtf8Sequence($string, $i);
		if ($res === false) return $string;
		$i += $res;
		$hasUtf8 = true;
	}
	return $hasUtf8 ? utf8_decode($string) : $string;
}

?>
