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

libLoad('utilities::getCorrectionMessage');

/**
 * Displays the relevant pages for info blocks
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class InfoBlockPage extends BlockPage
{
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

		if (! $this->block->isInfoBlock()) {
			$page = &$GLOBALS["PORTAL"]->loadPage('block_edit');
			$page->run();
			return FALSE;
		}

		$this->tpl->setGlobalVariable('working_path', $workingPath);

		$this->targetPage = 'info_block';
		return TRUE;
	}

	function initTemplate($activeTab)
	{
		$tabs = array(
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("info_block", array("action" => "edit", "working_path" => $this->workingPath))),
			"organize" => array("title" => "tabs.test.structure", "link" => linkTo("info_block", array("action" => "organize", "working_path" => $this->workingPath))),
			"copy_move" => array("title" => "tabs.test.copy_move", "link" => linkTo("info_block", array("action" => "copy_move", "working_path" => $this->workingPath))),
			"edit_perms" => array("title" => "tabs.test.perms", "link" => linkTo("info_block", array("action" => "edit_perms", "working_path" => $this->workingPath))),
			"delete" => array("title" => "tabs.test.delete.infoblock", "link" => linkTo("info_block", array("action" => "confirm_delete", "working_path" => $this->workingPath))),
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
		if (isset($this->workingPath) && $this->mayCreate && $this->mayEdit)
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

	// ACTIONS

	function doEdit()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->tpl->loadTemplateFile("EditItemBlock.html");
		$this->initTemplate("edit");

		$this->checkAllowed('view', true);

		$this->tpl->loadTemplateFile("EditInfoBlock.html");
		$this->initTemplate("edit");

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}

		if(get('revert') == 'true') {
			$this->tpl->setVariable('title', post('title'));
			$this->tpl->setVariable('description', post('description'));
		} else {
			$this->tpl->setVariable('title', $this->block->getTitle());
			$this->tpl->setVariable('description', $this->block->getDescription());
		}
		$this->tpl->setVariable('t_modified', $this->block->getModificationTime());
		$this->tpl->setVariable('t_created', $this->block->getCreationTime());
		$owner = $this->block->getOwner();
		$this->tpl->setVariable('author', $owner->getUsername() .' ('. $owner->getFullname() .')');
		$this->tpl->setVariable('media_connect_id', $this->block->getMediaConnectId());

		if ($this->block->isPublic(false)) {
			$this->tpl->setVariable('access', T('pages.block.access_public'));
		}
		else {
			$this->tpl->setVariable('access', T('pages.block.access_private'));
		}

		if ($this->mayEdit) {
			$this->tpl->touchBlock('form_submit_button');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.info').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doSave()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

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

		$this->checkAllowed('edit', true);

		$modifications['title'] = post('title');
		$modifications['description'] = post('description');

		if ($this->block->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->block->getTitle())));
		}

		redirectTo('info_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doOrganize()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("OrganizeInfoBlock.html");
		$this->initTemplate("organize");

		$pages = $this->block->getTreeChildren();

		for ($i = 0; $i < count($pages); $i++)
		{
			$this->tpl->setVariable('id', $pages[$i]->getId());
			$this->tpl->setVariable('title', $pages[$i]->getTitle());
			$this->tpl->parse('block_item');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.info').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doCreateInfoPage()
	{
		$workingPath = post('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('create', true);

		$infos = array();
		$infos['title'] = T('pages.info_page.new').' '.(count($this->block->getTreeChildren()) + 1);

		if ($newPage = $this->block->createTreeChild($infos)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.created_success', MSG_RESULT_POS, array("title" => $newPage->getTitle()));
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.create_infopage', array('title' => $this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.created_failure', MSG_RESULT_NEG, array("title" => $infos['title']));
		}

		redirectTo('info_page', array('working_path' => $workingPath, 'item_id' => $newPage->getId(), 'resume_messages' => 'true'));
	}

	function doConfirmDelete()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('delete', true);

		$this->tpl->loadTemplateFile("DeleteInfoBlock.html");
		$this->initTemplate("delete");

		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests))
			$this->tpl->touchBlock('delete_not_possible');

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.info').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doOrder()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$newOrder = post('order');

		$errors = $this->block->orderChilds($newOrder);
		TestStructure::storeCurrentStructure($this->blocks[1]->getId(),
			array('structure.reorder_info_pages', array('title' => $this->block->getTitle())));

		redirectTo('info_block', array('action' => 'organize', 'working_path' => $workingPath));
	}
}

?>
