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
 * Cuts everything marked by $mark from $content
 * @see cutBlockFromString
 * @param string block mark
 * @param string content to cut block off
 * @return string the cut block
 */
function pregCutBlockFromString($mark, $content)
{
	$acontent = preg_split($mark, $content);
	if ((count($acontent) % 2) != 1)
	{
		trigger_error('Odd number of marks found, cannot proceed.');
		return FALSE;
	}

	$content = '';
	for($i = 0; ($i * 2) < count($acontent); $i++)
	{
		$content .= $acontent[$i*2];
	}

	return $content;
}

?>