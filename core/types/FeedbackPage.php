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
 * Include the ParentTreeNode class
 */
require_once(dirname(__FILE__).'/ParentTreeNode.php');

/**
 * Include the FeedbackBlock class
 */
require_once(dirname(__FILE__).'/FeedbackBlock.php');

/**
 * Include the FeedbackParagraph class
 */
require_once(dirname(__FILE__).'/FeedbackParagraph.php');

/**
 * A feedback page is a collection of {@link FeedbackParagraph}s, displayed
 * together on one page.
 *
 * @package Core
 */

class FeedbackPage extends ParentTreeNode
{
	var $title;

	/**
	 * Pulls a feedback page from the database.
	 */
	function FeedbackPage($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->table = DB_PREFIX.'feedback_pages';
		$this->sequence = DB_PREFIX.'feedback_pages';
		$this->childrenTable = DB_PREFIX.'feedback_paragraphs';
		$this->childrenConnector = 'page_id';
		$this->childrenSequence = DB_PREFIX.'feedback_paragraphs';
		$this->parentConnector = 'block_id';
		$this->ParentTreeNode($id);
	}

	/**
	 * @access private
	 */
	function _returnChild($id) {
		return new FeedbackParagraph($id);
	}

	/**
	 * @access private
	 */
	function _returnParent($id)
	{
		return $GLOBALS["BLOCK_LIST"]->getBlockById($id, BLOCK_TYPE_FEEDBACK);
	}

	/**
	 * Returns owner of parent block
	 * @return User
	 */
	function getOwner()
	{
		$parent = $this->getParent();
		return $parent->getOwner();
	}

	/**
	 * Returns the internal title of this page.
	 * @return string
	 */
	function getTitle()
	{
		return $this->_getField('title');
	}

	/**
	 * Returns the class type (class name)
	 * @return Name of class
	 */
	function getType()
	{
		return __CLASS__;
	}

	/**
	 * Returns all paragraphs on this page.
	 * @return FeedbackParagraph[]
	 */
	function getParagraphs()
	{
		return FeedbackParagraph::getAllByPage($this->id);
	}

	/**
	 * Returns the media connect ID
	 * @return integer
	 */
	function getMediaConnectId()
	{
		$parent = $this->getParent();
		return $parent->getMediaConnectId();
	}

	/**
	 * Adds a new feedback paragraph to this page and returns it.
	 * @param mixed Associative array of data to feed into the paragraph
	 */
	function createChild($info)
	{
		$avalues = array();

		if (isset($info['content'])) $avalues['content'] = $info['content'];
		if (isset($info['pos'])) $avalues['pos'] = $info['pos'];

		$avalues['u_created'] = $GLOBALS['PORTAL']->userId;
		$avalues['t_created'] = time();
		$avalues['u_modified'] = $avalues['u_created'];
		$avalues['t_modified'] = $avalues['t_created'];

		return $this->_createChild($avalues);
	}

	/**
	 * Modify the data in the current page
	 * @param mixed Associative array of values to be modified
	 * @return boolean
	 */
	function modify($modifications)
	{

		if(!is_array($modifications)) {
				trigger_error('<b>FeedbackPage::modify</b>: $modifications is no valid array');
		}

		$avalues = array();
		if (array_key_exists('title', $modifications)) {
			$avalues['title'] = $modifications['title'];
		}
		if (array_key_exists('pos', $modifications)) {
			$avalues['pos'] = $modifications['pos'];
		}
		if (array_key_exists('media_connect_id', $modifications)) {
			$avalues['media_connect_id'] = $modifications['media_connect_id'];
		}
		if (array_key_exists('disabled', $modifications)) {
			$avalues['disabled'] = $modifications['disabled'] ? 1 : 0;
		}

		return $this->_modify($avalues);
	}

	function certEnabled()
	{
		return _returnParent();
	}

}

?>
