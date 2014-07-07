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
 * Loads the base class
 */
require_once(PORTAL.'BlockPage.php');

libLoad('html::defuseScripts');
libLoad('utilities::deUtf8');

/**
 * Displays the relevant pages for items
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class InfoPagePage extends BlockPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "edit";

	// HELPER FUNCTIONS

	function init($workingPath, $pageId)
	{
		$this->page = $this->getPage($this->block, $pageId);
		$this->workingPath = $workingPath;

		if (! $this->page) {
			$page = &$GLOBALS["PORTAL"]->loadPage('admin_start');
			$page->run();
			return FALSE;
		}

		if ($this->page->getDisabled()) $this->makeReadOnly();

		$this->tpl->setGlobalVariable('item_id', $pageId);
		$this->tpl->setGlobalVariable('working_path', $workingPath);

		return TRUE;
	}

	function initTemplate($activeTab, $itemId)
	{
		$tabs = array(
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("info_page", array("action" => "edit", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"delete" => array("title" => "tabs.test.delete.textpage", "link" => linkTo("info_page", array("action" => "confirm_delete", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"conditions" => array("title" => "tabs.test.conditions", "link" => linkTo("info_page", array("action" => "conditions", "working_path" => $this->workingPath, "item_id" => $itemId))),
		);

		$disabledTabs = array();
		if (!$this->mayEdit) {
			$disabledTabs[] = 'delete';
		}

		$this->initTabs($tabs, $activeTab, $disabledTabs);

		if($this->block->hasMultipleParents() && $activeTab != 'edit_perms' && $activeTab != 'copy_move') {
			$this->tpl->touchBlock("block_multi_linked");
			$parents = $this->getAllParents($this->block, array(), array());
			for($i = 0;$i < count($parents); $i++)
			{
				$this->tpl->setVariable('path', '_'.implode('_',$parents[$i]['path']).'_');
				$this->tpl->setVariable('title', implode('&gt;',$parents[$i]['titles']));
				$this->tpl->parse('linked_parent');
			}
		}
	}

	function setupAdminMenu()
	{
		if (isset($this->workingPath) && $this->mayCreate)
		{
			$this->tpl->setVariable('page', 'info_block');
			$this->tpl->setVariable('name', 'action');
			$this->tpl->setVariable('value', 'create_info_page');
			$this->tpl->parse('menu_admin_item_additional_info');

			$this->tpl->setVariable('name', 'working_path');
			$this->tpl->setVariable('value', $this->workingPath);
			$this->tpl->parse('menu_admin_item_additional_info');

			$this->tpl->setVariable('title', T('pages.info_block.new_info_page'));
			$this->tpl->parse('menu_admin_item');
		}
	}

	function getPage($block, $id)
	{
		if (! $block) {
			return NULL;
		}

		if (! $this->block->isInfoBlock() || ! $this->block->existsTreeChild($id))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.info_page_invalid', MSG_RESULT_NEG);
			return NULL;
		}

		$page = $block->getTreeChildById($id);

		return $page;
	}

	// ACTIONS

	function doEdit()
	{
		// include fckeditor
		require_once(PORTAL.'Editor.php');

		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('view', true);

		$this->tpl->loadTemplateFile("EditInfoPage.html");
		$this->initTemplate("edit", $id);

		// create FCKeditor
		$editor = new Editor('working_path='.$workingPath.'&item_id='.$id, $this->page->getContent());

		$this->tpl->setVariable('content', $editor->CreateHtml());
		$this->tpl->setVariable('title', $this->page->getTitle(false));
		$this->tpl->setVariable('t_modified', $this->page->getModificationTime());
		$this->tpl->setVariable('t_created', $this->page->getCreationTime());

		if ($this->mayEdit) {
			$this->tpl->touchBlock('form_submit_button');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.info_page.info_page'));

		$this->tpl->show();
	}

	
	
	
	function doSave()
	{
		$workingPath = get('working_path');
		$id = get('item_id');
		
		if ($id  == NULL) {
			sendXMLStatus(T('pages.paragraphs.empty_request'), array('type' => 'fail'));			
		}

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$modifications['title'] = post('title');
		$modifications['content'] = defuseScripts(deUtf8(post('fckcontent', '')));

		if($this->page->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->page->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->page->getTitle())));
		}

		redirectTo('info_page', array('action' => 'edit', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doConfirmDelete()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("DeleteInfoPage.html");
		$this->initTemplate("delete", $id);

		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests))
			$this->tpl->touchBlock('delete_not_possible');

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.info_page.info_page'));

		$this->tpl->show();
	}

	
	
	
	function doDelete()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);
		
		$pathIds = explode("_",$workingPath);

		if (post("cancel_delete", FALSE)) {
			redirectTo('info_page', array('action' => 'edit', 'working_path' => $workingPath, 'item_id' => $id, 'resume_messages' => 'true'));
		}

		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests)) {
			$this->page->modify(array('disabled' => 1));
			//foreach ($tests as $test) {
				TestStructure::storeCurrentStructure($pathIds[2], array('structure.disable_infopage', array('title' => $this->page->getTitle())));
			//}
			$GLOBALS['MSG_HANDLER']->addMsg('pages.disable_object.success', MSG_RESULT_POS, array('title' => $this->page->getTitle()));
		} elseif ($this->block->deleteTreeChild($id)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.deleted_success', MSG_RESULT_POS, array('title' => htmlentities($this->page->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.deleted_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->page->getTitle())));
		}

		redirectTo('info_block', array('working_path' => $workingPath, 'resume_messages' => 'true'));
	}
	
	
	
	
	function doConditions()
	{

		$workingPath = get('working_path');

		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditInfoConditions.html");
		$this->initTemplate("conditions", $id);

		libLoad("PEAR");
		require_once("Services/JSON.php");
		$json = new Services_JSON();

		$checkedOn = 'checked="checked"';
		$checkedOff = '';
		$selectedOn = 'selected="selected"';
		$selectedOff = '';

		$this->tpl->setVariable('resource_url', linkTo("info_page", array("action" => "get_resource", "working_path" => $workingPath, "item_id" => $id)));

		$needsAll = $this->page->getNeedsAllConditions();

		$this->tpl->setVariable('conditions_all_1_checked', $needsAll ? $checkedOn : $checkedOff);
		$this->tpl->setVariable('conditions_all_0_checked', $needsAll ? $checkedOff : $checkedOn);
		$this->tpl->setVariable('conditions_all_1_selected', $needsAll ? $selectedOn : $selectedOff);
		$this->tpl->setVariable('conditions_all_0_selected', $needsAll ? $selectedOff : $selectedOn);

		$this->tpl->setVariable('block_id', $this->blocks[count($this->blocks)-1]->getId());

		// Find the root of the tree - The nearest parent that is linked multiple times or the test itself
		// Conditions are restricted to items within this block
		$parents = array($this->block);
		do {
			$treeRoot = $parents[0];
			$parents = $treeRoot->getParents();
		} while (count($parents) == 1 && ! $parents[0]->isRootBlock());

		require_once(CORE."types/TestSelector.php");
		$testSelector = new TestSelector($treeRoot);
		$itemBlockIds = $testSelector->getParentTreeBlockIds();
		


		$activeItemBlock = $this->blocks[count($this->blocks)-1];
		$activeItemBlockId = $activeItemBlock->getId();

		$itemBlocks = array();

		foreach ($itemBlockIds as $itemBlockId) {
			$block = $GLOBALS["BLOCK_LIST"]->getBlockById($itemBlockId);
			if ($block->isItemBlock() == true)
				$itemBlocks[] = array(utf8_encode(shortenString($block->getTitle(),26)), $block->getId());
			if ($block->getId() == $activeItemBlockId) {
				break;
			}
		}
		

		$this->tpl->setVariable('preload_item_blocks', $json->encode($itemBlocks));

		$activeItemId = $this->page->getId();
		$activeItemId = NULL;

		$items = array();
		foreach ($activeItemBlock->getTreeChildren() as $item) {
			$items[] = $item->getId();
		}

		$conditions = $this->page->getConditions();
		$conditions = $this->convertConditionsToJavaScript($conditions);

		$preload = array(
			"item_block_id" => array(),
			"item_id" => array(),
		);

		foreach ($conditions as $condition)
		{
			foreach ($condition as $name => $value) {
				if (isset($preload[$name])) {
					$preload[$name][] = $value;
				}
			}
		}

		$this->tpl->setVariable("conditions", $json->encode($conditions));

		foreach (array_unique($preload["item_block_id"]) as $itemBlockId)
		{
			if($itemBlockId !== NULL) {
				$resource = $this->getResource("items", $itemBlockId);
				$this->tpl->setVariable("preload_item_block_id", $itemBlockId);
				$this->tpl->setVariable("preload_items", $json->encode($resource));
				$this->tpl->parse("add_items_js");
			}
		}

		foreach (array_unique($preload["item_id"]) as $itemId) {
			if ($itemId !== NULL) {
				$resource = $this->getResource("answers", $itemId);
				$this->tpl->setVariable("preload_item_id", $itemId);
				$this->tpl->setVariable("preload_answers", $json->encode($resource));
				$this->tpl->parse("add_answers_js");
			}
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->page->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	
	
	function doSaveConditions()
	{

		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->page->setNeedsAllConditions(post('conditions_all') ? TRUE : FALSE);

		$oldConditions = $this->page->getConditions();
		$newConditions = post("conditions", array());

		$newConditions = $this->convertConditionsToPhp($newConditions);

		$itemBlock = $this->page->getParent();

		$itemsPerPage = $itemBlock->getItemsPerPage();
		$dontSave = false;
;
		foreach($newConditions as $condition)
		{
			if($condition['id'] == NULL && $condition['item_block_id'] == NULL) continue;
			elseif($itemBlock->getId() == $condition['item_block_id']) $dontSave = true;
		}
		if(!empty($newConditions) && $itemsPerPage > 1 && $dontSave)
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.conditions.block_has_multiple_items_per_page', MSG_RESULT_NEG);
			redirectTo('info_page', array('action' => 'conditions', 'item_id' => $id, 'working_path' => $workingPath));
		}

		$newIds = array();
		foreach ($newConditions as $condition) {
			if ($condition["id"] != 0) {
				$newIds[] = $condition["id"];
			}
		}

		$deletedIds = array();
		foreach ($oldConditions as $condition) {
			if (! in_array($condition["id"], $newIds)) {
				$deletedIds[] = $condition["id"];
			}
		}

		$this->page->deleteConditions($deletedIds);
		
		foreach ($newConditions as $position => $condition)
		{
			if ($condition["id"] != 0) {
				$this->page->updateCondition($condition, $position);
			} else {
				$this->page->addCondition($condition, $position);
			}
		}

		$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.conditions.saved', MSG_RESULT_POS);

		redirectTo('info_page', array('action' => 'conditions', 'item_id' => $id, 'working_path' => $workingPath));
	}
	
	
	function convertConditionsToJavaScript($conditions)
	{
		for ($i = 0; $i < count($conditions); $i++) {
			if (isset($conditions[$i]["chosen"])) {
				$conditions[$i]["chosen"] = $conditions[$i]["chosen"] ? "yes" : "no";
			}
		}

		return $conditions;
	}
	
	
	
	function doGetResource()
	{
		$resource = get("resource");
		$parentId = get("parent_id");
		$workingPath = get('working_path');
		$id = get('item_id');
		if (! $this->init($workingPath, $id)) {
			return;
		}
		
		libLoad("PEAR");
		require_once("Services/JSON.php");
		$json = new Services_JSON();
		
		if (is_numeric($parentId)) {
			echo $json->encode($this->getResource($resource, $parentId));
		}
	}
	
	function getResource($resource, $parentId)
	{
		$result = array();

		if ($resource == "items")
		{
			$itemBlock = @$GLOBALS["BLOCK_LIST"]->getBlockById($parentId);
			if ($itemBlock && $itemBlock->isItemBlock()) {
				foreach ($itemBlock->getTreeChildren() as $item) {
					if ($item->getId() == $this->page->getId()) {
						break;
					}
					if (!$item->hasSimpleAnswer()) continue;
					$result[] = array(utf8_encode(shortenString($item->getTitle(FALSE), 26)), $item->getId());
				}
			}
		}
		elseif ($resource == "answers")
		{
			if ($item = @new Item($parentId)) {
				foreach ($item->getChildren() as $answer) {
					$result[] = array(utf8_encode(shortenString($answer->getTitle(FALSE), 26)), $answer->getId());
				}
			}
		}

		return $result;
	}
	
	function convertConditionsToPhp($conditions)
	{
		$requiredKeys = array("answer_id", "chosen", "id", "item_block_id", "item_id");

		// Verify the array keys of the conditions
		foreach (array_keys($conditions) as $i)
		{
			$keys = array_keys($conditions[$i]);
			// Delete unknown keys
			foreach ($keys as $key) {
				if (! in_array($key, $requiredKeys)) {
					unset($conditions[$i][$key]);
				}
			}
			// Add missing keys
			foreach ($requiredKeys as $key) {
				if (! in_array($key, $keys)) {
					$conditions[$i][$key] = NULL;
				}
			}
		}
		// Correct indizes
		$conditions = array_values($conditions);

		foreach (array_keys($conditions) as $i) {
			foreach ($conditions[$i] as $name => $value) {
				if ($value === "") {
					$conditions[$i][$name] = NULL;
				}
				if (is_numeric($value)) {
					$conditions[$i][$name] = (int)$value;
				}
			}
			if (isset($conditions[$i]["chosen"])) {
				$conditions[$i]["chosen"] = ($conditions[$i]["chosen"] == "yes");
			}
		}

		return $conditions;
	}
}
?>
