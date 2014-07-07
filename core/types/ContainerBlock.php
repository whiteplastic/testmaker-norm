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
 * Include the UserList class
 */
require_once(dirname(__FILE__).'/UserList.php');

/**
 * Include FileHandling class
 */
require_once(dirname(__FILE__).'/FileHandling.php');

/**
 * Modifier for simulating adding a block
 */
define('MODIFICATION_ADD', 1);

/**
 * Modifier for simulating removing a block
 */
define('MODIFICATION_REMOVE', 2);

/**
 * Problem flag for a missing source for a feedback block
 */
define('PROBLEM_MISSING', 2);

/**
 * Conceptually, container blocks are blocks that may contain other blocks.
 * They are used for describing tests and sub-tests.
 *
 * @package Core
 */
class ContainerBlock extends Block
{

	/**
	 * Creates a new ContainerBlock.
	 * @param DB db object
	 * @param integer id of block
	 */
	function ContainerBlock($id) {

		$this->db = &$GLOBALS['dao']->getConnection();
		$this->table = DB_PREFIX.'container_blocks';
		$this->sequence = DB_PREFIX.'blocks';
		$this->Block($id);
	}

	/**
	 * return correct table name for block determinated by blocktype
	 * @access private
	 * @param integer Block type
	 * @return string Table name
	 */
	function _getTableByType($type)
	{
		switch($type) {
			case BLOCK_TYPE_CONTAINER:
				return DB_PREFIX."container_blocks";
				break;
			case BLOCK_TYPE_INFO:
				return DB_PREFIX."info_blocks";
				break;
			case BLOCK_TYPE_FEEDBACK:
				return DB_PREFIX."feedback_blocks";
				break;
			case BLOCK_TYPE_ITEM:
				return DB_PREFIX."item_blocks";
				break;
		}
	}

	/**
	 * Returns whether to show the progress bar or not
	 * @return boolean
	 */
	function getShowProgressBar()
	{
		return $this->_getField('progress_bar') ? TRUE : FALSE;
	}

	/**
	 * Returns whether to show the pause button or not
	 * @return integer
	 */
	function getShowPauseButton()
	{
		return $this->_getField('pause_button');
	}

	/**
	 * Return whether to use the parent-test's style
	 * @return boolean
	 */
	function getUseParentStyle()
	{
		if ($this->isRootBlock()) return FALSE;
		$query = "SELECT use_parent_style FROM ".DB_PREFIX."item_style WHERE test_id=? LIMIT 1";
		$res = $this->db->getOne($query, array($this->getId()));
		return $res == 1 ? TRUE : FALSE;
	}

	/**
	 * Returns the password
	 * @return string
	 */
	function getPassword()
	{
		return $this->_getField('password');
	}

	/**
	 * Returns whether users should be asked whether they want to skip certain items
	 * @return boolean
	 */
	function getEnableSkip()
	{
		return $this->_getField('enable_skip') ? TRUE : FALSE;
	}

	/**
	 * Returns whether subtests should be displayed for this test in portal
	 * @return boolean
	 */
	function getShowSubtests()
	{
		return $this->_getField('show_subtests') ? TRUE : FALSE;
	}

	/**
	 * Returns whether the user's email address should be requested in conjunction with a TAN test
	 */
	function getTanAskEmail()
	{
		return $this->_getField('tan_ask_email') ? TRUE : FALSE;
	}

	/**
	 * Returns whether this block is inactive or not
	 */
	function isInactive()
	{
		return $this->_getField('subtest_inactive') ? TRUE : FALSE;
	}
	
	/**
	 * Returns whether this block is inactive or not
	 */
	 
	/*nction isInactive($parentBlock)
	{
		$parentId = $parentBlock->getId();
		
		$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX." WHERE id=? AND parent_id=? AND disabled=1", array($pathIds[$maxPos - 2], $pathIds[$maxPos - 3]), array($this->id));

		return $res == 1 ? TRUE : FALSE;
	}*/

	/**
	 * Returns if this block has been published (made runnable).
	 * @param string access type (portal, run)
	 * @param boolean Whether to check for the current user (true) or for
	 *   regular users, i.e. those who don't have any view permissions
	 *   (false). Note that access is allowed for everyone if it's allowed for
	 *   guests.
	 * @return boolean
	 */
	function isAccessAllowed($type, $specific = true, $parent = null)
	{
		switch ($type) {
			case 'portal': case 'run': case 'tanrun': case 'review': case 'preview': 
				break;
			case 'tan':
				$type = 'tanrun';
				break;
			case 'direct':
				$type = 'run';
				break;
			case NULL: case false:
				return ($this->isAccessAllowed('portal', $specific) || $this->isAccessAllowed('run', $specific) 
						|| $this->isAccessAllowed('tanrun', $specific) || $this->isAccessAllowed('review', $specific)
						|| $this->isAccessAllowed('password', $specific));
			default:
				return false;
		}

		// TANs are handled courtesy of the virtual TAN group
		if ($type == 'tanrun') {
			$grp = DataObject::getById('Group', GROUP_VIRTUAL_TAN);
			if($parent == null) return $grp->checkPermission('run', $this);
			else return $grp->checkPermission('run', $parent);
		}

		// We'll check the guest user first; after all, if the guest can do
		// it, why not let everyone else do it too?
		$user = DataObject::getById('User', 0);
		if ($user->checkPermission($type, $this)) return true;
	
		if (is_string($specific) && $specific == 'password') {
			$grp = DataObject::getById('Group', GROUP_VIRTUAL_PASSWORD);
			return $grp->checkPermission($type, $this);
		} elseif ($specific) {
			$user = $GLOBALS['PORTAL']->getUser();
			return ($user->checkPermission($type, $this));
		} else {
			$userList = new UserList();
			//isSpecial needs to much time for Access allowed check, rework needed
			foreach ($userList->getGroupList(false, true) as $group) {
				if (!$group->isSpecial() && $group->checkPermission($type, $this)) return true;
			}
			return false;
		}
	}


	/**
	 * Returns if this block will be valid after adding/removing/linking blocks
	 * The modifications array has to be organized as an 2 dimensional array,
	 * in the first dimension all modifications are listed. In the second dimension the fields
	 * 'id', 'parent', 'position' and 'type' have to be given. In 'id', 'parent' (and 'position') the new/old position of the added/removed block has to be given.
	 * The field 'type' is the kind of modification (MODIFICATION_ADD = add, MODIFICATION_REMOVE = remove)
	 * Example: $modifications = array(0 => array('id' => 5, 'parent' => 1, 'type' => MODIFICATION_REMOVE, 'original' => true), 1 => array('id' => 5, 'parent' => 1, 'position' => 2, 'type' = MODIFICATION_ADD, 'original' => false));
	 * The array problems get filled within 2 dimesion. In the first dimension all problems are listed,
	 * In the second dimension the fields 'public', 'id', 'type' (and 'source') are given.
	 * 'public' is the id of the public block, 'checkIntegrity' is called from
	 * 'id' is the block id where the problem appears
	 * 'type' is representing the kind of problem
	 * There is currently one kind problems: PROBLEM_MISSING = feedback block is missing corresponding item block before.
	 * @param $modifications array of modifications to be checked
	 * @param array 2-dimensional array where to store changed ids from item blocks and item answers for feedback blocks and dimensions format: array('blocks' => array(id1, id2, ...), 'item_answers' => array(id1, id2, ...))
	 * @param &$problems array  filled with appearing problems
	 * @param string name of action requiring this check ('move_target', 'move_source', 'delete', 'copy', 'link')
	 * @return bool
	 */
	function checkIntegrity($modifications, $changedIds, &$problems, $action = 'unknown')
	{
		$result = true;
		if(!is_array($modifications)) {
			trigger_error('<b>ContainerBlock::checkIntegrity()</b>: $modifications is no valid array');
			return false;
		}
		if(!is_array($problems)) {
			trigger_error('<b>ContainerBlock::checkIntegrity()</b>: $problems is no valid array');
			return false;
		}
		//get new list of children
		$list = $this->listModifiedNode($modifications);

		//check for problems
		$analyse = array();
		for($i = 0; $i < count($list); $i++)
		{
			$analyse[$list[$i]['id']] = true;
			if($list[$i]['type'] == BLOCK_TYPE_FEEDBACK)
			{
				$currentBlock = $GLOBALS["BLOCK_LIST"]->getBlockById($list[$i]['id'], BLOCK_TYPE_FEEDBACK);

				foreach ($currentBlock->getSourceIds() as $id) {
					if(isset($changedIds['blocks'][$id])) {
						$id = $changedIds['blocks'][$id];
					}
					if(!isset($analyse[$id]) || $analyse[$id] != true) {
						$problem['public'] = $this->id;
						$problem['id'] = $list[$i]['id'];
						$problem['type'] = PROBLEM_MISSING;
						$problem['source'] = $id;
						$problem['action'] = $action;
						$problems[] = $problem;
						$result = false;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if a new order of the blocks children is valid or not (only applicable for reordering of item and feedback blocks yet)
	 * @param array List of block ids indicating which block will be saved at which position
	 * @param array Array for saving problems
	 * @param string Performed action
	 */
	function validateOrder($list, &$problems, $action = 'unknown')
	{
		$result = true;
		if(!is_array($problems)) {
			trigger_error('<b>ContainerBlock::checkIntegrity()</b>: $problems is no valid array');
			return false;
		}

		// Get the current block and page structure of the test/block
		require_once(CORE.'types/TestStructure.php');
		$testBlocks = TestStructure::getBlockStructure($this->id);
		$testPages = TestStructure::getPageStructure($this->id);

		// First build a list of parent id -> child id relations
		$parentIds = array();
		$blockIds = array();
		foreach($testBlocks as $block) 
		{
			$keyParent = $block['parent_id'];
			$keyId = $block['block_id'];
			if(!array_key_exists($keyParent, $parentIds)) $parentIds[$keyParent] = array();
			if(!array_key_exists($keyId, $parentIds)) $blockIds[$keyId] = array();
			$parentIds[$keyParent][] = $block['block_id'];
			$blockIds[$keyId] = true;
		}

		// Now build a list with the new virtual order of the blocks
		$orderChanged = false;
		$newTestBlocks = array();
		foreach($list as $index => $listItem)
		{
			if(array_key_exists($index, $parentIds[$this->id]) && $listItem['id'] != $parentIds[$this->id][$index]) $orderChanged = true;
			$lastId = $listItem['id'];
			// Add the blocks of the structure according to the new order
			if (array_key_exists($lastId, $testBlocks))
				$newTestBlocks[] = $testBlocks[$lastId];
			// Reorder the child blocks
			unset($testBlocks[$lastId]);
			if(array_key_exists($lastId, $parentIds))
			{
				foreach($parentIds[$lastId] as $childId)
				{
					$newTestBlocks[] = $testBlocks[$childId];
				}
			}
		}

		// Nothing has been changed, don't do reordering
		if(!$orderChanged) return false;

		// Build a list with item -> condition relations
		$blockItemConditions = array();
		foreach($testPages[0] as $page)
		{
			$parentId = $page->parentNode->id;
			if(is_subclass_of($page, "Item"))
			{
				$condArray = $page->getConditions();
				if(!empty($condArray))
				{
					if(!array_key_exists($parentId, $blockItemConditions)) $blockItemConditions[$parentId] = array();
					foreach($condArray as $cond)
					{
						$blockItemConditions[$parentId][] = $cond['item_block_id'];
					}
				}
			}
		}

		// Check the requirements for the feedback blocks (source blocks before feedback blocks)...
		$ids = array();
		foreach($newTestBlocks as $block)
		{
			$ids[$block['block_id']] = true;
			if($block['block_type'] == "ItemBlock")
			{
				// Get the conditions of all items in the block
				if(array_key_exists($block['block_id'], $blockItemConditions))
				{
					foreach($blockItemConditions[$block['block_id']] as $condBlock)
					{
						if(array_key_exists($condBlock, $blockIds) && (!array_key_exists($condBlock, $ids) || $ids[$condBlock] != true) && $block['block_id'] != $condBlock)
						{
							// Indicate problems
							$problem['public'] = $this->id;
							$problem['id'] = $block['block_id'];
							$problem['type'] = PROBLEM_MISSING;
							$problem['source'] = $condBlock;
							$problem['action'] = $action;
							$problems[] = $problem;
							$result = false;	
						}
					}
				}
			}
			elseif($block['block_type'] == "FeedbackBlock")
			{
				// Indicate problems
				if(array_key_exists('source_ids', $block))
				{
					foreach($block['source_ids'] as $sourceId)
					{
						if(array_key_exists($sourceId, $blockIds) && (!array_key_exists($sourceId, $ids) || $ids[$sourceId] != true))
						{
							$problem['public'] = $this->id;
							$problem['id'] = $block['block_id'];
							$problem['type'] = PROBLEM_MISSING;
							$problem['source'] = $sourceId;
							$problem['action'] = $action;
							$problems[] = $problem;
							$result = false;
						}
					}
				}
			}
		}
		
		return $result;	
	}

	/**
	 * Reorder child item/text/feedback blocks;
	 */
	function reorderChildren($order, &$problems, $checkIntegrity = true)
	{
		foreach($order as $position => $child)
		{

			if(!$GLOBALS['BLOCK_LIST']->getBlockById($child['id'])->setPosition($position+1, $this->id, $problems, $checkIntegrity)) return false;
		}
	}

	/**
	 * prepare given array with data for database insertion
	 * @static
	 * @param array associative array with database fields and content
	 * @return array associative array with database fields and content
	 */
	function prepareDBData($data) {

		if(!is_array($data)) {
			trigger_error('<b>ContainerBlock:prepareDBData</b>: $data is no valid array');
		}

		$data2 = array();

		if(!isset($data['direct_access_key'])) {
			$data['direct_access_key'] = '';
		}
		$data2['direct_access_key'] = $data['direct_access_key'];

		if(!isset($data['permissions_recursive'])) {
			$data['permissions_recursive'] = 1;
		}
		$data2['permissions_recursive'] = $data['permissions_recursive'];

		if(!isset($data['progress_bar'])) {
			$data['progress_bar'] = 1;
		}
		$data2['progress_bar'] = $data['progress_bar'];

		if(!isset($data['show_subtests'])) {
			$data['show_subtests'] = 0;
		}
		$data2['show_subtests'] = $data['show_subtests'];

		if(!isset($data['enable_skip'])) {
			$data['enable_skip'] = 1;
		}
		$data2['enable_skip'] = $data['enable_skip'];

		if(!isset($data['pause_button']) || $data['pause_button'] == 1) {
			$data['pause_button'] = 1;
		}
		$data2['pause_button'] = $data['pause_button'];

		if(!isset($data['tan_ask_email']) || $data['tan_ask_email'] == 1) {
			$data['tan_ask_email'] = 1;
		}
		$data2['tan_ask_email'] = $data['tan_ask_email'];

		return $data2;
	}

	/**
	 * Returns a copy of the current block
	 * @param integer Parent ID to copy block into
	 * @param array 2-dimensional array where to store changed ids from item blocks and item answers for feedback blocks and dimensions format: array('blocks' => array(id1, id2, ...), 'item_answers' => array(id1, id2, ...))
	 * 	On user call only an empty array has to be given. The array is filled by the different copy functions of the nodes.
	 * @param array Where to store problems if the block would be copied (checkIntegrity)
	 * @return Block
	 */
	function copyNode($parentId, &$changedIds, &$problems)
	{
		$block = parent::copyNode($parentId, $changedIds, NULL, $problems);

		if(count($problems) == 0)
		{
			$query = 'SELECT * FROM '.DB_PREFIX.'item_style WHERE test_id = ?';
			$results = $this->db->getAll($query, array($this->id));
			if($this->db->isError($results)) return false;

			foreach($results as $result) {
				$rows = array();
				$values = array();
				for(reset($result); list($key, $value) = each($result);)
				{
					switch($key)
					{
						case 'test_id':
							$value = $block->getId();
							break;
						case 'id':
							$value = $this->db->nextId(DB_PREFIX.'item_style');
							break;
					}
					$rows[] = $key;
					$values[] = $value;
				}
				$query = 'INSERT INTO '.DB_PREFIX.'item_style ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';
				if($this->db->isError($this->db->query($query, $values))) return false;
			}
		}

		return $block;
	}

	/**
	 * modify the given informations in current block
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
		if(array_key_exists('language', $modifications)) {
			$avalues['def_language'] = $modifications['language'];
		}
		if(array_key_exists('progress_bar', $modifications)) {
			$avalues['progress_bar'] = $modifications['progress_bar'];
		}
		if(array_key_exists('pause_button', $modifications)) {
			$avalues['pause_button'] = $modifications['pause_button'];
		}
		if(array_key_exists('show_subtests', $modifications)) {
			$avalues['show_subtests'] = $modifications['show_subtests'];
		}
		if(array_key_exists('enable_skip', $modifications)) {
			$avalues['enable_skip'] = $modifications['enable_skip'];
		}
		if(array_key_exists('password', $modifications)) {
			$avalues['password'] = $modifications['password'];
		}
		if(array_key_exists('open_date', $modifications)) {
			$avalues['open_date'] = $modifications['open_date'];
		}
		if(array_key_exists('close_date', $modifications)) {
			$avalues['close_date'] = $modifications['close_date'];
		}
		if(array_key_exists('subtest_inactive', $modifications)) {
			$avalues['subtest_inactive'] = $modifications['subtest_inactive'];
		}
		if(array_key_exists('random_order', $modifications)) {
			$avalues['random_order'] = (int)$modifications['random_order'];
		}
		if(array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : 0;
		}
		if(array_key_exists('tan_ask_email', $modifications)) {
			$avalues['tan_ask_email'] = $modifications['tan_ask_email'];
		}

		return $this->_modify($avalues);
	}

	/**
	 * Creates and returns a new block of the given type
	 * @param integer type of block
	 * @param array Associative array with required informations (key names same as database column names of given type)
	 * @return Block
	 */
	function createChild($type, $informations = array())
	{

		if(!preg_match('/^[0-4]{1}$/', $type))
		{
			trigger_error('<b>ContainerBlock:createBlock</b>: $type is no valid block type');
			return NULL;
		}
		if(!is_array($informations))
		{
			trigger_error('<b>ContainerBlock:createBlock</b>: $informations is no valid array');
			return NULL;
		}
		if(isset($informations['pos']) && !preg_match('/^[0-9]+$/', $informations['pos']))
		{
			trigger_error('<b>ContainerBlock:createBlock</b>: $informations[\'pos\'] is not valid');
			return NULL;
		}

		//prepare given array for given type
		switch($type) {
			case BLOCK_TYPE_CONTAINER:
				$avalues = ContainerBlock::prepareDBData($informations);
				break;
			case BLOCK_TYPE_INFO:
				$avalues = InfoBlock::prepareDBData($informations);
				break;
			case BLOCK_TYPE_FEEDBACK:
				$avalues = FeedbackBlock::prepareDBData($informations);
				break;
			case BLOCK_TYPE_ITEM:
				$avalues = ItemBlock::prepareDBData($informations);
				break;
		}

		//prepare informations with type independent values
		$id = $this->db->nextId($this->childrenSequence);
		if($this->db->isError($id)) {
			return NULL;
		}

		$avalues['id']			= $id;
		$avalues['owner']		= $GLOBALS['PORTAL']->getUserId();

		if(isset($informations['title'])) {
			$avalues['title'] = $informations['title'];
		}
		if(isset($informations['description'])) {
			$avalues['description'] = $informations['description'];
		}

		//insert type information of block
		$query = 'INSERT INTO '.DB_PREFIX.'blocks_type (id, type) VALUES (?, ?)';
		$result = $this->db->query($query, array($avalues['id'], $type));
		if($this->db->isError($result)) {
			return NULL;
		}

		$child = $this->_createChild($type, $avalues);
		$child->setParentPermissions($this->id);
		
		if ($type == BLOCK_TYPE_CONTAINER) {
			$fields = array(
					'background_color' => "#ffffff",
					'font_family' => "",
					'font_size' => "",
					'font_style' => "",
					'font_weight' => "",
					'color' => "#000000",
					'item_background_color' => "#ffffff",
					'dist_background_color' => "#ffffff",
					'logo_align' => "left",
					'page_width' => "0",
					'item_borders' => 7,
					'use_parent_style' => 1,
				);
			
			$child->setStyle($fields);
		}

		return $child;
	}

	/**
	 * deletes the given block from the current block and removes all saved data of this block and its children if they are not children of any other block
	 * @param integer $id id of block
	 * @param array $problems problems of integrity (see checkIntegrity)
	 * @return boolean
	 */
	function deleteChild($id, &$problems, $simulate = false)
	{

		if(!$this->existsChild($id))
		{
			trigger_error('<b>ContainerBlock:deleteChild</b>: $id does not exist');
			return false;
		}
		if(!is_array($problems))
		{
			trigger_error('<b>ContainerBlock::deleteChild</b>: $problems is no valid array');
			return false;
		}
		if(count($problems) > 0)
		{
			trigger_error('<b>ContainerBlock::deleteChild</b>: $problems is not empty');
			return false;
		}

		//simulate in all Public Blocks containing this Block the situation if the block is already deleted
		$modification['parent'] = $this->id;
		$modification['id'] = $id;
		$modification['type'] = MODIFICATION_REMOVE;
		$modifications[] = $modification;
		$parentUpperPublicBlocks = $this->getUpperPublicBlocks();

		for($i = 0; $i < sizeof($parentUpperPublicBlocks); $i++) {
			$parentUpperPublicBlocks[$i]->checkIntegrity($modifications, array(), $problems, 'delete');
		}

		//interrupt deletion if problem appears
		if(count($problems) > 0)
		{
			$problems = array_unique($problems);
			return false;
		}

		if ($simulate) return true;

		//delete all test runs for given child
		require_once(CORE."types/TestRunList.php");
		$testRunList = new TestRunList();
		$testRuns = $testRunList->getTestRunsForTest($id);
		for ($i = 0; $i < count($testRuns); $i++) {
			$testRun = &$testRuns[$i];
			$testRun->delete();
		}

		return parent::deleteChild($id);
	}

	/**
	 * Return stylesheet informations for this test
	 *
	 * @return string Stylesheet definitions
	 */
	function getStyle()
	{
		$style = array();
		$query = "SELECT * FROM ".DB_PREFIX."item_style WHERE test_id=? LIMIT 1";
		$res = $this->db->query($query, array($this->getId()));

		if (!PEAR::isError($res) && $res->numRows())
		{
			while ($row = $res->fetchRow())
			{
				$style['Top']['text-align'] = $row['logo_align'];
				$style['Top']['logo-show'] = $row['logo_show'];
				$style['wrapper']['width'] = $row['page_width'] == 1 ? '800px' : 0;
				$style['wrapper']['margin'] = $row['page_width'] == 1 ? 'auto' : 0;
				$style['wrapper']['text-align'] = 'left';
				$style['body']['background-color'] = $row['background_color'];
				$style['Question']['font-family'] = $row['font_family'];
				$style['Question']['font-size'] = $row['font_size'];
				$style['Question']['font-style'] = $row['font_style'];
				$style['Question']['font-weight'] = $row['font_weight'];
				$style['Question']['color'] = $row['color'];
				$style['Answers']['font-family'] = $row['font_family'];
				$style['Answers']['font-size'] = $row['font_size'];
				$style['Answers']['font-style'] = $row['font_style'];
				$style['Answers']['font-weight'] = $row['font_weight'];
				$style['Answers']['color'] = $row['color'];
				$style['Question']['background-color'] = $row['item_background_color'];
				$style['Answers']['background-color'] = $row['dist_background_color'];
				// process item borders, which are stored as a 3-digit binary number
				if ($row['item_borders'] != NULL) {
					$tmp = decbin($row['item_borders']);
					$tmp = substr("000",0,3 - strlen($tmp)) . $tmp;
					$style['Question']['border'] = $tmp[0] != 0 ? '1px solid #cccccc' : 0;
					$style['Answers table.Border']['border'] = $tmp[1] != 0 ? '1px solid #cccccc' : 0;
					$style['Answers td.Border']['border'] = $tmp[2] != 0 ? '1px solid #cccccc' : 0;
				} else {
					$style['Question']['border'] = '1px solid #cccccc';
					$style['Answers table.Border']['border'] = '1px solid #cccccc';
					$style['Answers td.Border']['border'] = '1px solid #cccccc';
				}
			}
		} else if (! $res->numRows()) {
			//Default values
			$style['Top']['text-align'] = 'left';
			$style['Top']['logo-show'] = 0;
			$style['wrapper']['width'] = 0;
			$style['wrapper']['margin'] = 0;
			$style['wrapper']['text-align'] = 'left';
			$style['body']['background-color'] = '#ffffff';
			$style['Question']['font-family'] = 'Verdana';
			$style['Question']['font-size'] = 'none';
			$style['Question']['font-style'] = 'none';
			$style['Question']['font-weight'] = 'none';
			$style['Question']['color'] = '#000000';
			$style['Answers']['font-family'] = 'Verdana';
			$style['Answers']['font-size'] = 'none';
			$style['Answers']['font-style'] = 'none';
			$style['Answers']['font-weight'] = 'none';
			$style['Answers']['color'] = '#000000';
			$style['Question']['background-color'] = '#ffffff';
			$style['Answers']['background-color'] = '#ffffff';
			$style['Question']['border'] = '1px solid #cccccc';
			$style['Answers table.Border']['border'] = '1px solid #cccccc';
			$style['Answers td.Border']['border'] = '1px solid #cccccc';
		}
		return $style;
	}

	/**
	 * Return the default language of a test
	 *
	 * @return string Default Language
	 */
	function getLanguage()
	{
		$language = $this->_getField('def_language');
		return $language;
	}

	/**
	 * Return media_connect id of a logo
	 *
	 * @return mixed Media_connect_id or false
	 */
	function getLogo()
	{
		$query = "SELECT logo FROM ".DB_PREFIX."item_style WHERE test_id = ? LIMIT 1";
		$logo = $this->db->getOne($query, array($this->getId()));

		if (PEAR::isError($logo))
		{
			$logo = false;
		}
		return $logo;
	}
	/**
	 * Return logo_show of a logo
	 *
	 * @return int logo_show
	 */
	function getLogoShow()
	{
		$query = "SELECT logo_show FROM ".DB_PREFIX."item_style WHERE test_id = ? LIMIT 1";
		$logo_show = $this->db->getOne($query, array($this->getId()));

		if (PEAR::isError($logo_show))
		{
			$logo_show = 0;
		}
		return $logo_show;
	}

	/**
	 * set stylesheet informations for this test
	 *
	 * @param mixed Style informations
	 * @return boolean
	 */
	function setStyle($style)
	{
		if ($this->hasStyle())
		{
			$styles = '';
			$values = array();
			$first = true;
			foreach ($style as $key => $value)
			{
				if (!$first) $styles .= ',';
				$styles .= $key.' = ?';
				$values[] = $value;
				$first = false;
			}
			$values[] = $this->getId();
			$query = "UPDATE ".DB_PREFIX."item_style SET ".$styles." WHERE test_id=? LIMIT 1";
			$result = $this->db->query($query, $values);
		}
		else
		{
			$styles = '';
			$marks = '?,?,';
			$values = array($this->db->nextId(DB_PREFIX."item_style"), $this->getId());
			$first = true;
			if (!isset($style['logo_align'])) {
				$style['logo_align'] = '';
			}
			foreach ($style as $key => $value)
			{
				$values[] = $value;
				if (!$first) {
					$styles .= ',';
					$marks .= ',';
				}
				$styles .= $key;
				$marks .= '?';
				$first = false;
			}
			$query = "INSERT INTO ".DB_PREFIX."item_style (id, test_id, ".$styles.") VALUES(".$marks.")";
			$result = $this->db->query($query, $values);
		}
		if (PEAR::isError($result))
		{
			return false;
		}
		return true;
	}

	/**
	 * Checks if this block has a stylesheet associated with it.
	 * @return boolean
	 */
	function hasStyle()
	{
		$query = "SELECT count(id) FROM ".DB_PREFIX."item_style WHERE test_id = ? LIMIT 1";
		$has = $this->db->getOne($query, array($this->getId()));

		if (PEAR::isError($has) || !$has)
		{
			return false;
		}
		return true;
	}
	
	function hasRandomItemOrder()
	{
		return $this->_getField('random_order') == 1 ? true : false;
	}

	/**
	 * Sets the logo for this block to a certain media file.
	 * @param integer media_connect_id of the file
	 */
	function setLogo($media_connect_id = 0)
	{
		if ($this->hasStyle())
		{
			$query = "UPDATE ".DB_PREFIX."item_style SET logo=? WHERE test_id=?";
			$result = $this->db->query($query, array($media_connect_id, $this->getId()));
		}
		else
		{
			$query = "INSERT INTO ".DB_PREFIX."item_style (id, test_id, logo) VALUES(?,?,?)";
			$result = $this->db->query($query, array($this->db->nextId(DB_PREFIX."item_style"), $this->getId(), $media_connect_id));
		}
		if (PEAR::isError($result))
		{
			return false;
		}
		return true;
	}

	function getItemDisplayConditions($completeOnly = FALSE)
	{
		$conditions = array();

		foreach ($this->getChildren() as $child)
		{
			if ($child->isContainerBlock() || $child->isItemBlock()) {
				$conditions = array_merge($conditions, $child->getItemDisplayConditions($completeOnly));
			}
		}

		return $conditions;
	}

	function getItemIds($adaptive = TRUE)
	{
		$itemIds = array();

		foreach ($this->getChildren() as $child)
		{
			if ($child->isContainerBlock() || ($child->isItemBlock() && $adaptive)) {
				$itemIds = array_merge($itemIds, $child->getItemIds());
			}
		}

		return $itemIds;
	}

	/**
	 * Counts the number of children with a certain type
	 * @param integer The type of children to count
	 * @return integer
	 */
	function countChildrenByType($type)
	{
		$count = 0;
		foreach ($this->getChildren() as $child) {
			if ($child->getBlockType() == $type) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Prepares this block for deletion; cleans stylesheet table if exists
	 */
	function cleanUp()
	{
		// Remove style record
		if ($this->hasStyle()) {
			
			// Remove logo
			if ($logo = $this->getLogo()) {
				
				// but only if it is not used by any other test

				//$query ="SELECT count(id) FROM ".DB_PREFIX."item_style WHERE logo = '".$logo."'";
				//$count = $this->db->getOne($query, array($logo));
				//$logoUses = $this->db->getOne("SELECT COUNT(id) FROM ".DB_PREFIX."item_style WHERE logo = ".$logo);
				//echo $logoUses;
			//b/ 

			//	$logoUses = $this->db->getOne("SELECT COUNT(id) FROM ".DB_PREFIX."item_style WHERE logo = ".$logo);


				$fh = new FileHandling();
				//if($logoUses == 1)
					$fh->deleteMediaConnection($logo);
			}

			$query = "DELETE FROM ".DB_PREFIX."item_style WHERE test_id = ? LIMIT 1";
			$delete = $this->db->query($query, array($this->getId()));

			if (PEAR::isError($delete))
			{
				return false;
			}
		}

		parent::cleanUp();
	}

	/**
	 * \return string Text open date
	 */
	function getOpenDate()
	{
		return $this->_getField('open_date');
	}

	/**
	 * \return string Text close date
	 */
	function getCloseDate()
	{
		return $this->_getField('close_date');
	}
	
	/**
	*Return the number of shown Items of the container block
	*@return int 
	*/
	
	function getShownItems($testRun) {
		$childs = $this->getChildren();
		$total = 0;
		foreach ($childs as $child) {
				if ($child->isFeedbackBlock() == false)
				$total += $child->getShownItems($testRun);
		}
		return $total;
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
}

?>
