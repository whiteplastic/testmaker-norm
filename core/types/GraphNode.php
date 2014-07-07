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
 * This class is an abstract concept for nodes.
 *
 * @abstract
 * @package Core
 */

class GraphNode extends Node
{

	/**#@+
	 * @access private
	 */
	var $connectTable;
	var $connectId;
	var $connectParentId;
	/**#@-*/

	/**
	 * This constructor has to be overwritten.
	 * In the overwriting constructor the variable $this->table, $this->connectTable, $this->connectId and $this->connectParentId have to be set.
	 * After initializing this variable the overwring constructor has to call this overwritten constructor.
	 * @param DB The database object
	 * @param integer ID of the node
	 */
	function GraphNode($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		if(!isset($this->connectTable)) {
			trigger_error('<b>GraphNode</b>: $this->connectTable was not set');
		}
		if(!isset($this->connectId)) {
			trigger_error('<b>GraphNode</b>: $this->connectId was not set');
		}
		if(!isset($this->connectParentId)) {
			trigger_error('<b>GraphNode</b>: $this->connectParentId was not set');
		}

		$this->Node($id);

	}
	
	/**
	 * returns a graph node with the correct type, automatically  determined
	 * @return String
	 */
	function _returnNode($id)
	{
		trigger_error('<b>GraphNode:_returnNode</b>: function not overwritten by inherited class');
	}

	/**
	 * compare function to sort modifications array
	 * @access private
	 * @param $a element 1
	 * @param $b element 2
	 * @return int
	 */
	function _compareModifications($a, $b) {
		if($a['parent'] < $b['parent']){ 
			return -1;
		} elseif($a['parent'] > $b['parent']) {
			return 1;
		} else {
			if(!isset($a['position']) && isset($b['position'])) {
				return -1;
			} elseif(isset($a['position']) && !isset($b['position'])) {
				return 1;
			} elseif(!isset($a['position']) && !isset($b['position'])) {
				return 0;
			}
			if($a['position'] < $b['position']) {
				return -1;
			} elseif($a['position'] > $b['position']) {
				return 1;
			} else {
				return 0;
			}
		}
	}

	/**
	 * Returns the parent nodes of the current node
	 * @return GraphNode[]
	 */
	function &getParents()
	{
		$nodes = array();
		
		$query = 'SELECT '.$this->connectParentId.' FROM '.$this->connectTable.' WHERE '.$this->connectId.' = ?';
		$ids = $this->db->getAll($query, array($this->id));
		if($this->db->isError($ids)) {
			return false;
		}
		
		for($i = 0; $i < count($ids); $i++)
		{
				$nodes[] = $this->_returnNode($ids[$i][$this->connectParentId]);
		}

		return $nodes;
	}

	/**
	 * Get the unique parent of the block in respect to the working path (necessary for the uniqueness)
	 */
	function getParent($workingPath)
	{
		$parent = NULL;
		if($workingPath == "") return NULL;

		$parentId = WorkingPath::getParentId($this->id, $workingPath);

		$parent = $GLOBALS['BLOCK_LIST']->getBlockById($parentId);

		return $parent;
	}

	/**
	 * Returns the parent nodes of the current node
	 * @return GraphNode[]
	 */
	function getParentById($parent)
	{
		if(!$this->isParent($parent)) {
			trigger_error('<b>GraphNode:getParentById</b>: $parent is no parent of node');
			return false;
		}
		
		return $this->_returnNode($parent);
	}

	/**
	 * Returns if the given node is a parent of current node
	 * @param integer ID of the node to check if it is a parent
	 * @return boolean
	 */
	function isParent($parent)
	{
		$query = 'SELECT count(id) FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? AND '.$this->connectId.' = ?';
		$num = $this->db->getOne($query, array($parent, $this->id));
		if($this->db->isError($num)) {
			return false;
		}
		
		if($num == 1) {
			return true;
		} elseif ($num == 0) {
			return false;
		} else {
			trigger_error('<b>GraphNode:isParent</b>: database is inconsistent');
			return false;
		}
	}

	/**
	 * Returns if current node or an upper node of current node has more than one parent
	 * @return boolean
	 */
	function hasMultipleParents()
	{
		$prefetch = retrieve('MultipleGraphParents', $this->id);
		if ($prefetch !== NULL) return $prefetch;

		$parents = $this->getParents();
		
		if(count($parents) > 1) {
			store('MultipleGraphParents', $this->id, true);
			return true;
		} else {
			foreach($parents as $parent) {
				if($parent->hasMultipleParents()) {
					store('MultipleGraphParents', $this->id, true);
					return true;
				}
			}
			store('MultipleGraphParents', $this->id, false);
			return false;
		}
	}

	/**
	 * Returns the position of the current node
	 * @param integer ID of the node in which our position is to be determined
	 * @return integer
	 */
	function getPosition($parent)
	{
		if(!$this->isParent($parent))
		{
			trigger_error('<b>GraphNode:getPosition</b>: $parent is no parent of node');
			return false;
		}
		$query = 'SELECT pos FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? AND '.$this->connectId.' = ?';
		$position = $this->db->getOne($query, array($parent, $this->id));
		if($this->db->isError($position)) {
			return false;
		}

		return (int) $position;
	}

	/**
	 * Returns the position of the next node or if there is no next node return the position of the node itself
	 * @param integer ID of the node in which our position is to be determined
	 * @return mixed
	 */
	function getNextUsedPosition($parent)
	{
		if (!$this->isParent($parent))
		{
			trigger_error('<b>GraphNode:getNextUsedPosition</b>: $parent is on parent of node');
			return false;
		}
		$query = 'SELECT pos FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? AND pos > ? ORDER BY pos ASC LIMIT 1';
		if (!($position = $this->db->getOne($query, array($parent, $this->getPosition($parent)))))
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
	 * Returns the position of the previous node or if there is no previous node return the position of the node itself
	 * @param integer ID of the node in which our position is to be determined
	 * @return integer
	 */
	function getPreviousUsedPosition($parent)
	{
		if(!$this->isParent($parent))
		{
			trigger_error('<b>GraphNode:getNextUsedPosition</b>: $parent is on parent of node');
			return false;
		}
		$query = 'SELECT pos FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? AND pos < ? ORDER BY pos DESC LIMIT 1';
		if(!($position = $this->db->getOne($query, array($parent, $this->getPosition($parent))))) {
			return $this->getPosition($parent);
		} else {
			if($this->db->isError($position)) {
				return false;
			}
			return (int) $position;
		}
	}

	/**
	 * Sets the position of the current node. If this position is already used by another node, it will relocate all following nodes as necessary.
	 * @param integer New position
	 * @param integer ID of the node in which our position is to be changed
	 * @return boolean
	 */
	function setPosition($position, $parent)
	{
		
		if(!$this->isParent($parent))
		{
			trigger_error('<b>GraphNode:setPosition</b>: $parent os no parent of node');
			return false;
		}
		if(!preg_match('/^[0-9]+$/', $position) && $position != NULL)
		{
			trigger_error('<b>GraphNode:setPosition</b>: $position is not valid');
			return false;
		}
		if($position == $this->getPosition($parent)) {
			return true;
		}

		$parentNode = $this->getParentById($parent);
		if($parentNode->existsChildAtPosition($position)) {
			$query = 'UPDATE '.$this->connectTable.' SET pos = pos + 1 WHERE '.$this->connectParentId.' = ? AND pos >= ?';
			$result = $this->db->query($query, array($parent, $position));
		}

		$query = 'UPDATE '.$this->connectTable.' SET pos = ? WHERE '.$this->connectParentId.' = ? AND '.$this->connectId.' = ?';
		$result = $this->db->query($query, array($position, $parent, $this->id));

		return (!$this->db->isError($result));
	}

	/**
	 * Returns a copy of the current node
	 * @param parent node id
	 * @param 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
	 * @param predefined new id
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds, $newNodeId = NULL)
	{

		$query = 'SELECT * from '.$this->table.' WHERE id = ?';
		$result = $this->db->getRow($query, array($this->id));
		if($this->db->isError($result)) {
			return false;
		}

		if($newNodeId == NULL) {
			$newNodeId = $this->db->nextId($this->childrenSequence);
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
				case 't_created':
				case 't_modified':
					$value = time();
					break;
				case 'u_created':
				case 'u_modified':
					$value = $GLOBALS['PORTAL']->userId;
					break;
				case 'owner':
					$parent = $GLOBALS['BLOCK_LIST']->getBlockById($parentId);
					$pOwner = $parent->getOwner();
					$value = $pOwner->getId();
			}
			$rows[] = $key;
			$values[] = $value;
		}

		$query = 'INSERT INTO '.$this->table.' ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';
		$result = $this->db->query($query, $values);
		if($this->db->isError($result)) {
			return false;
		}

		$query = 'SELECT pos FROM '.$this->connectTable.' WHERE parent_id = ? ORDER BY pos DESC LIMIT 1';
		$num = $this->db->getOne($query, array($parentId));
		if($this->db->isError($num)) {
			return false;
		}
		if(!($position = ($this->db->getOne($query, array($parentId)) + 1))) {
			$position = 1;
		}

		$query = 'INSERT INTO '.$this->connectTable.' (id, pos, parent_id) VALUES (?, ?, ?)';
		$result = $this->db->query($query, array($newNodeId, $position, $parentId));
		if($this->db->isError($result)) {
			return false;
		}

		return $this->_returnNode($newNodeId);
	}

	/**
	 * insert a new link of the current node in the given node
	 * @return boolean
	 */
	function linkNode($parentId)
	{
		$query = 'SELECT count(pos) FROM '.$this->connectTable.' WHERE parent_id = ? AND id = ?';
		$num = $this->db->getOne($query, array($parentId, $this->id));
		if($this->db->isError($num)) {
			return false;
		}
		if($num > 0) {
			return true;
		}
		
		$query = 'SELECT pos FROM '.$this->connectTable.' WHERE parent_id = ?';
		if(!($position = $this->db->getOne($query, array($parentId)))) {
			$position = 1;
		} else {
			if($this->db->isError($position)) {
				return false;
			}
			$position++;
		}

		$query = 'SELECT count(id) FROM '.$this->connectTable.' WHERE id = ? AND pos = ? AND parent_id = ?';
		if($this->db->getOne($query, array($this->id, $position, $parentId)) > 0) {
			return true;
		}
		$query = 'INSERT INTO '.$this->connectTable.' (id, pos, parent_id) VALUES (?, ?, ?)';
		$result = $this->db->query($query, array($this->id, $position, $parentId));
		if($this->db->isError($result)) {
			return false;
		}

		return true;
	}
}

?>
