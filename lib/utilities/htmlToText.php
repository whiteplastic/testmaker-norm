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
 * Converts HTML to simple text
 *
 * @param String The HTML code to convert
 */
function htmlToText($html)
{
	// Strip the tags, decode the entities.
	$text = $html;
	$text = strip_tags($text);
	$text = html_entity_decode($text, ENT_COMPAT, "ISO8859-15");
	$text = trim($text);

	// Nothing left? Try to use the file name of the first used image then.
	if ($text == "") {
		$text = $html;
		if (preg_match("/<img[^>]*\ssrc=([\"'])([^\"']+)/", $text, $match)) {
			$text = $match[2];
			$text = preg_replace("#^.*[/\\\\]#", "", $text);
		} else {
			// Nothing meaningful could be found
			$text = "";
		}
	}

	return $text;
}

?>