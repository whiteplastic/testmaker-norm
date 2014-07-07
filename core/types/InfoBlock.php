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
require_once(dirname(__FILE__).'/ParentTreeBlock.php');

/**
 * Include the InfoPage class
 */
require_once(dirname(__FILE__).'/InfoPage.php');

/**
 * InfoBlock class
 *
 * @package Core
 */

class InfoBlock extends ParentTreeBlock
{
	function InfoBlock($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		$this->table = DB_PREFIX.'info_blocks';
		$this->sequence = DB_PREFIX.'blocks';
		$this->childrenTable = DB_PREFIX.'info_pages';
		$this->ParentTreeBlock($id);
	}

	function _returnTreeChild($id) {
		return new InfoPage($id);
	}

	/**
	 * modify the given informations in the block
	 * @param mixed[] $modification values to be modified
	 * @return bool
	 */
	function modify($modifications)
	{
		if(!is_array($modifications)) {
				trigger_error('<b>Block:modify</b>: $modifications is no valid array');
		}

		$avalues = array();
		if(array_key_exists('title', $modifications)) {
			$avalues['title'] = $modifications['title'];
		}
		if(array_key_exists('description', $modifications)) {
			$avalues['description'] = $modifications['description'];
		}
		if(array_key_exists('permissions_recursive', $modifications)) {
			$avalues['permissions_recursive'] = $modifications['permissions_recursive'];
		}
		if(array_key_exists('pos', $modifications)) {
			$avalues['pos'] = $modifications['pos'];
		}
		if(array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}
		if(array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : 0;
		}

		return $this->_modify($avalues);
	}

	/**
	 * Returns a copy of the current block
	 * @param parent id to copy block into
	 * @param 2 dimensional array where to store changed ids from item blocks and item answers for feedback blocks and dimensions format: array('blocks' => array(id1, id2, ...), 'item_answers' => array(id1, id2, ...))
	 * @param array where to store problems if the block would be copied
	 * @return Block
	 */
	function copyNode($parentId, &$changedIds, &$problems)
	{
		$block = parent::copyNode($parentId, $changedIds, NULL, $problems);

		if(count($problems) > 0)
		{
			return false;
		}
		
		return $block;
	}

	/**
	 * creates and returns a new info page
	 * @param $position position of page in infoblock
	 * @param $optionalInfos associative array of optional informations (content)
	 * @return InfoPage
	 */
	function createTreeChild($infos = array())
	{
		$avalues = array();
		if(array_key_exists('title', $infos))
		{
			$avalues['title'] = $infos['title'];
		}
		if(array_key_exists('content', $infos))
		{
			$avalues['content'] = $infos['content'];
		}
		if(array_key_exists('pos', $infos))
		{
			$avalues['pos'] = $infos['pos'];
		}

		return $this->_createTreeChild($avalues);
	}

	/**
	 * prepare given array with data for database insertion
	 * @param array associative array with database fields and content
	 * @return array associative array with database fields and content
	 */
	function prepareDBData($data) {

		if(!is_array($data)) {
			trigger_error('<b>InfoBlock:prepareDBData</b>: $data is no valid array');
		}

		return array();

	}
}

?>
