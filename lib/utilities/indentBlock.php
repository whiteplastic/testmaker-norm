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
 * Indents each line of a text block
 * @param string The block to indent
 * @param int The indentation depth
 * @param string String to use for indentation
 * @return string Indendet block
 */
function indentBlock($block, $depth = 1, $indent = ' ')
{
	$indent = str_repeat($indent, $depth);
	$block = str_replace("\n", "\n$indent", $block);

	return $indent.$block;
}

?>