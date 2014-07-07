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
 * Include the TreeNode class
 */
require_once(dirname(__FILE__).'/TreeNode.php');

/**
 * Include the Item class
 */
require_once(dirname(__FILE__).'/Item.php');

/**
 * ItemAnswer class
 *
 * @package Core
 */

class ItemAnswer extends TreeNode
{

	function ItemAnswer($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->table = DB_PREFIX.'item_answers';
		$this->sequence = DB_PREFIX.'item_answers';
		$this->parentConnector = 'item_id';
		$this->TreeNode($id);
	}

	/**
	 * returns parent node with given id
	 * @return Item
	 */
	function _returnParent($id)
	{
		if (! isset($GLOBALS["ITEM_CACHE"][$id])) {
			$GLOBALS["ITEM_CACHE"][$id] = new Item($id);
		}
		return $GLOBALS["ITEM_CACHE"][$id];
	}

	/**
	 * returns as title a shorten part of the answer itself
	 * @param boolean Shorten string
	 * @return String
	 */
	function getTitle($shorten = false)
	{
//		libLoad("utilities::htmlToText");
		$answer = $this->getAnswer();
//		$answer = htmlToText($answer);
		$answer = strip_tags($answer);
		return $shorten ? shortenString($answer, 25) : $answer;
	}

	/**
	 * returns the current answer
	 * @return String
	 */
	function getAnswer()
	{
		return $this->_getField('answer');
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
	 * returns if the current answer is correct
	 * @return bool
	 */
	function isCorrect()
	{
		$correct = $this->_getField('correct');

		if($correct == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns a copy of the current item answer
	 * @param id of target item block
	 * @param 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
	 * @return Node
	 */
	function copyNode($parentId, &$changedIds)
	{
		$node= parent::copyNode($parentId, $changedIds);

		//modify media pathes
		if(isset($node)) {
			$changedIds['item_answers'][$this->id] = $node->getId();
			$content = $node->getAnswer();
			$fileHandling = new FileHandling();
			$mediaPath = str_replace(ROOT, '', $fileHandling->getFileDirectory()).'media/';
			$mediaConnectId = $node->getMediaConnectId();
			$modulo = $mediaConnectId % 100;
			$content = preg_replace('/'.preg_quote($mediaPath, '/').'[0-9]+\/[0-9]+\/[0-9]+_/', $mediaPath.$modulo."/".$mediaConnectId."/".$mediaConnectId.'_', $content);
			$node->modify(array('answer' => $content));
		}
		return $node;

	}

	function modify($modifications) {
		$avalues = array();
		if(array_key_exists('pos', $modifications)) {
			$avalues['pos'] = $modifications['pos'];
		}
		if(array_key_exists('answer', $modifications)) {
			$avalues['answer'] = $modifications['answer'];
		}
		if(array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}
		if(array_key_exists('correct', $modifications)) {
			if($modifications['correct']) {
				$avalues['correct'] = 1;
			} else {
				$avalues['correct'] = 0;
			}
		}
		if(array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : 0;
		}


		return $this->_modify($avalues);
	}
	
	
	function getDisabled()
	{
			return $this->_getField('disabled');
	}
}

?>
