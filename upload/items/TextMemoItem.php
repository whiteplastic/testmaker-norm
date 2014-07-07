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


require_once('TextLineItem.php');

/**
 * @package Upload
 */

/**
 * The class for text_memo item objects
 *
 * @package Upload
 */
class TextMemoItem extends TextLineItem
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'TextMemoItem';

	public static $description = array(
					'de' => 'Freier Text, mehrzeilig',
					'en' => 'Free text, several rows');
	
 	var $templateFile = 'text_memo.html';
	
	function hasSimpleAnswer()
	{
		return false;
	}
}

?>
