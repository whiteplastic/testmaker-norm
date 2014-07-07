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
libLoad('utilities::storeRequestDataInSession');
libLoad('utilities::revertRequestDataFromSession');
libLoad('utilities::getCorrectionMessage');
libLoad('utilities::shortenString');
/**
 * Displays the relevant pages for items
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class ItemPage extends BlockPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "edit";
	var $db;

	// HELPER FUNCTIONS

	function init($workingPath, $itemId)
	{
		$this->item = $this->getItem($this->block, $itemId);
		$this->workingPath = $workingPath;

		if (! $this->item) {
			$page = &$GLOBALS["PORTAL"]->loadPage('admin_start');
			$page->run();
			return FALSE;
		}
		
		if ($this->item->getDisabled($workingPath)) $this->makeReadOnly();

		$this->tpl->setGlobalVariable('item_id', $itemId);
		$this->tpl->setGlobalVariable('working_path', $workingPath);

		return TRUE;
	}

	function initTemplate($activeTab, $itemId)
	{
		$tabs = array(
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("item", array("action" => "edit", "working_path" => $this->workingPath, "item_id" => $itemId))),
		);
		if($this->block->isIRTBlock()) {
			$tabs = array_merge($tabs, array(
				"edit_irt" => array("title" => "tabs.test.irt", "link" => linkTo("item", array("action" => "edit_irt", "working_path" => $this->workingPath, "item_id" => $itemId))),
			));
		}
		if ($this->item->getType() == 'MapItem') {
			$tabs = array_merge($tabs, array(
				"edit_locations" => array("title" => "tabs.test.locations", "link" => linkTo("item", array("action" => "edit_locations", "working_path" => $this->workingPath, "item_id" => $itemId))),
			));
		} else {
			$tabs = array_merge($tabs, array(
				"edit_answers" => array("title" => "tabs.test.answers", "link" => linkTo("item", array("action" => "edit_answers", "working_path" => $this->workingPath, "item_id" => $itemId))),
			));
		}
		$tabs = array_merge($tabs, array(
			"edit_time" => array("title" => "tabs.test.time", "link" => linkTo("item", array("action" => "edit_time", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"template" => array("title" => "tabs.test.template", "link" => linkTo("item", array("action" => "select_template", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"conditions" => array("title" => "tabs.test.conditions", "link" => linkTo("item", array("action" => "conditions", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"organize" => array("title" => "tabs.test.structure", "link" => linkTo("item", array("action" => "organize", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"copy" => array("title" => "tabs.test.copy", "link" => linkTo("item", array("action" => "confirm_copy", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"preview" => array("title" => "tabs.test.preview", "link" => linkTo("item", array("action" => "preview", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"delete" => array("title" => "tabs.test.delete.item", "link" => linkTo("item", array("action" => "confirm_delete", "working_path" => $this->workingPath, "item_id" => $itemId))),
		));

		$disabledTabs = array();

		if (!$this->mayEdit) {
			$disabledTabs[] = 'edit_time';
			$disabledTabs[] = 'edit_irt';
			$disabledTabs[] = 'template';
			$disabledTabs[] = 'organize';
			$disabledTabs[] = 'delete';
			$disabledTabs[] = 'copy';
			$disabledTabs[] = 'conditions';
		}
		if (!$this->mayRun) {
			$disabledTabs[] = 'preview';
		}

		if (count($this->item->getChildren()) <= 1) {
			$disabledTabs[] = "organize";
		}

		$this->initTabs($tabs, $activeTab, $disabledTabs);

		require_once(CORE."types/TestRunList.php");
		$blockList = $GLOBALS['BLOCK_LIST'];
		$path = $this->splitPath($this->workingPath);

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

	function getItem($block, $id)
	{
		if (! $block || ! $id) {
			return NULL;
		}

		if (! $block->isItemBlock() || ! $block->existsTreeChild($id))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.item_invalid', MSG_RESULT_NEG);
			return NULL;
		}

		$item = $block->getTreeChildById($id);

		return $item;
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

		if ($this->item->hasCustomScoring()) $this->item->preinitCustomScore();

		// create FCKeditor
		if (get('revert') == 'true')
		{
			$content = post('fckcontent');
		}
		else
		{
			$content = $this->item->getQuestion();
		}
		$editor = new Editor('working_path='.$workingPath.'&item_id='.$id, $this->item->getQuestion());

		$this->checkAllowed('view', true);

		$this->tpl->loadTemplateFile("EditItem.html");
		$this->initTemplate("edit", $id);

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}

		$this->tpl->setVariable('t_modified', $this->item->getModificationTime());
		$this->tpl->setVariable('t_created', $this->item->getCreationTime());
		if(get('revert') == 'true') {
			$this->tpl->setVariable('title', post('title'));
			if(post('answer_force')) {
				$this->tpl->touchBlock('answer_force_checked');
			}
		} else {
			$this->tpl->setVariable('title', $this->item->getTitle(false));
			if($this->item->isForced()) {
				$this->tpl->touchBlock('answer_force_checked');
			}
		}
		$parent = $this->item->getParent();
		if ($parent->isItemBlock() && $parent->isAdaptiveItemBlock()) {
			$this->tpl->touchBlock('answer_force_disabled');
		}
		$this->tpl->setVariable('question', $editor->CreateHtml());

		/*if ($this->mayEdit) {
			$this->tpl->touchBlock('form_submit_button');
		}*/
		
		$this->tpl->touchBlock('form_submit_button');
		if (!$this->mayEdit) {
			$this->tpl->touchBlock('reactivate');
		}

		require_once(CORE.'types/ItemTemplate.php');
		$itemTemplateObj = new ItemTemplate();
		$desc = $itemTemplateObj->getTemplateDesc('TextFloatLineItem', $GLOBALS['TRANSLATION']->getLanguage());
		$this->tpl->setVariable('template_desc', $desc);

		$body = $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doEditLocations()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditItemLocations.html");
		$this->initTemplate("edit_locations", $id);

		$locations = $this->item->getLocations($_SESSION['language']);
		for ($key = 0; $key < count($locations); ++$key)
		{
			$this->tpl->setVariable('id', $key);
			$this->tpl->setVariable('title', $locations[$key]->getTitle());
			$this->tpl->setVariable(!$locations[$key]->isUsed() ? 'checked0' : 'checked1', 'checked="checked"');
			$this->tpl->setVariable('duration', 'value="'.$locations[$key]->getDuration().'"');
			$this->tpl->setVariable('startTime', $locations[$key]->getStartTime() ? 'value="'.$locations[$key]->getStartTime().'"' : 'value="8:00"');
			$this->tpl->setVariable('endTime', $locations[$key]->getEndTime() ? 'value="'.$locations[$key]->getEndTime().'"' : 'value="17:00"');
			$this->tpl->setVariable('description', $locations[$key]->getDescription());
			if (!$locations[$key]->isUsed())
			{
				$this->tpl->setVariable('display', ' style="display: none"');
			}
			$this->tpl->parse('location_block');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}
	
	function doDisplaySolutions() {
		require_once(ROOT.'upload/items/MapItem.php');
		require_once(PORTAL.'PageSelector.php');
		$workingPath = get('working_path');
		$id = get('item_id');
		if (! $this->init($workingPath, $id)) {
			return;
		}		
		//Read GET, POST and SESSION variables
		if (isset($_SESSION['computeSolutions'])) $computeSolutions = $_SESSION['computeSolutions']; else $computeSolutions = false;
		if (!isset($_SESSION['entriesPerSolutionPage'])) $_SESSION['entriesPerSolutionPage']=25;
		if (isset($_REQUEST['entries_per_page'])) $_SESSION['entriesPerSolutionPage']=$_REQUEST['entries_per_page'];
		$entriesPerPage = $_SESSION['entriesPerSolutionPage'];
		if (isset($_REQUEST['page_number']) AND is_numeric($_REQUEST['page_number'])) $pageNumber=$_REQUEST['page_number']; else $pageNumber=1;
		if (isset($_SESSION['solutions'])) $solutions = $_SESSION['solutions']; else $solutions = array('No solution has been computed!');
		//Load the template
		$this->tpl->loadTemplateFile("DisplayMapSolutions.html");
		$this->initTemplate("display_solutions", $id);
		//Compute the solution
		if ($computeSolutions) {
			$mapItem = new MapItem(get('item_id'));
			$solutions = $mapItem->calcSolutions();
			$_SESSION['computeSolutions'] = false;
			$_SESSION['solutions'] = $solutions;
		}
		for ($i=($pageNumber-1)*$entriesPerPage; $i<($pageNumber-1)*$entriesPerPage+$entriesPerPage; $i++) {
			if ($i >= count($solutions)) $this->tpl->setVariable('solution','');
			else $this->tpl->setVariable('solution',$solutions[$i]);
			$this->tpl->parse('sampleSolutions');
		}
		//Show the template using the page selector, which shows multiple pages
		$pageSelector = new PageSelector(ceil(count($solutions)/$entriesPerPage), $pageNumber, 2, 'item', 
												array('action'=>'display_solutions','item_id'=>$id,'working_path'=>$workingPath,'resume_messages'=>'true'));
		$pageSelector->renderDefault($this->tpl);		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body",$body);
		$this->tpl->show();
	}
	
	function doExportSolutions() {
		if (isset($_SESSION['solutions'])) $solutions = $_SESSION['solutions']; else $solutions = array('No solutions have been computed!');
		$export = '';
		foreach ($solutions as $solution) {
			$export .= $solution."\r\n";
		}
		header("Content-type: application/oktet-stream");
		header('Content-Disposition: attachment; filename="solutions.txt"');
		print $export;
	}
	
	function doEditIrt()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditItemIRT.html");
		$this->initTemplate("edit_irt", $id);

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}

		if(get('revert') == 'true') {
			$this->tpl->setVariable('difficulty', post('difficulty'));
			$this->tpl->setVariable('discrimination', post('discrimination'));
			$this->tpl->setVariable('guessing', post('guessing'));
		} else {
			$this->tpl->setVariable('difficulty', $this->item->getDifficulty());
			$this->tpl->setVariable('discrimination', $this->item->getDiscrimination());
			$this->tpl->setVariable('guessing', $this->item->getGuessing());
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doPreview()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('run', true);

		redirectTo('test_make', array('action' => 'preview', 'item_id' => $id, 'working_path' => $workingPath));
	}

	function doEditTime()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditItemTime.html");
		$this->initTemplate("edit_time", $id);

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}

		if(get('revert') == 'true') {
			$this->tpl->setVariable('min_time', post('min_time'));
			$this->tpl->setVariable('max_time', post('max_time'));
		} else {
			$this->tpl->setVariable('min_time', $this->item->getMinTime());
			$this->tpl->setVariable('max_time', $this->item->getMaxTime());
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doEditAnswers()//Funktion um die Antwort von Items zu bearbeiten
	{
		$workingPath = get('working_path');
		$id = get('item_id');
		
		$matrix_answer_editable = get('answer_editable', false);
		if(!$this->block->getDefaultAnswers()) $matrix_answer_editable = true;
		
		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('view', true);

		$answers = $this->item->getChildren();
		$answers_string = '';
		for ($i = 0; $i < count($answers); $i++)
		{
			if (($this->item->classname == 'McsaHeaderItem') && ($matrix_answer_editable == false))
				$this->tpl->loadTemplateFile("EditAnswerParagraphViewMatrix.html");
			else
				$this->tpl->loadTemplateFile("EditAnswerParagraphView.html");
				
		
			$edit = $this->mayEdit;
			$title = $answers[$i]->getTitle();

			if ($answers[$i]->getDisabled($workingPath)) {
				$this->tpl->touchBlock('para_disabled');
				$title .= ' '. T('structure.disabled');
				$edit = false;
			}

			$this->tpl->setVariable("para_contents", $title);

			$this->tpl->setVariable("para_id", $answers[$i]->getId());
			if ($answers[$i]->isCorrect()) $this->tpl->setVariable("para_checked", 'checked="checked"');
			else $this->tpl->setVariable("para_value", '');
			$this->tpl->touchBlock('correctness');
			$this->tpl->setVariable("para_pos", $answers[$i]->getPosition());

			$this->tpl->setVariable("edit_link", linkTo('item', array('action' => 'edit_answer', 'working_path' => $workingPath, 'item_id' => $this->item->getId()), true, true));
			$this->tpl->setVariable("del_link", linkTo('item', array('action' => 'delete_answer', 'working_path' => $workingPath, 'item_id' => $this->item->getId()), true, true));
			if (!$edit) $this->tpl->hideBlock("para_may_edit");
			$answers_string .= trim($this->tpl->get());
		}

		$this->tpl->loadTemplateFile("EditItemAnswers.html");
		$this->initTemplate("edit_answers", $id);
		if ($this->item->classname == 'McsaHeaderItem' && !$matrix_answer_editable) 
			$this->tpl->touchBlock('explain_mcsa_header_item');	
		else
			$this->tpl->hideBlock('explain_mcsa_header_item');	
			
		$this->tpl->setVariable("paragraphs", $answers_string);
		$this->tpl->setVariable("add_link", linkTo('item', array('action' => 'edit_answer', 'working_path' => $workingPath, 'item_id' => $this->item->getId()), false, true));
		if (!$this->mayEdit) $this->tpl->hideBlock('para_add_button');
		
		if ($this->item->classname == 'McsaHeaderItem') {
			if ($matrix_answer_editable == false) {
				$this->tpl->hideBlock('para_add_button');
				$this->tpl->setVariable("editable", true);
				$this->tpl->touchBlock('edit_button');
				$this->tpl->hideBlock('take_over_button');
			}
			else {
				$block = $this->item->getParent();
				$exists = $block->existsAnyDefaultAnswer();
				//display only the take_over_button when defaultanswers exists
				if ($exists) {
					$this->tpl->touchBlock('take_over_button');
				}
				else {
					$this->tpl->hideBlock('take_over_button');
				}
				$this->tpl->hideBlock('edit_button');
			}
		}

		if ($this->item instanceof TextLineItem)
		{
			libLoad("utilities::validator");
			$restrictions = Validator::getRestrictions();
			$savedRestriction = $this->item->getRestriction();
			if($savedRestriction == "")
			{
				$this->tpl->setVariable("select", " selected=\"selected\"");
			}
			$this->tpl->setVariable("resVal", "");
			$this->tpl->setVariable("resDesc", "");
			$this->tpl->parse("answer_validation_options");
			foreach($restrictions as $restriction)
			{
				if($savedRestriction == $restriction) 
				{
					$this->tpl->setVariable("select", " selected=\"selected\"");
				}
				$this->tpl->setVariable("resVal", $restriction);
				$this->tpl->setVariable("resDesc", T("pages.item.restriction.".$restriction));
				$this->tpl->parse("answer_validation_options");
			}
			$this->tpl->setVariable("working_path", $workingPath);
			$this->tpl->setVariable("item_id", $id);
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doSelectTemplate()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("SelectItemTemplate.html");
		$this->initTemplate("template", $id);

		$templateDir = ROOT.'upload/items/';
		$activeFile = $this->item->getType();
		$template_cols = $this->item->getTemplateCols();
		$template_align = $this->item->getTemplateAlign();
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

		$this->tpl->setVariable('column', $template_cols);
		if ($template_align == 'v')
		{
			$this->tpl->touchBlock('vertical_active');
			$this->tpl->hideBlock('horizontal_active');
			$this->tpl->hideBlock('nothing_active');
		}
		elseif ($template_align == 'h')
		{
			$this->tpl->touchBlock('horizontal_active');
			$this->tpl->hideBlock('vertical_active');
			$this->tpl->hideBlock('nothing_active');
		}
		else
		{
			$this->tpl->touchBlock('nothing_active');
			$this->tpl->hideBlock('horizontal_active');
			$this->tpl->hideBlock('vertical_active');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();	
	}

	function doSave()
	{
	
		$workingPath = get('working_path');
		$id = get('item_id');

		if (post('title') == NULL) {
			sendXMLStatus(T('pages.paragraphs.empty_request'), array('type' => 'fail'));
		}
		
		$checkValues = array(
			array('title', post('title'), array(CORRECTION_CRITERION_LENGTH_MIN => 1)),
		);

		$GLOBALS["MSG_HANDLER"]->flushMessages();
		for($i = 0; $i < count($checkValues); $i++) {
			$tmp = getCorrectionMessage(T('forms.item.'.$checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
			if(count($tmp) > 0) {
				$this->correctionMessages[$checkValues[$i][0]] = $tmp;
			}
		}

		if(count($this->correctionMessages) > 0) {
			$this->doEdit();
			return;
		}

		if (! $this->init($workingPath, $id)) {
			return;
		}

		//$this->checkAllowed('edit', true);

		$modifications['question'] = defuseScripts(deUtf8(post('fckcontent', '')));
		$modifications['title'] = post('title');
		$modifications['answer_force'] = post('answer_force') ? true : false;

		if ($this->item->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->item->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->item->getTitle())));
		}
		redirectTo('item', array('action' => 'edit', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
	
	}

	/**
	 * This function is called by the "save"- AND the "sample solution"-button. The formular is saved in both cases. After saving the formular it
	 * depends on the pressed button if the formular will be shown again or if an optimal solution will be calculated.
	 */
	function doSaveLocations()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$locations = post('location');

		//Save the formular
		if ($this->item->saveLocations($locations)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->item->getTitle())));
		}
		else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->item->getTitle())));
		}

		//Show the formular again, if the "save" button has been clicked
		if (isset($_REQUEST['save'])) {
			redirectTo('item', array('action' => 'edit_locations', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
		//Calculate optimal solutions otherwise (i.e. if the "sample solution" button has been clicked)
		else {
			unset($_SESSION['solution']);
			$_SESSION['computeSolutions'] = true;
			redirectTo('item', array('action' => 'display_solutions', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
	}

	function doSaveIrt()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		$checkValues = array(
			array('difficulty', post('difficulty'), array(CORRECTION_CRITERION_NUMERIC => NULL)),
			array('discrimination', post('discrimination'), array(CORRECTION_CRITERION_NUMERIC => NULL, CORRECTION_CRITERION_NOT_ZERO => TRUE)),
			array('guessing', post('guessing'), array(CORRECTION_CRITERION_NUMERIC => NULL, CORRECTION_CRITERION_NUMERIC_MAX => 0.99999999,
													  CORRECTION_CRITERION_NUMERIC_MIN => 0)),
		);

		$GLOBALS["MSG_HANDLER"]->flushMessages();
		for($i = 0; $i < count($checkValues); $i++) {
			$tmp = getCorrectionMessage(T('forms.item.'.$checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
			if(count($tmp) > 0) {
				$this->correctionMessages[$checkValues[$i][0]] = $tmp;
			}
		}

		if(count($this->correctionMessages) > 0) {
			$this->doEditIrt();
			return;
		}

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$modifications['difficulty'] = post('difficulty');
		$modifications['discrimination'] = post('discrimination');
		$modifications['guessing'] = post('guessing');

		if ($this->item->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->item->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->item->getTitle())));
		}
		redirectTo('item', array('action' => 'edit_irt', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doSaveTime()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		$checkValues = array(
			array('min_time', post('min_time'), array(CORRECTION_CRITERION_NUMERIC_INTEGER => NULL)),
			array('max_time', post('max_time'), array(CORRECTION_CRITERION_NUMERIC_INTEGER => NULL)),
		);

		$GLOBALS["MSG_HANDLER"]->flushMessages();
		for($i = 0; $i < count($checkValues); $i++) {
			$tmp = getCorrectionMessage(T('forms.item.'.$checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
			if(count($tmp) > 0) {
				$this->correctionMessages[$checkValues[$i][0]] = $tmp;
			}
		}

		if(count($this->correctionMessages) > 0) {
			$this->doEditTime();
			return;
		}

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$modifications['min_time'] = post('min_time');
		$modifications['max_time'] = post('max_time');

		if ($this->item->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->item->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->item->getTitle())));
		}

		redirectTo('item', array('action' => 'edit_time', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doSaveTemplate()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$modifications = array();
		if (post("save", NULL))
		{
			$modifications['type'] = post('template');
			$modifications['template_cols'] = post('column');
			$modifications['template_align'] = post('align');
		}
		elseif (post("baseTemp", NULL))
		{
			$itemBlock = $this->item->getParent();
			$modifications['type'] = $itemBlock->getDefaultItemType();
			$modifications['template'] = '';
			$modifications['template_cols'] = '';
			$modifications['template_align'] = '';
		}

		if ($this->item->modify($modifications))
		{
			$this->item = $this->getItem($this->block, $this->item->getId());
			if ($this->item->hasCustomScoring()) $this->item->preinitCustomScore();
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->item->getTitle())));
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->item->getTitle())));
		}
		redirectTo('item', array('action' => 'select_template', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doOrganize()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$answers = $this->item->getChildren();

		$this->tpl->loadTemplateFile("OrganizeItem.html");
		$this->initTemplate("organize", $id);

		for ($i = 0; $i < count($answers); $i++) {
			$this->tpl->setVariable('title', $answers[$i]->getTitle());
			$this->tpl->setVariable('id', $answers[$i]->getId());
			$this->tpl->parse('block_item');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doConfirmDelete()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('delete', true);

		$this->tpl->loadTemplateFile("DeleteItem.html");
		$this->initTemplate("delete", $id);

		require_once(CORE."types/TestRunList.php");
		
		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests))
			$this->tpl->touchBlock('delete_not_possible');

		//check if display conditions of other items are dependent on this item
		$active_conditions = $this->item->getConditionDependencies();
		if ($active_conditions)
		{
			$this->tpl->touchBlock('active_item_conditions');
			$this->tpl->touchBlock('item_list');
			
			foreach ($active_conditions AS $condition)
			{
				$cond_item = Item::getItem($condition["parent_item_id"]);
				$cond_item_title = $cond_item->getTitle();
	            $this->tpl->setVariable("cond_item_id", $condition["parent_item_id"]);
	            $this->tpl->setVariable("cond_item_title", $cond_item_title);
	            $this->tpl->parse("item_list");
			}
		}		
			
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doDelete()
	{
		$this->db = $GLOBALS['dao']->getConnection();
		
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('delete', true);

		$title = htmlentities($this->item->getTitle());
		
		$pathIds = explode("_",$workingPath);

		if (post("cancel_delete", FALSE)) {
			redirectTo('item', array('action' => 'edit', 'working_path' => $workingPath, 'item_id' => $id, 'resume_messages' => 'true'));
		}
		
		if ($obj = $this->item->getDeletionIntegrity()) {
			if (is_a($obj, 'Dimension')) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.item.msg.inuse_dimension', MSG_RESULT_NEG, array('title' => htmlentities($obj->getTitle())));
				redirectTo('item', array('action' => 'edit', 'working_path' => $workingPath, 'item_id' => $id, 'resume_messages' => 'true'));
			}
		}
		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests)) {
			$this->item->modify(array('disabled' => 1));
			//foreach ($tests as $test) {
				TestStructure::storeCurrentStructure($pathIds[2], array('structure.disable_item', array('title' => $this->item->getTitle())));			
			//}
			$GLOBALS['MSG_HANDLER']->addMsg('pages.disable_object.success', MSG_RESULT_POS, array('title' => $this->item->getTitle()));
		} elseif ($this->block->deleteTreeChild($id)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.deleted_success', MSG_RESULT_POS, array('title' => $title));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.deleted_failure', MSG_RESULT_NEG, array('title' => $title));
			redirectTo('item', array('action' => 'edit', 'working_path' => $workingPath, 'item_id' => $id, 'resume_messages' => 'true'));
		}

		redirectTo('item_block', array('working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doOrder()
	{
		$workingPath = get('working_path');
		$item_id = get('item_id');

		if (! $this->init($workingPath, $item_id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$newOrder = post('order');

		$this->item->orderChilds($newOrder);

		redirectTo('item', array('action' => 'organize', 'working_path' => $workingPath, 'item_id' => $item_id));
	}

	function doDeleteAnswer()
	{
		$workingPath = get('working_path');
		$id = get('item_id');
		$answer_id = post('id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests)) {
			$answer = new ItemAnswer($answer_id);
			if (!$this->item->existsChild($answer_id))
			{
				sendXMLStatus(T('pages.id_out_of_place'), array('type' => 'fail'));
			}
			$answer->modify(array('disabled' => 1));
			foreach ($tests as $test) {
				TestStructure::storeCurrentStructure($test->getId(), array('structure.disable_itemanswer', array('title' => $this->item->getTitle())));
			}

			sendXMLStatus('', array('type' => 'ok_disabled', 'id' => $answer_id));
		}
		$res = $this->item->deleteChild($answer_id);

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
		$id = get('item_id');
		$answer_id = post('id', 0);

		if (! $this->init($workingPath, $id)) {
			return;
		}

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		if ($answer_id == 0)
		{
			$answer = $this->item->createChild(array());
			if ($tests = TestStructure::getTrackedContainingTests($this->block->getId())) {
				foreach ($tests as $test) {
					TestStructure::storeCurrentStructure($test->getId(), array('structure.create_itemanswer', array('title' => $this->item->getTitle())));
				}
			}
		}
		else
		{
			$answer = $this->item->getChildById($answer_id);
		}

		$this->tpl->loadTemplateFile('EditAnswerParagraphEdit.html');

		$this->tpl->setVariable('id', $answer->getId());
		if ($answer->isCorrect())
		{
			$this->tpl->setVariable('para_checked', 'checked="checked"');
		}
		$this->tpl->setVariable('link', linkTo('item', array('action' => 'save_answer', 'working_path' => $workingPath, 'item_id' => $id, 'answer_id' => $answer->getId()), true, true));

		// create FCKeditor
		$editor = new Editor('working_path='.$workingPath.'&item_id='.$id.'&answer_id='.$answer->getId(), $answer->getAnswer());
		$this->tpl->setVariable('para_contents', $editor->CreateHtml());

		sendHTMLMangledIntoXML($this->tpl->get(), array('id' => 'para_'. $answer->getId()));
	}
	
	/**
	 * check if the change of the correctness of an answer effects to a dimension in a Feedbackblock
	 * When yes, the dimesion will be corrected, if it is from scoretype 1
	 * @param itemAnswer
	 */
	 
	 
	function doChangeDimensionScores($answer)
	{
		$this->db = $GLOBALS['dao']->getConnection();
		$answerId = $answer->getId();
		$dims = $this->db->getRow("SELECT dimension_id FROM ".DB_PREFIX."dimensions_connect WHERE item_answer_id = ?", array($answerId));
		
		if (!empty($dims)) {
			$dims = array_unique($dims);
			foreach($dims as $dim) {
				$score_type = $this->db->getOne("SELECT score_type FROM ".DB_PREFIX."dimensions WHERE id = ?", $dim);
				if($score_type == 0 || $score_type == 1) {
					$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX."dimensions_connect WHERE item_answer_id = ?", array($answer->getId()));	
					if ($answer->isCorrect())
						$score = 1;
					else
						$score = 0;
						
					if ($this->db->isError($res)) {
						return false;
					} 
					
					else {
						if($res) {
							foreach($res as $value) {
								if (($value['score'] == 1) or ($value['score'] == 0)) {
									$this->db->query("UPDATE ".DB_PREFIX."dimensions_connect SET score = ? 
											WHERE item_answer_id = ? AND dimension_id=?", array($score, $answer->getId(), $value['dimension_id']));
								}
							}
						}
					}
				}
			}
			
		}
		
	}

	function doSaveAnswer()
	{
		$workingPath = get('working_path');
		$id = get('item_id');
		$answer_id = post('id');
		$isTrue = post('is_true');
		$save = post('save', false);
		
		if ($answer_id  == NULL) {
			sendXMLStatus(T('pages.paragraphs.empty_request'), array('type' => 'fail'));			
		}
		
		if (! $this->init($workingPath, $id)) {
			return;
		}

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		if ($this->item->existsChild($answer_id) && $save) {
			$answer = $this->item->getChildById($answer_id);

			$answer->modify(array('answer' => defuseScripts(deUtf8(post('fckcontent'))), 'correct' => $isTrue));
			$this->doChangeDimensionScores($answer);
		} elseif ($save) {
			sendXMLStatus(T('pages.id_gone'), array('type' => 'fail'));
		}
		sendXMLMessages(true);
	}

	function doConfirmCopy()
	{
		$workingPath = get('working_path');
		$item_id = get('item_id');

		if (! $this->init($workingPath, $item_id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$parent = $this->item->getParent();

		$this->tpl->loadTemplateFile("ConfirmCopyItem.html");
		$this->initTemplate("copy", $item_id);
		$this->tpl->setVariable("working_path", $workingPath);
		$this->tpl->setVariable("item_id", $item_id);
		$this->tpl->setVariable("target_title", $parent->getTitle());

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doCopy()
	{
		$workingPath = get('working_path');
		$item_id = get('item_id');

		if (! $this->init($workingPath, $item_id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$parent = $this->item->getParent();

		if (post('cancel_copy', false))
		{
			redirectTo("item", array("action" => "edit", "working_path" => $workingPath, "item_id" => $item_id));
		}

		$changedIds = array();
		if (!$newItem = $this->item->copyNode($parent->getId(), $changedIds))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.copy_failure', MSG_RESULT_NEG, array('title' => $this->item->getTitle()));
		}
		else
		{
			$newItem->modify(array('title' => T('pages.item.copy').' '.$newItem->getTitle()));
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.msg.copy_success', MSG_RESULT_POS, array('title' => $this->item->getTitle()));
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.copy_item', array('item_title' => $this->item->getTitle())));
		}

		redirectTo("item", array("action" => "edit", "working_path" => $workingPath, "item_id" => $newItem->getId(), "resume_messages" => "true"));
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
					if ($item->getId() == $this->item->getId()) {
						break;
					}
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

	function convertConditionsToJavaScript($conditions)
	{
		for ($i = 0; $i < count($conditions); $i++) {
			if (isset($conditions[$i]["chosen"])) {
				$conditions[$i]["chosen"] = $conditions[$i]["chosen"] ? "yes" : "no";
			}
		}

		return $conditions;
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

	function doConditions()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditItemConditions.html");
		$this->initTemplate("conditions", $id);

		libLoad("PEAR");
		require_once("Services/JSON.php");
		$json = new Services_JSON();

		$checkedOn = 'checked="checked"';
		$checkedOff = '';
		$selectedOn = 'selected="selected"';
		$selectedOff = '';

		$this->tpl->setVariable('resource_url', linkTo("item", array("action" => "get_resource", "working_path" => $workingPath, "item_id" => $id)));

		$needsAll = $this->item->getNeedsAllConditions();

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

		$activeItemId = $this->item->getId();
		$activeItemId = NULL;

		$items = array();
		foreach ($activeItemBlock->getTreeChildren() as $item) {
			$items[] = $item->getId();
		}

		$conditions = $this->item->getConditions();

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
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}

	function doSaveConditions($conditions = NULL)
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->item->setNeedsAllConditions(post('conditions_all') ? TRUE : FALSE);
		
		if ($conditions == NULL) 
			$newConditions = post("conditions", array());
		else
			$newConditions = $conditions;
		
		$oldConditions = $this->item->getConditions();		
		$newConditions = $this->convertConditionsToPhp($newConditions);
		$itemBlock = $this->item->getParent();
		$itemsPerPage = $itemBlock->getItemsPerPage();
		$dontSave = false;
	
		foreach($newConditions as $condition)
		{
			if($condition['id'] == NULL && $condition['item_block_id'] == NULL) continue;
			elseif($itemBlock->getId() == $condition['item_block_id']) $dontSave = true;
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
		$this->item->deleteConditions($deletedIds);

		foreach ($newConditions as $position => $condition)
		{
			if ($condition["id"] != 0) {
				$this->item->updateCondition($condition, $position);
			} else {
				$this->item->addCondition($condition, $position);
			}
		}

		$GLOBALS["MSG_HANDLER"]->addMsg('pages.item.conditions.saved', MSG_RESULT_POS);
		redirectTo('item', array('action' => 'conditions', 'item_id' => $id, 'working_path' => $workingPath));
	}
	

	function doSaveAnswerValidation()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}
		$restriction = post("answer_validation_pattern", "");
		
		$this->item->updateRestriction($restriction);
		redirectTo('item', array('action' => 'edit_answers', 'working_path' => $workingPath, 'item_id' => $id));
	}
	
	
	
	function doConfirmTakeOverStandardAnswers() {
		$workingPath = get('working_path');
		$item_id = get('item_id');

		if (! $this->init($workingPath, $item_id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("ConfirmTakeOverBlockAnswers.html");
		$this->tpl->setVariable("working_path", $workingPath);
		$this->tpl->setVariable("item_id", $item_id);
		$this->tpl->setVariable("editable", true);
		$this->initTemplate("edit_answers", $item_id);
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.item.question').": '".$this->item->getTitle()."'");
		$this->tpl->setVariable('body', $body);

		$this->tpl->show();
	}
	
	/**
	* take over the standard answers of the parent blockItem for this item.
	*/
	function doTakeOverStandardAnswers()
	{
		$workingPath = get('working_path');
		$id = get('item_id');
		
			if (! $this->init($workingPath, $id)) {
				return;
			}
		
		if (isset($_POST["confirm"])) {
		
			$block = $this->item->getParent();
			$answers = $block->getDefaultAnswers();
		
			$children = $this->item->getChildren();
		
			foreach ($children as $child) {
				if(!$this->item->deleteChild($child->getId())) {
					return false;
				}
			}
			foreach ($answers as $answer) {
				$infos["answer"] = $answer->getAnswer();
				if(!$this->item->createChild($infos)) {
					return false;
				}	
			}
			redirectTo('item', array('action' => 'edit_answers', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
		else {
			redirectTo('item', array('action' => 'edit_answers', 'item_id' => $id, 'working_path' => $workingPath, 'answer_editable' => true, 'resume_messages' => 'true'));
		}
	}
	
	function doEnable()
	{
		$workingPath = get('working_path');
		$id = get('item_id');
		
		if (! $this->init($workingPath, $id)) {
			return;
		}
		
		$pathIds = explode("_",$workingPath);
	
		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
	
		$parent = $this->item->getParent();
		$this->item->modify(array('disabled' => 0));
		$parent->modify(array('disabled' => 0));
		
		$db = &$GLOBALS['dao']->getConnection();
		$db->query("UPDATE ".DB_PREFIX."blocks_connect SET disabled = 0 WHERE id=?", array($parent->getId()));
				
		
		if (TestStructure::existsStructureOfTests($tests)) {	
			TestStructure::storeCurrentStructure($pathIds[2], array('structure.enable_item', array('title' => $this->item->getTitle())));
			$GLOBALS['MSG_HANDLER']->addMsg('pages.enable_object.success', MSG_RESULT_POS, array('title' => $this->item->getTitle()));			
		}

		redirectTo('item_block', array('working_path' => $workingPath, 'resume_messages' => 'true'));
	}
}
