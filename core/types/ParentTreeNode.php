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
require_once(CORE.'types/TreeNode.php');

/**
 * @package Core
 */
class ParentTreeNode extends TreeNode
{

	/**#@+
	 * @access private
	 */
	var $childrenTable;
	var $childrenConnector;
	var $childrenSequence;
	/**#@-*/

	/**
	 * This constructor has to be overwritten.
	 * In the overwriting constructor the variables $this->table, $this->parentNode, $this->parentConnector, $this->chidrenTable, $this->childrenConnector and $this->childrenSequence has to be set to the correct database names.
	 * After initializing those variables the overwring constructor has to call this overwritten constructor.
	 * @param Node parent node object
	 * @param integer ID of the node
	 */
	function ParentTreeNode($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		if(!isset($this->childrenTable))
		{
			trigger_error('<b>ParentTreeNode</b>: $this->childrenTable was not set');
		}
		if(!isset($this->childrenConnector))
		{
			trigger_error('<b>ParentTreeNode</b>: $this->childrenConnector was not set');
		}
		if(!isset($this->childrenSequence))
		{
			trigger_error('<b>ParentTreeNode</b>: $this->childrenSequence was not set');
		}

		$this->TreeNode($id);
	}

	/**
	 * This function creates an object of the correct type determinated by given id
	 */
	function _returnChild($id)
	{
		trigger_error('<b>ParentTreeNode:_returnChild</b>: function not overwritten by inherited class');
	}

	/**
	 * get all children of the current parent node
	 * @return mixed[]
	 */
	function getChildren()
	{
		if ($res = retrieve(get_class($this).'Children', $this->id)) {
			return $res;
		}

		$query = 'SELECT id FROM '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? ORDER BY pos';
		$ids = $this->db->getAll($query, array($this->id));
		if($this->db->isError($ids)) {
			return false;
		}
		$children = array();
		for($i = 0; $i < count($ids); $i++)
		{
			$children[] = $this->_returnChild($ids[$i]['id']);
		}
		store(get_class($this).'Children', $this->id, $children);
		return $children;
	}

	/**
	 * delete child by given id
	 * @param integer id of question in questionblock
	 * @return boolean
	 */
	function deleteChild($id)
	{
		if(!$this->existsChild($id))
		{
			trigger_error('<b>ParentTreeNode:deleteChild</b>: $id is no valid child id in current node');
			return false;
		}

		$child = $this->getChildById($id);
		$child->cleanUp();
		$query = 'DELETE FROM '.$this->childrenTable.' WHERE id = ?';
		$result = $this->db->query($query, array($id));
		if($this->db->isError($result)) {
			return false;
		}

		return true;
	}

	/**
	 * creates and returns a new child node
	 * @param $values associative array of informations
	 * @return TreeNode
	 */
	function _createChild($informations)
	{
		if(array_key_exists('pos', $informations) && ($informations['pos'] != NULL) && !preg_match('/^[0-9]+$/', $informations['pos']))
		{
			trigger_error('<b>TreeNode:createChildNode</b>: $position is no valid position');
			return false;
		}

		if(!array_key_exists('pos', $informations) || $informations['pos'] == NULL)
		{
			$informations['pos'] = $this->getNextFreePosition();
		}

		$id = $this->db->nextId($this->childrenSequence);
		if($this->db->isError($id)) {
			return false;
		}
		$informations['id'] = $id;
		$informations[$this->childrenConnector] = $this->id;

		$values = '';
		$columns = '';
		$quote = array();
		for(reset($informations); list($column, $value) = each($informations); )
		{
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

		return $this->_returnChild($id);
	 }

	/**
	 * creates and returns a new child node
	 * @param $position position of answer in question
	 * @param $optionalInfos associative array of optional informations (answer)
	 * @return TreeNode
	 */
	function createChild($infos)
	{
		trigger_error('<b>ParentTreeNode:createChild</b>: function not overwritten by inherited class');
	}

	/**
	 * Returns a copy of the current node
	 * @param int id of target node
	 * @param mixed[] 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds)
	{

		$node = parent::copyNode($parentId, $changedIds);

		$children = $this->getChildren();
		foreach($children as $child) {
			$child->copyNode($node->getId(), $changedIds);
		}

		return $node;

	}


	/**
	 * returns if a child exists in current node
	 * @param integer id of child node
	 * @return boolean
	 */
	function existsChild($id)
	{
		if(!preg_match('/^[0-9]+$/', $id))
		{
			trigger_error("<b>ParentTreeNode:existsChild</b>: '$id' is no valid id");
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

	function existsTreeChildAtPosition($position) {
		return $this->existsChildAtPosition($position);
	}

	/**
	 * Checks if any child exists at the given position
	 * @param integer Position of child in current node
	 * @return boolean
	 */
	function existsChildAtPosition($position)
	{
		if(!preg_match('/^[0-9]+$/', $position))
		{
			trigger_error('<b>ParentTreeNode:existAnyChildrenAtPosition</b>: $position is no valid position');
			return false;
		}
		$query = 'SELECT count(pos) from '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? AND pos = ?';
		$result = $this->db->getOne($query, array($this->id, $position));
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

	function getTreeChildByPosition($position) {
		return $this->getChildByPosition($position);
	}

	/**
	 * returns the child at given Position
	 * @param integer Position of question in questionblock
	 * @return Answer
	 */
	function getChildByPosition($position)
	{
		if(!preg_match('/^[0-9]+$/', $position))
		{
			trigger_error('<b>ParentTreeNode:getChildByPosition</b>: $position is no valid position');
			return false;
		}
		$query = 'SELECT count(pos) from '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? AND pos = ?';
		$result = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($result)) {
			return false;
		}
		if($result == 0 )
		{
			trigger_error('<b>ParentTreeNode:getChildByPosition</b>: no child found at $position');
			return false;
		}
		$query = 'SELECT id from '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? AND pos = ?';
		$id = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($id)) {
			return false;
		}

		return $this->_returnChild($id);
	}

	/**
	 * returns the child by given id
	 * @param integer Position of question in questionblock
	 * @return Answer
	 */
	function getChildById($id)
	{
		if(!$this->existsChild($id))
		{
			trigger_error("<b>ParentTreeNode:getChildById</b>: $id is no valid id in current node");
			return false;
		}

		return $this->_returnChild($id);
	}

	/**
	 * prepares everything to delete this block itself
	 * @return boolean
	 */
	function cleanUp()
	{
		$children = $this->getChildren();
		for($i = 0; $i < count($children); $i++) {
			$this->deleteChild($children[$i]->getId());
		}
		return parent::cleanUp();
	}

	/**
	 * returns the next free position for a child node
	 * @return Question
	 */
	function getNextFreePosition()
	{

		$query = 'SELECT pos FROM '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? ORDER BY pos DESC LIMIT 1';

		if(!($position = $this->db->getOne($query, array($this->id))))
		{
			return 1;
		}
		else {
			if($this->db->isError($position)) {
				return false;
			}

			return (((int) $position) + 1);

		}

	}

}

?>
