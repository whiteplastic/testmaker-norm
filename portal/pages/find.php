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
 * @package Portal
 */

/**
 * Finds a block in testmaker and redirects to the related block edit page 
 *
 * Default action: {@link doFind()}
 *
 * @package Portal
 */
class FindPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "find_block";

	function doFindBlock()
	{
		$blockId = get('block_id', 0);
		$db = $GLOBALS['dao']->getConnection();

		$workingPath = "0_";
		$id = $blockId;
		if($id != 0)
		{
			do
			{
				$pId = $db->getOne("SELECT parent_id FROM tm_blocks_connect WHERE id = ?", array($id));
				if(!$pId) break;
				$workingPath .= $pId . "_";
				$id = $pId;
			} while ($pId != 0);
		}
		redirectTo('block_edit', array('working_path' => $workingPath . $blockId . "_"));
	}
}
