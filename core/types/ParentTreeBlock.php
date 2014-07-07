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
require_once(dirname(__FILE__).'/Block.php');

/**
 * ParentBlock class
 *
 * @package Core
 */

class ParentTreeBlock extends Block
{

	/**#@+
	 * @access private
	 */
	var $childrenTable;
	var $childrenConnector;
	/**#@-*/

	function ParentTreeBlock($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		if(!isset($this->childrenTable))
		{
			trigger_error('<b>ParentBlock</b>: $this->childTable was not set');
		}
		$this->childrenConnector = 'block_id';
		$this->Block($id);
	}

	function _returnTreeChild($id)
	{
		trigger_error('<b>ParentTreeBlock:_returnTreeChild</b>: function not overwritten by inherited class');
	}

	/**
	 * get all tree children of the current block orderd by position
	 * @return TreeNode
	 */
	function &getTreeChildren()
	{
		if ($res = retrieve(get_class($this) .'Children', $this->id)) {
			return $res;
		}

		$query = 'SELECT id FROM '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? ORDER BY pos ASC';
		$ids = $this->db->getAll($query, array($this->id));
		if($this->db->isError($ids)) {
			return false;
		}
		$children = array();
		for($i = 0; $i < count($ids); $i++)
		{
			$children[] = $this->_returnTreeChild($ids[$i]['id']);
		}

		store(get_class($this) .'Children', $this->id, $children);
		return $children;
	}

	/**
	 * returns the next free position for a child node
	 * @return integer
	 */
	function getNextFreeTreePosition()
	{
		$query = 'SELECT pos FROM '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? ORDER BY pos DESC LIMIT 1';
		if(!($position = $this->db->getOne($query, array($this->id))))
		{
			return 1;
		}
		else
		{
			if($this->db->isError($position)) {
				return false;
			}

			return (((int) $position) + 1);
		}

	}

	/**
	 * creates and returns a new tree child
	 * @param $avalues associative array with informations to insert
	 * @return TreeNode
	 */
	function _createTreeChild($avalues)
	{
		if(array_key_exists('pos', $avalues) && !preg_match('/^[0-9]+$/', $avalues['pos']))
		{
			trigger_error('<b>ParentTreeBlock:createTreeChild</b>: '.$position.' is no valid position');
			return false;
		}

		if(!array_key_exists('pos', $avalues))
		{
			$avalues['pos'] = $this->getNextFreeTreePosition();
		}



		$id = $this->db->nextId($this->childrenTable);
		if($this->db->isError($id)) {
			return false;
		}

		$avalues['id'] = $id;
		$avalues[$this->childrenConnector] = $this->id;
		$avalues['t_created'] = time();
		$avalues['u_created'] = $GLOBALS['PORTAL']->getUserId();
		$avalues['t_modified'] = time();
		$avalues['u_modified'] = $GLOBALS['PORTAL']->getUserId();

		$values = '';
		$columns = '';
		$quote = array();
		for(reset($avalues); list($column, $value) = each($avalues); ) {
			if(strlen($columns) > 0)
			{
				$columns .= ', ';
			}
			if(strlen($values) > 0)
			{
				$values .= ', ';
			}
			$columns .= $column;
			$values .= '?';
			$quote[] = $value;
		}

		$query = 'INSERT INTO '.$this->childrenTable.' ('.$columns.') VALUES ('.$values.')';
		$result = $this->db->query($query, $quote);
		if($this->db->isError($result)) {
			return false;
		}

		return $this->_returnTreeChild($id);
	}

	/**
	 * Return media_connect_id
	 *
	 * @return integer
	 */
	function getMediaConnectId()
	{
		return $this->_getField('media_connect_id');
	}

	/**
	 * Checks if any tree child exists at the given position
	 * @param integer Position of tree child in block
	 * @return boolean
	 */
	function existsTreeChildAtPosition($position)
	{
		if(!preg_match('/^[0-9]+$/', $position))
		{
			trigger_error('<b>ParentTreeBlock:isAnyPageAtPosition</b>: '.$position.' is no valid position');
			return false;
		}
		$query = 'SELECT count(pos) from '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? AND pos = ?';
		$result = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($result)) {
			return false;
		}
		if($result == 1 )
		{
			return true;
		}
		elseif($result == 0)
		{
			return false;
		}
		else
		{
			trigger_error('<b>ParentTreeBlock:existsTreeChildAtPosition</b>: database is inconsistent');
			return false;
		}
	}

	/**
	 * returns tree child at given position
	 * @param integer position of tree child in block
	 * @return TreeNode
	 */
	function getTreeChildByPosition($position)
	{
		if(!preg_match('/^[0-9]+$/', $position))
		{
			trigger_error('<b>ParentTreeBlock:getTreeChildByPosition</b>: '.$position.' is no valid position');
			return false;
		}
		$query = 'SELECT count(pos) from '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? AND pos = ?';
		$result = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($result)) {
			return false;
		}
		if($result == 0 )
		{
			trigger_error('<b>ParentTreeBlock:getTreeChildByPosition</b>: no tree child found at position '.$position);
			return false;
		}
		$query = 'SELECT id FROM '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? AND pos = ?';
		$id = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($id)) {
			return false;
		}

		return $this->_returnTreeChild($id);
	}

	/**
	 * returns tree child by given id
	 * @param integer id of tree child in block
	 * @return TreeNode
	 */
	function getTreeChildById($id)
	{
		if(!$this->existsTreeChild($id))
		{
			trigger_error('<b>ParentTreeBlock:getTreeChildById</b>: '.$id.' is no valid tree child id in current block');
			return false;
		}

		return $this->_returnTreeChild($id);
	}
	

	/**
	 * delete tree child by given id
	 * @param integer id of tree child in block
	 * @return boolean
	 */
	function deleteTreeChild($id)
	{
		if(!$this->existsTreeChild($id))
		{
			trigger_error('<b>ParentTreeBlock:deleteTreeChild</b>: '.$id.' is no valid tree child id in current block');
			return false;
		}

		$child = $this->getTreeChildById($id);
		$child->cleanUp();
		$query = 'DELETE FROM '.$this->childrenTable.' WHERE id = ?';
		$result = $this->db->query($query, array($id));
		if($this->db->isError($result)) {
			return false;
		}
		return true;
	}

	/**
	 * returns if a tree child exists in current block
	 * @param integer id of page
	 * @return boolean
	 */
	function existsTreeChild($id)
	{
		if(!preg_match('/^[0-9]+$/', $id))
		{
			trigger_error('<b>ParentTreeBlock:existsTreeChild</b>: '.$id.' is no valid id');
			return false;
		}
		$query = 'SELECT count(id) from '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? AND id = ?';
		$result = $this->db->getOne($query, array($this->id, $id));
		if($this->db->isError($result)) {
			return false;
		}
		if($result == 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * prepares everything to delete this block itself
	 * @return boolean
	 */
	function cleanUp()
	{
		//delete children
		$children = $this->getTreeChildren();
		for($i = 0; $i < count($children); $i++) {
			$this->deleteTreeChild($children[$i]->getId());
		}

		//delete media connection if exits
		$mediaConnectId = $this->getMediaConnectId();
		if($mediaConnectId != NULL && $mediaConnectId != '') {
			$fileHandling = new FileHandling();
			$fileHandling->deleteMediaConnection($mediaConnectId);
		}

		return true;
	}

	/**
	 * Returns a copy of the current node
	 * @param parent id to copy item block into
	 * @param 2 dimensional array where to store changed ids from item blocks and item answers for feedback blocks and dimensions format: array('blocks' => array(id1, id2, ...), 'item_answers' => array(id1, id2, ...))
	 * @param predefined new node id
	 * @param array where to store problems if the block would be copied
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds, $newNodeId, &$problems)
	{
		$newNode = parent::copyNode($parentId, $changedIds, $newNodeId, $problems);

		if(count($problems) == 0)
		{
			//copy media connection
			$fileHandling = new FileHandling();
			$mediaConnectId = $newNode->getMediaConnectId();

			if($mediaConnectId != NULL && $mediaConnectId != '') {
				$modifications = array('media_connect_id' => $fileHandling->copyMediaConnection($mediaConnectId, $newNode->getId()));
				$newNode->modify($modifications);
				
				$query = "SELECT  cert_template_name FROM ".DB_PREFIX."feedback_blocks WHERE id=?";
				$filename = $this->db->getOne($query, array($newNode->id));

				//strip old media connect id
				$filename = substr(strchr($filename, '_'), 1);
				
				$templateFile = $newNode->getMediaConnectId().'_'.$filename;

				$query = "UPDATE ".DB_PREFIX."feedback_blocks SET cert_fname_item_id = ? , cert_lname_item_id = ? , cert_bday_item_id = ? , cert_template_name = ? WHERE id = ? LIMIT 1";
				$this->db->query($query, array(NULL, NULL, NULL, $templateFile, $newNode->id));
			}

			//copy children
			$children = $this->getTreeChildren();
			for($i = 0; $i < count($children); $i++)
			{
				$children[$i]->copyNode($newNode->getId(), $changedIds);
			}
		}

		return $newNode;
	}

	/**
	 * Order childs - this function is used to order childs of feedback blocks and info blocks, because
	 * the order of such blocks children can be handled unproblematically (no feedback source blocks or item display
	 * conditions have to be taken care of)
	 *
	 * @param mixed new itemorder
	 * @return mixed
	 */
	function orderChilds($order)
	{
		$errors = array();
		$ids = array();

		if (count($order) != count($this->getTreeChildren()))
		{
			return;
		}
		foreach ($order as $id)
		{
			if (!$this->existsTreeChild($id))
			{
				return;
			}
			if (in_array($id, $ids))
			{
				return;
			}
			else
			{
				$ids[] = $id;
			}
		}

		foreach($order as $position => $id)
		{
			$child = $this->getTreeChildById($id);
			$errors[] = $child->modify(array('pos' => ++$position));
		}
		return $errors;
	}

	/**
	 * returns how many items are displayed at once
	 * return integer
	 */
	function getItemsPerPage()
	{
		return 1;
	}

	/**
	 * Returns the number of children of this node.
	 * @return integer
	 */
	function getChildrenCount()
	{
		return $this->db->getOne('SELECT COUNT(*) FROM '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ?', array($this->id));
	}
	
	/*
	*Returns the ItemIds of this Block
	*@return array
	*/
	function getItemIds()
	{
		$itemIds = array();
		foreach ($this->getTreeChildren() as $child) {
			$itemIds[] = $child->getId();
		}
		return $itemIds;
	}
}

?>
