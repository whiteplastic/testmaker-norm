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
require_once(CORE.'types/MimeTypes.php');

libLoad('utilities::getCorrectionMessage');
libLoad('html::defuseScripts');
libLoad('utilities::deUtf8');

/**
 * Displays the relevant pages for container blocks
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class ContainerBlockPage extends BlockPage
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

		if (! $this->block->isContainerBlock()) {
			$page = &$GLOBALS["PORTAL"]->loadPage('block_edit');
			$page->run();
			return FALSE;
		}

		$this->tpl->setGlobalVariable('working_path', $workingPath);
		$this->targetPage = 'container_block';

		return TRUE;
	}

	function initTemplate($activeTab)
	{
		$tabs = array(
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("container_block", array("action" => "edit", "working_path" => $this->workingPath))),
			"organize" => array("title" => "tabs.test.structure", "link" => linkTo("container_block", array("action" => "organize", "working_path" => $this->workingPath))),
			"style" => array("title" => "tabs.test.style", "link" => linkTo("container_block", array("action" => "style", "working_path" => $this->workingPath))),
			"preview" => array("title" => "tabs.test.preview", "link" => linkTo("container_block", array("action" => "preview", "working_path" => $this->workingPath))),
			"copy_move" => array("title" => "tabs.test.copy_move", "link" => linkTo("container_block", array("action" => "copy_move", "working_path" => $this->workingPath))),
			"publish" => array("title" => "tabs.test.publish", "link" => linkTo("container_block", array("action" => "publish", "working_path" => $this->workingPath))),
			"edit_perms" => array("title" => "tabs.test.perms", "link" => linkTo("container_block", array("action" => "edit_perms", "working_path" => $this->workingPath))),
			"delete" => array("title" => "tabs.test.delete.test", "link" => linkTo("container_block", array("action" => "confirm_delete", "working_path" => $this->workingPath))),
			"show_history" => array("title" => "tabs.test.history", "link" => linkTo("container_block", array("action" => "show_history", "working_path" => $this->workingPath))),
		);

		$disabledTabs = array();
		$owner = $this->block->getOwner();
		$user = $GLOBALS['PORTAL']->getUser();
		if ($owner->getId() != $user->getId() && !$this->checkAllowed('admin', false, NULL)) {
			$disabledTabs[] = 'edit_perms';
		}
		if (!$this->mayEdit) {
			$disabledTabs[] = 'organize';
			$disabledTabs[] = 'style';
			$disabledTabs[] = 'publish';
		}
		if (!$this->mayPublish) {
			$disabledTabs[] = 'publish';
		}
		if (!$this->mayCopy && !$this->mayLink) {
			$disabledTabs[] = 'copy_move';
		}
		if (!$this->mayRun || $this->block->getShowSubtests()) {
			$disabledTabs[] = 'preview';
		}
		if (!$this->mayDelete) {
			$disabledTabs[] = 'delete';
		}
		$parentBlock = $this->block->getParent(get('working_path'));
		if ($parentBlock->getShowSubtests())
		{
			$disabledTabs[] = 'publish';
		}

		if (count($this->block->getChildren()) <= 1) {
			$disabledTabs[] = "organize";
		}

		if (!$parentBlock->isRootBlock()) {
			$disabledTabs[] = "show_history";
		}

		if(!$this->isRootTest()){
			$disabledTabs[] = "preview";
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

	function isRootTest()
	{
		if (substr_count($this->workingPath, '_') == 3)
		{
			return TRUE;
		}
		return FALSE;
	}

	function setupAdminMenu()
	{
		if (isset($this->workingPath) && $this->mayCreate && $this->mayEdit)
		{

			if(substr_count(trim($this->workingPath, '_'), '_') < 2) {
				# button: new container block
				$this->tpl->setVariable('page', 'container_block');
				$this->tpl->setVariable('name', 'action');
				$this->tpl->setVariable('value', 'create_container');
				$this->tpl->parse('menu_admin_item_additional_info');

				$this->tpl->setVariable('name', 'working_path');
				$this->tpl->setVariable('value', $this->workingPath);
				$this->tpl->parse('menu_admin_item_additional_info');

				$this->tpl->setVariable('title', T('pages.container_block.new_container'));
				$this->tpl->parse('menu_admin_item');
			}

			if (!$this->block->getShowSubtests()) {
				# button: new item
				$this->tpl->setVariable('page', 'container_block');
				$this->tpl->setVariable('name', 'action');
				$this->tpl->setVariable('value', 'create_item');
				$this->tpl->parse('menu_admin_item_additional_info');

				$this->tpl->setVariable('name', 'working_path');
				$this->tpl->setVariable('value', $this->workingPath);
				$this->tpl->parse('menu_admin_item_additional_info');

				$this->tpl->setVariable('title', T('pages.container_block.new_item'));
				$this->tpl->parse('menu_admin_item');

				# button: new text page
				$this->tpl->setVariable('page', 'container_block');
				$this->tpl->setVariable('name', 'action');
				$this->tpl->setVariable('value', 'create_info_page');
				$this->tpl->parse('menu_admin_item_additional_info');

				$this->tpl->setVariable('name', 'working_path');
				$this->tpl->setVariable('value', $this->workingPath);
				$this->tpl->parse('menu_admin_item_additional_info');

				$this->tpl->setVariable('title', T('pages.container_block.new_info_page'));
				$this->tpl->parse('menu_admin_item');
			}

			# button: new feedback block
			$this->tpl->setVariable('page', 'container_block');
			$this->tpl->setVariable('name', 'action');
			$this->tpl->setVariable('value', 'create_feedback');
			$this->tpl->parse('menu_admin_item_additional_info');

			$this->tpl->setVariable('name', 'working_path');
			$this->tpl->setVariable('value', $this->workingPath);
			$this->tpl->parse('menu_admin_item_additional_info');

			$this->tpl->setVariable('title', T('pages.container_block.new_feedback_block'));
			$this->tpl->parse('menu_admin_item');
		}
	}

	// ACTIONS

	function doEdit()
	{
		// include fckeditor
		require_once(PORTAL.'Editor.php');
		
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return FALSE;
		}

		$this->checkAllowed('view', true);

		$this->tpl->loadTemplateFile("EditContainerBlock.html");
		$this->initTemplate("edit");

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}

		if(get('revert') == 'true') {
			$this->tpl->setVariable('title', post('title'));
			$editor = new Editor('working_path='.$workingPath, post('description'));
			$editor->editor->ToolbarSet = 'noImage';
			$this->tpl->setVariable('description', $editor->CreateHtml());
			//$this->tpl->setVariable('description', post('description'));
		} else {
			$this->tpl->setVariable('title', $this->block->getTitle());
			$editor = new Editor('working_path='.$workingPath, $this->block->getDescription());
			$editor->editor->ToolbarSet = 'noImage';
			$this->tpl->setVariable('description', $editor->CreateHtml());
			//$this->tpl->setVariable('description', $this->block->getDescription());
		}

				
		$this->tpl->setVariable('t_modified', $this->block->getModificationTime());
		$this->tpl->setVariable('t_created', $this->block->getCreationTime());
		$owner = $this->block->getOwner();
		$this->tpl->setVariable('author', $owner->getUsername() .' ('. $owner->getFullname() .')');
		
		$random = $this->block->hasRandomItemOrder() ? 'checked="checked"' : '';
		$this->tpl->setVariable('random_order_checked', $random);

		$linkTo = linkToFile("start.php", array("test_path" => $workingPath), FALSE, TRUE);
		$linkToPt = preg_split("/\?/", $linkTo);

		$linkToPt[1] = str_replace("_", "-", $linkToPt[1]);
		if ($linkTo[strlen($linkToPt[1]) - 1] == "-")
			$linkTo[1] = substr_replace($linkToPt[1], "", strlen($linkToPt[1]) - 1, 1);
		$linkTo = $linkToPt[0]."?".$linkToPt[1];
		$this->tpl->setVariable('direct_access_url', $linkTo);

		
		if ($this->block->isPublic(false)) {
			$this->tpl->setVariable('access', T('pages.block.access_public'));
		} else {
			$this->tpl->setVariable('access', T('pages.block.access_private'));
		}

		if ($this->mayEdit) {
			$this->tpl->touchBlock('form_submit_button');
		}

		foreach($GLOBALS["TRANSLATION"]->getAvailableLanguages() as $language)
		{
			$this->tpl->setVariable("valueLang", $language);
			if ($this->block->getLanguage() == $language)
			{
				$this->tpl->setVariable("select", " selected=\"selected\"");
			}
			$this->tpl->setVariable("longLang", T('language.'.$language));
			$this->tpl->parse("languages");
		}

		if (!$this->isRootTest()) {
			$this->tpl->hideBlock('language_block');
			$this->tpl->hideBlock('show_subtests_block');
			$this->tpl->hideBlock('test_level_settings');
			$this->tpl->hideBlock('direct_access_block');
			if($this->block->getParent($workingPath)->getShowSubtests()) 
			{
				$check = $this->block->isInactive() ? ' checked="checked"' : '';
				$this->tpl->setVariable('subtest_inactive_checked', $check);
			}
		} else {
			$this->tpl->touchBlock('test_level_settings');
			$check = $this->block->getEnableSkip() ? ' checked="checked"' : '';
			$this->tpl->setVariable('enable_skip_checked', $check);

			$this->tpl->touchBlock('show_subtests_block');
			$check = $this->block->getShowSubtests() ? ' checked="checked"' : '';
			$this->tpl->setVariable('show_subtests_checked', $check);

			$this->tpl->touchBlock('direct_access_block');
			$this->tpl->hideBlock('subtest_inactive');
		}
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', "$title: '".$this->block->getTitle()."'");
		
		$parentBlock = $this->block->getParent($workingPath);
		$ul = new UserList();
		$groups = $ul->getGroupList();
		foreach ($groups as $group) {
			if ($parentBlock->id != 0) {
				$perm = $group->getPermissions($parentBlock);
				$this->block->setPermissions($perm, $group);
			}
		}
		
		$this->tpl->show();
	}

	function doCreateTans()
	{
		require_once(CORE."types/TANCollection.php");
		$dakc = new TANCollection($this->block->getId());

		$workingPath = get('working_path');

		$checkValues = array(
			array('forms.container_block.tan_amount', post('tan_amount'), array(CORRECTION_CRITERION_NUMERIC_MAX => TAN_MAX_NUM, CORRECTION_CRITERION_NUMERIC_MIN => 1)),
		);

		$GLOBALS["MSG_HANDLER"]->flushMessages();
		for($i = 0; $i < count($checkValues); $i++) {
			$tmp = getCorrectionMessage(T($checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
			if(count($tmp) > 0) {
				$this->correctionMessages[$checkValues[$i][0]] = $tmp;
			}
		}

		if(count($this->correctionMessages) > 0) {
			$this->doPublish();
			return;
		}

		$creationTime = $dakc->generateTANs(post('tan_amount'), $workingPath);
		if(!$creationTime)
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.container_block.new_tans_failed', MSG_RESULT_POS, array('amount' => post('tan_amount')));
			redirectTo('container_block', array('action' => 'publish', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.container_block.new_tans_success', MSG_RESULT_POS, array('amount' => post('tan_amount')));
			redirectTo('container_block', array('action' => 'publish', 'working_path' => $workingPath, 'resume_messages' => 'true', 'time' => $creationTime));
		}

	}

	function doPublishGroups()
	{
		$workingPath = getpost('working_path');
		if (!$this->init($workingPath)) {
			return false;
		}
		
		$run = post("run", array());
		$portal = post("portal", array());
		$review = post("review", array());
		if ((post('password')=='') && (isset($run[-2]) || isset($portal[-2]))) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.container_block.publish_groups_failed', MSG_RESULT_NEG);
			redirectTo('container_block', array('action' => 'publish', 'working_path' => $workingPath, 'resume_messages' => 'true'));
			return false;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('publish', true);

		$ul = new UserList();
		$groups = $ul->getGroupList(false, true);

		foreach ($groups as $group) {
			if ($group->isSpecial()) continue;

			$id = $group->get('id');
			$perms = $group->getPermissions($this->block);
			unset($perms['portal']);
			unset($perms['run']);
			// Legacy
			unset($perms['tanrun']);
			unset($perms['review']);

			if (isset($portal[$id]) && $portal[$id]) $perms['portal'] = 1;
			if (isset($run[$id]) && $run[$id]) $perms['run'] = 1;
			if (isset($review[$id]) && $review[$id]) $perms['review'] = 1;

			$group->setPermissions($perms, $this->block);
		}
		$this->block->modify(array('password' => post('password', '')));

		EditLog::addEntry(LOG_OP_PUBLISH, $this->block, NULL);

		$GLOBALS['MSG_HANDLER']->addMsg('pages.container_block.publish_groups_success', MSG_RESULT_POS);
		redirectTo('container_block', array('action' => 'publish', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doPublishDate()
	{
		libLoad('utilities::toTimestamp');

		$workingPath = getpost('working_path');
		if (!$this->init($workingPath)) {
			return false;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('publish', true);

		$this->block->modify(array('open_date' => toTimestamp(post('open_date','')), 'close_date' => toTimestamp(post('close_date', ''))));

		$GLOBALS['MSG_HANDLER']->addMsg('pages.container_block.publish_groups_success', MSG_RESULT_POS);
		redirectTo('container_block', array('action' => 'publish', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doPublish()
	{
		require_once(CORE."types/TANCollection.php");

		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return FALSE;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('publish', true);

		$this->tpl->loadTemplateFile("Publish.html");
		$this->initTemplate("publish");

		////////
		// Access configuration

		$ul = new UserList();
		$groups = $ul->getGroupList();
		$groupCount = 0;
		$guest = DataObject::getById('User', 0);
		$parentBlock = $this->block->getParent($workingPath); 
		$parentInSubtestMode = ($parentBlock == NULL) ? false : $parentBlock->getShowSubtests();
		
		foreach ($groups as $group) {
			if ($group->isSpecial()) continue;

			$this->tpl->setVariable('group_id', $group->get('id'));
			$this->tpl->setVariable('group_name', $group->get('groupname'));

			if (in_array($group->get('id'), $guest->getGroupIds())) {
				$this->tpl->setVariable('review_hidden', ' style="display:none"');
				if($this->block->getShowSubtests() || $parentInSubtestMode) 
				{
					$this->tpl->setVariable('run_disabled', ' disabled="disabled"');
					$this->tpl->setVariable('portal_disabled', ' disabled="disabled"');
				} 
			}

			// Check relevant permissions, gray out globally set ones
			foreach (array('portal', 'run', 'review') as $permName) {
				$perm = $group->checkPermission($permName, $this->block);
				$gperm = $group->checkPermission($permName);
				if (!$perm && !$gperm) continue;
				$checkStr = ' checked="checked"';
				if ($gperm) $checkStr .= ' disabled="disabled"';
				$this->tpl->setVariable("{$permName}_checked", $checkStr);
			}

			$this->tpl->parse('group_list');
			$groupCount++;
		}

		// Virtual groups
		$tanGrp = DataObject::getById('Group', GROUP_VIRTUAL_TAN);
		$pwdGrp = DataObject::getById('Group', GROUP_VIRTUAL_PASSWORD);
		$tanRunChecked = $tanGrp->checkPermission('run', $this->block) ? ' checked="checked"' : '';
		$pwdPortalChecked = $pwdGrp->checkPermission('portal', $this->block) ? ' checked="checked"' : '';
		$pwdRunChecked = $pwdGrp->checkPermission('run', $this->block) ? ' checked="checked"' : '';

		$this->tpl->setVariable('tan_run_checked', $tanRunChecked);
		$this->tpl->setVariable('pwd_portal_checked', $pwdPortalChecked);
		$this->tpl->setVariable('pwd_run_checked', $pwdRunChecked);
		$this->tpl->setVariable('password', $this->block->getPassword());
		if ($this->block->getShowSubtests() || $parentInSubtestMode) {
			$this->tpl->setVariable('pwd_run_disabled', 'disabled="disabled"');
			$this->tpl->setVariable('pwd_portal_disabled', 'disabled="disabled"');
		}

		if ($groupCount > 0) {
			$this->tpl->touchBlock('groups_save');
		} else {
			$this->tpl->touchBlock('no_groups');
			$this->tpl->touchBlock('groups_save');
		}
		$this->tpl->setVariable('working_path', $workingPath);

		////////
		// Open and close date
		if ($this->block->getOpenDate()) $this->tpl->setVariable('open_date', 'value="'.date('Y-m-d H:i', $this->block->getOpenDate()).'"');
		if ($this->block->getCloseDate()) $this->tpl->setVariable('close_date', 'value="'.date('Y-m-d H:i', $this->block->getCloseDate()).'"');

		////////
		// TANs

		for(reset($this->correctionMessages); list($key, $value) = each($this->correctionMessages);) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
		}

		//view link to created tans
		if(get('time', NULL) != NULL) {
			$this->tpl->touchBlock('show_new_tans');
			$this->tpl->setVariable('time', get('time'));
		}

		$dakc = new TANCollection($this->block->getId());
		$tansCount = $dakc->getTANCount();
		if($tansCount == 0)
		{
			$this->tpl->touchBlock('empty');
			$this->tpl->hideBlock('not_empty');
		}
		else
		{
			$this->tpl->hideBlock('empty');
			$this->tpl->touchBlock('not_empty');
			
			if (!isset($_SESSION["entriesPerPage"]))
			    $_SESSION["entriesPerPage"] = 20;
			//generate page list data
			if (isset($_POST["entries_per_page"])) {
				$entriesPerPage = intval($_POST["entries_per_page"]);
				$_SESSION["entriesPerPage"] = $entriesPerPage;
			}
			
			if (!isset($_POST["entries_per_page"]))
				$entriesPerPage = intval($_SESSION["entriesPerPage"]);
			  
			
			$this->tpl->setVariable($entriesPerPage . "_entries_per_page", "selected");
			$pageLinkDistance = 2;
			$pageCount = ceil($tansCount / $entriesPerPage);

			if (! isset($_SESSION["TAN_PAGE"])) {
				$_SESSION["TAN_PAGE"] = 1;
			}
			$_SESSION["TAN_PAGE"] = get("page_number", $_SESSION["TAN_PAGE"]);
			if ($_SESSION["TAN_PAGE"] > $pageCount || $_SESSION["TAN_PAGE"] == 0 || (isset($_SESSION["TAN_PAGE_COUNT"]) && $pageCount != $_SESSION["TAN_PAGE_COUNT"])) {
				$_SESSION["TAN_PAGE"] = 1;
			}
			$_SESSION["TAN_PAGE_COUNT"] = $pageCount;

			$this->tpl->touchBlock("page_selector");

			$this->tpl->setVariable('current_page', $_SESSION["TAN_PAGE"]);

			$lastType = "";

			//generate page list
			for ($i = 1; $i <= $pageCount; $i++)
			{
				$this->tpl->hideBlock("current_page");
				$this->tpl->hideBlock("other_page");

				$this->tpl->setVariable("page", $i);
				$this->tpl->setVariable("direct_page_link", linkTo("container_block", array("action" => "publish", "page_number" => $i, "working_path" => $workingPath, "sort_by" => get('sort_by'), "order" => get('order'))));

				if ($i == 1 || $i == $pageCount || ($i >= $_SESSION["TAN_PAGE"] - $pageLinkDistance && $i <= $_SESSION["TAN_PAGE"] + $pageLinkDistance))
				{
					if ($i == $_SESSION["TAN_PAGE"]) {
						$type = "current_page";
					}
					else {
						$type = "other_page";
					}
				}
				else {
					$type = "ellipsis";
				}

				if ($type != "ellipsis" || $lastType != $type) {
					$this->tpl->touchBlock($type);
					$lastType = $type;
				}

				$this->tpl->parse("direct_page_link");
			}

			if(get('sort_by') == 't_created' && get('order') != 'DESC') {
				if(get('sort_by') == 't_created') {
					$this->tpl->touchBlock('t_created_desc');
				}
				$this->tpl->setVariable('order_t_created', 'DESC');
			} else {
				if(get('sort_by') == 't_created') {
					$this->tpl->touchBlock('t_created_asc');
				}
				$this->tpl->setVariable('order_t_created', 'ASC');
			}
			if(get('sort_by') == 't_modified' && get('order') != 'DESC') {
				if(get('sort_by') == 't_modified') {
					$this->tpl->touchBlock('t_modified_desc');
				}
				$this->tpl->setVariable('order_t_modified', 'DESC');
			} else {
				if(get('sort_by') == 't_modified') {
					$this->tpl->touchBlock('t_modified_asc');
				}
				$this->tpl->setVariable('order_t_modified', 'ASC');
			}
			if((get('sort_by') != 't_created' && get('sort_by') != 't_modified' && get('sort_by') != 'test_run') || get('sort_by') == 'test_run' && get('order') != 'DESC') {
				if(get('sort_by') != 't_created' && get('sort_by') != 't_modified') {
					$this->tpl->touchBlock('test_run_desc');
				}
				$this->tpl->setVariable('order_test_run', 'DESC');
			} else {
				if(get('sort_by') != 't_created' && get('sort_by') != 't_modified') {
					$this->tpl->touchBlock('test_run_asc');
				}
				$this->tpl->setVariable('order_test_run', 'ASC');
			}

			//generate TAN list
		 	
			$tans = $dakc->getTANs($entriesPerPage * ($_SESSION["TAN_PAGE"] - 1), $entriesPerPage, get('sort_by'),get('order'));

			foreach($tans as $tan)
			{
				$this->tpl->setVariable('tan', $tan['access_key']);
				if($tan['test_run_id'] == '' || $tan['test_run_id'] == 0) {
					$this->tpl->setVariable('test_run_id', T('pages.container_block.tan_not_used'));
					$this->tpl->touchBlock('not_used');
					$this->tpl->hideBlock('used');
				} else {
					$this->tpl->touchBlock('test_run_link');
					$this->tpl->setVariable('test_run_id', $tan['test_run_id']);
					$this->tpl->touchBlock('used');
					$this->tpl->hideBlock('not_used');
					$this->tpl->setVariable('t_modified', $tan['t_modified']);
				}
				$this->tpl->setVariable('mail', $tan['mail']);
				$this->tpl->setVariable('t_created', $tan['t_created']);
				$this->tpl->parse('tans');
			}
		}
		if($this->block->getTanAskEmail())
		{
			$this->tpl->setVariable('tan_ask_email_checked', ' checked="checked"');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', "$title: '".$this->block->getTitle()."'");
		

		$this->tpl->show();
	}

	function doTanDates()
	{
		require_once(CORE."types/TANCollection.php");

		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return FALSE;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("TANDates.html");
		$this->initTemplate("tans");

		$dakc = new TANCollection($this->block->getId());
		$tanDates = $dakc->getTANDates();
		if(count($tanDates) == 0)
		{
			$this->tpl->touchBlock('empty');
			$this->tpl->hideBlock('not_empty');
		}
		else
		{
			$this->tpl->hideBlock('empty');
			$this->tpl->touchBlock('not_empty');

			//generate TAN date list

			foreach($tanDates as $tanDate)
			{
				$this->tpl->setVariable('tan_date', $tanDate);
				$this->tpl->parse('tan_dates');
			}
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', "$title: '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doViewTans()
	{
		require_once(CORE."types/TANCollection.php");

		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return FALSE;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("viewTANs.html");

		$dakc = new TANCollection($this->block->getId());
		$tans = $dakc->getTANsByDate(post('date', get('date')));
		if(count($tans) == 0)
		{
			$this->tpl->touchBlock('empty');
			$this->tpl->hideBlock('not_empty');
		}
		else
		{
			$this->tpl->hideBlock('empty');
			$this->tpl->touchBlock('not_empty');

			//generate TAN date list

			foreach($tans as $tan)
			{
				$this->tpl->setVariable('tan', $tan);
				$this->tpl->parse('tans');
			}
		}


		if (empty($result)) 
			$logo = "portal/images/tm-logo-sm.png";
		else 
			$logo = "upload/media/".$result;

		$body = $this->tpl->get();
		$this->tpl->loadTemplateFile("BareFrame.html");
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable("logo", $logo);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', "$title: '".$this->block->getTitle()."'");

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
			$tmp = getCorrectionMessage(T('forms.info_block.'.$checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
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
		$modifications['description'] = defuseScripts(deUtf8(post('fckcontent', '')));
		$modifications['language'] = post('language');
		$modifications['enable_skip'] = post('enable_skip', 0);
		$modifications['subtest_inactive'] = post('subtest_inactive', 0);
		$modifications['random_order'] = post('random_order');
		$modifications['show_subtests'] = post('show_subtests', 0);
		// Check if subtest mode is allowed
		if (!$this->block->getShowSubtests() && $modifications['show_subtests']) {
			// 1. only subtests and feedback on main level
			$children = $this->block->getChildren();
			foreach ($children as $block) {
				if (!$block->isContainerBlock() && !$block->isFeedbackBlock()) {
					$GLOBALS['MSG_HANDLER']->addMsg('pages.container_block.subtestmode_invalid_main', MSG_RESULT_NEG);
					redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
				}

				// 1a. make sure feedback within subtests does not reference other subtests
				if ($block->isContainerBlock()) {
					$subChildren = $block->getChildren();
					$subItemBlockIds = array();
					foreach ($subChildren as $subBlock) {
						if ($subBlock->isItemBlock()) $subItemBlockIds[$subBlock->getId()] = 1;
					}
					foreach ($subChildren as $subBlock) {
						if (!$subBlock->isFeedbackBlock()) continue;
						foreach ($subBlock->getSourceIds() as $sourceId) {
							if (!isset($subItemBlockIds[$sourceId])) {
								$errBlock = $GLOBALS['BLOCK_LIST']->getBlockById($sourceId);
								$GLOBALS['MSG_HANDLER']->addMsg('pages.container_block.subtestmode_invalid_feedback_ref', MSG_RESULT_NEG, array('subtest' => htmlentities($block->getTitle()), 'feedback_block' => htmlentities($subBlock->getTitle()), 'item_block' => htmlentities($errBlock->getTitle())));
								redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
							}
						}
					}
				}
			}
		}

		if ($this->block->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->block->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', -1, array('title' => htmlentities($this->block->getTitle())));
		}


		redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doConfirmDelete()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('delete', true);

		$this->tpl->loadTemplateFile("DeleteContainerBlock.html");
		$this->initTemplate("delete");
		
		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests))
			$this->tpl->touchBlock('delete_not_possible');

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', $title.": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doCreateContainer()
	{
		$workingPath = post('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('create', true);

		$data['title'] = T('pages.block.container_block_new').' '.($this->block->countChildrenByType(BLOCK_TYPE_CONTAINER)+1);

		if ($newBlock = $this->block->createChild(BLOCK_TYPE_CONTAINER, $data)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
			if (! $this->block->isRootBlock()) {
				TestStructure::storeCurrentStructure($this->block->getId(), array('structure.create_subtest'));
			}
			redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath.$newBlock->getId().'_', 'resume_messages' => 'true'));
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data);
			redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
	}

	function doCreateItem()
	{
		$workingPath = post('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('create', true);

		$data['title'] = T('pages.block.item_block_new').' '.($this->block->countChildrenByType(BLOCK_TYPE_ITEM)+1);
		$data['default_item_force'] = 1;
		if ($newBlock = $this->block->createChild(BLOCK_TYPE_ITEM, $data))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.create_itemblock'));

			// Run default item page create function
			setPost('working_path', $workingPath.$newBlock->getId().'_');
			$page = $GLOBALS["PORTAL"]->loadPage('item_block');
			$page->run('create_item');
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data);
			redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
	}

	function doCreateInfoPage()
	{
		$workingPath = post('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('create', true);

		$data['title'] = T('pages.block.info_block_new').' '.($this->block->countChildrenByType(BLOCK_TYPE_INFO)+1);

		if ($newBlock = $this->block->createChild(BLOCK_TYPE_INFO, $data))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.create_infoblock'));

			//run default info page create function
			setPost('working_path', $workingPath.$newBlock->getId().'_');
			$page = $GLOBALS["PORTAL"]->loadPage('info_block');
			$page->run('create_info_page');
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data['title']);
			redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
	}

	function doCreateFeedback()
	{
		$workingPath = post('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);
		$this->checkAllowed('create', true);

		$data['title'] = T('pages.block.feedback_block_new').' '.($this->block->countChildrenByType(BLOCK_TYPE_FEEDBACK)+1);

		if ($newBlock = $this->block->createChild(BLOCK_TYPE_FEEDBACK, $data)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
			TestStructure::storeCurrentStructure($this->blocks[1]->getId(), array('structure.create_feedbackblock'));
			redirectTo('feedback_block', array('action' => 'edit', 'working_path' => $workingPath.$newBlock->getId().'_', 'resume_messages' => 'true'));
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data);
			redirectTo('container_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
		}
	}

	function doOrganize()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("OrganizeContainerBlock.html");
		$this->initTemplate("organize");

		$childBlocks = $this->block->getChildren($this->block->getId());
		for ($i = 0; $i < count($childBlocks); $i++) {
			if ($childBlocks[$i]->getDisabled() != 1) {
				$this->tpl->setVariable('title', $childBlocks[$i]->getTitle());
				$this->tpl->setVariable('id', $childBlocks[$i]->getId());
				$this->tpl->parse('block_item');
			}
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', $title.": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doStyle()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("StyleContainerBlock.html");
		$this->initTemplate("style");

		require_once(CORE.'types/FileHandling.php');
		$fileHandling = new FileHandling();

		$submit = post('submit', NULL);

		// save changes
		if (isset($submit))
		{
			if ($_FILES['logo']['name'])
			{
				if (!array_key_exists($_FILES['logo']['type'], MimeTypes::$knownTypes))  {
					$GLOBALS['MSG_HANDLER']->addMsg('pages.media_organizer.upload_failed.mime', MSG_RESULT_NEG, array('file' => $_FILES['logo']['name']));
					$success = false;
					$delete = false;
				}
				else {
					$delete = $this->block->getLogo();
					$success = true;
				}
				// if old logo exists, delete it first
				if ($delete)
				{
					if ($fileHandling->deleteMediaConnection($delete))
					{
						$GLOBALS['MSG_HANDLER']->addMsg('pages.logo.del.pos', MSG_RESULT_POS);
					}
					else
					{
						$GLOBALS['MSG_HANDLER']->addMsg('pages.logo.del.neg', MSG_RESULT_NEG);
						$success = false;
					}
				}
				if ($success)
				{
					$media_connect_id = current($fileHandling->uploadMedia('logo', NULL, TRUE, $this->block->getId()));
					
					if ($this->block->setLogo($media_connect_id))
					{
						$GLOBALS['MSG_HANDLER']->addMsg('pages.logo.pos', MSG_RESULT_POS);
					}
					else
					{
						$GLOBALS['MSG_HANDLER']->addMsg('pages.logo.neg', MSG_RESULT_NEG);
					}
				}
			}
			// delete logo
			if (post('deleteLogo') == 'on' && $delete = $this->block->getLogo())
			{
				if ($fileHandling->deleteMediaConnection($delete))
				{
					$this->block->setLogo();
					EditLog::addEntry(LOG_OP_BLOCK_DELETE_LOGO, $this->block, NULL);
					$GLOBALS['MSG_HANDLER']->addMsg('pages.logo.del.pos', MSG_RESULT_POS);
				}
				else
				{
					$GLOBALS['MSG_HANDLER']->addMsg('pages.logo.del.neg', MSG_RESULT_NEG);
				}
			}
		}
		if (isset($submit['save']))
		{
			// item borders are stored as integer based on a 3-digit binary number (each digit stands for the corresponding border)
			$itemBorders = post('itemBorders', array());
			$border = '';
			for ($i = 0; $i < 3; $i++) {
				if (isset($itemBorders[$i])) $border .= '1';
				else $border .= '0';
			}
			$border = bindec($border);
			
			$fields = array(
				'background_color' => post('oabgcolorInput'),
				'font_family' => post('fonttypeInput'),
				'font_size' => post('fontsizeInput'),
				'font_style' => post('fontstyleInput', ''),
				'font_weight' => post('fontweightInput', ''),
				'color' => post('fontcolorInput'),
				'item_background_color' => post('qabgcolorInput'),
				'dist_background_color' => post('aabgcolorInput'),
				'logo_align' => post('logoAlign', ''),
				'logo_show' => post('logoShow', ''),
				'page_width' => post('pageWidth', ''),
				'item_borders' => $border,
				'use_parent_style' => post('use_parent_style', 0),
			);
			if ($this->block->setStyle($fields))
			{
				$GLOBALS['MSG_HANDLER']->addMsg('pages.style.pos', MSG_RESULT_POS);
			}
			else
			{
				$GLOBALS['MSG_HANDLER']->addMsg('pages.style.neg', MSG_RESULT_NEG);
			}

			$modifications = array("progress_bar" => post("progressBar"));
			if (! $this->block->modify($modifications))
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', -1, array('title' => htmlentities($this->block->getTitle())));
			}

			$modifications = array("pause_button" => post("pauseButton"));
			if (! $this->block->modify($modifications))
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.modified_failure', -1, array('title' => htmlentities($this->block->getTitle())));
			}
		}

		$media_con_id = $this->block->getLogo();
		
		if ($list = $fileHandling->listMedia($media_con_id))
		{
			$this->tpl->setVariable('media', $list[0]->getFilePath()."/".$list[0]->getFileName());
		}
		foreach(MimeTypes::$knownTypes as $mediaTypes) {
			$types[] = $mediaTypes["filetype"];
		}	
		$types = array_unique($types);
		foreach($types as $type) {
			$this->tpl->setVariable('mediatype', $type);
			$this->tpl->parse('medialist');
		}

		// display parent-style option for subtests only
		if (!$this->isRootTest()) {
			if ($this->block->getUseParentStyle())
				$this->tpl->setVariable('useParentStyleChecked', "checked=\"checked\"");
			$this->tpl->touchBlock('use_parent_style');
		}

		// parse stylesheet and display the options
		$style = $this->block->getStyle();
		
		if ($style)
		{
			$this->tpl->setVariable('show'.$style['Top']['logo-show'], 'checked="checked"');

			if ($style['Top']['text-align'])
			{
				$this->tpl->setVariable('align'.$style['Top']['text-align'], 'checked="checked"');
			}
			$this->tpl->setVariable('oabgcolor', $style['body']['background-color']);
			$this->tpl->setVariable('fonttype', $style['Question']['font-family']);
			if ($style['Question']['font-style'] == 'italic')
			{
				$this->tpl->setVariable('fontstyle', 'checked="checked"');
			}
			if ($style['Question']['font-weight'] == 'bold')
			{
				$this->tpl->setVariable('fontweight', 'checked="checked"');
			}
			$this->tpl->setVariable('fontsize', $style['Question']['font-size']);
			$this->tpl->setVariable('fontcolor', $style['Question']['color']);
			$this->tpl->setVariable('pageWidthFullOnChecked', $style['wrapper']['width'] == 0 ? " checked=\"checked\"" : "");
			$this->tpl->setVariable('pageWidthFullOffChecked', $style['wrapper']['width'] == '800px' ? " checked=\"checked\"" : "");
			$this->tpl->setVariable('borderQuestionChecked', $style['Question']['border'] == 0 ? "" : " checked=\"checked\"");
			$this->tpl->setVariable('borderAnswerBlockChecked', $style['Answers table.Border']['border'] == 0 ? "" : " checked=\"checked\"");
			$this->tpl->setVariable('borderAnswersChecked', $style['Answers td.Border']['border'] == 0 ? "" : " checked=\"checked\"");
			$this->tpl->setVariable('qabgcolor', $style['Question']['background-color']);
			$this->tpl->setVariable('aabgcolor', $style['Answers']['background-color']);
		}
		
		$this->tpl->setVariable('progressBarOnChecked', $this->block->getShowProgressBar() ? " checked=\"checked\"" : "");
		$this->tpl->setVariable('progressBarOffChecked', !$this->block->getShowProgressBar() ? " checked=\"checked\"" : "");
		$this->tpl->setVariable('pauseButtonOnChecked', ($this->block->getShowPauseButton()==1) ? " checked=\"checked\"" : "");
		$this->tpl->setVariable('pauseButtonTFChecked', ($this->block->getShowPauseButton()==2) ? " checked=\"checked\"" : "");
		$this->tpl->setVariable('pauseButtonOffChecked', ($this->block->getShowPauseButton()==0) ? " checked=\"checked\"" : "");

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', $title.": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doPreview()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('run', true);

		$this->tpl->loadTemplateFile("PreviewContainerBlock.html");
		$this->initTemplate("preview");

		if($this->isRootTest()){
			$this->tpl->touchBlock('preview_button');
			$this->tpl->hideBlock('subtest_button');
		}else {
			$this->tpl->hideBlock('preview_button');
			$this->tpl->touchBlock('subtest_button');
		}
			
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$title = ($this->isRootTest() ? T('pages.block.container_root') : T('pages.block.container'));
		$this->tpl->setVariable('page_title', $title.": '".$this->block->getTitle()."'");

				
		$this->tpl->show();
	}

	function doStartPreview()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('run', true);

		$testId = $this->block->getId();
		redirectTo('test_make', array('action' => 'start_test', 'source' => 'preview', 'id' => $testId, 'test_path' => $workingPath));
	}

	function doOrder()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$newOrder = post('order');
		// sort element to fix firefox 1.0.x bug
		ksort($newOrder);

		$problems = array();

		$desiredOrder = array();
		foreach($newOrder as $position => $id)
		{
			$desiredOrder[$position] = array("id" => (int)$id, "type" => $GLOBALS['BLOCK_LIST']->getBlockById($id)->getBlockType());
		}
		$res = $this->block->validateOrder($desiredOrder, $problems, 'move');
		
		// Check for problems or reorder without further validation
		if (!$res) {
			$this->generateProblemMessages($problems);
		} else {
			$this->block->reorderChildren($desiredOrder, $problems, false);
			if ($this->parentId) {
				$id = $this->parentId;
				$msg = 'structure.reorder_subtest';
				$params = array('title' => $this->block->getTitle());
			} else {
				$id = $this->block->getId();
				$msg = array();
				$msg[] = 'structure.reorder_test';
				$params = array();
			}
			TestStructure::storeCurrentStructure($id, $msg);
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.structure.saved', MSG_RESULT_POS);
		}

		redirectTo('container_block', array('action' => 'organize', 'working_path' => $workingPath));
	}

	function doShowHistory()
	{
		$this->tpl->loadTemplateFile("TestHistory.html");
		$this->initTemplate("show_history");
		$changelog = TestStructure::getChangelog($this->block->getId());
		if ($changelog) foreach ($changelog as $change) {
			$this->tpl->setVariable('version', $change['version']);
			$this->tpl->setVariable('date', $change['date']);
			$this->tpl->setVariable('message', $change['message']);
			$this->tpl->setVariable('user', $change['user']);
			$this->tpl->setVariable('testmaker_version', $change['testmaker_version']);
			$this->tpl->parse('log_entry');
		}
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('menu.user.management'));
		$this->tpl->show();
	}

	function doSaveTanOptions()
	{
		$workingPath = get('working_path');

		if (! $this->init($workingPath)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$modifications = array();
		$modifications['tan_ask_email'] = post('tan_ask_email', 0);

		$this->block->modify($modifications);

		$GLOBALS['MSG_HANDLER']->addMsg("pages.container_block.publish.tan_ask_email_saved", MSG_RESULT_POS);

		redirectTo("container_block", array("action" => "publish", "working_path" => $workingPath));
	}
}

?>
