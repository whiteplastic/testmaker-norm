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

/**
 * Loads the Dimension type
 */
require_once(CORE.'types/Dimension.php');

require_once(CORE.'types/DimensionGroup.php');

/**
 * Loads the ItemBlock type
 */
require_once(CORE.'types/ItemBlock.php');
require_once(PORTAL.'pages/pdf_generator.php');
require_once(CORE.'types/FeedbackBlock.php');


libLoad('oop::isSubclassOf');
libLoad('utilities::cutBlockFromString');
libLoad('utilities::getCorrectionMessage');


/**
 * Displays the relevant pages for feedback blocks
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class FeedbackBlockPage extends BlockPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "edit";

	// HELPER FUNCTIONS {{{

	function init($workingPath)
	{

		if (! $this->block) {
			$page = &$GLOBALS["PORTAL"]->loadPage('admin_start');
			$page->run();
			return FALSE;
		}

		if (!$this->block->isFeedbackBlock()) {
			$page = &$GLOBALS["PORTAL"]->loadPage('block_edit');
			$page->run();
			return FALSE;
		}

		$this->tpl->setGlobalVariable('working_path', $workingPath);

		$this->targetPage = 'feedback_block';
		return TRUE;
	}

	function initTemplate($activeTab)
	{
		$tabs = array(
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("feedback_block", array("action" => "edit", "working_path" => $this->workingPath))),
			"dim_groups" => array("title" => "tabs.test.dim_groups", "link" => linkTo("feedback_block", array("action" => "dim_groups", "working_path" => $this->workingPath))),
			"organize" => array("title" => "tabs.test.structure", "link" => linkTo("feedback_block", array("action" => "organize", "working_path" => $this->workingPath))),
			"copy_move" => array("title" => "tabs.test.copy_move", "link" => linkTo("feedback_block", array("action" => "copy_move", "working_path" => $this->workingPath))),
			"edit_perms" => array("title" => "tabs.test.perms", "link" => linkTo("feedback_block", array("action" => "edit_perms", "working_path" => $this->workingPath))),
			"delete" => array("title" => "tabs.test.delete.feedbackblock", "link" => linkTo("feedback_block", array("action" => "confirm_delete", "working_path" => $this->workingPath))),
			"certificate" => array("title" => "tabs.test.certificate", "link" => linkTo("feedback_block", array("action" => "edit_certificate", "working_path" => $this->workingPath))),
		);

		$disabledTabs = array();

		$owner = $this->block->getOwner();
		$user = $GLOBALS['PORTAL']->getUser();
		if ($owner->getId() != $user->getId() && !$this->checkAllowed('admin', false, NULL)) {
			$disabledTabs[] = 'edit_perms';
		}
		if (!$this->mayEdit) {
			$disabledTabs[] = 'organize';

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
		if (!$this->mayCreate && !$this->mayEdit) return;

		// Add new dimension
		if ($this->block->canAddDimension()) {
			$this->tpl->setVariable('page', 'dimension');
			$this->tpl->setVariable('name', 'action');
			$this->tpl->setVariable('value', 'edit');
			$this->tpl->parse('menu_admin_item_additional_info');
			$this->tpl->setVariable('name', 'working_path');
			$this->tpl->setVariable('value', $this->workingPath);
			$this->tpl->parse('menu_admin_item_additional_info');
			$this->tpl->setVariable('name', 'item_id');
			$this->tpl->setVariable('value', '-1');
			$this->tpl->parse('menu_admin_item_additional_info');
			$this->tpl->setVariable('title', T('pages.feedback_block.add_dimension'));
			$this->tpl->parse('menu_admin_item');
		}

		// Add new page
		$this->tpl->setVariable('page', 'feedback_block');
		$this->tpl->setVariable('name', 'action');
		$this->tpl->setVariable('value', 'create_feedback_page');
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('name', 'working_path');
		$this->tpl->setVariable('value', $this->workingPath);
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('title', T('pages.feedback_block.new_feedback_page'));
		$this->tpl->parse('menu_admin_item');
	}

	/**
	 * Function to list blocks which are or have item block inside in tree format
	 * @param int $parent contains the parent id
	 * @param string $template the htmlcode in which the results should be inserted
	 * @param int $select the id of the blocks which are preselected
	 * @param boolean $found recursive parameter indicating if there are item blocks inside the current block
	 * @param int $self id of the feedback block to know where to end listing of blocks (the source block has to appear before the feedback block)
	 * @param boolean $selfFound recursive parameter indicating if the loop is already past the feedback block
	 * @return string
	 */
	function _listSourceBlocks($parent, $template, $select, &$found, $self, &$selfFound)
	{
		$content = '';
		$root_block = $GLOBALS["BLOCK_LIST"]->getBlockById($parent);
		$mainBlock = $this->blocks[1];

		// Disregard blocks that aren't within the same test
		if ($parent == 0) {
			$blocks = array($mainBlock);
		} else {
			$blocks = &$root_block->getChildren();
		}

		// In subtest mode, discard item blocks outside our subtest
		if ($mainBlock->getId() == $parent && $mainBlock->getShowSubtests() && count($this->blocks) == 4) {
			$blocks = array($this->blocks[2]);
		}

		$found = false;
		$quitAfterThis = false;
		for ($i = 0; $i < count($blocks); $i++) {
			if (!$this->checkAllowed('view', false, $blocks[$i])) continue;

			// Only quit if we're dealing with a feedback block as $self. Others
			// still need to be processed.
			if ($blocks[$i]->getId() == $self) {
				if ($blocks[$i]->isFeedbackBlock()) {
					$selfFound = true;
				} else {
					$quitAfterThis = true;
				}
			}
			if ($selfFound) return $content;

			$item = $template;
			$item = str_replace('<!-- id -->', $blocks[$i]->getId(), $item);
			$item = str_replace('<!-- title -->', htmlentities($blocks[$i]->getTitle()), $item);
		
			if ($blocks[$i]->getDisabled()) {
					$item = str_replace('type="checkbox"', 'type="checkbox" style="display: none;"' , $item);
					$item = str_replace('<li>', '<li style="list-style-type: square; text-decoration: line-through; color:#999999">
												<input type="checkbox" disabled="disabled" <!-- checked -->' , $item);
					
			}
			
			if (in_array($blocks[$i]->getId(), $select)) {
				$item = str_replace('<!-- checked -->', 'checked="checked"', $item);
			} else {
				$item = str_replace('<!-- checked -->', '', $item);
			}
			$subFound = false;
			if ($blocks[$i]->isContainerBlock())
			{
				$subitems = $this->_listSourceBlocks($blocks[$i]->getId(), $template, $select, $subFound, $self, $selfFound);
				if (trim($subitems) && $subFound)
				{
					$found = true;
					$item = str_replace('<!-- SUBITEMS -->', '', $item);
					$item = str_replace('<!-- subitems -->', $subitems, $item);
				}
				else
				{
					$item = cutBlockFromString('<!-- SUBITEMS -->', $item);
				}
			}
			else
			{
				$item = cutBlockFromString('<!-- SUBITEMS -->', $item);
			}
			if ($blocks[$i]->isItemBlock()) {
				$found = true;
				$item = str_replace('<!-- OPTION -->', '', $item);

			}
			$item = cutBlockFromString('<!-- OPTION -->', $item);
			if ($blocks[$i]->isItemBlock() || $subFound) {
				$content .= $item;
			}

			// Did we decide to quit?
			if ($quitAfterThis) {
				$selfFound = true;
				return $content;
			}
		}

		return $content;
	}

	// }}} ACTIONS

	// New block / edit block {{{

	function doEdit($init = true)
	{
		$workingPath = getpost('working_path');
		if ($init && !$this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('view', true);

		$source_ids = $this->block->getSourceIds();
		$title = $this->block->getTitle();
		$desc = $this->block->getDescription();
		$modified = $this->block->getModificationTime();
		$create = $this->block->getCreationTime();
		$id = $this->block->id;
	
		$this->tpl->loadTemplateFile("EditFeedbackBlock.html");
		$this->initTemplate("edit");

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}
		$this->tpl->touchBlock('list_area');
		$this->tpl->parse('list_area');
		$body = $this->tpl->get('list_area');
		$abody = explode('<!-- LIST -->', $body);
		$item = $abody[1];
		$item = str_replace('<!-- title -->', T('pages.block.root'), $item);
		$item = cutBlockFromString('<!-- OPTION -->', $item);
		$subFound = false;
		$selfFound = false;
		$subitems = $this->_listSourceBlocks(0, $abody[1], $source_ids, $subFound, $id, $selfFound);
		if (trim($subitems) && $subFound)
		{
			$item = str_replace('<!-- SUBITEMS -->', '', $item);
			$item = str_replace('<!-- subitems -->', $subitems, $item);
		}
		else
		{
			$item = cutBlockFromString('<!-- SUBITEMS -->', $item);
		}
		$body = $abody[0].$item.$abody[2];

		$this->tpl->replaceBlock('list_area', $body);
		$this->tpl->touchBlock('list_area');

		$this->tpl->setVariable('working_path', $this->workingPath);
		if(get('revert') == 'true') {
			$this->tpl->setVariable('title', post('title'));
			$this->tpl->setVariable('description', post('description'));
		} else {
			$this->tpl->setVariable('title', $title);
			$this->tpl->setVariable('description', $desc);
		}
		$this->tpl->setVariable('t_modified', $modified);
		$this->tpl->setVariable('t_created', $create);
		$owner = $this->block->getOwner();
		$this->tpl->setVariable('author', $owner->getUsername() .' ('. $owner->getFullname() .')');
		$this->tpl->setVariable('media_connect_id', $this->block->getMediaConnectId());

		if ($this->block->isPublic(false)) {
			$this->tpl->setVariable('access', T('pages.block.access_public'));
		} else {
			$this->tpl->setVariable('access', T('pages.block.access_private'));
		}

		if ($this->block->getShowInSummary()) {
			$checked = ' checked="checked"';
		} else {
			$checked = '';
		}
		$this->tpl->setVariable('show_in_summary_checked', $checked);

		if ($this->mayEdit) {
			$this->tpl->touchBlock('form_submit_button');
		} else {
			$this->tpl->touchBlock('reactivate');
		}
		

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.feedback').": '".$title."'");

		$this->tpl->show();
	}

	// }}}
	// Dimension groups {{{

	function doDimGroups()
	{
		$workingPath = getpost('working_path');

		if (!$this->init($workingPath)) {
			return;
		}
		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditDimensionGroups.html");
		$this->initTemplate('dim_groups');

		$this->dims = DataObject::getBy('Dimension','getAllByBlockId',$this->block->getId());
		$this->dimGroups = DataObject::getBy('DimensionGroup','getAllByBlockId',$this->block->getId());

		if (post('id') && !isset($this->dimGroups[post('id')])) {
			$this->checkAllowed('invalid', true); 
		}

		if (post('edit')) {
			$group = $this->dimGroups[post('id')];
			$title = $group->get('title');
			$dimIds = $group->get('dimension_ids');
			$this->tpl->setVariable('edit_id', $group->get('id'));
			$this->tpl->touchBlock('edit_group');
		} elseif (post('delete')) {
			$group = $this->dimGroups[post('id')];
			$title = $group->get('title');
			if ($count = $group->usedInFeedback()) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_group_del_inuse', MSG_RESULT_NEG, array('name' => $title, 'count' => $count)); 
			} else if (!$group->delete()) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_group_del_error', MSG_RESULT_NEG, array('name' => $title));
			} else {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_group_deleted', MSG_RESULT_POS, array('name' => $title));
			}
			redirectTo('feedback_block', array('action' => 'dim_groups', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		} elseif (post('save')) {
			if (!post('title') || !post('dim', array())) {
				// Invalid input
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_group_fields', MSG_RESULT_NEG);
				$this->tpl->setVariable('edit_id', post('id'));
				$title = '';
				$dimIds = post('dim', array());

				if (post('id') == 0) {
					$this->tpl->touchBlock('create_group');
				} else {
					$this->tpl->touchBlock('edit_group');
					if (!post('title')) $title = $this->dimGroups[post('id')]->get('title');
					if (!post('dim')) $dimIds = $this->dimGroups[post('id')]->get('dimension_ids');
				}
			} else {
				if (post('id') == 0) {
					// Create new group
					DataObject::create('DimensionGroup',
								array('block_id' => $this->block->getId(), 
									  'title' => post('title'), 
									  'dimension_ids' => array_keys(post('dim', array()))));
					redirectTo('feedback_block', array('action' => 'dim_groups', 'working_path' => $workingPath, 'resume_messages' => 'true'));
				}
				// Save existing group
				$group = $this->dimGroups[post('id')];
				$group->set('title',post('title'));
				$group->set('dimension_ids',array_keys(post('dim', array())));
				$group->commit();
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_group_saved', MSG_RESULT_POS, array('name' => $group->get('title')));
				redirectTo('feedback_block', array('action' => 'dim_groups', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}
		} else {
			// List existing groups
			if (count($this->dimGroups) != 0) {
				$this->tpl->touchBlock('have_groups');
				foreach ($this->dimGroups as $group) {
					$this->tpl->setVariable('working_path', $workingPath);
					$this->tpl->setVariable('id', $group->get('id'));
					$this->tpl->setVariable('title', $group->get('title'));
					$this->tpl->parse('group');
				}
			}
			$this->tpl->touchBlock('create_group');
			$this->tpl->setVariable('edit_id', 0);
			$title = '';
			$dimIds = array();
		}

		$this->tpl->setVariable('title', (post('title') ? post('title') : $title));
		$orderedDims = array();
		foreach ($dimIds as $dimId) {
			if (!isset($this->dims[$dimId])) continue;
			$orderedDims[] = $this->dims[$dimId];
			unset($this->dims[$dimId]);
		}
		$orderedDims = array_merge($orderedDims, array_values($this->dims));
		foreach ($orderedDims as $dim) {
			$this->tpl->setVariable('dim_id', $dim->get('id'));
			$this->tpl->setVariable('dim_title', $dim->getTitle());
			$checked = in_array($dim->getId(), $dimIds) ? ' checked="checked"' : '';
			$this->tpl->setVariable('dim_checked', $checked);
			$this->tpl->parse('dim_list');
		}

		$body = $this->tpl->get();
		$title = $this->block->getTitle();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.feedback').": '".$title."'");
		$this->tpl->show();
	}

	// }}}
	// Pages functions {{{

	function doCreateFeedbackPage()
	{
		$workingPath = getpost('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('create', true);

		$infos = array();
		$infos['title'] = T('pages.feedback_page.new').' '.(count($this->block->getTreeChildren()) + 1);

		if ($newPage = $this->block->createTreeChild($infos)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.feedback_page.msg.created_success', MSG_RESULT_POS, array("title" => $newPage->getTitle()));
				TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.create_feedbackpage', array('title' => $this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.feedback_page.msg.created_failure', MSG_RESULT_NEG, array("title" => $infos['title']));
		}

		redirectTo('feedback_page', array('working_path' => $workingPath, 'item_id' => $newPage->getId(), 'resume_messages' => 'true'));
	}

	function doOrganize()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("OrganizeFeedbackBlock.html");
		$this->initTemplate("organize");

		$items = $this->block->getTreeChildren();

		for ($i = 0; $i < count($items); $i++) {
			$this->tpl->setVariable('title', $items[$i]->getTitle());
			$this->tpl->setVariable('id', $items[$i]->getId());
			$this->tpl->parse('block_item');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.feedback').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doOrder()
	{
		$workingPath = getpost('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$newOrder = post('order');

		$errors = $this->block->orderChilds($newOrder);
		TestStructure::storeCurrentStructure($this->blocks[1]->getId(),
			array('structure.reorder_feedback_pages', array('title' => $this->block->getTitle())));

		redirectTo('feedback_block', array('action' => 'organize', 'working_path' => $workingPath));
	}

	// }}}
	// Save/delete block {{{

	function doSave()
	{
		$workingPath = getpost('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$modifications['title'] = post('title');
		$modifications['description'] = post('description');
		$modifications['show_in_summary'] = post('show_in_summary');

		// Set new source IDs
		$ids = post('source_ids');
		if (!is_array($ids)) $ids = array();
		$this->block->setSourceIds($ids);

		$checkValues = array(
			array('title', post('title'), array(CORRECTION_CRITERION_LENGTH_MIN => 1)),
		);
		$GLOBALS["MSG_HANDLER"]->flushMessages();
		for($i = 0; $i < count($checkValues); $i++) {
			$tmp = getCorrectionMessage(T('forms.'.$checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
			if(count($tmp) > 0) {
				$this->correctionMessages[$checkValues[$i][0]] = $tmp;
			}
		}

		if(count($this->correctionMessages) > 0) {
			$this->doEdit();
			return;
		}

		// Modify it
		if ($this->block->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->block->getTitle())));
		}

		redirectTo('feedback_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doConfirmDelete()
	{
		$workingPath = getpost('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('delete', true);

		$this->tpl->loadTemplateFile("DeleteFeedbackBlock.html");
		$this->initTemplate("delete");

		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests))
			$this->tpl->touchBlock('delete_not_possible');

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.feedback').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	/**
	 * Fuctionality for the tab page for editing certificate preferences
	 */
	function doEditCertificate()
	{
		$workingPath = getpost('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("EditCertificate.html");
		$this->initTemplate("certificate");

		$this->tpl->touchBlock("preferences");
		
		// Is the certificate enabled? If yes, then preselect the saved items.
		$db = &$GLOBALS['dao']->getConnection();
		$cert = $db->getRow("SELECT cert_enabled, cert_fname_item_id, cert_lname_item_id, cert_bday_item_id, cert_template_name, cert_disable_barcode FROM {$this->block->table} WHERE id={$this->block->id}");
		
		if(PEAR::isError($cert))
		{
			$GLOBALS['MSG_HANDLER']->addMsg("pages.manage_item_templates.msg.error_writing_database", MSG_RESULT_NEG);
			redirectTo('feedback_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
		if($cert['cert_enabled'] || get('enabled') == 'true')
		{
			$this->tpl->setVariable('display', 'block');
			$this->tpl->setVariable('checked', 'checked=\"checked\"');
		}
		else
		{
			$this->tpl->setVariable('display', 'none');
		}

		// Show the pdf template file
		if($cert['cert_template_name'] == "")
		{
			$this->tpl->setVariable("color_name", "gray");
			$this->tpl->setVariable("template_name", T('pages.feedback_block.no_template_file'));
			$this->tpl->touchBlock("upload_form");
			$showManually = 0;
		}
		else
		{	//check if file acually exists
			$cert_id = Medium::getIdByFilename($cert['cert_template_name']);
			$filehandle = new FileHandling(); 
			if(is_file($filehandle->getMediaPath($cert_id))){
				$this->tpl->setVariable("color_name", "black");
				$this->tpl->setVariable("template_name", $cert['cert_template_name']);
				$showManually = 1;
			} else {
				$this->tpl->setVariable("color_name", "red");
				$this->tpl->setVariable("template_name", T('pages.feedback_block.no_template_file').": ".$cert['cert_template_name']);
				$showManually = 0;
			}
			$this->tpl->touchBlock("delete_template");
		}


		// Show the barcode selection
		$this->tpl->touchBlock('barcode_item');
		if($cert['cert_disable_barcode'] == "1")
			$this->tpl->setVariable('selected_barcode_item', 'checked="checked"');
		

		$valid = get('valid', '');

		if($valid == 'true')
		{
			$this->tpl->setVariable('message_color', 'green');
			$this->tpl->setVariable('result_message', T('pages.check_certificate.result_pos'));
		}
		elseif($valid == 'false')
		{
			$this->tpl->setVariable('message_color', 'red');
			$this->tpl->setVariable('result_message', T('pages.check_certificate.result_neg'));
		}
		
		$name = get('name', '');
		$date = get('date', '');
		
		if ($name != 'false' && $date != 'false') {
			$this->tpl->setVariable('name', $name);
			$this->tpl->setVariable('date', $date);
			$this->tpl->hideBlock('resultBarCodeNeg');
		}
		if ($name == 'false' && $date == 'false') {
			$this->tpl->hideBlock('resultBarCodePos');
			$this->tpl->setVariable('message_color', 'red');
			$this->tpl->setVariable('result_message_barcode', T('pages.check_certificate.result_neg'));
		}
		if ($name == '' && $date =='') {
			$this->tpl->hideBlock('resultBarCodePos');
			$this->tpl->hideBlock('resultBarCodeNeg');
		}
			
		$blockId = $this->block->getId();
		
		$this->tpl->setVariable('working_path', $workingPath);
		$this->tpl->setVariable('block_id', $blockId);
		
		$this->tpl->hideBlock('certificate_manuell');
		$user = $this->portal->getUser();
		if ( $user->checkPermission('admin') && $showManually)
			$this->tpl->touchBlock('certificate_manuell');
		

		// Deliver the page
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.feedback').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	/**
	 * Save the changes the user made on the certificate preferences tab page
	 */
	function doSaveCertificate()
	{
		$workingPath = getpost('working_path');
		$db = &$GLOBALS['dao']->getConnection();

		require_once(CORE."types/FileHandling.php");
		$filehandling = new FileHandling();
		if(isset($_POST['delete_template']))
		{
			// Delete Button pressed, delete the template
			$this->deleteTemplate();
		}

		$enabled = (post('enabled', '') == 'enabled') ? TRUE : FALSE;
		$disablebarcode = post('cert_disable_barcode', '');
		
		$cert = $db->getRow("SELECT cert_template_name FROM {$this->block->table} WHERE id={$this->block->id}");
		$templateFile = $cert['cert_template_name'];

		
		// enabled
		if($enabled)
		{
			$newFilename = "";
			// upload the template
			if(!empty($_FILES['template_file']['name']))
			{
				$file = $_FILES['template_file'];
				if($file['type'] == 'application/pdf' || $file['type'] == 'application/x-pdf' || $file['type'] == 'application/x-download' || $file['type'] == 'application/octet-stream')
				{
					$result = $filehandling->uploadMedia('template_file', $this->block->getMediaConnectId());
					$newFilename = $result['newFilename'];
					if($newFilename)
					{
						if ($this->block->getMediaConnectId() == NULL) {
							$tmp = preg_split("/_/", $newFilename);
							$mediaConnectId = $tmp[0];
							$this->block->modify(array('media_connect_id' => $mediaConnectId));
						}

						$GLOBALS['MSG_HANDLER']->addMsg("pages.feedback_block.file_uploaded_successfully", MSG_RESULT_POS);
					}
					else
					{
						$GLOBALS['MSG_HANDLER']->addMsg("pages.feedback_block.error_uploading_file", MSG_RESULT_NEG);
						redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'resume_messages' => 'true'));
					}
				}
				$templateFile = $newFilename;
			} elseif ($templateFile == NULL)
			{
				$GLOBALS['MSG_HANDLER']->addMsg("pages.feedback_block.no_template_file_specified", MSG_RESULT_NEG);
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}
			

			$query = "SELECT cert_enabled FROM {$this->block->table} WHERE id = ?";
			$cert = $db->getRow($query, array($this->block->id));
			if(PEAR::isError($cert))
			{
				$GLOBALS['MSG_HANDLER']->addMsg("pages.manage_item_templates.msg.error_writing_database", MSG_RESULT_NEG);
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}
			if($cert['cert_enabled']) 
			{
				$query = "UPDATE {$this->block->table} SET cert_disable_barcode = ? , cert_template_name = ? WHERE id = ? LIMIT 1";
				$db->query($query, array($disablebarcode, $templateFile, $this->block->id));
			}
			else 
			{
				$query = "UPDATE {$this->block->table} SET cert_enabled = 1, cert_disable_barcode = ? , cert_template_name = ? WHERE id = ? LIMIT 1";
				$db->query($query, array($disablebarcode, $templateFile, $this->block->id));
			}
		}
		else //disabled
		{
			$this->deleteTemplate(false);
			$query = "UPDATE {$this->block->table} SET cert_enabled=0,cert_disable_barcode=NULL,cert_fname_item_id=NULL,cert_lname_item_id=NULL,cert_bday_item_id=NULL,cert_template_name='' WHERE id = ? LIMIT 1";
			$db->query($query, array($this->block->id));
		}

		$GLOBALS['MSG_HANDLER']->addMsg("pages.feedback_block.save_certificate.success", MSG_RESULT_POS);
		redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function deleteTemplate($deleteFromTable = true)
	{
		$workingPath = get('working_path');
		$db = &$GLOBALS['dao']->getConnection();
		
		require_once(CORE."types/FileHandling.php");
		$filehandling = new FileHandling();
		if(isset($_POST['delete_template']))
		{
			$query = "SELECT cert_template_name FROM {$this->block->table} WHERE id = ?";
			$filename = $db->getOne($query, array($this->block->id));
			if($deleteFromTable)
			{
				$query = "UPDATE {$this->block->table} SET cert_template_name='' WHERE id = ? LIMIT 1";
				$result = $db->query($query, array($this->block->id));
			}
			if(PEAR::isError($result))
			{
				$GLOBALS['MSG_HANDLER']->addMsg("pages.manage_item_templates.msg.error_writing_database", MSG_RESULT_NEG);
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}
			else
			{
				$filehandling->deleteMediaByFilename($filename);
				$GLOBALS['MSG_HANDLER']->addMsg("pages.feedback_block.template_file_deleted", MSG_RESULT_POS);
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			}

		}
	}

	function doCheckCertificate()
	{
		$workingPath = get('working_path');
		$firstname = post('firstname', '');
		$lastname = post('lastname', '');
		$altName = str_replace(' ', '', post('name', ''));
		$birthday = post('birthday', '');
		$checksum = post('checksum', '');
		
		$name = $firstname." ".$lastname;
		if($name != '' && $birthday != '' && $checksum != '')
		{
			$db = &$GLOBALS['dao']->getConnection();
			$query = "SELECT * FROM ".DB_PREFIX."certificates WHERE checksum = ? LIMIT 1";
			$cert = $db->getRow($query, array($checksum));
			if ($cert == NULL)
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'valid' => 'false'));
			//New store format is used if name and birthday exist in table certificate
			if ($cert['birthday'] == NULL) {
				$birthday = explode(".",$birthday);
				$birthday = strtotime($birthday[1]."/".$birthday[0]."/".$birthday[3]);
			}
			$tmpChecksum341 = PdfGeneratorPage::myHash($cert['random'], $name, $birthday, $cert['stamp'], $cert['test_run_id']);
			$tmpChecksum = md5($cert['random'].$name.$birthday.$cert['stamp'].$cert['test_run_id']);
			$tmpChecksumAlt = md5($cert['random'].$altName.$birthday.$cert['stamp'].$cert['test_run_id']);
			$valid = ($tmpChecksum == $cert['checksum'] || $tmpChecksumAlt == $cert['checksum'] || $tmpChecksum341 == $cert['checksum']);
			if($valid)
			{
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'valid' => 'true'));
			}
			else
			{
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'valid' => 'false'));
			}
		}
		else
		{
			$GLOBALS['MSG_HANDLER']->addMsg("pages.check_certificate.wrong_input", MSG_RESULT_NEG);
			redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
	}
	
	function doCheckCertificateBarCode()
	{
		$workingPath = get('working_path');
		$checksum = post('checksum', '');
		$returnPage = get('return_page');
		
		//Set default values
		$name = 'XXXXXX';
		$birthday = 'XXXXXX';
		
		//Only get blockId if the refering page is editFeedBackBlock, otherwise this->block is RootBlock
		if ($returnPage != "checkCert")
			$blockId = $this->block->getId();

		$db = &$GLOBALS['dao']->getConnection();
		
		//Get the certificate if it exist
		$query = "SELECT * FROM ".DB_PREFIX."certificates WHERE checksum = ?";
		$cert = $db->getAll($query, array($checksum));
		$certarray = $cert;
		if (isset($certarray[0])) {
			$cert = $certarray[0];
			$userId = $db->getOne('SELECT user_id FROM '.DB_PREFIX.'test_runs  WHERE id = ?', array($cert['test_run_id']));
		}
		else
			$cert = NULL;
		
		if ($cert == NULL)
			if ($returnPage == "checkCert")
				redirectTo('check_cert', array('action' => 'check_certificate',  'name' => 'false', 'date' => 'false'));
			else
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'name' => 'false', 'date' => 'false'));
	
		$form_filled = NULL;
		//get user if exist
		if ($userId != 0) {
			require_once(CORE.'types/UserList.php');
			$userList = new UserList();
			$user = $userList->getUserById($userId);		
			$form_filled = $user->get('form_filled');
		}
		else {
			$name = "T-A-N";
		}
		//If name and birthday in certificate table is empty, try to get the data from the user or test run
		if  (($cert['name'] == NULL) && ($cert['birthday'] == NULL)) {
			if (isset($blockId)) {
				//first check if user filled the certificate formular
				if ($form_filled != NULL) {
					$name = $user->get('full_name');
					$birthday =$user->get('u_bday');
					$birthday = date('d.m.Y', $birthday);
				}
				else {
					$userData = $this->block->acquireCertData($cert['test_run_id'], $blockId);
					$name = $userData['firstname'].' '.$userData['lastname'];
					$birthday = $userData['birthday'];
				}
			}
	
			if ($returnPage == "checkCert")
			{
				$testId = $db->getOne('SELECT test_id FROM '.DB_PREFIX.'test_runs  WHERE id = ?', array($cert['test_run_id']));
				$result = $db->getAll('SELECT id FROM '.DB_PREFIX.'blocks_connect  WHERE parent_id = ?', array($testId));
		
				foreach($result as $row) {
					$feedBack = $db->getRow('SELECT * FROM '.DB_PREFIX.'feedback_blocks  WHERE id = ?', array($row['id']));
					//first check if user filled the certificate formular, else get data from the testrun
					if ($form_filled != NULL) {
						$name = $user->get('full_name');
						$birthday = $user->get('u_bday');
						$birthday = date('d.m.Y', $birthday);
					}
					elseif ($feedBack['cert_fname_item_id'] != NULL) {
						$userData = FeedbackBlock::acquireCertData($cert['test_run_id'], $row['id']);
						$name = $userData['firstname'].' '.$userData['lastname'];
						$birthday = $userData['birthday'];
						$blockId = $row['id'];
					}
					
				}
				for($i = 0; $i < count($certarray); $i++){
					if($i == 0) {
						$testId = $db->getOne('SELECT test_id FROM '.DB_PREFIX.'test_runs  WHERE id = ?', array($certarray[$i]['test_run_id']));
						$testTitle = $db->getOne('SELECT title FROM '.DB_PREFIX.'container_blocks WHERE id = ?', array($testId));
					}
					else {
						$testId = $db->getOne('SELECT test_id FROM '.DB_PREFIX.'test_runs  WHERE id = ?', array($certarray[$i]['test_run_id']));
						$testTitle .= ", ".$db->getOne('SELECT title FROM '.DB_PREFIX.'container_blocks WHERE id = ?', array($testId));
					}

				}
			}
		}
		else {
			$name = $cert['name'];
			$birthday = date('d.m.Y', $cert['birthday']);
				for($i = 0; $i < count($certarray); $i++){
					if($i == 0) {
						$testId = $db->getOne('SELECT test_id FROM '.DB_PREFIX.'test_runs  WHERE id = ?', array($certarray[$i]['test_run_id']));
						$testTitle = $db->getOne('SELECT title FROM '.DB_PREFIX.'container_blocks WHERE id = ?', array($testId));
					}
					else {
						$testId = $db->getOne('SELECT test_id FROM '.DB_PREFIX.'test_runs  WHERE id = ?', array($certarray[$i]['test_run_id']));
						$testTitle .= ", ".$db->getOne('SELECT title FROM '.DB_PREFIX.'container_blocks WHERE id = ?', array($testId));
					}

				}		
		}
		
		if ($returnPage == "checkCert")
		{
			if ($cert != NULL)
				redirectTo('check_cert', array('action' => 'check_certificate',  'name' => $name, 'date' => $birthday, 'testTitle' => $testTitle));
			else
				redirectTo('check_cert', array('action' => 'check_certificate',  'name' => 'false', 'date' => 'false'));
		}
		else
		{
			if ($cert != NULL)
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'name' => $name, 'date' => $birthday));
			else
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath, 'name' => 'false', 'date' => 'false'));
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

	
		$children = $this->block->getTreeChildren();
		
		$this->block->modify(array('disabled' => 0));
		
		foreach($children as $child) {
			$child->modify(array('disabled' => 0));		
		}
	
		$db = &$GLOBALS['dao']->getConnection();
		$db->query("UPDATE ".DB_PREFIX."blocks_connect SET disabled = 0 WHERE id=?", array($this->block->getId()));
				
		
		if (TestStructure::existsStructureOfTests($tests)) {	
			TestStructure::storeCurrentStructure($pathIds[2], array('structure.enable_feedback_block', array('title' => $this->block->getTitle())));
			$GLOBALS['MSG_HANDLER']->addMsg('pages.enable_object.success', MSG_RESULT_POS, array('title' => $this->block->getTitle()));			
		}

		redirectTo('feedback_block', array('working_path' => $workingPath, 'resume_messages' => 'true'));
	}
	
}

?>
