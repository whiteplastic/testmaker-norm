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
 * Test class
 * @package Core
 */
require_once(CORE.'types/TestSelector.php');

class Test
{
	/**
	 * @access private
	 */
	var $db;
	var $testBlock;
	var $path = array();

	/**
	 * Constructor
	 *
	 * @param integer Test id
	 */
	function Test($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->testBlock = $GLOBALS["BLOCK_LIST"]->getBlockById($id, BLOCK_TYPE_CONTAINER);
	}

	/**
	 * Return test block
	 */
	function getBlock()
	{
		return $this->testBlock;
	}

	/**
	 * Return subtest block
	 */
	function getSubtestBlock($subTestId)
	{
		return $GLOBALS['BLOCK_LIST']->getBlockById($subTestId);
	}

	/**
	 * Return test id
	 *
	 * @return integer Test id
	 */
	function getId()
	{
		return $this->testBlock->getId();
	}

	/**
	 * Return test title
	 *
	 * @return string Test title
	 */
	function getTitle()
	{
		return $this->testBlock->getTitle();
	}

	/**
	 * Return language of test
	 * @return string Test language
	 */
	function getLanguage()
	{
		return $this->testBlock->getLanguage();
	}

	/**
	 * Return test description
	 *
	 * @return string Test description
	 */
	function getDescription()
	{
		return $this->testBlock->getDescription();
	}

	/**
	 * Return the current block path with '_' as seperator
	 *
	 * @return string Current block path
	 */
	function getPath()
	{
		return implode('_', $this->path);
	}

	/**
	 * Return the number of items in test
	 * @return integer Number of items in test
	 */
	function getNumberItems()
	{
		$selector = new TestSelector($this->testBlock);
		return $selector->countItems();
	}

	/**
	 * Return the number of items in test
	 * @return integer Number of items in test
	 */
	function getNumberRequiredItems()
	{
		$selector = new TestSelector($this->testBlock);
		return $selector->countItems();
	}

	/**
	 * Return the number of pages (to display) in test
	 * @return integer Number of pages in test
	 */
	function getNumberPages()
	{
		$selector = new TestSelector($this->testBlock);
		return $selector->countChildren();
	}

	/**
	 * Return the number of items in test
	 * @return integer Number of items in test
	 */
	function getNumberItemsSubtest($subTestId)
	{
		$subTest = $this->getSubtestBlock($subTestId);
		$selector = new TestSelector($subTest);
		return $selector->countItems();
	}

	/**
	 * Return the number of items in test
	 * @return integer Number of items in test
	 */
	function getNumberRequiredItemsSubtest($subTestId)
	{
		$subTest = $this->getSubtestBlock($subTestId);
		$selector = new TestSelector($subTest);
		return $selector->countItems();
	}

	/**
	 * Return the number of pages (to display) in test
	 * @return integer Number of pages in test
	 */
	function getNumberPagesSubtest($subTestId)
	{
		$subTest = $this->getSubtestBlock($subTestId);
		$selector = new TestSelector($subTest);
		return $selector->countChildren();
	}

	/**
	 * Return whether the test is in subtest mode or not
	 * @return boolean True if the test is in subtest mode, else false
	 */
	function getShowSubtests()
	{
		return $this->testBlock->getShowSubtests();
	}

	/**
	 * Check if a certain child block exists
	 * @return boolean True if exists, false if not
	 */
	function existsChild($childId)
	{
		return $this->testBlock->existsChild($childId);
	}

	function _getParents($path)
	{
		$parents = array();
		$parents[0] = $this->testBlock;
		for ($i = 1; $i < count($path); $i++) {
			$parents[$i] = $parents[$i-1]->getChildById($path[$i]);
		}
		return $parents;
	}

	/**
	 * Return current item
	 *
	 * @param integer[] List of block ids
	 * @param integer Current item id
	 * @return mixed Current item
	 */
	function getCurrentItem($path, $itemId)
	{
		$parents = $this->_getParents($path);
		$parent = $parents[count($parents)-1];
		return $parent->getTreeChildById($itemId);
	}

	/**
	 * Return new item
	 *
	 * @param integer[] List of block ids
	 * @param integer Current item id
	 * @return mixed Next item or FALSE if finished
	 */
	function getNextItem($path = NULL, $itemId = NULL)
	{
		$parents = $this->_getParents($path);

		// search test start item from the last parent if no itemid is set
		if (! $itemId)
		{
			return $this->_searchFirstItem(end($parents));
		}
		elseif (is_subclass_of($parents[count($parents)-1], 'ParentTreeBlock'))
		{
			$item = $parents[count($parents)-1]->getTreeChildById($itemId);
			return $this->_getRekursiveNextItem($parents, $item);
		}
		else
		{
			return NULL;
		}
	}

	function _getRekursiveNextItem($parents, $child)
	{
		$nextItem = $this->_getNextItem(end($parents), $child);
		if (! $nextItem)
		{
			$child = array_pop($parents);
			array_pop($this->path);
			if (count($parents) == 0)
			{
				return FALSE;
			}
			return $this->_getRekursiveNextItem($parents, $child);
		}
		elseif (is_subclass_of($nextItem, 'TreeNode'))
		{
			return $nextItem;
		}
		else
		{
			$this->path[] = $nextItem->getId();
			return $this->_searchFirstItem($nextItem);
		}
	}

	/**
	 * Return the next item
	 *
	 * @param mixed Parent item
	 * @param mixed Last item
	 * @return mixed Next item
	 */
	function _getNextItem($parent, $item)
	{
		$position = $item->getNextUsedPosition($parent->getId());
		if (!$position)
		{
			return FALSE;
		}
		if (is_subclass_of($parent, 'ParentTreeBlock'))
		{
			return $parent->getTreeChildByPosition($position);
		}
		return $parent->getChildByPosition($position);
	}

	/**
	 * Search the first item from a given block
	 *
	 * @param mixed Parent block
	 * @return mixed First item in a block
	 */
	function _searchFirstItem($parent)
	{
		if (is_subclass_of($parent, 'ParentTreeBlock'))
		{
			$blocks = $parent->getTreeChildren();
			if ($blocks)
			{
				return $blocks[0];
			}
			else
			{
				trigger_error('Incomplete test', E_USER_ERROR);
			}
		}
		else
		{
			$children = $parent->getChildren();
			if ($children)
			{
				$this->path[] = $children[0]->getId();
				return $this->_searchFirstItem($children[0]);
			}
			else
			{
				trigger_error('Incomplete test', E_USER_ERROR);
			}
		}
	}

	/**
	 * Return stylesheet informations for this test
	 *
	 * @return string Stylesheet definitions
	 */
	function getStyle()
	{
		return $this->testBlock->getStyle();
	}

	/**
	 * Return media_connect_id for a test logo
	 *
	 * @return integer
	 */
	function getLogo()
	{
		return $this->testBlock->getLogo();
	}
	/**
	 * Return logo_show for a test logo
	 *
	 * @return integer
	 */
	function getLogoShow()
	{
		return $this->testBlock->getLogoShow();
	}

	/**
	 * Compares to strings lexicographically
	 */
	function compareTestTitle($testA, $testB)
	{
		return strcasecmp($testA->getTitle(), $testB->getTitle());
	}
}

?>
