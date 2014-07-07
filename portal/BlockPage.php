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
 * Base class for all pages dealing with blocks
 *
 * @package Portal
 */
class BlockPage extends Page
{
	var $db;
	var $blocks;
	var $block;
	var $parentId;
	var $parentPath;
	var $readOnlyMode = false;
	var $workingPath;

	function BlockPage($pageName)
	{
		$this->Page($pageName);

		// Security checks for working path
		$workingPath = getpost('working_path', '_0_');
		$this->workingPath = $workingPath;

		$user = $GLOBALS['PORTAL']->getUser();
		if (!$user || !$user->checkPermission('view')) {
			$ids = $this->splitPath($workingPath);
			$pathPrefix = array(array_shift($ids));
			$newPath = '_0_';
			$cur = $GLOBALS["BLOCK_LIST"]->getBlockById($pathPrefix[0]);
			while ($nxt = array_shift($ids)) {
				// Invalid working path -> truncate it
				if (!$cur->existsChild($nxt)) {
					redirectTo('block_edit', array('working_path' => $newPath));
				}
				$cur = $cur->getChildById($nxt);

				// Are we allowed to see it?
				if (!$user->checkPermission('view', $cur)) {
					// Ick... truncate working path
					redirectTo('block_edit', array('working_path' => $newPath));
				}
				$newPath = $this->joinPath($pathPrefix);
			}
		}

		$ids = $this->splitPath($this->workingPath);

		$blocks = array();
		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		$tmpPath = '_0_';
		for($i = 1; $i < count($ids); $i++)
		{
			if (!$blocks[count($blocks) - 1]->existsChild($ids[$i])) {
				redirectTo('block_edit', array('working_path' => $tmpPath));
			}
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
			$tmpPath .= $ids[$i].'_';
		}
		$this->blocks = $blocks;
		$this->block = $blocks[count($blocks) - 1];
		$this->parentId = count($ids) >= 2 ? $ids[count($ids) - 2] : NULL;
		$this->parentPath = '_'.implode('_', array_slice($ids, 0, -1)).'_';

		// Pre-get permissions
		$perms = array('run', 'portal', 'view', 'create', 'edit', 'delete', 'link', 'copy');
		foreach ($perms as $perm) {
			$field = snakeToCamel("may_$perm", false);
			$this->$field = $this->checkAllowed($perm);
		}
		$this->mayPublish = ($this->checkAllowed('publish', false, false, false) || $this->checkAllowed('admin', false, NULL));
		if ($this->block->getDisabled($this->workingPath)) $this->makeReadOnly();
	}

	/**
	 * Constrains the user interface to viewing only for the current view
	 */
	function makeReadOnly()
	{
		$this->mayCreate = false;
		$this->mayEdit = false;
		$this->mayDelete = false;
		$this->mayLink = false;
		$this->mayCopy = false;
		$this->readOnlyMode = true;
	}

	/**
	 * return a list of all possible pathes an Titles to the different parents
	 *
	 * @param ContainerBlock block to get all parents
	 * @param integer[] path get filled from the upper direction of block tree
	 *  has to be empty at top level of recursion
	 *  (example: $path: [0] => 1, => 5; missing is the first level of the
	 *  rootBlock which would be added to the front in the next recursion step.
	 *  1 is a block at the first level and 5 a block at the second level)
	 * @param string[] contains the titles related to the path array
	 *  has to be empty at top level of recursion
	 * @return mixed[]
	 */
	function getAllParents($block, $path, $titles)
	{
		$list = array();
		$children = $block->getParents();
		foreach($children as $child)
		{
			$newPath = array_merge(array($child->getId()), $path);
			$newTitles = array_merge(array($child->getTitle()), $titles);
			if($child->isRootBlock())
			{
				$list[] = array('path' => $newPath, 'titles' => $newTitles);
			} else {
				$list = array_merge($list, $this->getAllParents($child, $newPath, $newTitles));
			}
		}

		return $list;
	}

	// helper function for array filter
	function filterArray($var) {
		return !$var['disabled'];
	}

	/**
	 * Checks if the user is allowed to perform some sort of operation on the
	 * current block.
	 *
	 * @param string A permission name; see Group::checkPermission.
	 * @param boolean Outputs error message and redirects to start page if
	 *   access really is denied.
	 * @param mixed Target block, FALSE to check for the current block, or
	 *   NULL to check for global permissions. Defaults to the current block.
	 * @param boolean Whether to use virtual permissions (GID/UID 1 safety
	 *   belt and owner check).
	 */
	function checkAllowed($permission, $die = false, $target = FALSE, $useVirtual = TRUE)
	{
		if ($target === FALSE) $target = $this->block;

		if ($target && $target->getId() == $this->block->getId() && $this->readOnlyMode) {
			if (in_array($permission, array('create', 'edit', 'delete', 'link', 'copy'))) {
				$permission = 'invalid';
				$useVirtual = FALSE;
			}
		}

		return parent::checkAllowed($permission, $die, $target, $useVirtual);
	}

	/**
	 * Get correct term for current block type
	 * @return string term
	 */
	function getBlockTypeTitle() {
		switch($this->block->getBlockType())
		{
			case BLOCK_TYPE_CONTAINER:
				if (substr_count($this->workingPath, '_') == 3)
				{
					return T('pages.block.container_root');
				}
				else
				{
					return T('pages.block.container');
				}
			case BLOCK_TYPE_INFO:
				return T('pages.block.info');
			case BLOCK_TYPE_FEEDBACK:
				return T('pages.block.feedback');
			case BLOCK_TYPE_ITEM:
				return T('pages.block.item');
		}
	}

	function generateProblemMessages($problems)
	{
		$handledErrors = array();
		foreach ($problems as $problem)
		{
			$upperPublicBlock = $GLOBALS["BLOCK_LIST"]->getBlockById($problem['public']);
			$replace['access_title'] = $upperPublicBlock->getTitle();
			$tmpBlock = $GLOBALS["BLOCK_LIST"]->getBlockById($problem['id']);
			$replace['title'] = $tmpBlock->getTitle();

			switch($problem['type'])
			{
				case PROBLEM_MISSING:
					$tmpSourceBlock = $GLOBALS["BLOCK_LIST"]->getBlockById($problem['source']);
					$replace['source_title'] = $tmpSourceBlock->getTitle();
					$blockType = "";
					$tmpBlockType = $tmpBlock->getBlockType();
					$key = $problem['public'].'_'.$problem['id'].'_'.$problem['source'].'_'.$tmpBlockType;
					if(array_key_exists($key, $handledErrors)) continue;
					else $handledErrors[$key] = true;
					switch($tmpBlock->getBlockType())
					{
						case BLOCK_TYPE_FEEDBACK:
							$blockType = 'feedback_block';
							break;
						case BLOCK_TYPE_ITEM:
							$blockType = 'item_block';
							break;
						default:
							$blockType = 'feedback_block';
					}
					switch($problem['action'])
					{
						case 'move_source':
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.'.$blockType.'.msg.integrity_move_source', MSG_RESULT_NEG, $replace);
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.moved_failure', MSG_RESULT_NEG, $replace);
							break;
						case 'move_target':
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.'.$blockType.'.msg.integrity_move_target', MSG_RESULT_NEG, $replace);
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.moved_failure', MSG_RESULT_NEG, $replace);
							break;
						case 'copy':
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.'.$blockType.'.msg.integrity_copy', MSG_RESULT_NEG, $replace);
							break;
						case 'link':
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.'.$blockType.'.msg.integrity_link', MSG_RESULT_NEG, $replace);
							break;
						case 'delete':
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.'.$blockType.'.msg.integrity_delete', MSG_RESULT_NEG, $replace);
							break;
						default:
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.'.$blockType.'.msg.integrity', MSG_RESULT_NEG, $replace);
					}
					break;
				default:
					$GLOBALS["MSG_HANDLER"]->addMsg('pages.core.unknown_error', MSG_RESULT_NEG, $replace);
			}
		}
	}

	function doEditPerms()
	{
		$workingPath = get('working_path');

		if (!$this->init($workingPath)) {
			return;
		}
		if (!isset($this->targetPage)) return;

		$owner = $this->block->getOwner();
		$user = $GLOBALS['PORTAL']->getUser();
		if ($owner->getId() != $user->getId() && !$this->checkAllowed('admin', false, NULL) || $this->readOnlyMode) {
			$this->checkAllowed('invalid', true);
		}

		$this->tpl->loadTemplateFile("EditPermissions.html");
		$this->initTemplate('edit_perms');
		$this->tpl->setVariable('target_page', $this->targetPage);

		$ul = new UserList();
		$groups = $ul->getGroupList();

		foreach ($groups as $group) {
			// Ignore admin groups
			if ($group->checkPermission('admin')) continue;

			if (!$group->isSpecial()) continue;

			$this->tpl->setVariable('group_name', $group->get('groupname'));
			$this->tpl->setVariable('gid', $group->get('id'));

			// Check relevant permissions, read-only for those set globally
			$perms = $group->getPermissions($this->block);
			$gperms = $group->getPermissions();
			$aperms = array_merge($perms, $gperms);
			foreach ($aperms as $pname => $pvalue) {
				// Legacy
				if ($pname == 'tanrun') continue;

				$gperm_set = (isset($gperms[$pname]) && $gperms[$pname] && $pname != 'create');
				$perm_set = (isset($perms[$pname]) && $perms[$pname]);
				if (!$perm_set && !$gperm_set) continue;
				$checkStr = ' checked="checked"';
				if ($gperm_set) $checkStr .= ' disabled="disabled"';
				$this->tpl->setVariable("perms_{$pname}_checked", $checkStr);
			}

			if ($this->block->isContainerBlock() && $this->mayPublish) {
				$this->tpl->touchBlock('publish_perm');
			} else {
				$this->tpl->hideBlock('publish_perm');
			}

			$this->tpl->parse('group_perms');
		}

		if ($this->block->isContainerBlock() == 'container_block') {
			$this->tpl->touchBlock('perms_recursive');
			if($this->block->arePermissionsRecursive()) {
				$this->tpl->setVariable('perms_recursive', ' checked="checked"');
			} else {
				$this->tpl->setVariable('perms_recursive', '');
			}
		} else {
			$this->tpl->hideBlock('perms_recursive');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', $this->getBlockTypeTitle().': \''.$this->block->getTitle().'\'');
		$this->tpl->show();
	}

	function doSavePerms()
	{
		$workingPath = get('working_path');

		if (!$this->init($workingPath)) {
			return;
		}
		if (!isset($this->targetPage)) return;

		$owner = $this->block->getOwner();
		$user = $GLOBALS['PORTAL']->getUser();
		if ($owner->getId() != $user->getId() && !$this->checkAllowed('admin', false, NULL)) {
			$this->checkAllowed('invalid', true);
		}

		$ul = new UserList();
		$groups = $ul->getGroupList();
		$gset = post('perms', array());

		if (!is_array($gset)) return;

		$modifications = array();
		if(post('perms_recursive', false)) {
			$modifications['permissions_recursive'] = 1;
		} else {
			$modifications['permissions_recursive'] = 0;
		}

		$this->block->modify($modifications);

		foreach ($groups as $group) {
			// Silently ignore non-special groups; they're not displayed here anyway and if we allowed them,
			// people may be able to evade publication restrictions
			if (!$group->isSpecial()) continue;

			if (isset($gset[$group->get('id')])) {
				$perms = $gset[$group->get('id')];
				if(isset($perms['run'])) $perms['preview'] = $perms['run'];

				// Make sure nobody tries to slip past our lines of defense
				$invalid_perms = array();
				foreach ($perms as $pname => $pvalue) {
					// permissions
					if (!in_array($pname, Group::getPermissionNames(false, true))) $invalid_perms[] = $pname;
				}
				foreach ($invalid_perms as $pname) {
					unset($perms[$pname]);
				}

				// Prevent another way of evading publication restrictions
				if (!$this->mayPublish) unset($perms['publish']);

			} else {
				$perms = array();
			}
			$this->block->setPermissions($perms, $group);
		}

		EditLog::addEntry(LOG_OP_PRIVS_EDIT, $this->block, NULL);

		$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.perms_success', MSG_RESULT_POS, array("title" => $this->block->getTitle()));
		redirectTo($this->targetPage, array('action' => 'edit_perms', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doDelete()
	{
		$workingPath = get('working_path');
		$this->db = $GLOBALS['dao']->getConnection();
		
		$pathIds = explode("_",$workingPath);
		$maxPos = count($pathIds);

		if (!$this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('delete', true);
		$title = $this->block->getTitle();

		if (post("cancel_delete", FALSE)) {
			redirectTo('block_edit', array('working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		// Make sure we disable instead of deleting when deleting the block could corrupt test runs
		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests)) {
			$problems = array();
			// Simulated delete!
			if ($this->blocks[count($this->blocks)-2]->deleteChild($this->block->getId(), $problems, true)) {
				$this->block->modify(array('disabled' => 1));

				//fallunterscheidung ob item oder itemblock! (dann ->delete  child)
				// iscontainerblock?
				
				// disable contained children
				$child_items = $this->block->getTreeChildren();	//produces error when deleting subtests (container-blocks)!!!!  -> use getChildren //b/
				foreach($child_items AS $child)
				{
					$child->modify(array('disabled' => 1));
				}
					
				$block = $this->blocks[$maxPos - 3];
				$class = strtolower(get_class($block));
				$this->db->query("UPDATE ".DB_PREFIX."blocks_connect SET disabled = 1 WHERE id=? AND parent_id=?", array($pathIds[$maxPos - 2], $pathIds[$maxPos - 3]));
				TestStructure::storeCurrentStructure($pathIds[2], array('structure.disable_'. $class, array('title' => $block->getTitle())));
				$GLOBALS['MSG_HANDLER']->addMsg('pages.disable_object.success', MSG_RESULT_POS, array('title' => $title));
			} else {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.disable_object.failure', MSG_RESULT_NEG, array('title' => $title));
				$this->generateProblemMessages($problems);
			}
			redirectTo('block_edit', array('working_path' => $this->parentPath, 'resume_messages' => 'true'));
		}

		$problems = array();
		if ($this->blocks[count($this->blocks) - 2]->deleteChild($this->block->getId(), $problems)) {
			EditLog::addEntry(LOG_OP_DELETE, $this->block, NULL);
			
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.deleted_success', MSG_RESULT_POS, array("title" => $title));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.deleted_failure', MSG_RESULT_NEG, array("title" => $title));
			$this->generateProblemMessages($problems);
		}

		redirectTo('block_edit', array('working_path' => $this->parentPath, 'resume_messages' => 'true'));
	}

	function doCopyMove()
	{
		$workingPath = get('working_path');
		libLoad("utilities::shortenString");

		if (! $this->init($workingPath)) {
			return FALSE;
		}

		$blocks = array();
		$ids = $this->splitPath(get('working_path', '_'));
		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($ids); $i++) {
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
		} 

		$hasContainerChildren = false;
		$children = $blocks[count($blocks) - 1]->getChildren();
		for($i = 0; $i < count($children); $i++) {
			if($children[$i]->isContainerBlock()) {
				$hasContainerChildren = true;
				break;
			}
		}

		$blockingCondition = NULL;
		if ($this->block->isContainerBlock() || $this->block->isItemBlock())
		{
			// Make a lookup table with provided Item IDs
			$itemIds = array_flip($this->block->getItemIds());
			// Search the conditions for item IDs that are not provided by this block
			foreach ($this->block->getItemDisplayConditions() as $condition) {
				if (! isset($itemIds[$condition["item_id"]])) {
					$blockingCondition = $condition;
					break;
				}
			}
		}

		// The following arrays will be filled with blocks depending on which kind of block you want to copy
		$copyTargets = array();
		$moveLinkTargets = array();

		// A block with external conditions can neither be copied nor moved
		// To logic to find out the valid places is not to easy. This wouldn't be the right place, anyway.
		if (! $blockingCondition)
		{
			if(substr_count($workingPath, '_') > 3 || !$hasContainerChildren)
			{
				//add root block
				if(!$blocks[count($blocks) - 1]->isFeedbackblock()) {
					$value = array('title' => T('pages.block.root'), 'level' => 0);
					$copyTargets['_0_'] = $value;
					if($blocks[count($blocks) - 2]->getId() == 0) {
						$value['disabled'] = true;
					} else {
						$value['disabled'] = false;
					}
					$moveLinkTargets['_0_'] = $value;
				}
				$root = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
				$children = $root->getChildren();
				usort($children, Array("Test", "compareTestTitle"));
				//add first level
				for($i = 0; $i < count($children); $i++) {
					if($children[$i]->getId() != $blocks[count($blocks) - 1]->getId() && $this->checkAllowed('edit', false, $children[$i])) {
						if($children[$i]->isContainerBlock() && (!$blocks[count($blocks) - 1]->isFeedbackblock() || ($blocks[1]->getId() == $children[$i]->getId() && $blocks[count($blocks) - 2]->getId() != $children[$i]->getId()))) {
							$value = array('title' => $children[$i]->getTitle(), 'level' => 1);
							//root block is not for feedback blocksviewed, that's why one level less
							if($blocks[count($blocks) - 1]->isFeedbackblock())
							{
								$value['level']--;
							}
							$copyTargets['_0_'.$children[$i]->getId().'_'] = $value;
							if($blocks[count($blocks) - 2]->getId() != $children[$i]->getId()) {
								$value['disabled'] = false;
								$moveLinkTargets['_0_'.$children[$i]->getId().'_'] = $value;
			
							}
						}	//b/ schleife dauert verdammt lange...
						

						//add second level
						if(!$blocks[count($blocks) - 1]->isContainerBlock() && (!$blocks[count($blocks) - 1]->isFeedbackblock() || ($children[$i]->getId() == $blocks[1]->getId()))) {
							$children2 = $children[$i]->getChildren();
							for($j = 0; $j < count($children2); $j++) {
								if($children2[$j]->isContainerBlock() && $this->checkAllowed('edit', false, $children2[$j]) && ($children2[$j]->getId() != $blocks[1]->getId())) {
									$value = array('title' => $children2[$j]->getTitle());
									$value['level'] = 2;
									//root block is not for feedback blocksviewed, that's why one level less
									if($blocks[count($blocks) - 1]->isFeedbackblock())
									{
										$value['level']--;
									}
									$copyTargets['_0_'.$children[$i]->getId().'_'.$children2[$j]->getId().'_'] = $value;
									if($blocks[count($blocks) - 2]->getId() != $children2[$j]->getId()) {
										$value['disabled'] = false;
										$moveLinkTargets['_0_'.$children[$i]->getId().'_'.$children2[$j]->getId().'_'] = $value;
									}
								}
							}
						}
					}
				}
			}
			//add only root block
			else
			{
				$value = array('title' =>T('pages.block.root'), 'level' => 0);
				$copyTargets['_0_'] = $value;
				if ($blocks[count($blocks) - 2]->getId() == 0) {
					$value['disabled'] = true;
					$moveLinkTargets['_0_'] = $value;
				}
			}
		}

		$this->tpl->loadTemplateFile("CopyMoveBlock.html");
		$this->initTemplate("copy_move");

		
		if(sizeof($copyTargets) == 0) {
			$this->tpl->hideBlock('any_possible_copy');
			$this->tpl->touchBlock('no_one_copy');
			$this->tpl->hideBlock('only_one_copy');
			$this->tpl->hideBlock('more_copy');
		} elseif(sizeof($copyTargets) == 1) {
			$this->tpl->touchBlock('any_possible_copy');
			$this->tpl->hideBlock('no_one_copy');
			$this->tpl->touchBlock('only_one_copy');
			$this->tpl->hideBlock('more_copy');
			reset($copyTargets);
			list($key, $value) = each($copyTargets);
			$value['title'] = shortenString($value['title'],64);
			$this->tpl->setVariable('target_path_copy', $key);
			$this->tpl->setVariable('title_copy', $value['title']);
		} else {
			$this->tpl->touchBlock('any_possible_copy');
			$this->tpl->hideBlock('no_one_copy');
			$this->tpl->hideBlock('only_one_copy');
			$this->tpl->touchBlock('more_copy');
			for(reset($copyTargets); list($key, $value) = each($copyTargets);) {
				$value['title'] = shortenString($value['title'],64);
				$this->tpl->setVariable('target_path_copy', $key);
				$this->tpl->setVariable('title_copy', trim(str_repeat('>', $value['level']).' '.$value['title']));
				$this->tpl->parse('targets_copy');
			}
		}

		//removes disabled blocks
		$filteredMoveLinkTargets = array_filter($moveLinkTargets, array($this, 'filterArray'));
		if(sizeof($filteredMoveLinkTargets) == 0) {
			$this->tpl->hideBlock('any_possible_link_move');
			$this->tpl->touchBlock('no_one_link_move');
			$this->tpl->hideBlock('only_one_link_move');
			$this->tpl->hideBlock('more_link_move');
		} elseif(sizeof($filteredMoveLinkTargets) == 1) {
			$this->tpl->touchBlock('any_possible_link_move');
			$this->tpl->hideBlock('no_one_link_move');
			$this->tpl->touchBlock('only_one_link_move');
			$this->tpl->hideBlock('more_link_move');
			reset($filteredMoveLinkTargets);
			list($key, $value) = each($filteredMoveLinkTargets);
			$this->tpl->setVariable('target_path_link_move', $key);
			$value['title'] = shortenString($value['title'],64);
			$this->tpl->setVariable('title_link_move', $value['title']);
		} else {
			$this->tpl->touchBlock('any_possible_link_move');
			$this->tpl->hideBlock('no_one_link_move');
			$this->tpl->hideBlock('only_one_link_move');
			$this->tpl->touchBlock('more_link_move');
			for(reset($moveLinkTargets); list($key, $value) = each($moveLinkTargets);) {
				$this->tpl->setVariable('target_path_link_move', $key);
				$value['title'] = shortenString($value['title'],64);
				$this->tpl->setVariable('title_link_move', trim(str_repeat('>', $value['level']).' '.$value['title']));
				if($value['disabled'])
				{
					$this->tpl->setVariable('disabled_link_move', 'disabled="disabled" ');
				}
				$this->tpl->parse('targets_link_move');
			}
		}

		if ($blockingCondition) {
			$block = $GLOBALS["BLOCK_LIST"]->getBlockById($blockingCondition["owner_block_id"]);
			$item = $block->getTreeChildById($condition["owner_item_id"]);
			$this->tpl->setVariable("condition_owner_block_title", $block->getTitle());
			$this->tpl->setVariable("condition_owner_block_id", $block->getId());
			$this->tpl->setVariable("condition_owner_item_title", $item->getTitle());
			$this->tpl->setVariable("condition_owner_item_id", $item->getId());
			$this->tpl->touchBlock("blocking_condition");
		}

		if ($this->mayLink) $this->tpl->touchBlock('may_link');
		if (!$this->mayCopy) $this->tpl->hideBlock('may_copy');
		else $this->tpl->touchBlock('may_copy');
		

		$bl = &$GLOBALS["BLOCK_LIST"];
		if ($this->mayEdit && $this->checkAllowed('edit', false, $bl->getBlockById($this->parentId))) $this->tpl->touchBlock('may_move');


		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', $this->getBlockTypeTitle().': \''.$this->block->getTitle().'\'');

		$this->tpl->show();
	}

	function doConfirmCopyMove()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("ConfirmCopyMoveBlock.html");
		$this->initTemplate("copy_move");
		if(post('copy', FALSE)) {
			$this->tpl->setVariable('action', 'copy');
			$this->tpl->touchBlock('copy');
		} elseif(post('link', FALSE)) {
			$this->tpl->setVariable('action', 'link');
			$this->tpl->touchBlock('link');
		} else {
			$this->tpl->setVariable('action', 'move');
			$this->tpl->touchBlock('move');
		}
		$this->tpl->setVariable('target', post('target'));

		$ids = $this->splitPath(post('target', '_'));

		$blocks = array();
		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($ids); $i++)
		{
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
		}
		$this->tpl->setVariable('target_title', $blocks[count($blocks) - 1]->getTitle());

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', $this->getBlockTypeTitle().': \''.$this->block->getTitle().'\'');

		$this->tpl->show();
	}

	function doExecuteCopy()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		if (post("cancel_copy_move", FALSE)) {
			redirectTo('container_block', array('action' => 'copy_move', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		$this->checkAllowed('copy', true);

		$blocks = array();
		$ids = $this->splitPath(get('working_path', '_'));
		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($ids); $i++) {
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
		}

		$targetIds = $this->splitPath(get('target', '_'));
		$bl = &$GLOBALS["BLOCK_LIST"];
		$target = $bl->getBlockById($targetIds[count($targetIds) - 1]);
		
		$this->checkAllowed('edit', true, $target);

		$containerPath = '';
		$wasRootBlock = false;
		if(!$blocks[count($blocks) -1]->isContainerBlock() && $target->isRootBlock()) {
			$wasRootBlock = true;
			$data['title'] = T('pages.block.root_container_block_new').' '.(count($target->getChildren()) + 1);
			if ($target = $target->createChild(BLOCK_TYPE_CONTAINER, $data)) {
				$containerPath = $target->getId().'_';
			}
			else
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data);
				redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}
		}
		$problems = array();
		$changedIds = array();
		if($newBlock = $blocks[count($blocks) - 1]->copyNode($target->getId(), $changedIds, $problems)) {
			if($wasRootBlock) {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
			}
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.copied_successfully', MSG_RESULT_POS, array('title' => $this->block->getTitle()));
			$modifications = array('title' => T('pages.block.copy_of').$newBlock->getTitle());
			$newBlock->modify($modifications);
		} else {
			$this->generateProblemMessages($problems);
			if($wasRootBlock) {
				$tmpRootBlock = new RootBlock(0);
				$problems2 = array();
				$tmpRootBlock->deleteChild($targetBlocks[count($targetBlocks) - 1]->getId(), $problems2);
			}
			redirectTo('container_block', array('action' => 'copy_move', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
		
		$ul = new UserList();
		$groups = $ul->getGroupList();

		foreach ($groups as $group) {
			$perms = $group->getPermissions($target);
			$newBlock->setPermissions($perms, $group);
		}
		
		if ($this->block instanceof ItemBlock)
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.copy_itemblock', array('source_title' => $this->block->getTitle())));
		if ($this->block instanceof FeedbackBlock)
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.copy_feedbackblock', array('source_title' => $this->block->getTitle())));
		if ($this->block instanceof ContainerBlock and ! $target->isRootBlock())
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.copy_containerblock', array('source_title' => $this->block->getTitle())));
		if ($this->block instanceof InfoBlock)
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.copy_infoblock', array('source_title' => $this->block->getTitle())));

		redirectTo('block_edit', array('working_path' => get('target').$containerPath.$newBlock->getId().'_', 'resume_messages' => 'true'));
	}

	function doExecuteMove()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		if (post("cancel_copy_move", FALSE)) {
			redirectTo('container_block', array('action' => 'copy_move', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('edit', true, $this->blocks[count($this->blocks) - 2]);

		$blocks = array();
		$ids = $this->splitPath(get('working_path', '_'));
		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($ids); $i++) {
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
		}

		$blockToMove = $GLOBALS["BLOCK_LIST"]->getBlockById($ids[count($ids) - 1]);
			
		$targetIds = $this->splitPath(get('target', '_'));
		$targetBlocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($targetIds); $i++) {
			$targetBlocks[] = $targetBlocks[count($targetBlocks) - 1]->getChildById($targetIds[$i]);
		}
		$this->checkAllowed('edit', true, $targetBlocks[count($targetBlocks) - 1]);
		$bl = &$GLOBALS["BLOCK_LIST"];
		$target = $bl->getBlockById($targetIds[count($targetIds) - 1]);

				
		$containerPath = '';
		$wasRootBlock = false;
		if(!$blocks[count($blocks) -1]->isContainerBlock() && $target->isRootBlock()) {
			$wasRootBlock = true;
			$data['title'] = T('pages.block.root_container_block_new').' '.(count($target->getChildren()) + 1);
			if ($target = $target->createChild(BLOCK_TYPE_CONTAINER, $data)) {
				$targetBlocks[] = $target;
				$containerPath = $target->getId().'_';
			}
			else
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data);
				redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}
		}
		$problems = array();
		if($newBlock = $bl->moveBlock($blocks[count($blocks) - 1], $ids[count($ids) - 2], $targetBlocks[count($targetBlocks) - 1], NULL, $problems)) {
			if($wasRootBlock) {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
			}
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.moved_successfully', MSG_RESULT_POS, array('title' => $this->block->getTitle()));
		} else {
			$this->generateProblemMessages($problems);
			if($wasRootBlock) {
				$tmpRootBlock = new RootBlock(0);
				$problems2 = array();
				$tmpRootBlock->deleteChild($targetBlocks[count($targetBlocks) - 1]->getId(), $problems2);
			}
			redirectTo('block_edit', array('working_path' => $workingPath, 'resume_messages' => 'true'));
		}
		//echo 'fu';
		//echo $wasRootBlock;
		//echo 'fa';
/*
		$targetNewBlock=$bl->getBlockById($blocks[count($blocks) - 1]);
	*/	
		if(!$blockToMove->isContainerBlock()){	
			$ul = new UserList();
			$groups = $ul->getGroupList();
	
			foreach ($groups as $group) {
				$perms = $group->getPermissions($target);
				$blockToMove->setPermissions($perms, $group);
			}
		}
			
		// Update structure caches
		if ($this->block instanceof ItemBlock) {
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.move_itemblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.move_itemblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
		}
		if ($this->block instanceof FeedbackBlock) {
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.move_feedbackblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.move_feedbackblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
		}
		if ($this->block instanceof ContainerBlock and ! $target->isRootBlock()) {
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.move_containerblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.move_containerblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
		}
		if ($this->block instanceof InfoBlock) {
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.move_infoblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.move_infoblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
		}

		redirectTo('block_edit', array('working_path' => get('target').$containerPath.$blocks[count($blocks) - 1]->getId().'_', 'resume_messages' => 'true'));
	}

	function doExecuteLink()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		if (post("cancel_copy_move", FALSE)) {
			redirectTo('container_block', array('action' => 'copy_move', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		$this->checkAllowed('link', true);

		$blocks = array();
		$ids = $this->splitPath(get('working_path', '_'));
		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($ids); $i++) {
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
		}

		$targetIds = $this->splitPath(get('target', '_'));
		$bl = &$GLOBALS["BLOCK_LIST"];
		$target = $bl->getBlockById($targetIds[count($targetIds)-1]);
		$this->checkAllowed('edit', true, $target);

		$containerPath = '';
		$wasRootBlock = false;
		if(!$blocks[count($blocks) -1]->isContainerBlock() && $target->isRootBlock()) {
			$wasRootBlock = true;
			$data['title'] = T('pages.block.root_container_block_new').' '.(count($target->getChildren()) + 1);
			if ($target = $target->createChild(BLOCK_TYPE_CONTAINER, $data)) {
				$targetIds[] = $target->getId();
				$containerPath = $target->getId().'_';
			}
			else
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data);
				redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}
		}
		$problems = array();
		if($blocks[count($blocks) - 1]->linkNode($targetIds[count($targetIds) - 1], $problems)) {
			if($wasRootBlock) {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
			}
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.linked_successfully', MSG_RESULT_POS, array('title' => $this->block->getTitle()));
		} else {
			$this->generateProblemMessages($problems);
			if($wasRootBlock) {
				$tmpRootBlock = new RootBlock(0);
				$problems2 = array();
				$tmpRootBlock->deleteChild($targetBlocks[count($targetBlocks) - 1]->getId(), $problems2);
			}
			redirectTo('container_block', array('action' => 'copy_move', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		// Update structure caches
		if ($this->block instanceof ItemBlock)
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.link_itemblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
		if ($this->block instanceof FeedbackBlock)
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.link_feedbackblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
		if ($this->block instanceof ContainerBlock and ! $target->isRootBlock())
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.link_containerblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));
		if ($this->block instanceof InfoBlock)
			TestStructure::storeCurrentStructure($targetIds[1], array('structure.link_infoblock', array('target_title' => $target->getTitle(), 'source_title' => $this->block->getTitle())));

		redirectTo('block_edit', array('working_path' => get('target').$containerPath.$blocks[count($blocks) - 1]->getId().'_', 'resume_messages' => 'true'));
	}

}

?>
