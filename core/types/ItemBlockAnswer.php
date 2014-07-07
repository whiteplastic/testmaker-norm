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
 * Include the ItemAnswer class
 */
require_once(dirname(__FILE__).'/ItemBlock.php');

/**
 * ItemBlockAnswer class
 *
 * @package Core
 */

class ItemBlockAnswer
{

	var $id;
	var $itemBlock;
	var $db;

	function ItemBlockAnswer($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		$query = 'SELECT count(id) FROM '.DB_PREFIX.'item_block_answers WHERE id = ?';
		$num = $this->db->getOne($query, array($id));
		if($this->db->isError($num)) {
			return false;
		}
		if($num != 1) {
			trigger_error('<b>ItemBlockAnswer</b>: $id is no valid answer id');
			return false;
		}
		$this->id = $id;
	}

	/**
	 * returns the id of the current default answer
	 * @return Integer
	 */
	function getId()
	{
		return $this->id;
	}

	/**
	 * returns the current answer
	 * @return String
	 */
	function getAnswer()
	{
		$query = 'SELECT answer FROM '.DB_PREFIX.'item_block_answers WHERE id = ?';
		$answer = $this->db->getOne($query, array($this->id));
		if($this->db->isError($answer)) {
			return false;
		}
		return $answer;
	}

	/**
	 * returns the postition of the current default answer
	 * @return String
	 */
	function getPosition()
	{
		$query = 'SELECT pos FROM '.DB_PREFIX.'item_block_answers WHERE id = ?';
		$position = $this->db->getOne($query, array($this->id));
		if($this->db->isError($position)) {
			return false;
		}

		return (int) $position;
	}

	/**
	 * modify the given informations in the current default answer
	 * @param mixed[] $modification values to be modified
	 * @return bool
	 */
	function modify($modifications)
	{

		if(!is_array($modifications)) {
				trigger_error('<b>ItemAnswer::modify</b>: $modifications is no valid array');
		}

		if(array_key_exists('answer', $modifications)) {
			$avalues['answer'] = $modifications['answer'];
		}
		if(array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}

		$output = array();
		$sets = '';
		for(reset($avalues); list($column, $value) = each($avalues); ) {
			if(strlen($sets) > 0) {
				$sets .= ', ';
			}
			$sets .= $column.' = ?';
			$output[] = $value;
		}
		$output[] = $this->id;

		$query = 'UPDATE '.DB_PREFIX.'item_block_answers SET '.$sets.' WHERE id = ?';
		$result = $this->db->query($query, $output);
		if($this->db->isError($result)) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the position of the previous default answer or if there is no previous answer return the position of the answer itself
	 * @return integer
	 */
	function getPreviousUsedPosition()
	{
		$query = 'SELECT pos FROM '.DB_PREFIX.'item_answers WHERE item_block_id = ? AND pos < ? ORDER BY pos DESC LIMIT 1';

		if(!($position = $this->db->getOne($query, array($this->itemBlock->getId(), $this->getPosition()))))
		{
			return $this->getPosition();
		}
		else
		{
			if($this->db->isError($position)) {
				return false;
			}
			return (int) $position;
		}
	}

	/**
	 * Sets the position of the current default answer. If this position is already used by another default answer, it will relocate all following default answers as necessary.
	 * @param integer New position
	 * @return boolean
	 */
	function setPosition($position)
	{
		if(!preg_match('/^[0-9]+$/', $position) && $position != NULL)
		{
			trigger_error('<b>ItemBlockAnswer::setPosition</b>: $position is not valid');
			return false;
		}
		if($position == $this->getPosition()) {
			return true;
		}

		if($this->itemBlock->existsAnyDefaultAnswerAtPosition($position)) {
			$tmpAnswer = $this->itemBlock->getDefaultAnswerByPosition($position);
			$tmpAnswer->setPosition(($tmpAnswer->getPosition() + 1));
		}

		$result = $this->db->query('UPDATE '.DB_PREFIX.'item_block_answers SET pos = ? WHERE id = ?', array($position, $this->id));

		return (!$this->db->isError($result));
	}

	/**
	 * Return media connect id
	 */
	function getMediaConnectId()
	{
		$parent = $this->getParent();
		return $parent->getMediaConnectId();
	}

	function _getField($fieldname) {
		$query = 'SELECT '.$fieldname.' FROM '.DB_PREFIX.'item_block_answers WHERE id = ?';
		$field = $this->db->getOne($query, array($this->id));
		if($this->db->isError($field)) {
			return false;
		}

		return $field;
	}
}

?>
