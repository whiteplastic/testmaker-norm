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
 * Include the Item class
 */
require_once(dirname(__FILE__).'/Item.php');
require_once(dirname(__FILE__).'/ItemBlockAnswer.php');

/**
 * ItemBlock class
 *
 * @package Core
 */

class ItemBlock extends ParentTreeBlock
{

	function ItemBlock($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->table = DB_PREFIX.'item_blocks';
		$this->sequence = DB_PREFIX.'item_blocks';
		$this->childrenTable = DB_PREFIX.'items';
		$this->ParentTreeBlock($id);
	}

	function _returnTreeChild($id, $type = NULL) {
		if (!$type) {
			$query = 'SELECT type FROM '.$this->childrenTable.' WHERE id = ?';
			$type = $this->db->getOne($query, array($id));
			if ($this->db->isError($type)) {
				return false;
			}
		}
		$type ? $type : $type = 'McsaItem';
		if (!file_exists(ROOT.'upload/items/'.$type.'.php')) {
			trigger_error('Unknown item type.');
		}
		require_once(ROOT.'upload/items/'.$type.'.php');
		return new $type($id);
	}

	function &getTreeChildren() {
		$query = 'SELECT id, type FROM '.$this->childrenTable.' WHERE '.$this->childrenConnector.' = ? ORDER BY pos ASC';
		$res = $this->db->getAll($query, array($this->id));
		if ($this->db->isError($res)) {
			return false;
		}
		$children = array();
		for ($i = 0; $i < count($res); $i ++) {
			$children[] = $this->_returnTreeChild($res[$i]['id'], $res[$i]['type']);
		}
		return $children;
	}

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
		if (!array_key_exists('type', $avalues) || !$avalues['type']) {
			$avalues['type'] = $this->getDefaultItemType();
		}
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
	 * Returns if this an adaptive item block
	 * @return bool
	 */
	function isAdaptiveItemBlock()
	{
		$type = $this->_getField('type');

		if($type == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns if the items of this block should be IRT rated
	 * @return bool
	 */
	function isIRTBlock()
	{
		$irt = $this->_getField('irt');

		if($irt == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns if the items of this block should force an answer
	 * @return bool
	 */
	function isDefaultItemForced()
	{
		$force = $this->_getField('default_item_force');

		if($force == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * modify the given informations in the current block
	 * @param mixed[] $modification values to be modified
	 * @return bool
	 */
	function modify($modifications)
	{
		$avalues = array();
		if(array_key_exists('type', $modifications)) {
			if(($modifications['type'] != 0) && ($modifications['type'] != 1)) {
				trigger_error('<b>ItemBlock:modify</b>: $modifications[\'type\'] is no valid type');
				return false;
			}
			$avalues['type'] = $modifications['type'];
		}
		if(array_key_exists('title', $modifications)) {
			$avalues['title'] = $modifications['title'];
		}
		if(array_key_exists('description', $modifications)) {
			$avalues['description'] = $modifications['description'];
		}
		if(array_key_exists('introduction', $modifications)) {
			$avalues['introduction'] = $modifications['introduction'];
		}
		if(array_key_exists('hidden_intro', $modifications)) {
			$avalues['hidden_intro'] = $modifications['hidden_intro'];
		}
		if(array_key_exists('intro_firstonly', $modifications)) {
			$avalues['intro_firstonly'] = $modifications['intro_firstonly'];
		}
		if(array_key_exists('intro_pos', $modifications)) {
			$avalues['intro_pos'] = $modifications['intro_pos'];
		}
		if(array_key_exists('permissions_recursive', $modifications)) {
			$avalues['permissions_recursive'] = $modifications['permissions_recursive'];
		}
		if(array_key_exists('default_item_type', $modifications)) {
			$avalues['default_item_type'] = $modifications['default_item_type'];
		}
		if(array_key_exists('default_template_align', $modifications)) {
			$avalues['default_template_align'] = $modifications['default_template_align'];
		}
		if(array_key_exists('default_template_cols', $modifications)) {
			$avalues['default_template_cols'] = $modifications['default_template_cols'];
		}
		if(array_key_exists('default_item_force', $modifications)) {
			$avalues['default_item_force'] = $modifications['default_item_force'];
		}
		if(array_key_exists('max_items', $modifications)) {
			$avalues['max_items'] = $modifications['max_items'];
		}
		if(array_key_exists('max_sem', $modifications)) {
			$avalues['max_sem'] = $modifications['max_sem'];
		}
		if(array_key_exists('max_time', $modifications)) {
			$avalues['max_time'] = $modifications['max_time'];
		}
		if(array_key_exists('irt', $modifications)) {
			$avalues['irt'] = $modifications['irt'];
		}
		if(array_key_exists('default_min_item_time', $modifications)) {
			$avalues['default_min_item_time'] = $modifications['default_min_item_time'];
		}
		if(array_key_exists('default_max_item_time', $modifications)) {
			$avalues['default_max_item_time'] = $modifications['default_max_item_time'];
		}
		if(array_key_exists('items_per_page', $modifications)) {
			$avalues['items_per_page'] = $modifications['items_per_page'];
		}
		if(array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}
		if(array_key_exists('intro_label', $modifications)) {
			$avalues['intro_label'] = $modifications['intro_label'];
		}
		if(array_key_exists('random_order', $modifications)) {
			$avalues['random_order'] = (int)$modifications['random_order'];
		}
		if(array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : 0;
		}
		
		if(array_key_exists('conditions_need_all', $modifications)) {
			$avalues['conditions_need_all'] = $modifications['conditions_need_all'];
		}

		return $this->_modify($avalues);
	}

	/**
	 * prepare given array with data for database insertion
	 * @param array associative array with database fields and content
	 * @return array associative array with database fields and content
	 */
	function prepareDBData($data) {

		if(!is_array($data)) {
			trigger_error('<b>ItemBlock:prepareDBData</b>: $data is no valid array');
		}

		$data2 = array();

		if(!isset($data['type'])) {
			$data['type'] = 0;
		} elseif(($data['type'] != 1) && ($data['type'] != 0)) {
			trigger_error('<b>ItemBlock:prepareDBData</b>: $data[\'type\'] is no correct item block type');
			return NULL;
		}
		$data2['type'] = $data['type'];

		if(!isset($data['irt']) || $data['irt'] == false) {
			$data2['irt'] = 0;
		} else {
			$data2['irt'] = 1;
		}

		if (!isset($data['original'])) {
			$data['original'] = 0;
		}
		if(!isset($data['intro_pos'])) {
			$data['intro_pos'] = 0;
		}
		$data2['original'] = $data['original'];

		if (!isset($data['default_item_type'])) {
			$data['default_item_type'] = '';
		}
		$data2['default_item_type'] = $data['default_item_type'];

		if (!isset($data['default_template_align'])) {
			$data['default_template_align'] = 'h';
		}
		$data2['default_template_align'] = $data['default_template_align'];

		if (!isset($data['default_template_cols'])) {
			$data['default_template_cols'] = 2;
		}
		$data2['default_template_cols'] = $data['default_template_cols'];

		if (!isset($data['default_item_force'])) {
			$data['default_item_force'] = 0;
		}
		$data2['default_item_force'] = $data['default_item_force'];

		if(!isset($data['max_items'])) {
			$data['max_items'] = 0;
		}
		$data2['max_items'] = $data['max_items'];

		if(!isset($data['max_sem'])) {
			$data['max_sem'] = 0;
		}
		$data2['max_sem'] = $data['max_sem'];

		if(!isset($data['max_time'])) {
			$data['max_time'] = 0;
		}
		$data2['max_time'] = $data['max_time'];

		if(!isset($data['default_max_item_time'])) {
			$data['default_max_item_time'] = 0;
		}
		$data2['default_max_item_time'] = $data['default_max_item_time'];

		if(!isset($data['default_min_item_time'])) {
			$data['default_min_item_time'] = 0;
		}
		$data2['default_min_item_time'] = $data['default_min_item_time'];

		if(!isset($data['items_per_page'])) {
			$data['items_per_page'] = 0;
		}
		$data2['items_per_page'] = $data['items_per_page'];


		return $data2;

	}

	/**
	 * creates and returns a new item
	 * @param mixed[] associative array of informations
	 * @param boolean Whether to insert default item answers
	 * @return Item
	 */
	function createTreeChild($infos = array(), $useDefaults = true)
	{
		$avalues = Item::PrepareDBData($infos);

		if(!($child = $this->_createTreeChild($avalues))) {
			return false;
		}

		if (!$useDefaults) return $child;

		$defaultAnswers = $this->getDefaultAnswers();

		$infos = array();
		for($i = 0; $i < count($defaultAnswers); $i++) {
			$infos["answer"] = $defaultAnswers[$i]->getAnswer();
			if(!$child->createChild($infos)) {
				return false;
			}
		}

		return $child;
	}

	/**
	 * Returns a copy of the current item block
	 * @param parent id to copy item block into
	 * @param 2 dimensional array where to store changed ids from item blocks and item answers for feedback blocks and dimensions format: array('blocks' => array(id1, id2, ...), 'item_answers' => array(id1, id2, ...))
	 * @param array where to store problems if the block would be copied
	 * @return ItemBlock
	 */
	function copyNode($parentId, &$changedIds, &$problems)
	{
		$newNode = parent::copyNode($parentId, $changedIds, NULL, $problems);
		
		$fileHandling = new FileHandling();
		$mediaPath = str_replace(ROOT, '', $fileHandling->getFileDirectory()).'media/';

if($newNode) {

		$value = preg_replace('/'.preg_quote($mediaPath, '/').'[0-9]+_/', $mediaPath.$newNode->getMediaConnectId().'_', $newNode->getIntroduction());
		$newNode->modify(array('introduction' => $value));
}		
		if(count($problems) == 0)
		{
			
			//copy default answers
			$query = 'SELECT * FROM '.DB_PREFIX.'item_block_answers WHERE item_block_id = ?';
			$results = $this->db->getAll($query, array($this->id));
			if($this->db->isError($results))
			{
				return false;
			}
			//$fileHandling = new FileHandling();
			foreach($results as $result) {
				$rows = array();
				$values = array();
				for(reset($result); list($key, $value) = each($result);)
				{
					switch($key)
					{
						case 'id':
							$value = $this->db->nextId(DB_PREFIX.'item_block_answers');
							break;
						case 'item_block_id':
							$value = $newNode->getId();
							break;
						case 't_created':
							$value = time();
							break;
						case 't_modified':
							$value = time();
							break;
						case 'answer':
							$mediaPath = str_replace(ROOT, '', $fileHandling->getFileDirectory()).'media/';
							$value = preg_replace('/'.preg_quote($mediaPath, '/').'[0-9]+_/', $mediaPath.$newNode->getMediaConnectId().'_', $value);
					}
					$rows[] = $key;
					$values[] = $value;
				}
				$query = 'INSERT INTO '.DB_PREFIX.'item_block_answers ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';
				$this->db->query($query, $values);
			}
		}

		return $newNode;
	}

	/**
	 * Returns the number of maximal items for the current block
	 * @return bool
	 */
	function getMaxItems()
	{
		return $this->_getField('max_items');
	}

	/**
	 * Returns the maximal SEM of the current block
	 * @return bool
	 */
	function getMaxSem()
	{
		return $this->_getField('max_sem');
	}

	/**
	 * Returns the maximal time for the current block
	 * @return bool
	 */
	function getMaxTime()
	{
		return $this->_getField('max_time');
	}

	/**
	 * Returns the default maximal time for items in current block
	 * @return bool
	 */
	function getDefaultMaxItemTime()
	{
		return $this->_getField('default_max_item_time');
	}

	/**
	 * Returns the default minimal time for items in current block
	 * @return bool
	 */
	function getDefaultMinItemTime()
	{
		return $this->_getField('default_min_item_time');
	}

	function getIntroduction()
	{
		return $this->_getField('introduction');
	}

	function isHiddenIntro()
	{
		if ($this->_getField('hidden_intro') != 1)
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	function isIntroFirstOnly()
	{
		if ($this->_getField('intro_firstonly') != 1)
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	function getIntroPos()
	{
		return ! $this->_getField('intro_pos');
	}
	
	function getIntroLabel()
	{
		return $this->_getField('intro_label');
	}
	
	function hasRandomItemOrder()
	{
		return $this->_getField('random_order') == 1 ? true : false;
	}
	
	/**
	 * get all default answers of the current itemblock
	 * @return ItemBlockAnswer[]
	 */
	function getDefaultAnswers()
	{
		$query = 'SELECT id FROM '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? ORDER BY pos ASC';
		$ids = $this->db->getAll($query, array($this->id));
		if($this->db->isError($ids))
		{
			return false;
		}
		$answers = array();
		for($i = 0; $i < count($ids); $i++) {
			$answers[] = new ItemBlockAnswer($ids[$i]['id']);
		}
		return $answers;
	}

	/**
	 * delete default answer by given id
	 * @param integer id of itemblock
	 * @return boolean
	 */
	function deleteDefaultAnswer($id) {
		if(!$this->existsDefaultAnswer($id)) {
			trigger_error('<b>ItemBlock:deleteDefaultAnswer</b>: $id is no valid default answer id in current itemblock');
			return false;
		}

		$query = 'DELETE FROM '.DB_PREFIX.'item_block_answers WHERE id = ?';
		$result = $this->db->query($query, array($id));
		if($this->db->isError($result))
		{
			return false;
		}

		return true;
	}

	/**
	 * return default answer by given id
	 * @return ItemAnswer
	 */
	function getDefaultAnswerById($id)
	{
		$query = 'SELECT count(id) FROM '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? AND id = ?';
		$num = $this->db->getAll($query, array($this->id, $id));
		if($this->db->isError($num))
		{
			return false;
		}
		if($num == 0) {
			trigger_error('<b>ItemBlock::getDefaultAnswerById</b>: $id is no valid default answer id in this itemblock');
			return false;
		}

		return new ItemBlockAnswer($id);
	}

	/**
	 * creates and returns a new default answer
	 * @param $position position of default answer in itemblock
	 * @param $optionalInfos associative array of optional informations (answer)
	 * @return ItemAnswer
	 */
	function createDefaultAnswer($position = NULL, $optionalInfos = array())
	{
		if($position != NULL && !preg_match('/ [0-9]+$/', $position)) {
			trigger_error('<b>ItemBlock::createDefaultAnswer</b>: $position is no valid position');
			return false;
		}

		if($position == NULL) {
			$position = $this->getNextFreeDefaultAnswerPosition();
		}

		$id = $this->db->nextId(DB_PREFIX.'item_block_answers');
		if($this->db->isError($id))
		{
			return false;
		}

		$avalues['id']			= $id;
		$avalues['item_block_id'] = $this->id;
		$avalues['pos'] = $position;
		$avalues['t_created']	= time();
		$avalues['t_modified']	= time();

		if(isset($optionalInfos['answer'])) {
			$avalues['answer'] = $optionalInfos['answer'];
		}

		$values = '';
		$columns = '';
		$quote = array();
		for(reset($avalues); list($column, $value) = each($avalues); ) {
			if(strlen($columns) > 0) {
				$columns .= ', ';
			}
			if(strlen($values) > 0) {
				$values .= ', ';
			}
			$columns .= $column;
			$values .= '?';
			$quote[] = $value;
		}

		$query = 'INSERT INTO '.DB_PREFIX.'item_block_answers ('.$columns.') VALUES ('.$values.')';
		$result = $this->db->query($query, $quote);
		if($this->db->isError($result))
		{
			return false;
		}

		return new ItemBlockAnswer($id);
	}

	/**
	 * returns the next free position for a default answer
	 * @return Item
	 */
	function getNextFreeDefaultAnswerPosition()
	{
		$query = 'SELECT pos FROM '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? ORDER BY pos DESC LIMIT 1';

		if(!($position = $this->db->getOne($query, array($this->id))))
		{
			return 1;
		}
		else {
			if($this->db->isError($position))
			{
				return false;
			}

			return (((int) $position) + 1);

		}

	}

	/**
	 * Returns a list of feedback blocks this block acts as a source for.
	 */
	function getFeedbackDestinations()
	{
		$ids = $this->db->getAll('SELECT * FROM '.DB_PREFIX.'feedback_blocks_connect WHERE item_block_id = ?', array($this->id));

		if ($this->db->isError($ids)) {
			return array();
		}

		$res = array();
		foreach ($ids as $id) {
			$res[] = $GLOBALS["BLOCK_LIST"]->getBlockById($id['feedback_block_id'], BLOCK_TYPE_FEEDBACK);
		}
		return $res;
	}

	/**
	 * returns if a default answer exists in current itemblock
	 * @param integer id of item
	 * @return boolean
	 */
	function existsDefaultAnswer($id) {
		if(!preg_match('/^[0-9]+$/', $id)) {
			trigger_error('<b>ItemBlock:existsDefaultAnswer</b>: $id is no valid id');
			return false;
		}
		$query = 'SELECT count(id) from '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? AND id = ?';
		$result = $this->db->getOne($query, array($this->id, $id));
		if($this->db->isError($result))
		{
			return false;
		}
		if($result == 0) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * returns if any default answer exists in current itemblock
	 * @return boolean
	 */
	function existsAnyDefaultAnswer() {

		$query = 'SELECT count(id) from '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? ';
		$result = $this->db->getOne($query, array($this->id));
		if($this->db->isError($result))
		{
			return false;
		}
		if($result == 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if any default answer exists at the given position
	 * @param integer Position of answer in item
	 * @return boolean
	 */
	function existsAnyDefaultAnswerAtPosition($position) {
		if(!preg_match('/^[0-9]+$/', $position)) {
			trigger_error('<b>ItemBlock:isAnyDefaultAnswerAtPosition</b>: $position is no valid position');
			return false;
		}
		$query = 'SELECT count(pos) from '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? AND pos = ?';
		$result = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($result))
		{
			return false;
		}
		if($result > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * returns default answer at given Position
	 * @param integer Position of item in itemblock
	 * @return Answer
	 */
	function getDefaultAnswerByPosition($position) {
		if(!preg_match('/^[0-9]+$/', $position)) {
			trigger_error('<b>ItemBlock:getDefaultAnswerByPosition</b>: $position is no valid position');
			return false;
		}
		$query = 'SELECT count(pos) from '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? AND pos = ?';
		$result = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($result))
		{
			return false;
		}
		if($result == 0 ) {
			trigger_error('<b>ItemBlock:getDefaultAnswerByPosition</b>: no default answer found at $position');
			return false;
		}
		$query = 'SELECT id from '.DB_PREFIX.'item_block_answers WHERE item_block_id = ? AND pos = ?';
		$id = $this->db->getOne($query, array($this->id, $position));
		if($this->db->isError($id))
		{
			return false;
		}

		return new ItemBlockAnswer($id);
	}

	/**
	 * returns the current item type
	 * @return string
	 */
	function getDefaultItemType()
	{
		$val = $this->_getField('default_item_type');
		if (!$val) return 'McsaItem';
		return $val;
	}

	/**
	 * returns the current default item number of answer rows for the template
	 * @return int
	 */
	function getDefaultTemplateCols()
	{
		$val = $this->_getField('default_template_cols');
		if (!$val) return 2;
		return $val;
	}

	/**
	 * returns the current default template align for the template
	 * @return char
	 */
	function getDefaultTemplateAlign()
	{
		$val = $this->_getField('default_template_align');
		if (!$val) return 'h';
		return $val;
	}

	/**
	 * returns how many item are displayed at once
	 * @return integer
	 */
	function getItemsPerPage()
	{
		$val = $this->_getField('items_per_page');
		if (!$val) return 1;
		return $val;
	}

	/** 
	 * returns the number of items in an item-block
	 * (disregarding possible hiding)
	 * @return integer
	 */     
	function getCountItemsInBlock($blockid)
	{
		$sql = 'SELECT COUNT(id) FROM '.DB_PREFIX.'items WHERE block_id = '.$blockid;
		$num = $this->db->getOne($sql);
		
		return $num;
	}
	
	/**
	 * overwrite all item answers and item types with the default ones of the item block
	 */
	function overwriteItems($overwrite) {
		$items = $this->getTreeChildren();
		$defaultAnswers = $this->getDefaultAnswers();
		$modifications = array();

		if(isset($overwrite['min_item_time']) && $overwrite['min_item_time']) {
			$modifications['min_time'] = $this->getDefaultMinItemTime();
		}
		if(isset($overwrite['max_item_time']) && $overwrite['max_item_time']) {
			$modifications['max_time'] = $this->getDefaultMaxItemTime();
		}
		if(isset($overwrite['item_force']) && $overwrite['item_force']) {
			$modifications['answer_force'] = $this->isDefaultItemForced();
		}

		$infos = array();
		for($i = 0; $i < count($items); $i++) {
			if(isset($overwrite['default_answers']) && $overwrite['default_answers']) {
				$answers = $items[$i]->getChildren();
				for($j = 0; $j < count($answers); $j++) {
					if(!$items[$i]->deleteChild($answers[$j]->getId())) {
						return false;
					}
				}
				for($j = 0; $j < count($defaultAnswers); $j++) {
					$infos["answer"] = $defaultAnswers[$j]->getAnswer();
					if(!$items[$i]->createChild($infos)) {
						return false;
					}
				}
			}

			if(sizeof($modifications) > 0) {
				if(!$items[$i]->modify($modifications)) {
					return false;
				}
			}
		}

		return true;
	}

	function getItemDisplayConditions($completeOnly = FALSE)
	{
		$conditions = array();

		foreach ($this->getTreeChildren() as $child)
		{
			foreach ($child->getConditions($completeOnly) as $condition) {
				$condition["owner_block_id"] = $this->getId();
				$condition["owner_item_id"] = $child->getId();
				$conditions[] = $condition;
			}			
		}

		return $conditions;
	}

	function getItemIds()
	{
		$itemIds = array();

		foreach ($this->getTreeChildren() as $child) {
			$itemIds[] = $child->getId();
		}

		return $itemIds;
	}

	function orderChilds($newOrder)
	{
		// Item IDs
		$handledIds = array();

		foreach ($newOrder as $childId)
		{
			$child = $this->getTreeChildById($childId);
			$conditions = $child->getConditionsOnSiblings();
			foreach ($conditions as $condition) {
				if (! in_array($condition["item_id"], $handledIds) && $target = @$this->getTreeChildById($condition["item_id"])) {
					$GLOBALS["MSG_HANDLER"]->addMsg("types.item_block.order_conflict.condition", MSG_RESULT_NEG, array("referrer_title" => $child->getTitle(), "target_title" => $target->getTitle()));
					return FALSE;
				}
			}
			$handledIds[] = $child->getId();
		}

		return parent::orderChilds($newOrder);
	}
	
	/**
	*Return the number of shown Items of the block
	*@param int Id of the Block.
	*@return int 
	*/
	
	function getShownItems($testRun) {
		$answers = array();
		$answers = $testRun->getGivenAnswerSetsByBlockId($this->id);
		$num = count($answers);
		return $num;
	}
	



	function getNeedsAllConditions()
	{
		return $this->_getField('conditions_need_all') ? TRUE : FALSE;
	}

	

	
	/*
	Set flag for default item conditions in the block 
	*/
	
	function setDefaultConditions($default)
	{
		$id = $this->getId();

		$query = 'UPDATE '.DB_PREFIX.'item_blocks SET default_conditions = ? WHERE id = ?';
		$this->db->query($query, array($default,$id));
	}
	
	/*
	If ItemBlock has default conditions
	*/
	
	function hasDefaultConditions()
	{
		$id = $this->getId();
		$query = 'SELECT  default_conditions FROM '.DB_PREFIX.'item_blocks WHERE id = ?';
		$value = $this->db->getOne($query, array($id));
		return intval($value);
	}
	
	/**
	 * @access private
	 **/
	function _getConditionsByQuery($query, $completeOnly = FALSE)
	{
		$conditions = array();
		
		while ($condition = $query->fetchRow())
		{
			if ($condition['item_block_id'] === NULL && $condition['item_id'] === NULL && $condition['answer_id'] === NULL) continue;

			
			// Skip conditions that refer to non-existing objects
			$id = $condition["id"];
			if ($condition["item_block_id"] !== NULL) {
				if (! $block = @$GLOBALS["BLOCK_LIST"]->getBlockById($condition["item_block_id"])) { 
				$sql = "DELETE FROM ".DB_PREFIX."block_conditions WHERE id = $id";
				$this->db->query($sql);
				continue; 
				}
				if ($condition["item_id"] !== NULL) {
					if (! $item = @$block->getTreeChildById($condition["item_id"])) { 
					$sql = "DELETE FROM ".DB_PREFIX."block_conditions WHERE id = $id";
					$this->db->query($sql);
					continue; }
					if ($condition["answer_id"] !== NULL) {
						if (!@$item->getChildById($condition["answer_id"])) { 
						$sql = "DELETE FROM ".DB_PREFIX."block_conditions WHERE id = $id";
						$this->db->query($sql);
						continue; }
					}
				}
 			}

			unset($condition["position"]);
			unset($condition["parent_item_id"]);
			// If chosen is set, the condition is considered complete
			if ($condition["chosen"] !== NULL) {
				$condition["chosen"] = $condition["chosen"] ? TRUE : FALSE;
			} elseif ($completeOnly) {
				continue;
			}
			$conditions[] = $condition;
		}

		return $conditions;
	}

	function getConditions($completeOnly = FALSE)
	{
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."block_conditions WHERE parent_item_id=? ORDER BY pos", $this->getId());
		return $this->_getConditionsByQuery($query, $completeOnly);
	}

	function getConditionsOnSiblings($completeOnly = FALSE)
	{
		$parent = $this->getParent();
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."block_conditions WHERE parent_item_id=? AND item_block_id=? ORDER BY pos", array($this->getId(), $parent->getId()));
		return $this->_getConditionsByQuery($query, $completeOnly);
	}

	function addCondition($condition, $position)
	{
		$condition["id"] = $this->db->nextId(DB_PREFIX.'item_conditions');

		$condition["parent_item_id"] = $this->getId();
		$condition["pos"] = $position;
		if ($condition["chosen"] !== NULL) {
			$condition["chosen"] = $condition["chosen"] ? 1 : 0;
		}
		$sql = "INSERT INTO ".DB_PREFIX."block_conditions (".implode(",", array_keys($condition)).") VALUES (".implode(", ", array_fill(0, count($condition), "?")).")";
		$values = array_values($condition);
		$this->db->query($sql, $values);
	}

	function updateRestriction($restriction)
	{
		$this->db->query("UPDATE ".DB_PREFIX."items SET restriction=? WHERE id=? LIMIT 1", array($restriction, $this->getId()));
	}

	function updateCondition($condition, $position)
	{
		$id = $condition["id"];
		unset($condition["id"]);

		$condition["parent_item_id"] = $this->getId();
		$condition["pos"] = $position;
		if ($condition["chosen"] !== NULL) {
			$condition["chosen"] = $condition["chosen"] ? 1 : 0;
		}
		$sql = "UPDATE ".DB_PREFIX."block_conditions SET ";
		$first = TRUE;
		foreach ($condition as $name => $value) {
			if (! $first) {
				$sql .= ", ";
			} else {
				$first = FALSE;
			}
			$sql .= $name."=?";
		}
		$sql .= " WHERE id=?";
		$values = array_merge(array_values($condition), array($id));
		$this->db->query($sql, $values);
	}

	function deleteConditions($ids)
	{
		if (! $ids) { return; }
		$sql = "DELETE FROM ".DB_PREFIX."block_conditions WHERE id IN (".implode(", ", array_fill(0, count($ids), "?")).")";
		$this->db->query($sql, $ids);
	}

	function deleteAllConditions()
	{
		$this->db->query("DELETE FROM ".DB_PREFIX."block_conditions WHERE parent_item_id=?", array($this->getId()));
	}

	function setNeedsAllConditions($needsAll)
	{
		return $this->modify(array('conditions_need_all' => $needsAll ? 1 : 0));
	}

	function fullfillsConditions($testRun)
	{
		// Get all complete conditions
		if (! $conditions = $this->getConditions(TRUE)) {
			return TRUE;
		}

		$needsAll = $this->getNeedsAllConditions();
		// If all are needed, one is enough to fail -> default to TRUE
		// If just one is needed, one is enough to pass -> default to FALSE
		$fullfillsConditions = $needsAll;

		foreach ($conditions as $condition)
		{
			$fullfillsCurrent = !$condition["chosen"];

			// Look at the answer id to determine whether the condition is fullfilled.
			// If none is found, consider this condition unfullfilled.
			if ($answerSet = $testRun->getGivenAnswerSetByItemId($condition["item_id"]))
			{
				foreach ($answerSet->getAnswers() as $answerKey => $answer) {
					if (($answerKey == $condition["answer_id"]) && $answer) {
						$fullfillsCurrent = $condition["chosen"];
						break;
					}
				}
			}

			// We only need one: we're happy
			if ($fullfillsCurrent && ! $needsAll) {
				$fullfillsConditions = TRUE;
				break;
			}
			// All have to be fullfilled, you lose
			elseif (! $fullfillsCurrent && $needsAll) {
				$fullfillsConditions = FALSE;
				break;
			}
		}

		return $fullfillsConditions;
	}
}

?>
