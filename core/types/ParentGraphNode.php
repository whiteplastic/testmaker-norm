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
require_once(dirname(__FILE__).'/GraphNode.php');


/**
 * This class is an abstract concept for nodes.
 *
 * @abstract
 * @package Core
 */

class ParentGraphNode extends GraphNode
{

	/**#@+
	 * @access private
	 */
	var $childrenSequence;
	/**#@-*/

	/**
	 * This constructor has to be overwritten.
	 * In the overwriting constructor the variable $this->table, $this->connectTable, $this->connectId, $this->connectParentId and $this->childrenSequence have to be set.
	 * After initializing this variable the overwring constructor has to call this overwritten constructor.
	 * @param integer ID of the node
	 */
	function ParentGraphNode($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		if(!isset($this->childrenSequence))
		{
			trigger_error('<b>ParentGraphNode</b>: $this->childrenSequence was not set');
		}

		$this->GraphNode($id);
	}

	function _getTableByType($type)
	{
		return $this->table;
	}

	/**
	 * Returns the child nodes of the current node ordered by position
	 * @return GraphNode[]
	 */
	function &getChildren()
	{
		//Thr ObjectCache don't work correct. Until the problem is fixed its comment out.
		/*if ($res = retrieve(get_class($this) .'Children', $this->id)) {
			return $res;
		}*/

		$nodes = array();

		$query = 'SELECT '.$this->connectId.' FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? ORDER BY pos';
		$ids = $this->db->getAll($query, array($this->id));
		if($this->db->isError($ids)) {
			return false;
		}

		for($i = 0; $i < count($ids); $i++)
		{
			if($ids[$i][$this->connectId] != 0)
			{
				$nodes[] = $this->_returnNode($ids[$i][$this->connectId]);
			}
		}

		store(get_class($this) .'Children', $this->id, $nodes);
		return $nodes;
	}

	/**
	 * Returns the number of children of this node.
	 * @return integer
	 */
	function getChildrenCount()
	{
		return $this->db->getOne('SELECT COUNT(*) FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ?', array($this->id));
	}

	/**
	 * Returns the parent nodes of the current node
	 * @return GraphNode[]
	 */
	function getChildById($child)
	{
		if(!$this->existsChild($child))
		{
			trigger_error('<b>ParentGraphNode:getChildById</b>: $parent is no parent of node');
			return false;
		}

		return $this->_returnNode($child);
	}

	/**
	 * Returns the child at given position
	 * @param integer position of the block
	 * @return GraphNode
	 */
	function getChildByPosition($position)
	{
		if(!$this->existsChildAtPosition($position)) {
			trigger_error('<b>ParentGraphNode:getChild</b>: $position at current is empty!');
			return false;
		}
		$query = 'SELECT '.$this->connectId.' FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? AND pos = ?';
		$id = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($id)) {
			return false;
		}

		return $this->_returnNode($id);
	}

	/**
	 * Returns if the given node is a child of current node
	 * @param integer ID of the node to check if it is a child
	 * @return integer
	 */
	function existsChild($child)
	{
		$query = 'SELECT count('.$this->connectId.') FROM '.$this->connectTable.' WHERE '.$this->connectId.' = ? AND '.$this->connectParentId.' = ?';
		$num = $this->db->getOne($query, array($child, $this->id));

		if($this->db->isError($num)) {
			return false;
		}

		if($num == 1)
		{
			return true;
		}
		elseif ($num == 0)
		{
			return false;
		}
		else
		{
			trigger_error('<b>ParentGraphNode:existsChild</b>: database is inconsistent');
			return false;
		}
	}

	/**
	 * Returns if any child exists at given position
	 * @param integer position of node in current node to check if it exists
	 * @return integer
	 */
	function existsChildAtPosition($position)
	{
		$query = 'SELECT count('.$this->connectId.') FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? AND pos = ?';
		$num = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($num)) {
			return false;
		}

		if($num == 1)
		{
			return true;
		}
		elseif ($num == 0)
		{
			return false;
		}
		else
		{
			trigger_error('<b>ParentGraphNode:existsChildAtPosition</b>: database is inconsistent');
			return false;
		}
	}

	/**
	 * returns the next free position for a child node
	 * @return integer
	 */
	function getNextFreePosition()
	{
		$query = 'SELECT pos FROM '.$this->connectTable.' WHERE '.$this->connectParentId.' = ? ORDER BY pos DESC LIMIT 1';
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
	 * Creates and returns a new node of the given type
	 * @param integer $position position of node in parent node
	 * @param mixed[] $optionalinfo associative array with optionalinformations (title, description)
	 * @return GraphNode
	 */
	function _createChild($type, $data = array())
	{

		if(!is_array($data))
		{
			trigger_error('<b>ParentGraphNode::createChild</b>: $data is no array');
			return false;
		}

		if(!array_key_exists('pos', $data) || $data['pos'] == NULL)
		{
			$position = $this->getNextFreePosition();
		}
		elseif($this->existsChildAtPosition($data['pos']))
		{
			$tmpblock = $this->getChildByPosition($data['pos']);
			$tmpblock->setPosition(($tmpblock->getPosition($parent) + 1), $this->id);
			$position = $data['pos'];
		}
		else
		{
			$position = $data['pos'];
		}

		if(!array_key_exists('id', $data) || !preg_match('/^[0-9]+$/', $data['id']))
		{
			$id = $this->db->nextId($this->childrenSequence);
			if($this->db->isError($id)) {
				return false;
			}
			$data['id'] = $id;
		}

		$owner = $this->getOwner();
		$data['owner'] = $owner->getId();
		$data['t_created'] = time();
		$data['t_modified'] = time();
		$data['u_created'] = $GLOBALS['PORTAL']->getUserId();
		$data['u_modified'] = $GLOBALS['PORTAL']->getUserId();

		$values = '';
		$columns = '';
		for(reset($data); list($column, $value) = each($data); )
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
			$values .= $this->db->quoteSmart($value);
		}

		$query = 'INSERT INTO '.$this->_getTableByType($type).' ('.$columns.') VALUES ('.$values.')';
		$result = $this->db->query($query);
		if($this->db->isError($result)) {
			return false;
		}

		$query = 'INSERT INTO '.$this->connectTable.' (id, parent_id, pos) VALUES (?, ?, ?)';
		$result = $this->db->query($query, array($data['id'], $this->id, $position));
		if($this->db->isError($result)) {
			return false;
		}

		return $this->_returnNode($data['id']);
	}

	/**
	 * deletes the given node from the current node and removes all children if they are not children of any other block
	 * @param integer $id id of node to delete
	 * @return boolean
	 */
	function deleteChild($id)
	{
		if(!$this->existsChild($id))
		{
			trigger_error('<b>ParentGraphNode:deleteChild</b>: $id does not exist');
			return false;
		}

		$block = $this->getChildById($id);
		$parentBlocks = $block->getParents();

		$foundDifferentParent = false;
		for($i = 0; $i < count($parentBlocks); $i++)
		{
			if($parentBlocks[$i]->getId() != $this->id)
			{
				$foundDifferentParent = true;
			}
		}

		$query = 'DELETE FROM '.$this->connectTable.' WHERE '.$this->connectId.' = ? AND '.$this->connectParentId.' = ?';
		$result = $this->db->query($query, array($id, $this->id));
		if($this->db->isError($result)) {
			return false;
		}

		if(!$foundDifferentParent)
		{
			$block->cleanUp();
			$blockType = $block->getBlockType();
			
			//if blockType is item block delete all media in the block
			if ($blockType == 3) {
				$mediaConnectId = $block->getMediaConnectId();
				if ($mediaConnectId != NULL) {
					$query = "SELECT id FROM ".DB_PREFIX."media WHERE media_connect_id = ?";
					$result = $this->db->query($query, array($mediaConnectId));
					while ($result->fetchInto($row)) {
						$fileHandling = new FileHandling();
						$fileHandling->deleteMedia($row['id']);
					}
					
				}
			}
			$query = 'DELETE FROM '.$this->_getTableByType($block->getBlockType()).' WHERE id = ?';
			$result = $this->db->query($query, array($id));
			if($this->db->isError($result)) {
				return false;
			}
			
			if ($blockType == 3) {
				if ($mediaConnectId != NULL) {
					$modulo = $mediaConnectId % 100;
					$dirname = ROOT."upload/media/".$modulo."/".$mediaConnectId;
					if (is_dir($dirname))
						rmdir($dirname);
				}
			}	
		}

		return true;
	}

	/**
	 * Returns an array of all nodes inside the current node in correct order
	 * The modifications array has to be orgnaized as an 2 dimensional array,
	 * in the first dimension all modifications are listed. In the second dimension the fields
	 * 'id', 'parent', 'position' and 'type' have to be given. In 'id', 'parent' (and 'position') is the new/old position of the added/removed node given.
	 * The field 'type' is the kind of modification (MODIFICATION_ADD = add, MODIFICATION_REMOVE = remove)
	 * Example: $modifications = array(0 => array('id' => 5, 'parent' => 1, 'type' = 2), 1 => array('id' => 5, 'parent' => 1, 'position' => 2, 'type' = 1));
	 * @param $modifications array of modifications to be checked
	 * @return mixed[]
	 */
	function listModifiedNode($modifications)
	{
		if(!is_array($modifications)) {
			trigger_error('<b>GraphNode:listModifiedNode()</b>: $modifications is no valid array');
			return false;
		}

		usort($modifications, array(get_class($this), '_compareModifications'));

		$children = $this->getChildren();

		$newIds = array();

		//add insert blocks at beginning
		for($j = 0; $j < count($modifications); $j++)
		{
			if(!isset($modifications[$j]['id']) || !isset($modifications[$j]['parent'])) {
				trigger_error('<b>GraphNode:listModifiedNode()</b>: $modifications['.$j.'] is no valid modification array');
				return false;
			}

			if($modifications[$j]['type'] == MODIFICATION_ADD) {
				if($modifications[$j]['parent'] == $this->id) {
					if(!isset($modifications[$j]['position'])) {
						trigger_error('<b>GraphNode:listModifiedNode()</b>: $modifications['.$j.'] is no valid modification array');
						return false;
					}
					if($modifications[$j]['position'] == 1)
					{
						$newIds[] = $modifications[$j]['id'];
					}
				}
			}
		}

		$lastPosition = 0;
		for($i = 0; $i < count($children); $i++)
		{
			$lastPosition = $children[$i]->getPosition($this->id);
			$continue = false;
			for($j = 0; $j < count($modifications); $j++)
			{
				if(!isset($modifications[$j]['id']) || !isset($modifications[$j]['parent'])) {
					trigger_error('<b>GraphNode:listModifiedNode()</b>: $modifications['.$j.'] is no valid modification array');
					return false;
				}

				switch($modifications[$j]['type']) {
					case MODIFICATION_ADD:
						if($modifications[$j]['parent'] == $this->id) {
							if(!isset($modifications[$j]['position'])) {
								trigger_error('<b>GraphNode:listModifiedNode()</b>: $modifications['.$j.'] is no valid modification array');
								return false;
							}
							if(($modifications[$j]['position'] >= $children[$i]->getPosition($this->id)) && ($modifications[$j]['position'] < $children[$i]->getNextUsedPosition($this->id)))
							{
								$newIds[] = $modifications[$j]['id'];
							}
						}
						break;
					case MODIFICATION_REMOVE:
						if($modifications[$j]['parent'] == $this->id) {
							if($modifications[$j]['id'] == $children[$i]->getId())
							{
								$continue = true;
							}
						}
						break;
				}
			}
			if($continue == true) {
				continue;
			}
			$newIds[] = $children[$i]->getId();
		}

		//add insert blocks at end
		for($j = 0; $j < count($modifications); $j++)
		{
			if(!isset($modifications[$j]['id']) || !isset($modifications[$j]['parent'])) {
				trigger_error('<b>GraphNode:listModifiedNode()</b>: $modifications['.$j.'] is no valid modification array');
				return false;
			}

			if($modifications[$j]['type'] == MODIFICATION_ADD)
			{
				if($modifications[$j]['parent'] == $this->id) {
					if(!isset($modifications[$j]['position'])) {
						trigger_error('<b>GraphNode:listModifiedNode()</b>: $modifications['.$j.'] is no valid modification array');
						return false;
					}
					if($modifications[$j]['position'] > $lastPosition)
					{
						$lastPosition = $modifications[$j]['position'];
						$newIds[] = $modifications[$j]['id'];
					}
				}
			}
		}

		$list = array();
		for($i = 0; $i < count($newIds); $i++)
		{
			$currentNode = $this->_returnNode($newIds[$i]);
			$entry = array('id' => $newIds[$i], 'type' => $currentNode->getBlockType());
			$list[] = $entry;
			$list = array_merge($list, $currentNode->listModifiedNode($modifications));
		}

		return $list;
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
	 * Returns a copy of the current node
	 * @param target of target node
	 * @param 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
ß	 * @return Node
	 */
	function copyNode($parentId, &$changedIds)
	{
		$newNode = parent::copyNode($parentId, $changedIds);

		$children = $this->getChildren();
		for($i = 0; $i < count($children); $i++)
		{
			$children[$i]->copyNode($newNode->getId(), $changedIds);
		}

		return $newNode;
	}

}

?>
