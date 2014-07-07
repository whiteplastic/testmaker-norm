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
 * Loads the base class
 */
require_once(PORTAL.'BlockPage.php');

/**
 * Display the correct Edit Page for the selected block
 *
  * Default action: {@link doEdit()}
*
 * @package Portal
 */
class BlockEditPage extends BlockPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "edit";

	function doEdit()
	{

		if ($this->block->getId() == 0) {
			$page = &$GLOBALS["PORTAL"]->loadPage('admin_start');
			$page->run();
			return;
		}

		$this->checkAllowed('view', true);

		switch($this->block->getBlockType()) {
			case BLOCK_TYPE_CONTAINER:
				$page = &$GLOBALS["PORTAL"]->loadPage('container_block');
				$page->run();
				break;
			case BLOCK_TYPE_INFO:
				$page = &$GLOBALS["PORTAL"]->loadPage('info_block');
				$page->run();
				break;
			case BLOCK_TYPE_FEEDBACK:
				$page = &$GLOBALS["PORTAL"]->loadPage('feedback_block');
				$page->run();
				break;
			case BLOCK_TYPE_ITEM:
				$page = &$GLOBALS["PORTAL"]->loadPage('item_block');
				$page->run();
				break;
		}
	}
}

?>
