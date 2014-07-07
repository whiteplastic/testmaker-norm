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
 * Provides access to the leafs of a test tree (or statistics about them)
 * @package Core
 */
class TestSelector
{
	/**#@+
	 * @access private
	 */
	var $test;
	var $countItems;
	var $countRequiredItems;
	/**#@-*/

	/**
	 * Constructor
	 * @param ContainerBlock The test to search
	 */
	function TestSelector(&$test)
	{
		$this->test = &$test;
	}

	/**
	 * Determines the current item based on a test run
	 * @param TestRun The test run to search
	 * @return Item
	 */
	function getCurrentItem(&$testRun)
	{
		$this->lastAnswerSet = $testRun->getLastAnswerSet();

		if (! $this->lastAnswerSet) {
			return NULL;
		}

		return $this->_findItem($this->test, "current");
	}

	/**
	 * Determines the next item based on a test run
	 * @param TestRun The test run to search
	 * @return Item
	 */
	function getNextItem(&$testRun)
	{
		$this->lastAnswerSet = $testRun->getLastAnswerSet();

		$this->useNext = !$this->lastAnswerSet;

		return $this->_findItem($this->test, "next");
	}

	/**
	 * Returns all children of all block of a certain type
	 * @param TestRun The test run to search
	 * @return TreeNode[]
	 */
	function getChildrenOfSpecificBlocks($blockType)
	{
		$this->blockType = $blockType;
		return $this->_findItem($this->test, "specific_block_children");
	}

	/**
	 * Returns a list of IDs of all block children
	 * @return int[]
	 */
	function getChildrenIds()
	{
		return $this->_findItem($this->test, "children_ids");
	}

	/**
	 * Returns a list of IDs of all ParentTreeBlocks
	 * @param int Optional block type to limit the result to blocks of this type
	 * @return int[]
	 */
	function getParentTreeBlockIds($blockType = NULL)
	{
		$this->blockType = $blockType;
		return $this->_findItem($this->test, "parent_tree_block_ids");
	}

	/**
	 * Counts all block children
	 * @return int
	 */
	function countChildren()
	{
		$this->countItems = FALSE;
		$this->countRequiredItems = FALSE;
		return $this->_findItem($this->test, "children_count");
	}

	/**
	 * Counts all <kbd>{@link Item}</kbd> children
	 * @return int
	 */
	function countItems()
	{
		$this->countItems = TRUE;
		$this->countRequiredItems = FALSE;
		return $this->_findItem($this->test, "children_count");
	}

	/**
	 * Counts all <kbd>{@link Item}</kbd> children that are required
	 * @return int
	 */
	function countRequiredItems()
	{
		$this->countItems = TRUE;
		$this->countRequiredItems = TRUE;
		return $this->_findItem($this->test, "children_count");
	}

	/**
	 * Tree traversal
	 * @access private
	 * @return mixed
	 */
	function _findItem($block, $searchMode)
	{
		if ($searchMode == "children_count" || $searchMode == "item_count" || $searchMode == "required_items_count") {
			$result = 0;
		}
		elseif ($searchMode == "specific_block_children" || $searchMode == "children_ids" || $searchMode == "parent_tree_block_ids") {
			$result = array();
		}
		else {
			$result = NULL;
		}

		if (is_a($block, "ParentTreeBlock"))
		{
			if ($searchMode == "children_count" && $block->isItemBlock() && $block->isAdaptiveItemBlock()) {
				return 1;
			}
			elseif ($searchMode == "parent_tree_block_ids") {
				if (! $this->blockType || $block->isBlockType($this->blockType)) {
					return array($block->getId());
				} else {
					return array();
				}
			}

			$children = $block->getTreeChildren();

			if ($searchMode == "children_count" && $this->countItems && !$block->isItemBlock()) 
			{
				return 0;
			}
			elseif ($searchMode == "specific_block_children") {
				return ($block->isBlockType($this->blockType)) ? $children : array();
			}

			foreach ($children as $child)
			{
				if ($searchMode == "next")
				{
					if ($this->useNext) {
						$result = $child;
						$this->useNext = FALSE;
						break;
					}
					else {
						if ($child->getId() == $this->lastAnswerSet->getItemId() && $block->getId() == $this->lastAnswerSet->getBlockId()) {
							$this->useNext = TRUE;
						}
					}
				}
				elseif ($searchMode == "current")
				{
					if ($child->getId() == $this->lastAnswerSet->getItemId()) {
						$result = $child;
						break;
					}
				}
				elseif ($searchMode == "children_count")
				{
					if (! $this->countRequiredItems || $child->isForced()) {
						if (($child->getDisabled() == false) && ($child->nodeDisabled($child->getId(), $block->getId())) == false)
							$result++;
					}
				}
				elseif ($searchMode == "children_ids")
				{
					$result[] = array($block->getId(), $child->getId());
				}
			}
			if($searchMode == "children_count" && !$this->countItems)
			{
				$itemsPerPage = (int)$block->getItemsPerPage();
				if($itemsPerPage > 1)
				{
					$result = ceil($result/$itemsPerPage);
				}
			}
		}
		elseif (is_a($block, "ParentGraphNode"))
		{
			$children = $block->getChildren();
			foreach ($children as $child)
			{
				if ($searchMode == "children_count") {
					if (($child->getDisabled() == false) && ($child->nodeDisabled($child->getId(), $block->getId())) == false)
						$result += $this->_findItem($child, $searchMode);
				}
				elseif ($searchMode == "specific_block_children" || $searchMode == "children_ids" || $searchMode == "parent_tree_block_ids") {
					$result = array_merge($result, $this->_findItem($child, $searchMode));
				}
				else {
					$result = $this->_findItem($child, $searchMode);
					if ($result !== NULL) {
						break;
					}
				}
			}
		}

		return $result;
	}
	
	/*
	Returns blocks of a special Type.
	@param BlockType
	@return block
	*/
	function getBlocksOfType($blockType, $block = NULL)
	{
		if ($block == NULL)
			$block = $this->test;

		$blocksReturn = array();
		
		if (is_a($block, "ParentGraphNode"))
		{
			$children = $block->getChildren();
			foreach ($children as $child) {
				//BlockType = 0 is Subtest
				if ($child->getBlockType() == BLOCK_TYPE_CONTAINER) {
					$blocksReturn = array_merge($blocksReturn, $this->getBlocksOfType($blockType, $child));
				}
					
				if ($child->getBlockType() == $blockType) {
					$blocksReturn[] = $child;
				}
			}
		}		
		return $blocksReturn;
	}
}

?>
