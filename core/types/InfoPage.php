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
 * Include the InfoBlock class
 */
require_once(dirname(__FILE__).'/InfoBlock.php');

/**
 * Include the Block class
 */
require_once(dirname(__FILE__).'/TreeNode.php');

/**
 * InfoPage class
 *
 * @package Core
 */

libLoad("utilities::shortenString");

class InfoPage extends TreeNode
{
	var $id;
	var $content;

	function InfoPage($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		$this->table = DB_PREFIX.'info_pages';
		$this->sequence = DB_PREFIX.'info_pages';
		$this->parentConnector = 'block_id';

		$this->TreeNode($id);
	}

	/**
	 * returns parent node with given id
	 * @return InfoBlock
	 */
	function _returnParent($id)
	{
		return $GLOBALS["BLOCK_LIST"]->getBlockById($id, BLOCK_TYPE_INFO);
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
	 * returns the title of current info page or if not set a short part of the content
	 * @return String
	 */
	function getTitle($shorten = true)
	{
		$query = 'SELECT title FROM '.$this->table.' WHERE id = ?';
		$field = $this->db->getOne($query, array($this->id));
		if($this->db->isError($field)) {
			return false;
		}
		if($shorten && (strlen(trim($field)) == 0)) {
			$field = shortenString(strip_tags($this->getContent()), 25);
		}

		return $field;
	}

	/**
	 * returns the content of the current page
	 * @return String
	 */
	function getContent()
	{
		return $this->_getField('content');
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
	 * Returns a copy of the current node
	 * @param integer target parent node id
	 * @param mixed[] 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
	 * @param integer predefined new node id
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds, $newNodeId = NULL)
	{
		$node = parent::copyNode($parentId, $changedIds, $newNodeId = NULL);
		
		//modify media pathes
		if(isset($node)) {
			$content = $this->getContent();
			$fileHandling = new FileHandling();
			$mediaPath = str_replace(ROOT, '', $fileHandling->getFileDirectory()).'media/';
			$newParent = new InfoBlock($parentId);
			$mediaConnectId = $newParent->getMediaConnectId();
			$modulo = $mediaConnectId % 100;
			$content = preg_replace('/'.preg_quote($mediaPath, '/').'[0-9]+\/[0-9]+\/[0-9]+_/', $mediaPath.$modulo."/".$mediaConnectId."/".$mediaConnectId.'_', $content);
			$node->modify(array('content' => $content));
		}
		
		return $node;
	}

	/**
	 * modify the given informations in the current page
	 * @param mixed[] $modification values to be modified
	 * @return bool
	 */
	function modify($modifications)
	{
		if(!is_array($modifications)) {
				trigger_error('<b>InfoPage::modify</b>: $modifications is no valid array');
		}

		$avalues = array();
		if(array_key_exists('content', $modifications)) {
			$avalues['content'] = $modifications['content'];
		}
		if(array_key_exists('title', $modifications)) {
			$avalues['title'] = $modifications['title'];
		}
		if(array_key_exists('pos', $modifications)) {
			$avalues['pos'] = $modifications['pos'];
		}
		if(array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}
		if(array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : 0;
		}

		return $this->_modify($avalues);
	}
	
	function getNeedsAllConditions()
	{
		return $this->_getField('conditions_need_all') ? TRUE : FALSE;
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
				$sql = "DELETE FROM ".DB_PREFIX."info_conditions WHERE id = $id";
				$this->db->query($sql);
				continue; 
				}
				if ($condition["item_id"] !== NULL) {
					if (! $item = @$block->getTreeChildById($condition["item_id"])) { 
					$sql = "DELETE FROM ".DB_PREFIX."info_conditions WHERE id = $id";
					$this->db->query($sql);
					continue; }
					if ($condition["answer_id"] !== NULL) {
						if (!@$item->getChildById($condition["answer_id"])) { 
						$sql = "DELETE FROM ".DB_PREFIX."info_conditions WHERE id = $id";
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
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."info_conditions WHERE parent_item_id=? ORDER BY pos", $this->getId());
		return $this->_getConditionsByQuery($query, $completeOnly);
	}

	function getConditionsOnSiblings($completeOnly = FALSE)
	{
		$parent = $this->getParent();
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."info_conditions WHERE parent_item_id=? AND item_block_id=? ORDER BY pos", array($this->getId(), $parent->getId()));
		return $this->_getConditionsByQuery($query, $completeOnly);
	}

	function addCondition($condition, $position)
	{
		$condition["id"] = $this->db->nextId(DB_PREFIX.'info_conditions');

		$condition["parent_item_id"] = $this->getId();
		$condition["pos"] = $position;
		if ($condition["chosen"] !== NULL) {
			$condition["chosen"] = $condition["chosen"] ? 1 : 0;
		}
		$sql = "INSERT INTO ".DB_PREFIX."info_conditions (".implode(",", array_keys($condition)).") VALUES (".implode(", ", array_fill(0, count($condition), "?")).")";
		$values = array_values($condition);
		$this->db->query($sql, $values);
		
	}
	
	function deleteConditions($ids)
	{
		if (! $ids) { return; }
		$sql = "DELETE FROM ".DB_PREFIX."info_conditions WHERE id IN (".implode(", ", array_fill(0, count($ids), "?")).")";
		$this->db->query($sql, $ids);
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
		$sql = "UPDATE ".DB_PREFIX."info_conditions SET ";
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
	
	
	// Returns an infoPage of its real type
	static function getInfoPage($id)
	{
		$infoPage = new InfoPage($id);
		if (!$infoPage) return null;
		return $infoPage;
	}
}

?>
