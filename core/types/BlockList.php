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

/**#@+
 * All the different block types
 */
require_once(CORE.'types/RootBlock.php');
require_once(CORE.'types/ContainerBlock.php');
require_once(CORE.'types/InfoBlock.php');
require_once(CORE.'types/ItemBlock.php');
require_once(CORE.'types/FeedbackBlock.php');
require_once(CORE.'types/Test.php');
/**#@-*/

/**
 * Type ID for container blocks
 */
define('BLOCK_TYPE_CONTAINER', 0);
/**
 * Type ID for info blocks
 */
define('BLOCK_TYPE_INFO', 1);
/**
 * Type ID for feedback blocks
 */
define('BLOCK_TYPE_FEEDBACK', 2);
/**
 * Type ID for item blocks
 */
define('BLOCK_TYPE_ITEM', 3);

// Install BlockList
new BlockList();

/**
 * This class contains all functions to get or insert Blocks
 *
 * @package Core
 */
class BlockList
{

	/**#@+
	 * @access private
	 */
	var $db;
	var $connectTable;
	var $connectId;
	var $parentConnectId;
	/**#@-*/


	/**
	 * Creates a new list of blocks.
	 */
	function BlockList()
	{
		if (isset($GLOBALS["BLOCK_LIST"])) {
			trigger_error("Please do not construct BlockList directly, use \$GLOBALS[\"BLOCK_LIST\"] instead", E_USER_ERROR);
		}

		$this->db = &$GLOBALS['dao']->getConnection();
		$this->connectTable = DB_PREFIX.'blocks_connect';
		$this->connectId = 'id';
		$this->connectParentId = 'parent_id';

		$GLOBALS["BLOCK_LIST"] = &$this;
	}

	/**
	 * Checks if the given block exists
	 * @param integer ID of block
	 * @return boolean
	 */
	function existsBlock($id)
	{
		//fix: root block not stored in database
		if ($id == "0")
		{
			return true;
		}

		if (!preg_match('/^[0-9]+$/', $id))
		{
			return false;
		}
		$query = 'SELECT count(id) FROM '.DB_PREFIX.'blocks_type WHERE id = ?';
		$query2 = 'SELECT count(id) FROM '.DB_PREFIX.'blocks_connect WHERE id = ?';
		$num = $this->db->getOne($query, array($id));
		$num2 = $this->db->getOne($query2, array($id));
		if($this->db->isError($num)) {
			return false;
		}


		if ($num > 0 && $num2 > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Determines the type of a block and returns it
	 * @param integer ID of the block
	 * @return integer Type of the block encoded as a number
	 */
	function getBlockType($id)
	{
		if (!preg_match('/^[0-9]+$/', $id))
		{
			return false;
		}
		$query = 'SELECT type FROM '.DB_PREFIX.'blocks_type WHERE id = ?';
		$type = $this->db->getOne($query, array($id));
		if($this->db->isError($type))
		{
			return false;
		} else return $type;
	}

	/**
	 * Checks if the given block is a child of another block
	 * @param integer ID of potential child
	 * @param integer ID of potential parent
	 * @return boolean
	 */
	function existsBlockInParent($id, $parent)
	{
		if(!$this->existsBlock($parent))
		{
			trigger_error('<b>BlockList:existsBlockInParent</b>: $parent does not exist');
			return false;
		}
		if(!$this->existsBlock($id))
		{
			trigger_error('<b>BlockList:existsBlockInParent</b>: $id does not exist');
			return false;
		}

		$query = 'SELECT count(id) FROM '.$this->connectTable.' WHERE '.$this->connectTable.' = ? AND '.$this->connectParentId.' = ?';
		$num = $this->db->getOne($query, array($id, $parent));
		if($this->db->isError($num)) {
			return false;
		}

		if($num > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Checks if the given block type exists
	 * @param integer Type of block
	 * @return boolean
	 */
	function existsType($type)
	{
		$tables = $this->_getTableNames();

		if(isset($tables[$type]))
		{
			return true;
		} else {
			return false;
		}
	}

	var $blocks;

	/**
	 * Returns block by given id
	 * @param integer id of block
	 * @param integer type of block
	 * @return Block
	 */
	function &getBlockById($id, $expectedType = NULL)
	{
		if (! isset($this->blocks[$id]))
		{
			if (! $this->existsBlock($id)) {
				//All ways are somehow bad...
				//trigger_error('<b>BlockList::getBlockById</b>: There is no block with the ID '.$id);
				//$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.test_not_found", MSG_RESULT_NEG);
				//redirectTo("start");
				return NULL;
			}

			if ($id == 0) {
				// We don't use a constant for the root block, since this only has meaning in here
				$type = -1;
			} else {
				$query = 'SELECT type FROM '.DB_PREFIX.'blocks_type WHERE id = ?';
				$type = $this->db->getOne($query, array($id));
			}

			if (isset($expectedType) && $type != $expectedType) {
				trigger_error('<b>BlockList::getBlockById</b>: Block '.$id.' is of type '.$type.', but type '.$expectedType.' was expected');
				return NULL;
			}
			
			switch($type)
			{
				case -1:
					$this->blocks[$id] = new RootBlock();
					break;
				case BLOCK_TYPE_CONTAINER:
					$this->blocks[$id] = new ContainerBlock($id);
					break;
				case BLOCK_TYPE_INFO:
					$this->blocks[$id] = new InfoBlock($id);
					break;
				case BLOCK_TYPE_FEEDBACK:
					$this->blocks[$id] = new FeedbackBlock($id);
					break;
				case BLOCK_TYPE_ITEM:
					$this->blocks[$id] = new ItemBlock($id);
					break;
			}
		}

		return $this->blocks[$id];
	}

	/**
	 * Returns the correct table name for given block
	 * @access private
	 * @param integer id of block
	 * @return String
	 */
	function _getTableById($id)
	{
		if(!$this->existsBlock($id))
		{
			trigger_error('<b>BlockList:_getTableById</b>: $id does not exist');
			return false;
		}
		$query = 'SELECT type FROM '.DB_PREFIX.'blocks_type WHERE id = ?';
		$type = $this->db->getOne($query, array($id));
		if($this->db->isError($type)) {
			return false;
		}

		switch($type)
		{
			case BLOCK_TYPE_CONTAINER:
				return DB_PREFIX.'container_block';
			case BLOCK_TYPE_INFO:
				return DB_PREFIX.'info_block';
			case BLOCK_TYPE_FEEDBACK:
				return DB_PREFIX.'feedback_block';
			case BLOCK_TYPE_ITEM:
				return DB_PREFIX.'item_block';
			default:
				return false;
		}
	}

	/**
	 * Moves the given block to a new position and/or parent block
	 * @param Block instance of block to move
	 * @param integer ID of parent block
	 * @param Block instance of block where to move
	 * @param integer New position of block in parent block (automatically last position if NULL)
	 * @param array where to store problems if the block would be moved (see ContainerBlock::checkIntegrity)
	 * @return boolean
	 */
	function moveBlock($source, $parentId, $target, $newPosition, &$problems)
	{

		if(!$source->isParent($parentId))
		{
			trigger_error('<b>BlockList::moveBlock</b>: parent is no valid parent of given block');
			return false;
		}
		if(!preg_match('/^[0-9]+$/', $newPosition) && $newPosition != NULL)
		{
			trigger_error('<b>BlockList::moveBlock</b>: $position is not valid');
			return false;
		}
		if($newPosition == NULL)
		{
			$newPosition = $target->getNextFreePosition();
		}
		if(!is_array($problems))
		{
			trigger_error('<b>BlockList::moveBlock</b>: $problems is no valid array');
			return false;
		}
		if(count($problems) > 0)
		{
			trigger_error('<b>BlockList::moveBlock</b>: $problems is not empty');
			return false;
		}

		if($parentId == $target->getId()) {
			return true;
		}

		$id = $source->getId();
		$newParent = $target->getId();

		//simulate in all Public Blocks containing or contained this Block the situation if the block is already moved
		$modification['parent'] = $parentId;
		$modification['id'] = $id;
		$modification['type'] = 2;
		$modifications[] = $modification;
		$modification['parent'] = $newParent;
		$modification['position'] = $newPosition;
		$modification['id'] = $id;
		$modification['type'] = 1;
		$modifications[] = $modification;
		$parentUpperPublicBlocks = array();
		if($parentId != 0)
		{
			$parentBlock = $this->getBlockById($parentId);
			$parentUpperPublicBlocks = $parentBlock->getUpperPublicBlocks();
		}
		for($i = 0; $i < sizeof($parentUpperPublicBlocks); $i++) {
			$parentUpperPublicBlocks[$i]->checkIntegrity($modifications, array(), $problems, 'move_source');
		}
		$newParentUpperPublicBlocks = array();
		if($newParent != 0)
		{
			$newParentBlock = $this->getBlockById($newParent);
			$newParentUpperPublicBlocks = $newParentBlock->getUpperPublicBlocks();
		}
		for($i = 0; $i < sizeof($newParentUpperPublicBlocks); $i++) {
			$changedIds = array();
			$newParentUpperPublicBlocks[$i]->checkIntegrity($modifications, $changedIds, $problems, 'move_target');
		}

		//interrupt if problems appear
		if(count($problems) > 0)
		{
			$problems = array_unique($problems);
			return false;
		}

		//reposition existing blocks if nescessary
		$parentBlock = $this->getBlockById($newParent);
		if($parentBlock->existsChildAtPosition($newPosition)) {
			$tmpblock = $parentBlock->getBlockByPosition($newPosition);
			$tmpblock->setPosition(($tmpblock->getPosition($newParent) + 1), $newParent, $problems);
		}

		//update database link
		$quote = array($newParent);
		$query = 'UPDATE '.$this->connectTable.' SET '.$this->connectParentId.' = ?';
		$query .= ', pos = ?';
		$quote[] = $newPosition;
		$quote[] = $id;
		$quote[] = $parentId;
		$query .= ' WHERE '.$this->connectId.' = ? AND '.$this->connectParentId.' = ?';
		$result = $this->db->query($query, $quote);

		return (!$this->db->isError($result));
	}

	/**
	 * Attempts to find a given block in a subtest of a given test.
	 * @param int The ID of the block to locate.
	 * @param int The ID of the test to look in.
	 * @return int The ID of the subtest the block was found in, 0 to indicate that the block is a direct child of the test
	 *   or NULL to indicate that the block is not part of the test at all.
	 */
	function findParentInTest($blockId, $testId)
	{
		$query = 'SELECT b.id FROM '.DB_PREFIX.'blocks_connect b LEFT JOIN '.DB_PREFIX.'blocks_connect b2 ON (b2.parent_id = b.id) WHERE (b.parent_id = ? AND b2.id = ?) OR (b.parent_id = ? AND b.id = ?)';
		$res = $this->db->getOne($query, array($testId, $blockId, $testId, $blockId));
		if (PEAR::isError($res)) return NULL;
		if ($res == $blockId) return 0;
		return $res;
	}

	/**
	 * Return a list of all available test
	 *
	 * @param integer ID of parent (usually 0, unless we're dealing with a show_subtests test)
	 * @return array List of tests
	 */
	function getTestList($parentId = 0)
	{
		$tests = array();

		$query = 'SELECT cb.id FROM '.DB_PREFIX.'container_blocks AS cb INNER JOIN '.DB_PREFIX.'blocks_connect AS bc USING (id) WHERE bc.parent_id = ? AND ((cb.open_date IS NULL OR cb.open_date < '.NOW.') AND (cb.close_date IS NULL OR cb.close_date = 0 OR cb.close_date > '.NOW.')) ORDER BY title';
		$ids = $this->db->getAll($query, array($parentId));

		if ($this->db->isError($ids)) {
			return array();
		}
		for ($i = 0; $i < count($ids); $i++)
		{
			$tests[] = new Test($ids[$i]['id']);
		}
		return $tests;
	}
	
	/**
	 * Checks a test ID and returns the associated test and whether a password
	 * is required or not.
	 */
	function checkTestId($testId, $accessType)
	{
		if (! is_numeric($testId)) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.invalid_link", MSG_RESULT_NEG);
			return FALSE;
			
		}

		if (! $GLOBALS["BLOCK_LIST"]->existsBlock($testId)) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.test_not_found", MSG_RESULT_NEG);
			return FALSE;
		}

		$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testId);
		if (! $test->isContainerBlock()) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.invalid_link", MSG_RESULT_NEG);
			return FALSE;
		}

		$userId = $GLOBALS['PORTAL']->getUserId();
		$passwordRequired = FALSE;
		if($userId === 0 && $accessType != 'tan')	// user is guest with or without password
		{
			$workingPath = get('test_path', get('working_path', ''));
			$parent = $test->getParent($workingPath);
			if ($parent && ($parent->getShowSubtests() || $test->getShowSubtests()))
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.subtest_not_available", MSG_RESULT_NEG);
				return FALSE;
			}
		}
		
		if(!$test->isAccessAllowed($accessType)) 
		{
			if ($test->getPassword() != "" && $test->isAccessAllowed($accessType, 'password')) {
				$passwordRequired = TRUE;
			} elseif($accessType == 'tan')
			{
				$workingPath = get('test_path', get('working_path', ''));
				$parent = $test->getParent($workingPath);
				if(((isset($parent) && $parent->getShowSubtests()) || $test->getShowSubtests()) && !$test->isAccessAllowed($accessType, true, $parent)) return FALSE;
			} else {
				if($userId == 0)
					$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.password_required", MSG_RESULT_NEG);		
				else
					$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.access_denied", MSG_RESULT_NEG);
				
				return FALSE;
			}
		}

		return array($test, $passwordRequired);
	}
}

?>
