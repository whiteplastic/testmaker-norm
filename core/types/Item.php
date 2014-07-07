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


define('MULTI_BLOCK', 0);
define('ADAPTIVE_BLOCK', 1);

/**
 * @package Core
 */

/**
 * Include the ParentTreeNode class
 */
require_once(dirname(__FILE__).'/ParentTreeNode.php');

/**
 * Include the ItemAnswer class
 */
require_once(dirname(__FILE__).'/ItemAnswer.php');

/**
 * Include the ItemBlock class
 */
require_once(dirname(__FILE__).'/ItemBlock.php');

/**
 * Include the Dimension class
 */
require_once(dirname(__FILE__).'/Dimension.php');

/**
 * Include the FileHandling class
 */
require_once(dirname(__FILE__).'/FileHandling.php');

/**
 * The class for item objects
 *
 * @package Core
 */
class Item extends ParentTreeNode
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'Item';
	var $templateFile = 'no_template.html';
	var $allowedInBlock = array(MULTI_BLOCK, ADAPTIVE_BLOCK);
	
	var $mediaFiles = '';

	function Item($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->table = DB_PREFIX.'items';
		$this->sequence = DB_PREFIX.'items';
		$this->childrenTable = DB_PREFIX.'item_answers';
		$this->childrenConnector = 'item_id';
		$this->childrenSequence = DB_PREFIX.'item_answers';
		$this->parentConnector = 'block_id';
		$this->ParentTreeNode($id);
	}

	// Returns an item of its real type
	static function getItem($id)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$sql = "SELECT type FROM " . DB_PREFIX . "items WHERE id=?";
		if(!$class = $db->getOne($sql, array($id))) $class = "McsaItem";
		require_once(ROOT.'upload/items/' . $class . '.php');
		$item = new $class($id);
		if (!$item) return null;
		return $item;
	}
	
	/**
	 * returns child node with given id
	 * @return ItemAnswer
	 */
	function _returnChild($id) {
		return new ItemAnswer($id);
	}

	/**
	 * returns parent node with given id
	 * @return ItemBlock
	 */
	function _returnParent($id)
	{
		return $GLOBALS["BLOCK_LIST"]->getBlockById($id, BLOCK_TYPE_ITEM);
	}

	/**
	 * returns the current question
	 * @return String
	 */
	function getQuestion()
	{
		return $this->_getField('question');
	}

	function getRestriction()
	{
		return $this->_getField('restriction');
	}

	function getAdditionalActions()
	{
		return $this->additionalActions;
	}

	/**
	 * Returns the title of current item
	 * @param boolean Whether to return shortened item if title is empty
	 * @param boolean Whether to HTML-escape the title (shortened item is
	 *   not escaped)
	 * @return String
	 */
	function getTitle($shorten = true, $escape = false)
	{
		$field = $this->_getField('title');
		if ($escape) {
			$field = htmlentities($field);
		}
		if($shorten && (strlen(trim($field)) == 0)) {
			$field = shortenString(strip_tags($this->getQuestion()), 25);
		}

		return $field;
	}

	/**
	 * Returns the type (class name) of this object
	 * @return String Class name of this objects class
	 */
	function getType() {
		return get_class($this);
	}

	/**
	 * returns the template
	 * @return string
	 */
	function getTemplate()
	{
		return $this->templateFile;
	}

	/**
	 * returns the effective template, taking into account the default template of the parent
	 * @return int
	 */
	function getEffectiveTemplate()
	{
		return $this->getTemplate();
	}

	/**
	 * returns the current item number of answer rows for the template
	 * @return int
	 */
	function getTemplateCols()
	{
		return $this->_getField('template_cols');
	}

	/**
	 * returns the current item align for the template
	 * @return char
	 */
	function getTemplateAlign()
	{
		return $this->_getField('template_align');
	}

	/**
	 * returns the current item difficulty
	 * @return float
	 */
	function getDifficulty()
	{
		return $this->_getField('difficulty');
	}

	/**
	 * returns the current item discrimination
	 * @return float
	 */
	function getDiscrimination()
	{
		return $this->_getField('discrimination');
	}


	/**
	 * returns the current item guessing chance
	 * @return float
	 */
	function getGuessing()
	{
		return $this->_getField('guessing');
	}

	/**
	 * returns minimal time for current item
	 * @return int
	 */
	function getMinTime()
	{
		return $this->_getField('min_time');
	}

	/**
	 * returns owner of upper block
	 * @return User
	 */
	function getOwner()
	{
		$parent = $this->getParent();
		return $parent->getOwner();
	}

	/**
	 * returns the media connect id
	 * @return int
	 */
	function getMediaConnectId()
	{
		$parent = $this->getParent();
		return $parent->getMediaConnectId();
	}
	
	/**
	 * returns the media files
	 * @return string
	 */
	function getMediaFiles($string)
	{
		$pos = strpos($string, "src=");
		$files = '';
		if ($pos != false) {
			while (strpos($string, "src=") != false) {
				$string = strstr($string, "src=");
				$pos = strpos($string, ".");			
				$files = $files.' '.substr($string, 18, $pos-14);
			}
			return $files;
		}
		else
			return false;
	}
	/**
	 * Returns a copy of the current node
	 * @param integer target parent node id
	 * @param mixed[] 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
	 * @param integer predefined new node id
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds, $newNodeId = NULL)
	{
		$node = parent::copyNode($parentId, $changedIds, $newNodeId);

		//modify media pathes
		if(isset($node)) {
			$changedIds['items'][$this->id] = $node->getId();
			$content = $node->getQuestion();
			$fileHandling = new FileHandling();
			$mediaPath = str_replace(ROOT, '', $fileHandling->getFileDirectory()).'media/';
			$newParent = new ItemBlock($parentId);
			$mediaConnectId = $newParent->getMediaConnectId();
			$modulo = $mediaConnectId % 100;
			$content = preg_replace('/'.preg_quote($mediaPath, '/').'[0-9]+\/[0-9]+\/[0-9]+_/', $mediaPath.$modulo."/".$mediaConnectId."/".$mediaConnectId.'_', $content);
			$node->modify(array('question' => $content));
			foreach ($this->getConditions() as $condition)
			{
				if (isset($changedIds["blocks"][$condition["item_block_id"]])) {
					$condition["item_block_id"] = $changedIds["blocks"][$condition["item_block_id"]];
					$condition["item_id"] = $changedIds["items"][$condition["item_id"]];
					$condition["answer_id"] = $changedIds["item_answers"][$condition["answer_id"]];
				}
				$node->addCondition($condition, $condition["pos"]);
			}
		}
		return $node;
	}

	/**
	 * returns maximal time for current item
	 * @return int
	 */
	function getMaxTime()
	{
		return $this->_getField('max_time');
	}

	/**
	 * Returns if the item should force an answer (always true for adaptive items)
	 * @return bool
	 */
	function isForced()
	{
		if ($this->_getField('answer_force') != 1) {
			$parent = $this->getParent();
			return $parent->isItemBlock() && $parent->isAdaptiveItemBlock();
		}
		return true;
	}

	/*
	 * Returns whether the item is linked
	 * @return bool
	 */
	function isLinked()
	{
		$res = $this->db->query("SELECT * FROM ".DB_PREFIX."item_conditions WHERE parent_item_id=?", $this->getId());
		$linked = false;
		if ($res) {
			$row = $res->fetchRow();
			$linked = ($row["item_block_id"] != NULL && $row["item_id"] != NULL && $row	["answer_id"] != NULL);

		}
		return $linked;
	}

	function createChild($infos)
	{
		$avalues = array();
		if(array_key_exists('pos', $infos)) {
			$avalues['pos'] = $infos['pos'];
		}
		if(array_key_exists('answer', $infos)) {
			$avalues['answer'] = $infos['answer'];
		}
		if(array_key_exists('correct', $infos)) {
			$avalues['correct'] = $infos['correct'];
		}
		if(array_key_exists('media_connect_id', $infos)) {
			$avalues['media_connect_id'] = $infos['media_connect_id'];
		}
		return $this->_createChild($avalues);
	}

	function cleanUp()
	{
		$this->deleteAllConditions();
		parent::cleanUp();
	}

	/**
	 * Checks whether this item can be deleted without violating integrity.
	 * @return mixed If deletion would violate integrity, an object that still uses this item; NULL otherwise.
	 */
	function getDeletionIntegrity()
	{
		$res = $this->db->query("SELECT dc.dimension_id FROM ".DB_PREFIX."item_answers AS a
			JOIN ".DB_PREFIX."dimensions_connect AS dc ON (a.id = dc.item_answer_id)
			WHERE a.item_id = ?", $this->getId());
		
		if ($res) {
			$row = $res->fetchRow();
			if ($row) {
				foreach($row as $value) {
					$dim = DataObject::getById('Dimension', $value);
					$parentBlock = $dim->getParent();
					if (($parentBlock->getDisabled() == false))
						return $dim;
				}
			}
		}
	}

	/**
	 * prepare given array with data for database insertion
	 * @param array associative array with database fields and content
	 * @return array associative array with database fields and content
	 */
	function prepareDBData($data)
	{
		if (!is_array($data))
		{
			trigger_error('<b>Item:prepareDBData</b>: $data is no valid array');
		}

		$data2 = array();
		if (array_key_exists('type', $data))
		{
			$data2['type'] = $data['type'];
		}
		if (array_key_exists('template_cols', $data))
		{
			$data2['template_cols'] = $data['template_cols'];
		}
		if (array_key_exists('template_align', $data))
		{
			$data2['template_align'] = $data['template_align'];
		}
		if (!empty($data['max_time']))
		{
			$data2['max_time'] = $data['max_time'];
		}
		else
		{
			$data2['max_time'] = $this->getDefaultMaxItemTime();
		}
		if (!empty($data['min_time']))
		{
			$data2['min_time'] = $data['min_time'];
		}
		else
		{
			$data2['min_time'] = $this->getDefaultMinItemTime();
		}
		if (array_key_exists('answer_force', $data))
		{
			$data2['answer_force'] = $data['answer_force'];
		}
		else
		{
			$data2['answer_force'] = $this->isDefaultItemForced();
		}
		if (array_key_exists('pos', $data))
		{
			$data2['pos'] = $data['pos'];
		}
		if (array_key_exists('title', $data))
		{
			$data2['title'] = $data['title'];
		}
		if (array_key_exists('question', $data))
		{
			$data2['question'] = $data['question'];
		}
		if (array_key_exists('difficulty', $data))
		{
			$data2['difficulty'] = $data['difficulty'];
		}
		if (array_key_exists('discrimination', $data))
		{
			$data2['discrimination'] = $data['discrimination'];
		}
		if (array_key_exists('guessing', $data))
		{
			$data2['guessing'] = $data['guessing'];
		}
		if (array_key_exists('conditions_need_all', $data))
		{
			$data2['conditions_need_all'] = $data['conditions_need_all'];
		}

		return $data2;
	}

	/**
	 * modify the given informations in the current node
	 * @param mixed[] $modification values to be modified
	 * @return bool
	 */
	function modify($modifications)
	{
		$avalues = array();
		if(array_key_exists('pos', $modifications)) {
			$avalues['pos'] = $modifications['pos'];
		}
		if(array_key_exists('title', $modifications)) {
			$avalues['title'] = $modifications['title'];
		}
		if(array_key_exists('question', $modifications)) {
			$this->checkPluginRequirements($modifications['question']);
			$avalues['question'] = $modifications['question'];
		}
		if(array_key_exists('type', $modifications)) {
			$avalues['type'] = $modifications['type'];
		}
		if(array_key_exists('template_cols', $modifications)) {
			$avalues['template_cols'] = $modifications['template_cols'];
		}
		if(array_key_exists('template_align', $modifications)) {
			$avalues['template_align'] = $modifications['template_align'];
		}
		if(array_key_exists('difficulty', $modifications)) {
			$avalues['difficulty'] = $modifications['difficulty'];
		}
		if(array_key_exists('guessing', $modifications)) {
			$avalues['guessing'] = $modifications['guessing'];
		}
		if(array_key_exists('discrimination', $modifications)) {
			$avalues['discrimination'] = $modifications['discrimination'];
		}
		if(array_key_exists('min_time', $modifications)) {
			$avalues['min_time'] = $modifications['min_time'];
		}
		if(array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}
		if(array_key_exists('max_time', $modifications)) {
			$avalues['max_time'] = $modifications['max_time'];
		}
		if(array_key_exists('answer_force', $modifications)) {
			$avalues['answer_force'] = $modifications['answer_force'];
		}
		if(array_key_exists('conditions_need_all', $modifications)) {
			$avalues['conditions_need_all'] = $modifications['conditions_need_all'];
		}
		if(array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : NULL;
		}

		return $this->_modify($avalues);
	}

	/*
	 * Search for keywords that refer to a something thats related to content which requires a plugin (e.g. Flash).
	 */
	function checkPluginRequirements($itemQuestion)
	{
		$embedRegex = '/<embed[^>]*/';
		$mimeRegex = '/type=\"([a-zA-Z\-\/]+)\"/';
		$requiredTypes = array();
		preg_match_all($embedRegex, $itemQuestion, $embed);
		if(count($embed[0]) > 0)
		{
			foreach($embed[0] as $embedString)
			{
				if(preg_match($mimeRegex, $embedString, $hits))
				{
					$requiredTypes[] = $hits[1];
				}
			}
			$modifications = array('required_plugins' => implode(";", $requiredTypes));
			$this->_modify($modifications);
		}
	}

	/*
	 * Returns an array containing the currently necessary plugins for this item (as mime type string)
	 */
	function getPluginRequirements()
	{
		$plugins = array();
		$value = $this->_getField('required_plugins');
		if($value != "") 
		{
			$plugins = explode(';', $value);
		}
		return $plugins;
	}

	/**
	 * order childs
	 *
	 * @param mixed new itemorder
	 * @return mixed
	 */
	function orderChilds($order)
	{
		$errors = array();
		$ids = array();

		if (count($order) != count($this->getChildren()))
		{
			return;
		}
		foreach ($order as $id)
		{
			if (!$this->existsChild($id))
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
			$child = $this->getChildById($id);
			$errors[] = $child->modify(array('pos' => ++$position));
		}
		return $errors;
	}

	function interpretateAnswers($answers_array)
	{
		trigger_error('Must be override by Item');
	}

	/**
	 * Compare a given answer and the correct answer for this item
	 */
	function evaluateAnswer($answer)
	{
		trigger_error('Must be override by Item');
	}

	/**
	 * Parse the appropriated templatefile
	 */
	function parseTemplate($key, $items_count)
	{
		$this->_initTemplate();

		return $this->qtpl->get();
	}

	function _initTemplate()
	{
		$templateDir = ROOT.'upload/items/';
		$this->qtpl = new Sigma($templateDir);
		$this->qtpl->setCallbackFunction("T", "T");
		$this->qtpl->loadTemplateFile($this->templateFile);
		$this->qtpl->setGlobalVariable("item_id", $this->getId());
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
				$sql = "DELETE FROM ".DB_PREFIX."item_conditions WHERE id = $id";
				$this->db->query($sql);
				continue; 
				}
				if ($condition["item_id"] !== NULL) {
					if (! $item = @$block->getTreeChildById($condition["item_id"])) { 
					$sql = "DELETE FROM ".DB_PREFIX."item_conditions WHERE id = $id";
					$this->db->query($sql);
					continue; }
					if ($condition["answer_id"] !== NULL) {
						if (!@$item->getChildById($condition["answer_id"])) { 
						$sql = "DELETE FROM ".DB_PREFIX."item_conditions WHERE id = $id";
						$this->db->query($sql);
						continue; }
					}
				}
 			}

			unset($condition["position"]);
			//unset($condition["parent_item_id"]);
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
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."item_conditions WHERE parent_item_id=? ORDER BY pos", $this->getId());
		return $this->_getConditionsByQuery($query, $completeOnly);
	}

	function getConditionsOnSiblings($completeOnly = FALSE)
	{
		$parent = $this->getParent();
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."item_conditions WHERE parent_item_id=? AND item_block_id=? ORDER BY pos", array($this->getId(), $parent->getId()));
		return $this->_getConditionsByQuery($query, $completeOnly);
	}
	
	function getConditionDependencies($completeOnly = FALSE)
	{
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."item_conditions WHERE item_id=? ORDER BY parent_item_id", $this->getId());
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
		$sql = "INSERT INTO ".DB_PREFIX."item_conditions (".implode(",", array_keys($condition)).") VALUES (".implode(", ", array_fill(0, count($condition), "?")).")";
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
		$sql = "UPDATE ".DB_PREFIX."item_conditions SET ";
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
		$sql = "DELETE FROM ".DB_PREFIX."item_conditions WHERE id IN (".implode(", ", array_fill(0, count($ids), "?")).")";
		$this->db->query($sql, $ids);
	}

	function deleteAllConditions()
	{
		$this->db->query("DELETE FROM ".DB_PREFIX."item_conditions WHERE parent_item_id=?", array($this->getId()));
	}

	function getNeedsAllConditions()
	{
		return $this->_getField('conditions_need_all') ? TRUE : FALSE;
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
	
	function verifyCorrectness($givenAnswerSet)
	{
		$answerTimeout = $givenAnswerSet->hadTimeout();
		$wasSkipped = $givenAnswerSet->getWasSkipped();

		$correct = $this->evaluateAnswer($givenAnswerSet);
		if (($wasSkipped))  {
			    $correct = ANSWER_SKIPPED;
			}

		if ($answerTimeout && !$correct && ($correct != '0')) {
			    $correct = ANSWER_TIMEOUT;
			}

		return $correct;
	}

	/**
	 * Determines whether the item class provides its own scoring for feedback
	 * dimensions. Should be overwritten by descendants if desired.
	 *
	 * Classes that implement custom scoring should provide a method
	 * <kbd>getCustomScore</kbd> that accepts one parameter containing a
	 * GivenAnswerSet and returns a score value. Additionally, the default
	 * method <kbd>preinitCustomScore</kbd> should be overwritten if so
	 * desired.
	 * @return boolean
	 */
	function hasCustomScoring()
	{
		return false;
	}

	/**
	 * Determines whether the item class implies a simple type of answers so
	 * that it can be used as a display condition for other items (and
	 * possibly other things).
	 *
	 * Classes that imply more complex answers should overwrite this method to
	 * return false.
	 * @return boolean
	 */
	function hasSimpleAnswer()
	{
		return true;
	}

	/**
	 * Initializes an item of a custom scoring type so that it can be used in dimensions.
	 */
	function preinitCustomScore()
	{
		if ($this->getChildren()) return;
		$this->createChild(array(
			'pos' => 0,
			'answer' => '',
			'correct' => 0,
		));
	}
	
	/**
	*compares then possible anwers of two items and return true if they are exactly the same
	*/
	static function compareAnswers($item1, $item2) {
		$equal = true; 
		
		$answers1 = $item1->getChildren();
		$answers2 = $item2->getChildren();
		
		if (count($answers1) != count($answers2)) {
			return false;
		}
		for ($i = 0; $i < count($answers1); $i++)
		{
			if ($answers1[$i]->getTitle() != $answers2[$i]->getTitle()) {
				$equal = false;
			}
		}
		return $equal;
	}
}

?>
