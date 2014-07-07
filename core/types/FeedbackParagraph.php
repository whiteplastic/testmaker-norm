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
 * Include the FeedbackPage class
 */
require_once(dirname(__FILE__).'/FeedbackBlock.php');

/**
 * Include the FeedbackGenerator class
 */
require_once(dirname(__FILE__).'/FeedbackGenerator.php');

/**
 * Include the TreeNode class
 */
require_once(dirname(__FILE__).'/TreeNode.php');

/**
 * A feedback paragraph contains text with an optional set of conditions on
 * when it is displayed in feedbacks.
 *
 * @package Core
 */
class FeedbackParagraph extends TreeNode
{
	var $id;
	var $content;

	/**
	 * Returns an array of all paragraphs in the page with the given ID.
	 * @static
	 */
	function getAllByPage($id)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$ids = $db->getAll("SELECT id FROM ".DB_PREFIX."feedback_paragraphs WHERE page_id = ? ORDER BY pos, id", array($id));
		if ($db->isError($ids)) {
			return array();
		}
		$paragraphs = array();
		foreach ($ids as $paraId) {
			$paragraphs[] = new FeedbackParagraph($paraId['id']);
		}
		return $paragraphs;
	}

	/**
	 * Pulls a feedback paragraph from the database.
	 */
	function FeedbackParagraph($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->table = DB_PREFIX.'feedback_paragraphs';
		$this->sequence = DB_PREFIX.'feedback_paragraphs';
		$this->parentConnector = 'page_id';

		$this->TreeNode($id);

		// Get conditions
		$page = $this->getParent();
		$block = $page->getParent();
		$dims = DataObject::getBy('Dimension','getAllByBlockId',$block->getId());
		$dimIds = array();
		foreach ($dims as $dim) {
			$dimIds[(int) $dim->get('id')] = 1;
		}
		$this->conditions = array();
		$conditions = $this->db->getAll("SELECT * FROM ".DB_PREFIX."feedback_conditions WHERE paragraph_id = ?", array($this->id));
		if (!$this->db->isError($conditions)) {
			$conds = array();
			foreach ($conditions as $c) {
				if (!isset($dimIds[(int) $c['dimension_id']])) continue;
				$conds[$c['dimension_id']] = array(
					'min_value' => $c['min_value'],
					'max_value' => $c['max_value'],
				);
			}
			$this->conditions = $conds;
		}
	}

	/**
	 * Returns a copy of the current node
	 * @param integer target parent node id
	 * @param mixed[] Associative array of changed IDs, mapping node type (as name) to
	 *   an associative array that in turn maps old ID to new ID.
	 * @param integer Predefined new ID
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds, $newNodeId = NULL)
	{
		//update tags
		$paragraph = parent::copyNode($parentId, $changedIds, $newNodeId);
		$content = $this->getContents();
		$newContent = FeedbackGenerator::expandText($content, array($this, '_modifyTagForCopy'), array($changedIds));

		//modify media pathes
		$fileHandling = new FileHandling();
		$mediaPath = str_replace(ROOT, '', $fileHandling->getFileDirectory()).'media/';
		$newParent = new FeedbackPage($parentId);
		$mediaConnectId = $newParent->getMediaConnectId();
		$modulo = $mediaConnectId % 100;
		$newContent = preg_replace('/'.preg_quote($mediaPath, '/').'[0-9]+\/[0-9]+\/[0-9]+_/', $mediaPath.$modulo."/".$mediaConnectId."/".$mediaConnectId.'_', $newContent);

		$paragraph->modify(array('content' => $newContent));

		// update display conditions
		$conditions = array();
		foreach ($this->getConditions() as $condition) {
			$plugin = Plugin::load('extconds', $condition['type']);
			$conditions[] = $plugin->modifyForCopy($condition, $changedIds);
		}
		$paragraph->setConditions($conditions);

		return $paragraph;
	}
	
	function _modifyTagForCopy($type, $params, $changedIds)
	{
		$obj = Plugin::load('feedback', $type, array(isset($this) ? $this : NULL));
		if (!$obj) return '???';

		$newParams = $obj->modifyForCopy($params, $changedIds);
		$result = "<feedback";
		foreach ($newParams as $key => $value) {
			$result .= ' _fb_'. $key .'="'. $value .'"';
		}
		return $result .' />';
	}

	/**
	 * @access private
	 */
	function _returnParent($id)
	{
		return new FeedbackPage($id);
	}

	/**
	 * Returns an array of conditions on displaying this paragraph.
	 */
	function getConditions()
	{
		$extconds = explode("\n", $this->_getField('ext_conditions'));
		$result = array();
		foreach ($extconds as $cond) {
			if (!$cond) continue;
			$result[] = unserialize(urldecode($cond));
		}

		if (isset($this->conditions))  {
			foreach ($this->conditions as $dimId => $cond) {
				$result[] = array(
					'type'		=> 'interval',
					'dim_id'	=> $dimId,
					'min_value'	=> ($cond['min_value'] / 1000.0),
					'max_value'	=> ($cond['max_value'] / 1000.0),
				);
			}
		}
		return $result;
	}

	/**
	 * Checks if all display conditions are fulfilled.
	 *
	 * @param Array Associative array mapping dimension IDs to their scores.
	 * @return boolean
	 */
	function checkConditions($generator)
	{
		// Check extended conditions too
		foreach ($this->getConditions() as $cond) {
			$obj = Plugin::load('extconds', $cond['type']);
			if (!$obj) continue;
			if (!$obj->checkCondition($cond, $generator)) return false;
		}
		return true;
	}

	/**
	 * Sets the conditions on displaying this paragraph, passed in an array
	 * similar to that used in getConditions().
	 *
	 * @param mixed[] The array.
	 */
	function setConditions($conditions)
	{
		$data = '';
		foreach ($conditions as $cond) {
			$data .= urlencode(serialize($cond)) ."\n";
		}
		$res = $this->db->query("UPDATE ".DB_PREFIX."feedback_paragraphs SET ext_conditions = ? WHERE id = ?", array($data, $this->id));
		return !($this->db->isError($res));
	}

	/**
	 * Removes all conditions on this paragraph.
	 * @return bool True if the operation succeeded.
	 */
	function removeConditions()
	{
		$res = $this->db->query('DELETE FROM '.DB_PREFIX.'feedback_conditions WHERE paragraph_id = ?', array($this->id));
		if (PEAR::isError($res)) {
			return false;
		}
		$res = $this->db->query('UPDATE '.DB_PREFIX."feedback_paragraphs SET ext_conditions = '' WHERE id = ?", array($this->id));
		if (PEAR::isError($res)) {
			return false;
		}
		return true;
	}

	/**
	 * Prepares this paragraph for deletion.
	 */
	function cleanUp()
	{
		$this->removeConditions();
		parent::cleanUp();
	}

	/**
	 * Returns the contents of the paragraph
	 * @return String
	 */
	function getContents()
	{
		return $this->_getField('content');
	}

	/**
	 * modify the given informations in the current page
	 * @param mixed[] $modification values to be modified
	 * @return bool
	 */
	function modify($modifications)
	{
		if(!is_array($modifications)) {
				trigger_error('<b>FeedbackParagraph::modify</b>: $modifications is no valid array');
		}

		$avalues = array();
		if(array_key_exists('content', $modifications)) {
			$avalues['content'] = $modifications['content'];
		}

		return $this->_modify($avalues);
	}

}

?>
