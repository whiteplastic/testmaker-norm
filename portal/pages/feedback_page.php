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
 * Loads the Dimension class
 */
require_once(CORE.'types/Dimension.php');

libLoad('html::defuseScripts');
libLoad('utilities::deUtf8');

/**
 * Displays the relevant pages for feedbacks
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class FeedbackPagePage extends BlockPage
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
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("feedback_page", array("action" => "edit", "working_path" => $this->workingPath, "item_id" => $itemId))),
			"delete" => array("title" => "tabs.test.delete.feedbackpage", "link" => linkTo("feedback_page", array("action" => "confirm_delete", "working_path" => $this->workingPath, "item_id" => $itemId))),
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
		if (!$this->mayCreate) return;

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

	function getPage($block, $id)
	{
		if (! $block) {
			return NULL;
		}

		if (! $this->block->isFeedbackBlock() || ! $this->block->existsTreeChild($id)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.feedback_page.msg.feedback_page_invalid', MSG_RESULT_NEG);
			return NULL;
		}

		$page = $block->getTreeChildById($id);

		return $page;
	}

	/**
	 * @access private
	 * @static
	 */
	function _buildDimList($block)
	{
		$d_data = DataObject::getBy('Dimension','getAllByBlockId',$block->getId());
		$dims = array();

		// Consider adaptive blocks
		$qbIds = $block->getSourceIds();
		foreach ($qbIds as $qbId) {
			$qb = $GLOBALS['BLOCK_LIST']->getBlockById($qbId, BLOCK_TYPE_ITEM);
			if (!$qb->isAdaptiveItemBlock() && !$qb->isIRTBlock()) continue;
			$dims[-($qbId)] = array(
				'dim_id'	=> $qbId,
				'dim_name'	=> T('pages.feedback_page.theta_dim', array('name' => $qb->getTitle())),
			);
		}

		// Ordinary dimensions
		foreach ($d_data as $d) {
			$dims[$d->get('id')] = array(
				'dim_id'	=> $d->get('id'),
				'dim_name'	=> $d->get('name'),
			);
		}

		// List of groups
		$dimgroups = array();
		$groups = DataObject::getBy('DimensionGroup','getAllByBlockId',$block->getId());
		foreach ($groups as $g) {
			$dimgroups[$g->get('id')] = array(
				'group_id'		=> $g->get('id'),
				'group_name'	=> $g->get('title'),
				'group_dims'	=> $g->get('dimension_ids'),
			);
		}

		return array($dims, $dimgroups);
	}

	/**
	 * @access private
	 */
	function _renderParagraphView($p)
	{
		list($dims, $dimgroups) = $this->_buildDimList($this->block);
		$workingPath = $this->workingPath;
		$this->tpl->loadTemplateFile("EditFeedbackParagraphView.html");

		$c = $p->getConditions();
		$cCount = count($c);
		foreach ($c as $cData) {
			$plugin = Plugin::load('extconds', $cData['type']);
			$out = $plugin->renderCondition($dims, $dimgroups, $this->page, $cData, 'view');
			$this->tpl->setVariable('condition', $out);
			$this->tpl->parse('para_conditions');
		}
		if ($cCount > 0) $this->tpl->touchBlock('para_has_conds');
		$this->tpl->setVariable('para_id', $p->getId());

		// XXX: This line was introduced after [2324] and should be reworked once the new feedback editing interface is finished.
		$contents = FeedbackGenerator::expandText($p->getContents(), array($this, '_curlifyTag'), array());
		$this->tpl->setVariable('para_contents', $contents);

		if ($this->mayEdit) {
			$this->tpl->setVariable('del_link', linkTo('feedback_page', array('action' => 'delete_paragraph', 'working_path' => $workingPath), true, true));
			$this->tpl->setVariable('edit_link', linkTo('feedback_page', array('action' => 'edit_paragraph', 'working_path' => $workingPath), true, true));
			$this->tpl->setVariable('move_link', linkTo('feedback_page', array('action' => 'move_paragraph', 'working_path' => $workingPath), true, true));
			$this->tpl->touchBlock('para_may_edit');
		}
		return $this->tpl->get();
	}

	function _curlifyTag($type, $params)
	{
		$res = '{'. $type .'&#58;';
		$sp = '';
		foreach ($params as $key => $val) {
			if ($key == 'type') continue;
			$res .= $sp;
			if (!$sp) $sp = ' ';
			$res .= $key .'="'. $val .'"';
		}
		return $res .'}';
	}

	// ACTIONS

	function doEdit()
	{
		$workingPath = getpost('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('view', true);

		// Render paragraphs
		$block = $this->page->getParent();
		list($dims, $dimgroups) = $this->_buildDimList($block);

		$ps_data = $this->page->getParagraphs();
		$para_string = '';
		if (isset($ps_data) && count($ps_data) > 0) {
			foreach ($ps_data as $p) {
				$para_string .= trim($this->_renderParagraphView($p));
			}
		}

		$this->tpl->loadTemplateFile("EditFeedbackPage.html");
		$this->initTemplate("edit", $id);

		// Setup prototypes for feedback conditions
		$pluginTypes = Plugin::getAllByType('extconds');
		foreach ($pluginTypes as $pluginType) {
			$plugin = Plugin::load('extconds', $pluginType);
			if (!$plugin->checkApplicability($this->page)) continue;
			$proto = $plugin->renderCondition($dims, $dimgroups, $this->page, $plugin->getConditionPrototype(), 'edit');
			$this->tpl->setVariable('proto_condition', '<input type="hidden" name="condition_types[]" value="'. $pluginType ."\" />\n". $proto);
			$this->tpl->setVariable('proto_type', $pluginType);
			$this->tpl->parse('proto_conditions');

			// and load javascript file for plugin
			$path = $plugin->getPath();
			$dir = basename($path);
			if (file_exists($path .'ConditionScript.js')) {
				$this->tpl->setVariable('script_dir', $dir);
				$this->tpl->parse('extconds_scripts');
			}
		}

		// build javascript list of dimensions and groups
		$dimsSeq = array();
		$dimgroupsSeq = array();
		$i = 0;
		foreach ($dims as $dimId => $dimInfo) {
			$dims[$dimId]['seq'] = $i;
			$dims[$dimId]['groups'] = array();
			$dims[$dimId]['group_seqs'] = array();
			$dimsSeq[$i++] = $dimId;
		}
		$i = 0;
		foreach ($dimgroups as $grpId => $grpInfo) {
			$dimgroups[$grpId]['seq'] = $i;
			$dimgroupsSeq[$i++] = $grpId;
			foreach ($grpInfo['group_dims'] as $dimId) {
				$dims[$dimId]['groups'][] = $grpId;
				$dims[$dimId]['group_seqs'][] = ($i-1);
			}
		}
		$jsDims = array();
		$jsDimTxts = array();
		$jsGrps = array();
		$jsGrpTxts = array();
		$jsGrpCounts = array();
		$jsDims2Grps = array();
		foreach ($dimsSeq as $dimSeq => $dimId) {
			$dimInfo = $dims[$dimId];

			$jsDims[] = $dimsSeq[$dimSeq];
			$jsDimTxts[] = "'". $this->tpl->_jsEscape($dimInfo['dim_name']) ."'";
			$jsDims2Grps[] = '['. implode(', ', $dimInfo['group_seqs']) .']';
		}
		foreach ($dimgroupsSeq as $grpSeq => $grpId) {
			$grpInfo = $dimgroups[$grpId];

			$jsGrps[] = $dimgroupsSeq[$grpSeq];
			$jsGrpTxts[] = "'". $this->tpl->_jsEscape($grpInfo['group_name']) ."'";
			$jsGrpCounts[] = count($grpInfo['group_dims']);
		}
		$js  = 'dimensionIds = ['. implode(', ', $jsDims) ."];\n";
		$js .= 'dimensionTexts = ['. implode(', ', $jsDimTxts) ."];\n";
		$js .= 'dimgroupIds = ['. implode(', ', $jsGrps) ."];\n";
		$js .= 'dimgroupTexts = ['. implode(', ', $jsGrpTxts) ."];\n";
		$js .= 'dimgroupCounts = ['. implode(', ', $jsGrpCounts) ."];\n";
		$js .= 'dimsToGrps = ['. implode(', ', $jsDims2Grps) ."];\n";
		$js .= "dimsSequence = new Object();\n";
		foreach ($dims as $dimId => $dimInfo) {
			if (!isset($dimInfo['dim_id'])) continue;
			$js .= "\tdimsSequence['$dimId'] = $dimInfo[seq];\n";
		}
		$js .= "grpsSequence = new Object();\n";
		foreach ($dimgroups as $grpId => $grpInfo) {
			$js .= "\tgrpsSequence['$grpId'] = $grpInfo[seq];\n";
		}

		$this->tpl->setVariable('dimgroups_js', $js);

		$this->tpl->setVariable('paragraphs', $para_string);
		$this->tpl->setVariable('title', $this->page->getTitle(false));
		$this->tpl->setVariable('t_modified', $this->page->getModificationTime());
		$this->tpl->setVariable('t_created', $this->page->getCreationTime());
		$owner = $this->block->getOwner();
		$this->tpl->setVariable('author', $owner->getUsername() .' ('. $owner->getFullname() .')');
		if ($this->mayEdit) {
			$this->tpl->touchBlock('form_submit_button');
		}
		$this->tpl->setVariable('add_link',
			linkTo('feedback_page', array('action' => 'edit_paragraph', 'working_path' => $workingPath, 'page_id' => $this->page->getId()), false, true));

		$body = $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.feedback_page.feedback_page'));

		$this->tpl->show();
	}

	function doSave()
	{
		$workingPath = getpost('working_path');
		$id = getpost('item_id');

		if (! $this->init($workingPath, $id)) {		
			return;
		}

		$this->checkAllowed('edit', true);

		$modifications['title'] = post('title');
		if ($this->page->modify($modifications)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.modified_success', MSG_RESULT_POS, array('title' => htmlentities($this->page->getTitle())));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.modified_failure', MSG_RESULT_NEG, array('title' => htmlentities($this->page->getTitle())));
		}

		redirectTo('feedback_page', array('action' => 'edit', 'item_id' => $id, 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

	function doEditParagraph()
	{
		// include fckeditor
		require_once(PORTAL.'Editor.php');

		$workingPath = get('working_path');
		$id = post('id');
		$pageId = get('page_id');

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		if ($id == 0) {
			$page = new FeedbackPage($pageId);
			$pagePar = $page->getParent();
			if ($pagePar->getId() != $this->block->getId()) {
				sendXMLStatus(T('pages.id_out_of_place'), array('type' => 'fail'));
			}

			$para = $page->createChild(array());
			$id = $para->getId();
		} else {
			$para = new FeedbackParagraph($id);
			$page = $para->getParent();
			$pagePar = $page->getParent();

			// Yay, duplication
			if ($pagePar->getId() != $this->block->getId()) {
				sendXMLStatus(T('pages.id_out_of_place'), array('type' => 'fail'));
			}
		}

		$this->tpl->loadTemplateFile('EditFeedbackParagraphEdit.html');
		$conds = $para->getConditions();

		list($dims, $dimgroups) = $this->_buildDimList($this->block);
		foreach ($conds as $cond) {
			$plugin = Plugin::load('extconds', $cond['type']);
			$out = $plugin->renderCondition($dims, $dimgroups, $page, $cond, 'edit');
			$this->tpl->setVariable('condition', '<input type="hidden" name="condition_types[]" value="'. $cond['type'] ."\" />\n". $out);
			$this->tpl->setVariable('condition_type', $cond['type']);
			$this->tpl->parse('para_conditions');
		}

		$this->tpl->setVariable('id', $id);
		$this->tpl->setVariable('link', linkTo('feedback_page', array('action' => 'save_paragraph', 'working_path' => $workingPath), true, true));

		// XXX: This line was introduced after [2324] and should be reworked once the new feedback editing interface is finished.
		$contents = FeedbackGenerator::expandText($para->getContents(), array($this, '_curlifyTag'), array());

		// create FCKeditor
		$editor = new Editor('working_path='.$workingPath.'&item_id='.$page->getId(), $contents);
		$this->tpl->setVariable('para_contents', $editor->CreateHtml());

		$this->tpl->setVariable('page_id', $page->getId());
		$this->tpl->setVariable('working_path', $workingPath);

		sendHTMLMangledIntoXML($this->tpl->get(), array('id' => 'para_'. $id));
	}

	function doSaveParagraph()
	{
		$id = post('id');
		$save = post('save', false);

		if ($id == NULL) {
			sendXMLStatus(T('pages.paragraphs.empty_request'), array('type' => 'fail'));			
		}

		if (!$this->mayView) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		$para = new FeedbackParagraph($id);
		$page = $para->getParent();
		$pagePar = $page->getParent();
		if ($pagePar->getId() != $this->block->getId()) {
			sendXMLStatus(T('pages.id_out_of_place'), array('type' => 'fail'));
		}

		if ($save) {
			if (!$this->mayEdit) {
				sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
			}

			// Shuffle submitted data into a usable format
			$conditions = array();
			$cdata = post('conditions', array());
			foreach (post('condition_types', array()) as $ctype) {
				$cond = array('type' => $ctype);
				$curdata = &$cdata[$ctype];
				foreach (array_keys($curdata) as $attrName) {
					$arr = &$curdata[$attrName];
					$cond[$attrName] = array_shift($arr);
				}
				$conditions[] = $cond;
			}

			$para->removeConditions();
			$para->setConditions($conditions);

			// Why can't FCKeditor's character set be customized? :(
			$content = deUtf8(post('fckcontent', ''));
			$para->modify(array('content' => defuseScripts($content)));
		}

		sendXMLMessages(true);
	}

	function doDeleteParagraph()
	{
		$id = post('id');

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		$para = new FeedbackParagraph($id);
		$page = $para->getParent();
		$block = $page->getParent();

		if ($block->getId() != $this->block->getId()) {
			sendXMLStatus(T('pages.id_out_of_place'), array('type' => 'fail'));
		}

		$res = $page->deleteChild($para->getId());

		if ($res) {
			sendXMLStatus('', array('type' => 'ok', 'id' => $id));
		}

		sendXMLMessages();
	}

	function doMoveParagraph()
	{
		$upper = post('upper');
		$lower = post('lower');
		$us = post('id');

		if (!$this->mayEdit) {
			sendXMLStatus(T('pages.permission_denied'), array('type' => 'fail'));
		}

		$para = new FeedbackParagraph($lower);
		$page = $para->getParent();
		$parent = $page->getParent();
		$para2 = new FeedbackParagraph($upper);
		$page2 = $para2->getParent();
		$parent2 = $page2->getParent();

		if ($parent->getId() != $this->block->getId() || $parent2->getId() != $this->block->getId()) {
			sendXMLStatus(T('pages.id_out_of_place'), array('type' => 'fail'));
		}

		$upperPos = $para2->getPosition();
		$res = $para->setPosition($upperPos);

		if ($res) {
			sendXMLStatus('', array('type' => 'ok', 'lower' => $lower, 'upper' => $upper, 'id' => $us));
		}

		sendXMLMessages();
	}

	function doConfirmDelete()
	{
		$workingPath = get('working_path');
		$id = getpost('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('delete', true);

		$this->tpl->loadTemplateFile("DeleteFeedbackPage.html");
		$this->initTemplate("delete", $id);

		$tests = TestStructure::getTrackedContainingTests($this->block->getId());
		if (TestStructure::existsStructureOfTests($tests))
			$this->tpl->touchBlock('delete_not_possible');

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.feedback_page.feedback_page'));

		$this->tpl->show();
	}

	function doDelete()
	{
		$workingPath = get('working_path');
		$id = get('item_id');

		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('delete', true);

		if (post("cancel_delete", FALSE)) {
			redirectTo('feedback_page', array('action' => 'edit', 'working_path' => $workingPath, 'item_id' => $id, 'resume_messages' => 'true'));
		}

		$title = htmlentities($this->page->getTitle());

		$tests = TestStructure::getTrackedContainingTests($this->block->getId());

		if (TestStructure::existsStructureOfTests($tests)) {
			$this->page->modify(array('disabled' => 1));
			foreach ($tests as $test) {
				TestStructure::storeCurrentStructure($test->getId(), array('structure.disable_feedbackpage', array('title' => $this->page->getTitle())));
			}
			$GLOBALS['MSG_HANDLER']->addMsg('pages.disable_object.success', MSG_RESULT_POS, array('title' => $this->page->getTitle()));
		} elseif ($this->block->deleteTreeChild($id)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.deleted_success', MSG_RESULT_POS, array('title' => $title));
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.info_page.msg.deleted_failure', MSG_RESULT_NEG, array('title' => $title));
		}

		redirectTo('feedback_block', array('working_path' => $workingPath, 'resume_messages' => 'true'));
	}
}

