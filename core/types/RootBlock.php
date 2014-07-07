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
 * @package Core
 */

/**
 * Include the Block class
 */
require_once(dirname(__FILE__).'/ContainerBlock.php');

/**
 * RootBlock class
 *
 * Represents the workspace
 *
 * @package Core
 */
class RootBlock extends ContainerBlock
{
	function RootBlock()
	{
		$this->id = 0;
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->type = BLOCK_TYPE_CONTAINER;
		$this->table = '';
		$this->connectTable = DB_PREFIX.'blocks_connect';
		$this->connectId = 'id';
		$this->connectParentId = 'parent_id';
		$this->childrenSequence = DB_PREFIX.'blocks';
	}

	/**
	 * Returns if this block is allowed for access
	 * @return bool
	 */
	function isAccessAllowed($portal = false, $specific = false)
	{
		return false;
	}

	/**
	 * Returns the title of the current block
	 * @return String
	 */
	function getTitle()
	{
		return T('pages.block.root');
	}

	/**
	 * Returns the description of the current block
	 * @return String
	 */
	function getDescription()
	{
		return '';
	}

	/**
	 * Returns if the usage of this block is allowed
	 * @return bool
	 */
	function isUsageAllowed()
	{
		return false;
	}

	/**
	 * Returns if copying of this block is allowed
	 * @return bool
	 */
	function isCopyingAllowed()
	{
		return false;
	}

	function setPosition($position, $parent, &$problems)
	{
		return true;
	}

	function modify($modifications)
	{
		return true;
	}

	function arePermissionsRecursive() {
		return false;
	}

	function getOwner()
	{
		if (! $id = $GLOBALS["PORTAL"]->getUserId()) {
			return NULL;
		}
		return DataObject::getById('User', $id);
	}

	function getShowSubtests()
	{
		return false;
	}
}
