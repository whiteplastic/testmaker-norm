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
libLoad('utilities::getCorrectionMessage');

/**
 * Displays the relevant pages for item blocks
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class ItemBlockPage extends BlockPage
{
	//todo: set to 'edit' or 'info' if user is allowed or not to change
	/**
	 * @access private
	 */
	var $defaultAction = "edit";

	// HELPER FUNCTIONS

	function init($workingPath)
	{

		if (! $this->block) {
			$page = &$GLOBALS["PORTAL"]->loadPage('admin_start');
			$page->run();
			return FALSE;
		}

		if (! $this->block->isItemBlock()) {
			$page = &$GLOBALS["PORTAL"]->loadPage('block_edit');
			$page->run();
			return FALSE;
		}

		$this->tpl->setGlobalVariable('working_path', $workingPath);

		$this->targetPage = 'item_block';
		return TRUE;
	}

	function initTemplate($activeTab)
	{
		$tabs = array(
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("item_block", array("action" => "edit", "working_path" => $this->workingPath))),
			"defaults_edit" => array("title" => "tabs.test.default.edit", "link" => linkTo("item_block", array("action" => "edit_defaults", "working_path" => $this->workingPath))),
			"default_answers" => array("title" => "tabs.test.default.answers", "link" => linkTo("item_block", array("action" => "default_answers", "working_path" => $this->workingPath))),
			"template" => array("title" => "tabs.test.default.template", "link" => linkTo("item_block", array("action" => "select_template", "working_path" => $this->workingPath))),
			"conditions" => array("title" => "tabs.test.conditions.standard", "link" => linkTo("item_block", array("action" => "conditions", "working_path" => $this->workingPath, 
			"block_id" => $this->block->getId() ))),
			"organize" => array("title" => "tabs.test.structure", "link" => linkTo("item_block", array("action" => "organize", "working_path" => $this->workingPath))),
			"copy_move" => array("title" => "tabs.test.copy_move", "link" => linkTo("item_block", array("action" => "copy_move", "working_path" => $this->workingPath))),
			"edit_perms" => array("title" => "tabs.test.perms", "link" => linkTo("item_block", array("action" => "edit_perms", "working_path" => $this->workingPath))),
			"delete" => array("title" => "tabs.test.delete.itemblock", "link" => linkTo("item_block", array("action" => "confirm_delete", "working_path" => $this->workingPath))),
		);

		$disabledTabs = array();

		$owner = $this->block->getOwner();
		$user = $GLOBALS['PORTAL']->getUser();
		if ($owner->getId() != $user->getId() && !$this->checkAllowed('admin', false, NULL)) {
			$disabledTabs[] = 'edit_perms';
		}
		if (!$this->mayEdit) {
			$disabledTabs[] = 'default_answers';
			$disabledTabs[] = 'defaults_edit';
			$disabledTabs[] = 'template';
			$disabledTabs[] = 'organize';
			$disabledTabs[] = 'edit_perms';

			if (!$this->mayCopy && !$this->mayLink) {
				$disabledTabs[] = 'copy_move';
			}
		}
		if (!$this->mayDelete) {
			$disabledTabs[] = 'delete';
		}

		if (count($this->block->getTreeChildren()) <= 1) {
			$disabledTabs[] = "organize";
		}

		$this->initTabs($tabs, $activeTab, $disabledTabs);

		require_once(CORE."types/TestRunList.php");
		$path = $this->splitPath($this->workingPath);

		if($this->block->hasMultipleParents() && $activeTab != 'edit_perms') {
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
		if (isset($this->workingPath) && $this->mayCreate && $this->mayEdit)
		{
			$this->tpl->setVariable('page', 'item_block');
			$this->tpl->setVariable('name', 'action');
			$this->tpl->setVariable('value', 'create_item');
			$this->tpl->parse('menu_admin_item_additional_info');

			$this->tpl->setVariable('name', 'working_path');
			$this->tpl->setVariable('value', $this->workingPath);
			$this->tpl->parse('menu_admin_item_additional_info');

			$this->tpl->setVariable('title', T('pages.item_block.new_item'));
			$this->tpl->parse('menu_admin_item');
		}
	}

	// ACTIONS

	function doEdit()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("EditItemBlock.html");
		$this->initTemplate("edit");

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.substr($key, (strrpos($key, '.') + 1)));
		}

		$this->tpl->setVariable('t_modified', $this->block->getModificationTime());
		$this->tpl->setVariable('t_created', $this->block->getCreationTime());
		$owner = $this->block->getOwner();
		$this->tpl->setVariable('author', $owner->getUsername() .' ('. $owner->getFullname() .')');
		$this->tpl->setVariable('media_connect_id', $this->block->getMediaConnectId());

		if(get('revert') == 'true') {
			$this->tpl->setVariable('description', post('description'));
			$this->tpl->setVariable('title', post('title'));
			if (post('adaptive')) {
				$this->tpl->setVariable('adaptive', 'checked="checked"');
				$this->tpl->setVariable('adaptive_disabled', 'disabled="disabled"');
				$this->tpl->setVariable('irt_disabled', 'disabled="disabled"');
				$this->tpl->setVariable('ipp_disabled', 'disabled="disabled"');
				$this->tpl->setVariable('max_items', post('max_items'));
				$this->tpl->setVariable('max_sem', post('max_sem'));
			} else {
				$this->tpl->setVariable('description', $this->block->getDescription());
				$this->tpl->setVariable('adaptive_enabled', 'disabled="disabled"');
				if (post('irt')) {
					$this->tpl->setVariable('irt', 'checked="checked"');
				}
				$this->tpl->setVariable('ipp', post('ipp'));
				$this->tpl->setVariable('max_items', $this->block->getMaxItems());
				$this->tpl->setVariable('max_sem', $this->block->getMaxSem());
			}
		} else {
			$this->tpl->setVariable('description', $this->block->getDescription());
			$this->tpl->setVariable('title', $this->block->getTitle());
			if ($this->block->isAdaptiveItemBlock()) {
				$this->tpl->setVariable('adaptive', 'checked="checked"');
				$this->tpl->setVariable('adaptive_disabled', 'disabled="disabled"');
				$this->tpl->setVariable('irt_disabled', 'disabled="disabled"');
				$this->tpl->setVariable('ipp_disabled', 'disabled="disabled"');
			} else {
				$this->tpl->setVariable('adaptive_enabled', 'disabled="disabled"');
				if ($this->block->isIRTBlock()) {
					$this->tpl->setVariable('irt', 'checked="checked"');
				}
				if ($this->block->getItemsPerPage()) {
					$this->tpl->setVariable('ipp', $this->block->getItemsPerPage());
					$this->tpl->setVariable('adaptive', 'disabled="disabled"');
				}
			}
			$this->tpl->setVariable('max_items', $this->block->getMaxItems());
			$this->tpl->setVariable('max_sem', $this->block->getMaxSem());
			$random = $this->block->hasRandomItemOrder() ? 'checked="checked"' : '';
			$this->tpl->setVariable('random_order_checked', $random);
		}

		if ($this->block->isPublic(false)) {
			$this->tpl->setVariable('access', T('pages.block.access_public'));
		} else {
			$this->tpl->setVariable('access', T('pages.block.access_private'));
		}

		if ($this->mayEdit) {
			$this->tpl->touchBlock('form_submit_button');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.item').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doEditDefaults()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("EditDefaultsItemBlock.html");
		$this->initTemplate("defaults_edit");

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}

		if(get('revert') == 'true') {
			$this->tpl->setVariable('default_max_item_time', post('default_max_item_time'));
			$this->tpl->setVariable('default_min_item_time', post('default_min_item_time'));
			$this->tpl->setVariable('max_time', post('max_time', 0));
			if(post('default_item_force')) {
				$this->tpl->touchBlock('default_item_force_checked');
			}
		} else {
			$this->tpl->setVariable('default_max_item_time', $this->block->getDefaultMaxItemTime());
			$this->tpl->setVariable('default_min_item_time', $this->block->getDefaultMinItemTime());
			$this->tpl->setVariable('max_time', $this->block->getMaxTime());
			if($this->block->isDefaultItemForced()) {
				$this->tpl->touchBlock('default_item_force_checked');
			}
		}
		if($this->block->isAdaptiveItemBlock())
		{
			$this->tpl->setVariable('adaptive_disabled', 'disabled="disabled"');
		}
		
		$this->tpl->setVariable('intro_label', $this->block->getIntroLabel());
		
		// include fckeditor
		require_once(PORTAL.'Editor.php');

		// create FCKeditor
		$editor = new Editor('working_path='.$workingPath, $this->block->getIntroduction());
		$this->tpl->setVariable('introduction', $editor->CreateHtml());

		if ($this->block->isHiddenIntro())
		{
			$this->tpl->setVariable('hidden_intro', 'checked="checked"');
		}
		
		if ($this->block->isIntroFirstOnly())
		{
			$this->tpl->setVariable('intro_firstonly', 'checked="checked"');
		}
		
		$this->tpl->setVariable($this->block->getIntroPos() ? 'up' : 'down', 'checked="checked"');

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.item').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doDefaultAnswers()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$answers = $this->block->getDefaultAnswers();
		$answers_string = '';
		for ($i = 0; $i < count($answers); $i++)
		{
			$this->tpl->loadTemplateFile("EditAnswerParagraphView.html");

			$this->tpl->setVariable("para_contents", $answers[$i]->getAnswer());
			$this->tpl->setVariable("para_id", $answers[$i]->getId());

			$this->tpl->setVariable("edit_link", linkTo('item_block', array('action' => 'edit_answer', 'working_path' => $workingPath, true, true)));
			$this->tpl->setVariable("del_link", linkTo('item_block', array('action' => 'delete_answer', 'working_path' => $workingPath, true, true)));
			$this->tpl->touchBlock("para_may_edit");
			$answers_string .= trim($this->tpl->get());
		}

		$this->tpl->loadTemplateFile("EditItemBlockAnswers.html");
		$this->initTemplate('default_answers');
		$this->tpl->setVariable("paragraphs", $answers_string);
		$this->tpl->setVariable("add_link", linkTo('item_block', array('action' => 'edit_answer', 'working_path' => $workingPath, false, true)));

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->block->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}
	
	
	function doConditions()
	{
		$workingPath = get('working_path');
		$id = get('block_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditBlockConditions.html");
		$this->initTemplate("conditions", $id);

		libLoad("PEAR");
		require_once("Services/JSON.php");
		$json = new Services_JSON();

		$checkedOn = 'checked="checked"';
		$checkedOff = '';
		$selectedOn = 'selected="selected"';
		$selectedOff = '';

		$childItems = $this->block->getTreeChildren();
		
		$needsAll = $this->block->getNeedsAllConditions();

		//$this->tpl->setVariable('resource_url', linkTo("item_block", array("action" => "get_resource", "working_path" => $workingPath, "item_id" => $childItems[0]->getId())));
		
		$this->tpl->setVariable('resource_url', linkTo("item_block", array("action" => "get_resource", "working_path" => $workingPath, "block_id" => $this->block->getId())));

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
		$itemBlockIds = $testSelector->getParentTreeBlockIds(BLOCK_TYPE_ITEM);

		$activeItemBlock = $this->blocks[count($this->blocks)-1];
		$activeItemBlockId = $activeItemBlock->getId();

		$itemBlocks = array();

		foreach ($itemBlockIds as $itemBlockId) {
			$block = $GLOBALS["BLOCK_LIST"]->getBlockById($itemBlockId);		
			if (($block->isItemBlock() == true) AND (!$block->getDisabled()))
				$itemBlocks[] = array(utf8_encode(shortenString($block->getTitle(),26)), $block->getId());
			if ($block->getId() == $activeItemBlockId) {
				break;
			}
		}
  
		$this->tpl->setVariable('preload_item_blocks', $json->encode($itemBlocks));

		$activeItemId = $childItems[0]->getId();
		$activeItemId = NULL;

		$items = array();
		foreach ($activeItemBlock->getTreeChildren() as $item) {
			$items[] = $item->getId(); 
		}

		if ($this->block->hasDefaultConditions() == TRUE)
			$conditions =  $this->block->getConditions();
		else
			$conditions = array();
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
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->block->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}
	

	
	function doSaveConditions($conditions = NULL, $conditions_all = NULL, $overwrite = FALSE)
	{
		$workingPath = get('working_path');
		$id = get('block_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		if(isset($_POST['overwrite']))
			$overwrite = TRUE;
		
		if(isset($_POST['cancel']))
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.conditions.notoverwritten', MSG_RESULT_NEG);	
	
		if($conditions_all == NULL)
			$conditions_all = get('conditions_all');
		
		$this->block->setNeedsAllConditions($conditions_all ? TRUE : FALSE);

		if(isset($_SESSION["conditions"])){
			$conditions = $_SESSION["conditions"];
			unset($_SESSION["conditions"]);
		}

		$newConditions = $conditions;
	
		$oldConditions = $this->block->getConditions();		
		$newConditions = $this->convertConditionsToPhp($newConditions);
		
		$itemsPerPage = $this->block->getItemsPerPage();
		$dontSave = false;
	
		foreach($newConditions as $condition)
		{
			if($condition['id'] == NULL && $condition['item_block_id'] == NULL) continue;
			elseif($this->block->getId() == $condition['item_block_id']) $dontSave = true;
		}
		
		if(!empty($newConditions) && $itemsPerPage > 1 && $dontSave)
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.conditions.block_has_multiple_items_per_page', MSG_RESULT_NEG);
			redirectTo('item', array('action' => 'conditions', 'item_id' => $id, 'working_path' => $workingPath));
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
		$this->block->deleteConditions($deletedIds);

		foreach ($newConditions as $position => $condition)
		{
			if ($condition["id"] != 0) {
				$this->block->updateCondition($condition, $position);
				$this->block->setDefaultConditions(TRUE);
			} else {
				$this->block->addCondition($condition, $position);
				$this->block->setDefaultConditions(TRUE);
			}
		}

		if ($overwrite) {
			$children = $this->block->getTreeChildren();		

			foreach ($children as $item) {
				$item->setNeedsAllConditions($conditions_all ? TRUE : FALSE);
				$oldConditions = $item->getConditions();

				$deletedIds = array();
				foreach ($oldConditions as $condition) {
					$deletedIds[] = $condition["id"];
				}
				
				$item->deleteConditions($deletedIds);

				$newIds = array();
			
				foreach ($newConditions as $position => $condition)
				{
					$item->addCondition($condition, $position);
				}
			}
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.conditions.overwritten', MSG_RESULT_POS);	
		}
		
		$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.conditions.saved', MSG_RESULT_POS);
		redirectTo('item_block', array('action' => 'conditions', 'block_id' => $this->block->getId(), 'working_path' => $workingPath));
	}
	
	function doConfirmConditions()
	{
		$workingPath = get('working_path');
		$overwrite_conditions = post('overwrite');
		$conditions_all = post('conditions_all');
		$newConditions = post("conditions", array());
		$id = get('block_id');

		if ($overwrite_conditions) {
			$_SESSION["conditions"] = $newConditions;
		
			$this->tpl->loadTemplateFile("ConfirmBlockConditions.html");
			$this->tpl->setVariable("working_path", $workingPath);
			$this->tpl->setVariable("conditions_all", $conditions_all);
			$this->initTemplate("item_conditions", $id);
			$body = $this->tpl->get();
			$this->loadDocumentFrame();
			$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->block->getTitle()."'");
			$this->tpl->setVariable('body', $body);

			$this->tpl->show();			
			//$this->doSaveConditions($newConditions, $conditions_all, TRUE);
		}
		else 
			$this->doSaveConditions($newConditions, $conditions_all, FALSE);
		
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
		$workingPath = get('working_path');
		$parentId = get("parent_id");

		if (! $this->init($workingPath)) {
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
					/*if ($item->getId() == $this->item->getId()) {
						break;
					}*/
					if ($item->getDisabled()) continue;
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

	
	function doSelectTemplate()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->tpl->loadTemplateFile("SelectDefaultItemTemplate.html");
		$this->initTemplate("template");

		$activeFile = $this->block->getDefaultItemType();
		$template_cols = $this->block->getDefaultTemplateCols();
		$template_align = $this->block->getDefaultTemplateAlign();

		$items = array();

		require_once(CORE.'types/ItemTemplate.php');
		$itemTemplateObj = new ItemTemplate();
		// get a list of the templates pre-filtered by the items per page of this block
		$items = $itemTemplateObj->getList($this->block->getItemsPerPage());
		$templateDir = $itemTemplateObj->getTemplateDir();

		ksort($items);
		$itemsPerPage = $this->block->getItemsPerPage();

		$cols = 3;
		$cells = 0;
		foreach ($items as $name => $data)
		{
			$modelVars = get_class_vars($name);
			if(($itemsPerPage > 1)  && array_key_exists('enableWithMultipleItems', $modelVars) && !$modelVars['enableWithMultipleItems']) continue;			
			$thumb = str_replace('.html', '.png', $modelVars['templateFile']);
			if (file_exists($templateDir.$thumb)) {
				$thumbnail = "upload/items/".$thumb;
			} else {
				$thumbnail = "portal/images/default_template_thumbnail.png";
			}

			$this->tpl->setVariable('title', $name);
			$this->tpl->setVariable('desc', $data['description'][$GLOBALS['TRANSLATION']->getLanguage()]);
			$this->tpl->setVariable('thumbnail', $thumbnail);
			$this->tpl->setVariable('filename', $name);

			$this->tpl->hideBlock("active_template_cell");
			$this->tpl->hideBlock("inactive_template_cell");
			$this->tpl->hideBlock("empty_template_cell");

			if($activeFile == $name) {
				$this->tpl->touchBlock("active_template_cell");
			} else {
				$this->tpl->touchBlock("inactive_template_cell");
			}

			$this->tpl->parse('template_cell');

			if (($cells+1) % $cols == 0) {
				$this->tpl->parse('template_row');
			}
			$cells++;
		}
		while ($cells % $cols != 0) {
			$this->tpl->touchBlock('empty_template_cell');
			$this->tpl->parse('template_cell');
			$cells++;
		}
		$this->tpl->setVariable('column', $template_cols);
		if ($template_align == 'v')
		{
			$this->tpl->touchBlock('vertical_active');
			$this->tpl->hideBlock('horizontal_active');
		}
		else
		{
			$this->tpl->touchBlock('horizontal_active');
			$this->tpl->hideBlock('vertical_active');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.item').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doSave()
	{
		$workingPath = get('working_path');

		$checkValues = array(
			array('forms.title', post('title'), array(CORRECTION_CRITERION_LENGTH_MIN => 1)),
		);

		if (! $this->init($workingPath)) {
			return;
		}

		$modifications['type'] = post('adaptive') ? 1 : 0;
		if(post('adaptive')) {
			$checkValues = array_merge($checkValues, array(
				array('forms.item_block.max_items', post('max_items'), array(CORRECTION_CRITERION_NUMERIC => NULL)),
				array('forms.item_block.max_sem', post('max_sem'), array(CORRECTION_CRITERION_NUMERIC => NULL)),
			));
			$modifications['max_items'] = post('max_items');
			$modifications['max_sem'] = post('max_sem');
			$modifications['irt'] = 1;
		} else {
			$modifications['irt'] = post('irt') ? 1 : 0;
		}
		$modifications['items_per_page'] = post('items_per_page');
		$modifications['title'] = post('title');
		$modifications['description'] = post('description');
		$modifications['random_order'] = post('random_order');
		
		// Do not save modifications to items_per_page when the items have display conditions
		if(post('items_per_page', 1) > 1)
		{
			$conditions = $this->block->getItemDisplayConditions();
			$dontSaveCond = false;
			$dontSaveType = false;
			foreach($conditions as $condition)
			{
				if($condition['owner_block_id'] == $condition['item_block_id']) $dontSaveCond = true;
			}
			if($dontSaveCond)
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.items_have_conditions', MSG_RESULT_NEG);
				redirectTo('item_block', array('action' => 'edit', 'working_path' => $workingPath));
			}

			$defaultItemType = $this->block->getDefaultItemType();
			if($defaultItemType != "McsaQuickItem")
			{
				$children = $this->block->getTreeChildren();
				foreach($children as $index => $child)
				{
					if($child->getTemplate() == "McsaQuickItem")
					{
						$dontSaveType = true;
					}
				}
			} else
			{
				$dontSaveType = true;
			}
			if($dontSaveType)
			{
				$GLOBALS['MSG_HANDLER']->addMsg("pages.block.msg.incompatible_template", MSG_RESULT_NEG);
				redirectTo('item_block', array('action' => 'edit', 'working_path' => $workingPath)); 
			}
		}

		$GLOBALS["MSG_HANDLER"]->flushMessages();
		for($i = 0; $i < count($checkValues); $i++) {
			$tmp = getCorrectionMessage(T($checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
			if(count($tmp) > 0) {
				$this->correctionMessages[$checkValues[$i][0]] = $tmp;
			}
		}

		if(count($this->correctionMessages) > 0) {
			$this->doEdit();
			return;
		}

		if ($this->block->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->block->getTitle())));
		}

		$newDefaultAnswers = post('new_default_answers');
		for ($i = 0; $i < ((int) post('counter')); $i++) {
			$optionalInfos['answer'] = $newDefaultAnswers["$i"];
			$this->block->createDefaultAnswer(NULL, $optionalInfos);
		}

		$defaultAnswers = post('default_answers');
		$defaultAnswersDelete = post('default_answers_delete');
		if (isset($defaultAnswers) && is_array($defaultAnswers)) {
			for (reset($defaultAnswers); list($id, $value) = each($defaultAnswers);) {
				if (isset($defaultAnswersDelete[$id]) && $defaultAnswersDelete[$id]) {
					$this->block->deleteDefaultAnswer($id);
				} else {
					$modifications['answer'] = $value;
					$defaultAnswer = $this->block->getDefaultAnswerById((int) $id);
					$defaultAnswer->modify($modifications);
				}
			}
		}

		redirectTo('item_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doSaveDefaults()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$checkValues = array(
			array('default_max_item_time', post('default_max_item_time'), array(CORRECTION_CRITERION_NUMERIC => NULL)),
			array('default_min_item_time', post('default_min_item_time'), array(CORRECTION_CRITERION_NUMERIC_MAX_NOTNULL => post('default_max_item_time'))),
			array('max_time', post('max_time'), array(CORRECTION_CRITERION_NUMERIC => NULL))
		);


		$modifications['max_time'] = post('max_time');
		$modifications['default_max_item_time'] = post('default_max_item_time');
		$modifications['default_min_item_time'] = post('default_min_item_time');
		$modifications['default_item_force'] = post('default_item_force') ? true : false;
		$modifications['introduction'] = post('fckcontent');
		$modifications['intro_label'] = post('intro_label');
		
		if (!post('hidden_intro')) {
			$modifications['hidden_intro'] = 0;
		}
		else {
			$modifications['hidden_intro'] = 1;
		}
		
		if (!post('intro_firstonly')) {
			$modifications['intro_firstonly'] = 0;
		}
		else {
			$modifications['intro_firstonly'] = 1;
		}
		
		$modifications['intro_pos'] = post('intro_pos', 0);

		$GLOBALS["MSG_HANDLER"]->flushMessages();
		for($i = 0; $i < count($checkValues); $i++) {
			$tmp = getCorrectionMessage(T('forms.item_block.'.$checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
			if(count($tmp) > 0) {
				$this->correctionMessages[$checkValues[$i][0]] = $tmp;
			}
		}

		if(count($this->correctionMessages) > 0) {
			$this->doEditDefaults();
			return;
		}

		if ($this->block->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->block->getTitle())));
		}

		$newDefaultAnswers = post('new_default_answers');
		for ($i = 0; $i < ((int) post('counter')); $i++) {
			$optionalInfos['answer'] = $newDefaultAnswers["$i"];
			$this->block->createDefaultAnswer(NULL, $optionalInfos);
		}

		$defaultAnswers = post('default_answers');
		$defaultAnswersDelete = post('default_answers_delete');
		if (isset($defaultAnswers) && is_array($defaultAnswers)) {
			for (reset($defaultAnswers); list($id, $value) = each($defaultAnswers);) {
				if (isset($defaultAnswersDelete[$id]) && $defaultAnswersDelete[$id]) {
					$this->block->deleteDefaultAnswer($id);
				} else {
					$modifications['answer'] = $value;
					$defaultAnswer = $this->block->getDefaultAnswerById((int) $id);
					$defaultAnswer->modify($modifications);
				}
			}
		}
		if (post("overwrite_min_item_time") == "true" || post("overwrite_max_item_time") == "true" || post("overwrite_item_force") == "true") {
			redirectTo('item_block', array('action' => 'confirm_overwrite', 'working_path' => $workingPath, 'overwrite_min_item_time' => post('overwrite_min_item_time'), 'overwrite_max_item_time' => post('overwrite_max_item_time'), 'overwrite_item_force' => post('overwrite_item_force'), 'resume_messages' => 'true'));
		} else {
			redirectTo('item_block', array('action' => 'edit_defaults', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
	}

	function doSaveTemplate()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$modifications['default_item_type'] = post('template');
		$modifications['default_template_cols'] = post('column');
		$modifications['default_template_align'] = post('align');
		if ($this->block->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->block->getTitle())));
		}
		$items = $this->block->getTreeChildren();
		
		if (post('for_all') == "checked") {
			$modifications['type'] = post('template');
			foreach ($items as $item) {	
				$item->modify($modifications);
			}
		}
		redirectTo('item_block', array('action' => 'select_template', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doOrganize()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("OrganizeItemBlock.html");
		$this->initTemplate("organize");

		$items = $this->block->getTreeChildren();

		for ($i = 0; $i < count($items); $i++) {
			$this->tpl->setVariable('title', $items[$i]->getTitle(true, true));
			$this->tpl->setVariable('id', $items[$i]->getId());
			$this->tpl->parse('block_item');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.item').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doCreateItem()
	{
		$workingPath = post('working_path');
		$post = post('item_type');
		if (is_array($post)) {
			$type = key($post);
		} else {
			$type = $post;
		}

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('create', true);

		$info['title'] = T('pages.item.new').' '.(count($this->block->getTreeChildren()) + 1);
		$info['discrimination'] = 0;
		$info['difficulty'] = 0;
		$info['guessing'] = 0;
		$info['min_time'] = 0;
		$info['max_time'] = 0;

		if ($newItem = $this->block->createTreeChild($info)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.created_success', MSG_RESULT_POS, array('title' => $newItem->getTitle()));
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.create_item', array('title' => $this->block->getTitle())));
		
			if ($this->block->hasDefaultConditions()) {
				$conditions =  $this->block->getConditions();
			
			foreach($conditions as $position => $condition) {
				$conditions[$position]["id"] = NULL;
				$newItem->addCondition($condition, $position);
			}
		}
			
			if ($newItem->hasCustomScoring()) $newItem->preinitCustomScore();
			redirectTo('item', array('working_path' => $this->workingPath, 'item_id' => $newItem->getId(), 'resume_messages' => 'true'));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.created_failure', MSG_RESULT_NEG, array('title' => $info['title']));
			redirectTo('item_block', array('working_path' => $this->workingPath, 'resume_messages' => 'true'));
		}
		
	}

	function doConfirmDelete()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("DeleteItemBlock.html");
		$this->initTemplate("delete");

		require_once(CORE."types/TestRunList.php");
		
		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests))
			$this->tpl->touchBlock('delete_not_possible');

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.item').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doConfirmOverwriteAnswers()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("OverwriteAnswersItemBlock.html");
		$this->initTemplate("overwrite_answers");

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.item').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}
	function doConfirmOverwrite()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("OverwriteItemBlock.html");
		$this->initTemplate("overwrite");
		if(get("overwrite_min_item_time") == "true") {
			$this->tpl->touchBlock("overwrite_min_item_time");
		}
		if(get("overwrite_max_item_time") == "true") {
			$this->tpl->touchBlock("overwrite_max_item_time");
		}
		if(get("overwrite_item_force") == "true") {
			$this->tpl->touchBlock("overwrite_item_force");
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.item').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doOverwrite()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$title = htmlentities($this->block->getTitle());

		if (post("cancel_overwrite", FALSE)) {
			redirectTo('item_block', array('action' => 'edit_defaults', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		$overwrite = array();
		if(post("overwrite_min_item_time") == "true") {
			$overwrite["min_item_time"] = true;
		}
		if(post("overwrite_max_item_time") == "true") {
			$overwrite["max_item_time"] = true;
		}
		if(post("overwrite_item_force") == "true") {
			$overwrite["item_force"] = true;
		}

		if ($this->block->overwriteItems($overwrite)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item_block.msg.overwrite_success', MSG_RESULT_POS, array("title" => $title));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item_block.msg.overwrite_failure', MSG_RESULT_NEG, array("title" => $title));
		}

		redirectTo('item_block', array('action' => 'edit_defaults', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doOverwriteAnswers()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$title = htmlentities($this->block->getTitle());

		if (post("cancel_overwrite", FALSE)) {
			redirectTo('item_block', array('action' => 'default_answers', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		$overwrite = array('default_answers' => true);

		if ($this->block->overwriteItems($overwrite)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item_block.msg.overwrite_answers_success', MSG_RESULT_POS, array("title" => $title));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item_block.msg.overwrite_answers_failure', MSG_RESULT_NEG, array("title" => $title));
		}

		redirectTo('item_block', array('action' => 'default_answers', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doOrder()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$newOrder = post('order');

		$this->block->orderChilds($newOrder);
		TestStructure::storeCurrentStructure($this->blocks[1]->getId(),
			array('structure.reorder_items', array('title' => $this->block->getTitle())));

		redirectTo('item_block', array('action' => 'organize', 'working_path' => $workingPath));
	}

	function doDeleteAnswer()
	{
		$workingPath = get('working_path');
		$answer_id = post('id');

		if (! $this->init($workingPath)) {
			return;
		}

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		$res = $this->block->deleteDefaultAnswer($answer_id);

		if ($res) {
			sendXMLStatus('', array('type' => 'ok', 'id' => $answer_id));
		}

		sendXMLMessages();
	}

	function doEditAnswer()
	{
		// include fckeditor
		require_once(PORTAL.'Editor.php');

		$workingPath = get('working_path');
		$answer_id = post('id', 0);

		if (! $this->init($workingPath)) {
			return;
		}

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		if ($answer_id == 0)
		{
			$answer = $this->block->createDefaultAnswer();
		}
		else
		{
			$answer = $this->block->getDefaultAnswerById($answer_id);
		}

		$this->tpl->loadTemplateFile('EditDefaultAnswerParagraphEdit.html');

		$this->tpl->setVariable('id', $answer->getId());
		$this->tpl->setVariable('link', linkTo('item_block', array('action' => 'save_answer', 'working_path' => $workingPath, 'answer_id' => $answer->getId()), true, true));

		// create FCKeditor
		$editor = new Editor('working_path='.$workingPath.'&answer_id='.$answer->getId(), $answer->getAnswer());
		$this->tpl->setVariable('para_contents', $editor->CreateHtml());

		sendHTMLMangledIntoXML($this->tpl->get(), array('id' => 'para_'. $answer->getId()));
	}

	function doSaveAnswer()
	{
		$workingPath = get('working_path');
		$answerId = get('answer_id');
		$save = post('save', false);

		if (! $this->init($workingPath)) {
			return;
		}

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		$answer = $this->block->getDefaultAnswerById($answerId);
		if ($save)
		{
			$answer->modify(array('answer' => defuseScripts(deUtf8(post('fckcontent')))));
		}

		sendXMLMessages(true);
	}
}

?>
