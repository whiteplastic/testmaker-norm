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
 * Needs the class it inherites from
 */
require_once(dirname(__FILE__).'/Node.php');

/**
 * @package Core
 */
class TreeNode extends Node
{

	/**#@+
	 * @access private
	 */
	var $parentNode;
	var $parentConnector;
	/**#@-*/

	/**
	 * This constructor has to be overwritten.
	 * In the overwriting constructor the variables $this->table, $this->parentNode and $this->parentConnector has to be set to the correct database names.
	 * After initializing those variables the overwring constructor has to call this overwritten constructor.
	 * @param DB The database object
	 * @param integer ID of the node
	 */
	function TreeNode($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		if(!isset($this->parentConnector))
		{
			trigger_error('<b>TreeNode</b>: $this->parentConnector was not set');
		}

		$this->Node($id);

		$query = 'SELECT '.$this->parentConnector.' FROM '.$this->table.' WHERE id = ?';
		$parentId = $this->db->getOne($query, array($this->id));
		if($this->db->isError($parentId) || empty($parentId)) {
			return false;
		}
		$this->parentNode = $this->_returnParent($parentId);
	}

	/**
	 * returns the title of current node or if not set a short part of the content
	 * @return String
	 */
	function getTitle($shorten = false)
	{
		trigger_error('<b>TreeNode:getTitle</b>: function not overwritten by inherited class');
	}

	/**
	 * returns parent node with given id
	 * @return Integer
	 */
	function _returnParent($id)
	{
		trigger_error('<b>TreeNode:_returnParent</b>: function not overwritten by inherited class');
	}
	
	/**
	 * Checks if this node is disabled
	 * @return boolean
	 */
	function getDisabled($path = "0")
	{
		//check if node is an item and if disabled
		$nodeId = $this->getId();
		$table = $this->table ? $this->table : DB_PREFIX."items";
	    $res = $this->db->getAll("SELECT * FROM ".$table." WHERE id=? AND disabled=1", array($nodeId));	
		if (count($res) >= 1) {
			return true;
		}
		//check if node is an item and if parent is disabled
		$res = $this->db->getAll("SELECT * FROM ".$table." WHERE id=?", array($nodeId));	
		if (count($res) >= 1) {
			if ($this->getParent()->getDisabled())
				return true;
		}
		
		
		if ($path != "0") {	
			$pathIds = explode("_", $path);
			$maxPos = count($pathIds);
			
			$BlockId = $this->id;
			$parentBlockId = 0;
			
			$backTrack = $maxPos;
			$pathIds[$backTrack] = $BlockId;
			
			//check if block is disabled
			if ($maxPos > 1) {
				$parentBlockId = $pathIds[$maxPos-2];
				$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX."blocks_connect WHERE id=? AND parent_id=? AND disabled=1", array($BlockId, $parentBlockId));
				if (count($res) >= 1) {
					return true;
				}
			}
			
			//check if a parent block is disabled
			for ($i = 0; $i < $maxPos; $i++) {
				if ($BlockId == $pathIds[$i])
					$backTrack = $i;
			}
			
			for ($j = $backTrack; $j > 0; $j--) {
				$BlockId = $pathIds[$j];
				$parentBlockId = $pathIds[$j-1];
				$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX."blocks_connect WHERE id=? AND parent_id=? AND disabled=1", array($BlockId, $parentBlockId));
				if (count($res) >= 1) {
					return true;
				}
			}
			return false;
		}
		return false;
	}
	
	function nodeDisabled($childId, $parentId) {
		$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX."blocks_connect WHERE id=? AND parent_id=? AND disabled=1", array($childId, $parentId));
			if (count($res) >= 1) {
				return true;
			}
		return false;
	}

	/**
	 * returns the parent node
	 * @return String
	 */
	function &getParent() {
		return $this->parentNode;
	}

	/**
	 * Returns if an upper node of the current node has more than one parent
	 * @return boolean
	 */
	function hasMultipleParents() {
		return $this->parentNode->hasMultipleParents();
	}

	/**
	 * returns the current position
	 * @return String
	 */
	function getPosition()
	{
		return $this->_getField('pos');
	}

	/**
	 * Sets the position of the current node. If this position is already used by another node, it will relocate all following nodes as necessary.
	 * @param integer new position
	 * @return boolean
	 */
	function setPosition($position)
	{
		if(!preg_match('/^[0-9]+$/', $position) && $position != NULL)
		{
			trigger_error('<b>TreeNode:setPosition</b>: $position is not valid');
			return false;
		}
		if($position == $this->getPosition()) {
			return true;
		}

		if($this->parentNode->existsTreeChildAtPosition($position)) {
			$tmpChild = $this->parentNode->getTreeChildByPosition($position);
			$tmpChild->setPosition(($tmpChild->getPosition() + 1));
		}

		$result = $this->db->query('UPDATE '.$this->table.' SET pos = ? WHERE id = ?', array($position, $this->id));

		return (!$this->db->isError($result));
	}

	/**
	 * Returns a copy of the current node
	 * @param parent node id
	 * @param 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
	 * @param predefined new node id
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds, $newNodeId = NULL)
	{
		$query = 'SELECT * from '.$this->table.' WHERE id = ?';
		$result = $this->db->getRow($query, array($this->id));
		if($this->db->isError($result)) {
			return false;
		}

		$query = 'SELECT pos FROM '.$this->table.' WHERE '.$this->parentConnector.' = ? ORDER BY pos DESC LIMIT 1';
		if(!($position = $this->db->getOne($query, array($parentId))))
		{
			$position = 1;
		}
		else
		{
			if($this->db->isError($position)) {
				return false;
			}
			$position++;
		}

		if($newNodeId == NULL) {
			$newNodeId = $this->db->nextId($this->sequence);
		}

		$rows = array();
		$values = array();
		for(reset($result); list($key, $value) = each($result);)
		{
			switch($key)
			{
				case 'id':
					$value = $newNodeId;
					break;
				case 'pos':
					$value = $position;
					break;
				case $this->parentConnector:
					$value = $parentId;
					break;
				case 't_created':
				case 't_modified':
					$value = time();
					break;
				case 'u_created':
				case 'u_modified':
					$value = $GLOBALS['PORTAL']->userId;
					break;
			}
			$rows[] = $key;
			$values[] = $value;
		}

		$query = 'INSERT INTO '.$this->table.' ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';
		$result = $this->db->query($query, $values);

		if($this->db->isError($result)) {
			return false;
		}

		return $this->_returnSelf($newNodeId);
	}

	/**
	 * Returns the position of the next node in current parent node or if there is no next node return the position of the node itself
	 *
	 * @param integer parent id not used by this item type, only for GraphNode compatibility
	 * @return mixed
	 */
	function getNextUsedPosition($id = NULL)
	{
		$query = 'SELECT pos FROM '.$this->table.' WHERE '.$this->parentConnector.' = ? AND pos > ? ORDER BY pos ASC LIMIT 1';

		if (!($position = $this->db->getOne($query, array($this->parentNode->getId(), $this->getPosition()))))
		{
			return false;
		}
		else
		{
			if($this->db->isError($position)) {
				return false;
			}
			return (int) $position;
		}
	}

	/**
	 * Returns the position of the previous node in current parent node or if there is no previous node return the position of the node itself
	 * @return integer
	 */
	function getPreviousUsedPosition()
	{
		$query = 'SELECT pos FROM '.$this->table.' WHERE '.$this->parentConnector.' = ? AND pos < ? ORDER BY pos DESC LIMIT 1';

		if(!($position = $this->db->getOne($query, array($this->parentNode->getId(), $this->getPosition())))) {
			return $this->getPosition();
		} else {
			if($this->db->isError($position)) {
				return false;
			}
			return (int) $position;
		}
	}

}

?>
