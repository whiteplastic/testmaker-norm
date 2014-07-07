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
 * @package Portal
 */

/**
 * Loads the Dimension class
 */
require_once(CORE.'types/Dimension.php');

/**
 * Loads the Page class
 */
require_once(PORTAL.'Page.php');

libLoad('utilities::cutBlockFromString');

/**
 * Displays the block tree menu
 *
 * @package Portal
 */

class MenuBlockTree {

	/**
	 * @access private
	 */
	var $completeTree;

	function MenuBlockTree($completeTree = TRUE)
	{
		$this->completeTree = $completeTree;
	}
	
	function _menuItemCompare($itemA, $itemB)
	{
		return strcasecmp($itemA->getTitle(), $itemB->getTitle());
	}

	/**
	 * Function to list blocks in tree format
	 */
	function _listBlocks($parents, $indentTypes, &$template, $path, $selectorMode, $itemId, $multipleUse)
	{
		$content = '';
		$block = NULL;
		if(is_subclass_of($parents[count($parents) - 1], 'ParentTreeBlock')) {
			$blocks = $parents[count($parents) - 1]->getTreeChildren();
			if ($parents[count($parents) - 1]->isFeedbackBlock()) {
				$block = &$parents[count($parents) - 1];
				$dims = DataObject::getBy('Dimension','getAllByBlockId',$parents[count($parents) - 1]->getId());
				$blocks = array_merge($dims, $blocks);
			}
		} else {
			if ($selectorMode) {
				$blockIds = Page::splitPath($path);
				while ($blockIds[0] != $parents[count($parents)-1]->getId()) {
					array_shift($blockIds);
				}
				if (count($blockIds) > 1) {
					$blocks = array($parents[count($parents)-1]->getChildById($blockIds[1]));
				} else {
					$blocks = array();
				}
			} else {
				$blocks = $parents[count($parents) - 1]->getChildren();
			}
		}
		if($path == "_0_") usort($blocks, Array("Test", "compareTestTitle"));

		$parentPath = '';
		for($i = 0; $i < count($parents); $i++) {
			$parentPath .= '_'.$parents[$i]->getId();
		}
		if($i > 0) {
			$parentPath .= '_';
		}

		// Return whether there are subitems or not
		if (! $selectorMode && (count($parents) > 0) && ! preg_match('/^'.$parentPath.'$/', $path)) {
			return $blocks ? $content : '';
		}

		// Simulate theta 'dimensions'
		if ($block && !$selectorMode) {
			$qbIds = $block->getSourceIds();
			foreach ($qbIds as $qbId) {
				$qb = $GLOBALS['BLOCK_LIST']->getBlockById($qbId, BLOCK_TYPE_ITEM);
				if (!$qb->isAdaptiveItemBlock() && !$qb->isIRTBlock()) continue;

				$owner = $block->getOwner();
				$item = str_replace('<!-- username -->', htmlentities($owner->getUsername()), $template);
				$item = cutBlockFromString('<!-- MENUICON -->', $item);
				$menuIcon = "portal/images/menu_empty.gif";
				$item = cutBlockFromString('<!-- SUBITEMS -->', $item);

				$foreign = ($owner->getId() != $GLOBALS['PORTAL']->getUserId());
				$item = cutBlockFromString('<!-- ACTIVE -->', $item);
				$item = str_replace('<!-- NOT_ACTIVE -->', '', $item);

				$title = T('pages.feedback_page.theta_dim', array('name' => $qb->getTitle()));
				$link = "javascript:alert('". addslashes(T('pages.tree.special_dim')) ."');";
				$icon = "portal/images/menu_dimension.gif";

				$item = cutBlockFromString('<!-- MULTIPLE_USE -->', $item);
				$item = str_replace('<!-- title -->', '<em>'. htmlentities($title) .'</em>', $item);
				$item = str_replace('<!-- link -->', htmlspecialchars($link), $item);
				$item = str_replace('<!-- menuicon -->', $menuIcon, $item);
				$item = str_replace('<!-- icon -->', $icon, $item);
				$item = str_replace('<!-- dots -->', 'none', $item);
				$item = str_replace('<!-- indent -->', '', $item);
				$item = str_replace('<!-- id -->', -($qbId), $item);

				if($foreign) {
					$item = str_replace('<!-- FOREIGN -->', '', $item);
				} else {
					$item = cutBlockFromString('<!-- FOREIGN -->', $item);
				}
				$content .= $item;

			}
		}

		for($i = 0; $i < count($blocks); $i++)
		{
			$item = $template;

			$owner = '';
			$foreign = false;
			$disabled = false;
			if (is_a($blocks[$i], 'Dimension')) {
				$dimParent = &$blocks[$i]->getParent();
				$owner = $dimParent->getOwner();
				$disabled = $dimParent->getDisabled($path);
			} else {
				$owner = $blocks[$i]->getOwner();
				$disabled = $blocks[$i]->getDisabled($path);
			}
			
			$item = str_replace('<!-- username -->', htmlentities($owner->getUserName()), $item);
			if ($owner->getId() != $GLOBALS['PORTAL']->getUserId())
			{
				$foreign = true;
			}
			else
			{
				$foreign = false;
			}

			if($multipleUse || $blocks[$i]->hasMultipleParents()) {
				$actMultipleUse = true;
			} else {
				$actMultipleUse = false;
			}
			if (is_subclass_of($blocks[$i], "Block"))
			{
				if (!$GLOBALS['PORTAL']->page->checkAllowed('view', false, $blocks[$i])) continue;

				$newParents = array_merge($parents, array($blocks[$i]));
				$newIndentTypes = array_merge($indentTypes, array(($i+1) == count($blocks) || ! $this->completeTree ? 0 : 1));
				$workingPath = $parentPath.$blocks[$i]->getId().'_';
				if (! $this->completeTree && substr($path, 0, strlen($workingPath)) != $workingPath && $selectorMode)
				{
					continue;
				}

				if($workingPath == $path && ($itemId == NULL || $blocks[$i]->isContainerBlock()))
				{
					$active = TRUE;
					$item = cutBlockFromString('<!-- NOT_ACTIVE -->', $item);
					$item = str_replace('<!-- ACTIVE -->', '', $item);
				}
				else
				{
					$active = FALSE;
					$item = cutBlockFromString('<!-- ACTIVE -->', $item);
					$item = str_replace('<!-- NOT_ACTIVE -->', '', $item);
				}

				$type = $blocks[$i]->getBlockType();
				$link = linkTo("block_edit", array("working_path" => $workingPath));
				$title = $blocks[$i]->getTitle();
				$icon = "portal/images/menu_folder".($active ? "_current" : "").($foreign ? "_foreign" : "").".gif";

				if ($blocks[$i]->isItemBlock()) {
					$icon = "portal/images/menu_folder_item".($foreign ? "_foreign" : "").".gif";
				}
				elseif ($blocks[$i]->isInfoBlock()) {
					$icon = "portal/images/menu_folder_info".($foreign ? "_foreign" : "").".gif";
				}
				elseif ($blocks[$i]->isFeedbackBlock()) {
					$icon = "portal/images/menu_folder_feedback".($foreign ? "_foreign" : "").".gif";
				}

				if ($selectorMode || $this->completeTree) {
					$subitems = $this->_listBlocks($newParents, $newIndentTypes, $template, $path, $selectorMode, $itemId, $actMultipleUse);
				} else {
					$subitems = '';
				}
				if(trim($subitems))
				{
					if(preg_match('/_'.$blocks[$i]->getId().'_/', $path)) {
						$menuIcon = "portal/images/menu_expanded.gif";
						$item = str_replace('<!-- SUBITEMS -->', '', $item);
						$item = str_replace('<!-- subitems -->', $subitems, $item);
					} else {
						$menuIcon = "portal/images/menu_collapsed.gif";
						$item = cutBlockFromString('<!-- SUBITEMS -->', $item);
					}
				}
				else
				{
					$menuIcon = "portal/images/menu_empty.gif";
					$item = cutBlockFromString('<!-- SUBITEMS -->', $item);
				}
			}
			else
			{
				if (! $this->completeTree)
				{
					if ($selectorMode)
					{
						continue;
					}
					else
					{
						$item = cutBlockFromString('<!-- MENUICON -->', $item);
					}
				}
				$type = "_";
				$link = linkTo("block_edit", array("working_path" => $path));
				$title = "(unknown ".($i+1).")";
				$icon = "portal/images/menu_file".($foreign ? "_foreign" : "").".gif";
				$menuIcon = "portal/images/menu_empty.gif";
				$item = cutBlockFromString('<!-- SUBITEMS -->', $item);
				$mark = false;
				if (getpost('page') != 'dimension' && !is_a($blocks[$i], 'Dimension') && $blocks[$i]->getId() == $itemId)
				{
					$mark = true;
				}
				elseif (strtolower(get_class($blocks[$i])) == getpost('page') && $blocks[$i]->getId() == $itemId)
				{
					$mark = true;
				}
				if ($mark)
				{
					$item = cutBlockFromString('<!-- NOT_ACTIVE -->', $item);
					$item = str_replace('<!-- ACTIVE -->', '', $item);
				}
				else
				{
					$item = cutBlockFromString('<!-- ACTIVE -->', $item);
					$item = str_replace('<!-- NOT_ACTIVE -->', '', $item);
				}

				$title = $blocks[$i]->getTitle();
				if (is_subclass_of($blocks[$i], "Item"))
				{
					$type = "?";
					$link = linkTo("item", array("working_path" => $path, "item_id" => $blocks[$i]->getId()));
					$icon = "portal/images/menu_file_item".($foreign ? "_foreign" : "").".gif";
				}
				elseif (is_a($blocks[$i], "InfoPage"))
				{
					$type = "?";
					$link = linkTo("info_page", array("working_path" => $path, "item_id" => $blocks[$i]->getId()));
					$icon = "portal/images/menu_file_info".($foreign ? "_foreign" : "").".gif";
				}
				elseif (is_a($blocks[$i], "FeedbackPage"))
				{
					$type = "?";
					$link = linkTo("feedback_page", array("working_path" => $path, "item_id" => $blocks[$i]->getId()));
					$icon = "portal/images/menu_file_feedback".($foreign ? "_foreign" : "").".gif";
				}
				elseif (is_a($blocks[$i], "Dimension"))
				{
					$type = "?";
					$link = linkTo("dimension", array("working_path" => $path, "item_id" => $blocks[$i]->getId()));
					$icon = "portal/images/menu_dimension.gif";
				}
			}
			if($actMultipleUse) {
				$item = str_replace('<!-- MULTIPLE_USE -->', '', $item);
			} else {
				$item = cutBlockFromString('<!-- MULTIPLE_USE -->', $item);
			}

			libLoad("utilities::shortenString");

			$indent = "";
			if ($selectorMode)
			{
				$dots = ($i+1 == count($blocks) || ! $this->completeTree) ? "ne" : "nes";
				if (! $this->completeTree)
				{
					$menuIcon = "portal/images/menu_empty.gif";
				}
				for ($level = 1; $level < count($parents); $level++)
				{
					if ($indentTypes[$level])
					{
						$indent .= "<div class=\"MenuIndentLine\"></div>";
					}
					else
					{
						$indent .= "<div class=\"MenuIndentEmpty\"></div>";
					}
				}
			}
			else
			{
				$dots = "none";
			}

			$item = str_replace('<!-- title -->', htmlentities($title), $item);
			$item = str_replace('<!-- link -->', htmlentities($link), $item);
			$item = str_replace('<!-- menuicon -->', $menuIcon, $item);
			$item = str_replace('<!-- icon -->', $icon, $item);
			$item = str_replace('<!-- dots -->', $dots, $item);
			$item = str_replace('<!-- indent -->', $indent, $item);
			$item = str_replace('<!-- id -->', $blocks[$i]->getId(), $item);
			$item = str_replace('<!-- MENUICON -->', '', $item);

			if (($disabled) && ($foreign)){
				$item = str_replace('<!-- FDISABLED -->', '', $item);
			} else {
				$item = cutBlockFromString('<!-- FDISABLED -->', $item);
			} 
			
			if($foreign) {
				$item = str_replace('<!-- FOREIGN -->', '', $item);
			} else {
				$item = cutBlockFromString('<!-- FOREIGN -->', $item);
			}

			if ($disabled) {
				$item = str_replace('<!-- DISABLED -->', '', $item);
			} else {
				$item = cutBlockFromString('<!-- DISABLED -->', $item);
			}

			$content .= $item;
		}

		return $content;
	}
	

	function getMenuTree($template, $path, $itemId)
	{
		$atemplate = explode('<!-- LIST -->', $template);
		$item = $atemplate[1];

		$item = cutBlockFromString('<!-- MENUICON -->', $item);
		$item = str_replace('<!-- title -->', T('pages.block.root'), $item);
		$item = str_replace('<!-- working_path -->', '_0_', $item);
		$item = str_replace('<!-- link -->', htmlentities(linkTo("block_edit", array("working_path" => "_0_"))), $item);
		$item = str_replace('<!-- icon -->', "portal/images/menu_workspace.gif", $item);
		$item = str_replace('<!-- indent -->', '', $item);
		$item = cutBlockFromString('<!-- FOREIGN -->', $item);
		$item = cutBlockFromString('<!-- MULTIPLE_USE -->', $item);
		$item = cutBlockFromString('<!-- DISABLED -->', $item);
		$item = cutBlockFromString('<!-- FDISABLED -->', $item);

		if($path == '_0_')
		{
			$item = cutBlockFromString('<!-- NOT_ACTIVE -->', $item);
			$item = str_replace('<!-- ACTIVE -->', '', $item);
		}
		else
		{
			$item = cutBlockFromString('<!-- ACTIVE -->', $item);
			$item = str_replace('<!-- NOT_ACTIVE -->', '', $item);
		}
		$subitems = $this->_listBlocks(array($GLOBALS["BLOCK_LIST"]->getBlockById(0)), array(0), $atemplate[1], $path, TRUE, $itemId, false);
		if(trim($subitems))
		{
			$item = str_replace('<!-- SUBITEMS -->', '', $item);
			$item = str_replace('<!-- subitems -->', $subitems, $item);
		}
		else
		{
			$item = cutBlockFromString('<!-- SUBITEMS -->', $item);
		}

		return $atemplate[0].$item.$atemplate[2];
	}

	function getMenuOfCurrent($template, $path, $itemId)
	{
		if ($path == "")
		{
			$path = "_0_";
		}
		$atemplate = explode('<!-- LIST -->', $template);
		$parentIds = Page::splitPath($path);
		$parents = array();
		$parents[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($parentIds); $i++)
		{
			$parents[] = $parents[count($parents) - 1]->getChildById($parentIds[$i]);
		}
		$indent = array_fill(0, count($parents), 0);
		$item = $this->_listBlocks($parents, $indent, $atemplate[1], $path, FALSE, $itemId, false);
		return $atemplate[0].$item.$atemplate[2];
	}
}

?>
