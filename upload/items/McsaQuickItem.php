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


require_once('McsaItem.php');

/**
 * @package Upload
 */

/**
 * The class for mcsa_quick item objects
 *
 * @package Upload
 */
class McsaQuickItem extends McsaItem
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'McsaQuickItem';

	public static $description = array(
					'de' => 'Eine Antwortalternative wählbar, mit direkter Weiterleitung',
					'en' => 'Single answer allowed, with quick forwarding'); 
	
  	var $templateFile = 'mcsa_quick.html';

	// Disable the template with multiple items per page
	var $enableWithMultipleItems = false;
}

?>
