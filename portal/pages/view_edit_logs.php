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
 * Loads the base class
 */
require_once(PORTAL.'ManagementPage.php');
require_once(PORTAL.'PageSelector.php');

class ViewEditLogsPage extends ManagementPage
{
    var $defaultAction = "default";
	
	function doDefault()
	{
		$this->checkAllowed('admin', true);

		$this->tpl->loadTemplateFile("ViewEditLogs.html");
		$this->initTemplate('view_edit_logs');

		if (post('entries_per_page')) {
			$_SESSION['editlog_pagesize'] = intval(post('entries_per_page'));
		} elseif (!isset($_SESSION['editlog_pagesize'])) {
			$_SESSION['editlog_pagesize'] = 10;
		}
		$pageSize = $_SESSION['editlog_pagesize'];
		
		if (post('username') !== NULL) {
			$userName = post('username', '');
			$optype = post('optype', '');

			$_SESSION['editlog_username'] = $userName;
			$_SESSION['editlog_optype'] = $optype;
		} elseif (isset($_SESSION['editlog_username'])) {
			$userName = $_SESSION['editlog_username'];
			$optype = $_SESSION['editlog_optype'];
		} else {
			$userName = ''; $optype = '';
		}

		// Prefill form
		$this->tpl->setVariable('username', $userName);
		foreach ($GLOBALS['LOG_OP_VAL2STR'] as $key => $val) {
			$this->tpl->setVariable('optype_id', $key);
			$this->tpl->setVariable('optype_desc', T('labels.editlog.'. $val));
			if ($optype == $key) {
				$this->tpl->setVariable('optype_checked', ' selected="selected"');
			}
			$this->tpl->parse('optypes');
		}

		// Fetch entries
		if (!$userName) $userName = NULL;
		if (!$optype) $optype = NULL;
		$offset = (getpost('page_number', 1) - 1) * $pageSize;
		if ($offset < 0) $offset = 0;
		$entries = EditLog::findEntries($userName, $optype, NULL, $offset, $pageSize);

		$protocolCount = EditLog::countEntries($userName, $optype, NULL);
		$pageLinkDistance = 2;
		
		$pageCount = ceil($protocolCount/$pageSize);
		
		if (! isset($_SESSION["PROTOCOL_PAGE"])) {
			$_SESSION["PROTOCOL_PAGE"] = $pageCount;
		}
		
		$_SESSION["PROTOCOL_PAGE"] = getpost("page_number", $_SESSION["PROTOCOL_PAGE"]);
		if ($_SESSION["PROTOCOL_PAGE"] > $pageCount || $_SESSION["PROTOCOL_PAGE"] == 0) {
			$_SESSION["PROTOCOL_PAGE"] = $pageCount;
		}
		$this->tpl->setVariable($pageSize . "_entries_per_page", "selected");
		
	    $pageSelector = new PageSelector($pageCount, $_SESSION['PROTOCOL_PAGE'], $pageLinkDistance, 'view_edit_logs');
		$pageSelector->renderDefault($this->tpl);

		if (!$entries) {
			$this->tpl->touchBlock('no_log_rows');
		} else foreach ($entries as $entry) {
			$timeStr = date(T('pages.core.date_time'), $entry['stamp']);
			$this->tpl->setVariable('log_time', $timeStr);
			$op = $GLOBALS['LOG_OP_VAL2STR'][$entry['operation']];
			$this->tpl->setVariable('log_op', T('labels.editlog.'. $op));

			if ($entry['target']) {
				$target = $entry['target'];
				if (is_a($target, 'User') || is_a($target, 'Group') || is_a($target, 'Block')) {
					$this->tpl->setVariable('log_target', $GLOBALS['PORTAL']->labeledLinkToObject($target));
				} else {
					$targetParent = $target->getParent();
					$this->tpl->setVariable('log_target', $GLOBALS['PORTAL']->labeledLinkToObject($targetParent) .' &raquo; '. htmlspecialchars($target->getTitle()));
				}
			} elseif ($entry['operation'] == LOG_OP_PRIVACY_POLICY){
				if (!isset($entry['target_id'])) $entry['target_id'] = '0';
				$this->tpl->setVariable('log_target', '<a href="index.php?page=view_edit_logs" onclick="window.open(\'index.php?page=show_privacy_policy&amp;action=show_privacy_popup&amp;version='.$entry['target_id'].'\', \'page_help_\'+((new Date()).getTime()), \'width=600,height=540,left=\'+Math.round((screen.width-600)/2)+\',top=\'+Math.round((screen.height-540)/2)+\',scrollbars=yes,status=yes,toolbar=yes,resizable=yes\'); return false">' . $entry['target_type'] .' #'. $entry['target_id'].'</a>');		
			} elseif ($entry['operation'] == LOG_OP_MAINTENANCE_MODE){
				if (isset($entry['target_id'])){ $entry['target_id'] = 'on'; } else { $entry['target_id'] = 'off';}
					//$entry['target_id'] == 0 ? $entry['target_id'] = 'off' : $entry['target_id'] = 'on';
					$this->tpl->setVariable('log_target', $entry['target_type'] .' ('. $entry['target_id'].')');//}		
			} elseif ($entry['target_title']) {
				if (!isset($entry['target_id'])) $entry['target_id'] = '???';
				$this->tpl->setVariable('log_target', $entry['target_type'] .': '. $entry['target_title'].' (#'.$entry['target_id'].')');
			} else {
				if (!isset($entry['target_id'])) $entry['target_id'] = '0';	// ???
				$this->tpl->setVariable('log_target', $entry['target_type'] .' #'. $entry['target_id']);
			}
			$this->tpl->setVariable('log_user', $GLOBALS['PORTAL']->labeledLinkToObject($entry['user']));

			// Display details section
			switch ($entry['operation']) {
			case LOG_OP_MAINTENANCE_MODE:
				if (!isset($entry['details'])) $entry['details'] = '';
				$this->tpl->setVariable('log_details', $entry['details']);
				break;
			case LOG_OP_PRIVACY_POLICY:
				if (!isset($entry['details'])) $entry['details'] = '-';
				$details = $entry['details'];	
				$this->tpl->setVariable('log_details', "Dauer: ".$details);
				break;
			case LOG_OP_DELETE:
				$wp = $entry['details']['working_path'];
				if ($wp) {
					$wpArr = $this->splitPath($wp);
					$parentId = $wpArr[count($wpArr)-1];
					if ($GLOBALS['BLOCK_LIST']->existsBlock($parentId)) {
						$parentBlock = $GLOBALS['BLOCK_LIST']->getBlockById($parentId);
						$this->tpl->setVariable('log_delete_link', $GLOBALS['PORTAL']->labeledLinkToObject($parentBlock));
					} else {
						$parentBlock = 'Block #'. $parentId;
						$this->tpl->setVariable('log_delete_link', $parentBlock);
					}

					$title = $entry['details']['title'];
					$this->tpl->setVariable('log_delete_title', $title);
					$this->tpl->setVariable('log_details', '');
				} break;
			case LOG_OP_TEST_SURVEY:	
			case LOG_OP_TESTRUN_DELETE:
			case LOG_OP_DATA_EXPORT:
				$detstring = '';
				if($details = $entry['details']){
					foreach($details as $detail => $dvalue){
						$detstring = $detstring.$detail.':'.$dvalue.'; ';
					}
				}
				$this->tpl->setVariable('log_details', $detstring);
			}

			$this->tpl->parse('log_row');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('menu.user.management'));
		$this->tpl->show();
	}

}
