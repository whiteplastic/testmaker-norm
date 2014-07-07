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
require_once(dirname(__FILE__).'/FeedbackPage.php');

/**
 * Include the Dimension class
 */
require_once(dirname(__FILE__).'/Dimension.php');

/**
 * Include the DimensionGroup class
 */
require_once(dirname(__FILE__).'/DimensionGroup.php');

define('TM_FEEDBACK_HAS_ADAPTIVE', 1);
define('TM_FEEDBACK_ALL_ADAPTIVE', 2);

/**
 * A container for feedback data. Feedback consists of {@link Dimension}s and
 * {@link FeedbackPage}s.
 *
 * @package Core
 */
class FeedbackBlock extends ParentTreeBlock
{
	/**
	 * Pulls a feedback block from the database.
	 */
	function FeedbackBlock($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->table = DB_PREFIX.'feedback_blocks';
		$this->sequence = DB_PREFIX.'blocks';
		$this->childrenTable = DB_PREFIX.'feedback_pages';
		$this->ParentTreeBlock($id);
	}

	
	/**
	 * @access private
	 */
	function _returnTreeChild($id) {
		return new FeedbackPage($id);
	}

	/**
	 * Returns whether this feedback block should be included in the feedback
	 * summary (which is displayed when a test run is re-viewed later).
	 * @return boolean
	 */
	function getShowInSummary()
	{
		$res = $this->_getField('show_in_summary');
		return ($res === NULL || $res);
	}

	/**
	 * Returns the IDs of all item blocks associated with this feedback block.
	 * @return Array
	 */
	function getSourceIds()
	{
		if (!isset($this->sourceIds)) {
			$res = $this->db->getAll("SELECT item_block_id FROM ".DB_PREFIX."feedback_blocks_connect
				WHERE feedback_block_id = ?", array($this->id));
			if (PEAR::isError($res)) {
				return NULL;
			}
		}
		$res2 = array();
		foreach ($res as $row) {
			$res2[] = intval($row['item_block_id']);
		}
		return $res2;
	}

	/**
	 * Changes the list of item block IDs associated with this feedback block.
	 * @param Array The new list of IDs
	 * @return bool True on success.
	 */
	function setSourceIds($ids)
	{
		// First, get rid of old IDs
		$res = $this->db->query("DELETE FROM ".DB_PREFIX."feedback_blocks_connect
			WHERE feedback_block_id = ?", array($this->id));
		if (PEAR::isError($res)) {
			return false;
		}

		$bl = &$GLOBALS["BLOCK_LIST"];
		foreach ($ids as $id) {
			// Make sure source block exists, otherwise be evil and silently
			// ignore this ID
			if (!$bl->existsBlock($id)) continue;
			$blk = $bl->getBlockById($id);
			if (!$blk->isItemBlock()) continue;

			$res = $this->db->query("INSERT INTO ".DB_PREFIX."feedback_blocks_connect
				VALUES (?, ?)", array($id, $this->id));
			if (PEAR::isError($res)) {
				return false;
			}
		}

		return true;
	}

	function setShowInSummary($value)
	{
		$res = $this->db->query("UPDATE ".DB_PREFIX."feedback_blocks SET show_in_summary = ? WHERE id = ?", array($value, $this->id));
		if(PEAR::isError($res))
		{
			return false;
		} else return true;
	}

	/**
	 * Checks whether one of this block's sources is adaptive.
	 * @param boolean Whether to allow IRT-enabled blocks.
	 * @return boolean
	 */
	function hasAdaptiveSource($includeIRT = true)
	{
		$sources = $this->getSourceIds();
		if (count($sources) == 0) return false;

		$sourcesStr = '('. implode(', ', array_map('intval', $sources)) . ')';
		$query = "SELECT COUNT(*) FROM ".DB_PREFIX."item_blocks WHERE id IN $sourcesStr AND (type = 1";
		if ($includeIRT) $query .= " OR irt = 1";
		$query .= ')';
		$adapCount = $this->db->getOne($query);

		if ($adapCount > 0) return ($adapCount == count($sources) ? TM_FEEDBACK_ALL_ADAPTIVE : TM_FEEDBACK_HAS_ADAPTIVE);
	}

	/**
	 * Checks whether it makes sense to add a new dimension to this block.
	 * @return boolean
	 */
	function canAddDimension()
	{
		if (count($this->getSourceIds()) == 0) return false;
		if ($this->hasAdaptiveSource() == TM_FEEDBACK_ALL_ADAPTIVE) return false;
		return true;
	}

	/**
	 * Creates and returns a new feedback page
	 * @param mixed Associative array of optional information ('title' and
	 *   'pos')
	 * @return FeedbackPage
	 */
	function createTreeChild($infos = array())
	{
		$avalues = array();

		if(array_key_exists('title', $infos))
		{
			$avalues['title'] = $infos['title'];
		}
		if(array_key_exists('pos', $infos))
		{
			$avalues['pos'] = $infos['pos'];
		}

		return $this->_createTreeChild($avalues);
	}

	/**
	 * Modify the block's data
	 * @param mixed Associative array of values to be modified
	 * @return boolean
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
		if(array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}
		if(array_key_exists('show_in_summary', $modifications)) {
			$avalues['show_in_summary'] = $modifications['show_in_summary'] ? 1 : 0;
		}
		if(array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : 0;
		}

		return $this->_modify($avalues);
	}

	/**
	 * Removes dimensions prior to letting ParentTreeBlock deal with deleting
	 * our feedback pages.
	 */
	function cleanUp()
	{
		$dims = DataObject::getBy('Dimension','getAllByBlockId',$this->id);
		foreach ($dims as $dim) {
			$dim->delete();
		}
		$result = $this->db->query('DELETE FROM '.DB_PREFIX.'feedback_blocks_connect WHERE feedback_block_id = ?', array($this->id));
		if (PEAR::isError($result)) {
			return false;
		}

		parent::cleanUp();
	}

	/**
	 * Returns a copy of the current feedback block.
	 * @param ID of parent to copy block into
	 * @param array Where to store changed IDs from item blocks for feedback blocks
	 * @param array Where to store problems if the block would be copied
	 * @return FeedbackBlock
	 */
	function copyNode($parentId, &$changedIds, &$problems) {

		$newNodeId = $this->db->nextId($this->childrenSequence);
		
		//get dimension groups for this block
		$oldGroups = DataObject::getBy('DimensionGroup','getAllByBlockId',$this->id);

		// copy dimensions
		$dims = DataObject::getBy('Dimension', 'getAllByBlockId', $this->id);
		foreach($dims as $dim) {
			$dim->copyDimension($newNodeId, $changedIds);
		}


		// copy dimension groups
		foreach($oldGroups as $oldGroup) {
			$oldDims = $oldGroup->get('dimension_ids');
			$newDims = array();		
			foreach ($oldDims as $oldDim) {
				if (isset($changedIds['dimensions'][$oldDim]))
					$newDims[] = $changedIds['dimensions'][$oldDim];
			}	
			$newGroup = DataObject::create('DimensionGroup', array(
				'block_id' => $newNodeId,
				'title' => $oldGroup->getTitle(),
				'dimension_ids' => $newDims
			));
			$changedIds['dimension_groups'][$oldGroup->getId()] = $newGroup->get('id');
		}

							
		$block = parent::copyNode($parentId, $changedIds, $newNodeId, $problems);

		
		$feedbackpages = $block->getTreeChildren();
		
		foreach ($feedbackpages as $fbpage)
		{
			foreach ($fbpage->getParagraphs() as $fbparagraph)
			{
				$content = str_replace("\"", "&quot;", $fbparagraph->getContents());
				
				if(isset($changedIds["dimension_groups"])) {
					foreach ($changedIds["dimension_groups"] as $old_ID => $new_ID)
						$content = str_replace("dimgroup=&quot;".$old_ID."&quot;", "dimgroup=&quot;".$new_ID."&quot;", $content);
				}
				if(isset($changedIds["dimension_groups"])) {
					foreach ($changedIds["dimensions"] as $old_ID => $new_ID) {
						$content = str_replace("=&quot;".$old_ID."&quot;", "=&quot;".$new_ID."&quot;", $content);
						$content = str_replace("=&quot;".$old_ID.":", "=&quot;".$new_ID.":", $content); 
					}
				}

				$content = str_replace("&quot;", "\"", $content);
				$fbparagraph->modify(array('content' => $content));
			}
			
		}				
		
		
		if(count($problems) == 0)
		{
			
			//change source id to correct (copied) item block
			$newSources = array();
			$sources = $this->getSourceIds();
			foreach($sources as $source) {
				if(isset($changedIds['blocks'][$source])) {
					$newSources[] = $changedIds['blocks'][$source];
				} else {
					$newSources[] = $source;
				}
			}
			$block->setSourceIds($newSources);
		} else {
			//delete already created dimensions if copying fails
			DataObject::query('Dimension','deleteByBlockId',$newNodeId);
		}

		return $block;

	}

	/**
	 * Check if the certificate is enabled and the template file name is specified for this block
	 * @return true when the certfificate is enabled and the template file name was given, false otherwise
	 */
	function isCertEnabled()
	{	
		$cert = $this->db->getRow("SELECT cert_enabled, cert_template_name FROM {$this->table} WHERE id={$this->id}");
		return $cert['cert_enabled'] && ($cert['cert_template_name'] != "");
	}
	
	/**
	 * Acquire the item id's for the user data from test run for the certificate
	 */
	 
	function  acquireCertItemIds()
	{
		$itemIds = array();
		if ($this->isCertEnabled()) {
			$itemIds[] = $this->data['cert_fname_item_id'];
			$itemIds[] = $this->data['cert_lname_item_id'];
			$itemIds[] = $this->data['cert_bday_item_id'];
		}
		return $itemIds;
	}

	/**
	 * Acquire user data from test run for the certificate
	 */
	static function acquireCertData($testRunId, $blockId)
	{
		$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		require_once(CORE."types/TestRunList.php");
		$testRunList = new TestRunList();
		$currentTestRun = $testRunList->getTestRunById($testRunId);

		// Look up the necessary item ids
		$db = $GLOBALS['dao']->getConnection();
		$query = "SELECT cert_fname_item_id, cert_lname_item_id, cert_bday_item_id, cert_template_name, cert_disable_barcode FROM {$block->table} WHERE id = ?";
		$itemIds = $db->getRow($query, array($blockId));
		
		
		$userData = array();
		// Get the firstname
		$firstnameItemId = $itemIds['cert_fname_item_id'];
		$firstnameGivenAnswerSet = $currentTestRun->getGivenAnswerSetByItemId($firstnameItemId);
		if ($firstnameGivenAnswerSet == NULL)
			return False;
			
		$firstnameGivenAnswers = $firstnameGivenAnswerSet->getAnswers();
		reset($firstnameGivenAnswers);
		$answer = (count($firstnameGivenAnswers) > 0) ? current($firstnameGivenAnswers) : null;
		$userData['firstname'] =  $answer;
		

		// Get the lastname
		$lastnameItemId = $itemIds['cert_lname_item_id'];
		$lastnameGivenAnswerSet = $currentTestRun->getGivenAnswerSetByItemId($lastnameItemId);
		if ($lastnameGivenAnswerSet == NULL)
			return FALSE;
		$lastnameGivenAnswers = $lastnameGivenAnswerSet->getAnswers();
		reset($lastnameGivenAnswers);
		$answer = (count($lastnameGivenAnswers) > 0) ? current($lastnameGivenAnswers) : null;
		$userData['lastname'] = $answer;

		// Get the birthday date
		$birthdayItemId = $itemIds['cert_bday_item_id'];
		$birthdayGivenAnswerSet = $currentTestRun->getGivenAnswerSetByItemId($birthdayItemId);
		if($birthdayGivenAnswerSet) $birthdayGivenAnswers = $birthdayGivenAnswerSet->getAnswers();
		reset($birthdayGivenAnswers);
		$answer = (count($birthdayGivenAnswers) > 0) ? current($birthdayGivenAnswers) : null;
		$userData['birthday'] =  $answer ;
		
		$userData['cert_disable_barcode'] =  $itemIds['cert_disable_barcode'] ;
		return $userData;
	}

	/**
	 * Prepare given array with data for database insertion
	 * @param array associative array with database fields and content
	 * @return array associative array with database fields and content
	 */
	function prepareDBData($data)
	{
		return array_merge($data, array('show_in_summary' => 1));
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

}

?>
