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
 * Load ancestor class
 */
require_once(dirname(__FILE__).'/ParentGraphNode.php');

/**
 * This class provides methods common to all sorts of blocks.
 *
 * @abstract
 * @package Core
 */
class Block extends ParentGraphNode
{
	/**#@+
	 * @access private
	 */
	var $type;
	/**#@-*/

	/**
	 * This constructor has to be overwritten.
	 * In the overwriting constructor the variable <kbd>$this->table</kbd> has to be set.
	 * After that, this overwritten constructor needs to be called.
	 * @param integer ID of the block
	 */
	function Block($id)
	{
		if(!isset($this->table)) {
			trigger_error('<b>Block</b>: $this->table was not set');
		}

		$this->db = &$GLOBALS['dao']->getConnection();
		$this->connectTable = DB_PREFIX.'blocks_connect';
		$this->connectId = 'id';
		$this->connectParentId = 'parent_id';
		$this->childrenSequence = DB_PREFIX.'blocks';

		$this->GraphNode($id);

		$query = 'SELECT type FROM '.DB_PREFIX.'blocks_type WHERE id = ?';
		$type = $this->db->getOne($query, array($this->id));
		if($this->db->isError($type)) {
			return NULL;
		}
		$this->type = $type;
	}

	/**
	 * Prepare given array with data for database insertion.
	 * This method must be overwritten by implementation classes.
	 * @static
	 * @param array associative array with database fields and content
	 * @return array associative array with database fields and content
	 */
	function prepareDBData($data) {
		trigger_error('<b>Block:prepareDBData</b>: function not overwritten by inherited class');
	}

	/**
	 * Return node with correct type determined by id
	 * @access private
	 * @param integer ID of node
	 * @return Block
	 */
	function _returnNode($id) {
		$block = $GLOBALS["BLOCK_LIST"]->getBlockById($id);
		return $block;
	}

	/**
	 * Returns all top-level ancestor blocks (i.e. potential test blocks),
	 * optionally limited to those that have been publicly participated
	 * in or published.
	 * @return Block[]
	 */
	function getUpperPublicBlocks($checkPublished = false)
	{
		$blocks = array();

		// Check if the root block is a parent of ours. If so, we're happy
		// first-level blocks and add ourselves to the mix.
		foreach ($this->getParents() as $pBlock) {
			if ($pBlock->isRootBlock()) {
				if ($this->isContainerBlock()) {
					if ($checkPublished && !TestStructure::wantToTrackStructure($this->getId())) continue;
					$blocks[$this->getId()] = $this;
				}
			} else {
				// In case we're looking for tracked tests, we also want subtests for tests
				if ($checkPublished && $pBlock instanceof ContainerBlock && $pBlock->getShowSubtests() && TestStructure::wantToTrackStructure($this->getId())) {
					$blocks[$this->getId()] = $this;
				}
				$blocks = array_merge($blocks, $pBlock->getUpperPublicBlocks());
			}
		}
		return array_values($blocks);
	}

	/**
	 * Checks if this block has been published (made runnable).
	 * @param boolean Whether to check if the block is public for the current
	 *   user (true) or for the guest user and other non-privileged groups
	 *   (false).
	 * @return bool
	 */
	function isPublic($specific = true)
	{

		if($this->isContainerBlock() && $this->isAccessAllowed(false, $specific))
		{
			return true;
		}

		$parents = $this->getParents();
		foreach ($parents as $parent) {
			if($parent->isPublic($specific)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the block is a container block
	 * @return boolean
	 */
	function isContainerBlock()
	{
		return ($this->type == BLOCK_TYPE_CONTAINER);
	}

	/**
	 * Checks if the block is the root block
	 * @return boolean
	 */
	function isRootBlock()
	{
		return ($this->id == 0);
	}

	/**
	 * Checks if the block is a item block
	 * @return boolean
	 */
	function isItemBlock()
	{
		return ($this->type == BLOCK_TYPE_ITEM);
	}

	/**
	 * Checks if the block is a feedback block
	 * @return boolean
	 */
	function isFeedbackBlock()
	{
		return ($this->type == BLOCK_TYPE_FEEDBACK);
	}

	/**
	 * Checks if the block is an info block
	 * @return boolean
	 */
	function isInfoBlock()
	{
		return ($this->type == BLOCK_TYPE_INFO);
	}

	/**
	 * Checks if the block is of a certain type
	 * @param int A block type constant
	 * @return boolean
	 */
	function isBlockType($blockType)
	{
		return ($this->type == $blockType);
	}

	/**
	 * Returns the title of the current block
	 * @return String
	 */
	function getTitle()
	{
		return $this->_getField('title');
	}

	/**
	 * Returns the description of the current block
	 * @return String
	 */
	function getDescription()
	{
		return $this->_getField('description');
	}

	/**
	 * Returns whether this block has been configured to pass permission
	 * changes on to its children.
	 * @return boolean
	 */
	function arePermissionsRecursive()
	{
		return ($this->_getField('permissions_recursive') == 1);
	}

	/**
	 * Returns if the usage of this block is allowed
	 * @return boolean
	 */
	function isUsageAllowed()
	{
		$user = $GLOBALS['PORTAL']->getUser();
		return ($user->checkPermission('link', $this));
	}

	/**
	 * Returns if copying of this block is allowed
	 * @return boolean
	 */
	function isCopyingAllowed()
	{
		$user = $GLOBALS['PORTAL']->getUser();
		return ($user->checkPermission('copy', $this));
	}

	/**
	 * Sets the position of the current block. If this position is already used by another block, it will relocate all following blocks as necessary.
	 * @param integer New position
	 * @param integer ID of the block in which our position is to be changed
	 * @param array where to store problems if the block would be moved
	 * @param boolean flag indicating whether validation of order should be done or not (in case it has been done already -> validateOrder)
	 * @return boolean
	 */
	function setPosition($position, $parent, &$problems, $checkIntegrity = true)
	{

		if(!$this->isParent($parent))
		{
			trigger_error('<b>Block:setPosition</b>: $parent does not exist');
			return false;
		}
		$parentBlock = $this->getParentById($parent);
		if(!$parentBlock->isContainerBlock())
		{
			trigger_error('<b>Block:setPosition</b>: $parent is no container block');
			return false;
		}
		if(!preg_match('/^[0-9]+$/', $position) && $position != NULL)
		{
			trigger_error('<b>Block:setPosition</b>: $position is not valid');
			return false;
		}
		if(!is_array($problems))
		{
			trigger_error('<b>Block:setPosition</b>: $problems is no valid array');
			return false;
		}
		if(count($problems) > 0)
		{
			trigger_error('<b>Block:setPosition</b>: $problems is not empty');
			return false;
		}
		if($position == $this->getPosition($parent)) {
			return true;
		}

		//simulate in all Public Blocks containing this Block the situation if the block is already moved
		$upperPublicBlocks = $this->getUpperPublicBlocks();
		$modification['parent'] = $parent;
		$modification['id'] = $this->id;
		$modification['type'] = MODIFICATION_REMOVE;
		$modifications[] = $modification;
		$modification['parent'] = $parent;
		$modification['position'] = $position;
		// off-by-one error fixed (take two)? --jk
		if ($position > $this->getPosition($parent)) {
			$modification['position']++;
		}
		$modification['id'] = $this->id;
		$modification['type'] = MODIFICATION_ADD;
		$modifications[] = $modification;

		if($checkIntegrity)
		{
			for($i = 0; $i < sizeof($upperPublicBlocks); $i++) {
				$upperPublicBlocks[$i]->checkIntegrity($modifications, array(), $problems, 'move');
			}

			if(count($problems) > 0)
			{
				$problems = array_unique($problems);
				return false;
			}
		}
		return parent::setPosition($position, $parent);
	}

	/**
	 * Get type of block
	 * @return integer
	 */
	function getBlockType() {
		return (int) $this->type;
	}

	/**
	 * Changes a group's permissions on this block.
	 *
	 * @param array Associative array of permission names <kbd>=></kbd> values.
	 * @param Group The group to set permissions for.
	 */
	function setPermissions($perms, $group)
	{
		$recursive = $this->arePermissionsRecursive();

		$group->setPermissions($perms, $this);

		if ($this->isContainerBlock() && $recursive) {
			$childs = $this->getChildren();
			foreach ($childs as $child) {
				$user = $GLOBALS['PORTAL']->getUser();
				$owner = $child->getOwner();
				if($user->checkPermission('admin') || $user->getId() == $owner->getId()) {
					if($recursive) {
						$modifications = array('permissions_recursive' => '1');
						$child->modify($modifications);
					}
					$child->setPermissions($perms, $group);
				}
			}
		}
	}

	/**
	 * Set permissions of block to same as parent block
	 * @param id of parent block to get permissions from
	 * @return bool success
	 */
	function setParentPermissions($parentId)
	{
		$parent = $this->getParentById($parentId);

		// Bail out if it's the root block we're talking about
		if ($parent->getId() == 0) return true;

		$recursive = $parent->arePermissionsRecursive();
		if ($recursive) {
			$this->modify(array('permissions_recursive' => '1'));
		} else {
			$this->modify(array('permissions_recursive' => '0'));
		}
		$query = 'SELECT * from '.DB_PREFIX.'group_permissions WHERE block_id = ?';
		$results = $this->db->getAll($query, array($parentId));

		if($this->db->isError($results)) {
			return false;
		}

		foreach($results as $result)
		{
			$rows = array();
			$values = array();
			for(reset($result); list($key, $value) = each($result);)
			{
				switch($key)
				{
					case 'block_id':
						$value = $this->id;
						break;
				}
				$rows[] = $key;
				$values[] = $value;
			}
			$query = 'INSERT INTO '.DB_PREFIX.'group_permissions ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';

			$result = $this->db->query($query, $values);
			if($this->db->isError($result)) {
				return false;
			}
		}

		return true;

	}

	/**
	 * Returns the owner of the current block
	 * @return User
	 */
	function getOwner()
	{
		$owner = $this->_getField('owner');
		return DataObject::getById('User', $owner);
	}

	/**
	 * Prepares everything to delete this block itself.
	 * This method should be overriden by inheriting classes as necessary.
	 * @return boolean
	 */
	function cleanUp()
	{
		$children = $this->getChildren();
		for($i = 0; $i < count($children); $i++) {
			$problems = array();
			$this->deleteChild($children[$i]->getId(), $problems);
			if(count($problems) > 0) {
				return false;
			}
		}

		$query = 'DELETE FROM '.DB_PREFIX.'blocks_type WHERE id = ?';
		$result = $this->db->query($query, array($this->id));
		if($this->db->isError($result)) {
			return false;
		}

		return GraphNode::cleanUp();

	}

	/**
	 * Returns a copy of the current block.
	 * @param parent ID to copy block into
	 * @param array 2-dimensional array where to store changed ids from item blocks and item answers for feedback blocks and dimensions format: array('blocks' => array(id1, id2, ...), 'item_answers' => array(id1, id2, ...))
	 * @param integer Predefined new id
	 * @param array Problem array (see ContainerBlock::checkIntegrity)
	 * @return Block
	 */
	function copyNode($parentId, &$changedIds, $newNodeId, &$problems)
	{
		$parentBlock = $GLOBALS["BLOCK_LIST"]->getBlockById($parentId);

		//simulate in all Public Blocks to which this Block is copied the situation if the block is already copied
		$modification['parent'] = $parentId;
		$modification['position'] = $parentBlock->getNextFreePosition();
		$modification['id'] = $this->id;
		$modification['type'] = 1;
		$modifications[] = $modification;
		
		//check integrity in all accessible blocks
		$parentUpperPublicBlocks = array();
		if($parentId != 0)
		{
			$parentUpperPublicBlocks = $parentBlock->getUpperPublicBlocks();
		}
		for($i = 0; $i < sizeof($parentUpperPublicBlocks); $i++) {
			$parentUpperPublicBlocks[$i]->checkIntegrity($modifications, $changedIds, $problems, 'copy');
		}
		
		//interrupts if problems appear
		if(count($problems) > 0)
		{
			$problems = array_unique($problems);
			return NULL;
		}

		//get new chid id if no one is provided
		if($newNodeId == NULL) {
			$newNodeId = $this->db->nextId($this->childrenSequence);
		}
		
		//insert type data
		$changedIds['blocks'][$this->id] = $newNodeId;
		$query = 'INSERT INTO '.DB_PREFIX.'blocks_type (id, type) VALUES (?, ?)';
		$result = $this->db->query($query, array($newNodeId, $this->getBlockType()));
		if($this->db->isError($result)) {
			return false;
		}

		$newNode = GraphNode::copyNode($parentId, $changedIds, $newNodeId);

		//copy children of block (not using inherited function, because of different parameters)
		$children = $this->getChildren();
		for($i = 0; $i < count($children); $i++)
		{
			//do not copy child if it is the new block itself
			if($children[$i]->getId() != $newNode->getId()) {
				$children[$i]->copyNode($newNode->getId(), $changedIds, $problems);
			}
		}

		return $newNode;

	}

	/**
	 * Insert a new link of the current node in the given node
	 * @param integer parent ID to link node into
	 * @param array Where to store problems if the block would be linked (see ContainerBlock::checkIntegrity)
	 * @return boolean
	 */
	function linkNode($parentId, &$problems)
	{
		$parentBlock = $GLOBALS["BLOCK_LIST"]->getBlockById($parentId);

		//simulate in all Public Blocks to which this Block is linked the situation if the block is already linked
		$modification['parent'] = $parentId;
		$modification['position'] = $parentBlock->getNextFreePosition();
		$modification['id'] = $this->id;
		$modification['type'] = 1;
		$modifications[] = $modification;
		$parentUpperPublicBlocks = array();
		if($parentId != 0)
		{
			$parentUpperPublicBlocks = $parentBlock->getUpperPublicBlocks();
		}
		for($i = 0; $i < sizeof($parentUpperPublicBlocks); $i++) {
			$parentUpperPublicBlocks[$i]->checkIntegrity($modifications, array(), $problems, 'link');
		}

		//interrupt if problems appear
		if(count($problems) > 0)
		{
			$problems = array_unique($problems);
			return false;
		}

		parent::linkNode($parentId);

		return true;
	}

	/**
	 * Alias for setPosition().
	 */
	function reorder($parentId, $position, &$problems)
	{
		return $this->setPosition($position, $parentId, $problems);
	}
}

?>
