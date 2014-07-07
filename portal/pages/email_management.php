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
 * Default action: {@link doListEmail()}
 */

/**
 * Loads the base class
 */
require_once(PORTAL.'ManagementPage.php');
require_once(CORE."types/Email.php");

libLoad('utilities::getCorrectionMessage');

class EmailManagementPage extends ManagementPage
{
	var $defaultAction = "list_emails";
	
	function doListEmails()
	{
		$emails = Email::getEmailList();
		$this->checkAllowed('admin', true);
		$this->tpl->loadTemplateFile("ListEmails.html");
		$this->initTemplate("manage_emails");
		
		if ($emails) foreach ($emails as $email) {
			$subject = $email->getSubject();
			if(!$subject) 
			{
				$this->tpl->setVariable('italic', "style=\"font-style:italic;\"");
				$this->tpl->setVariable('subject', T('pages.emails.no_title'));
			} else
			{
				$this->tpl->setVariable('subject', $subject);
			}
			$this->tpl->setVariable('email_id', $email->getId());
			$this->tpl->parse('Email');
		}
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('menu.user.management'));
		$this->tpl->show();
	}

	function doGetEmailAddresses()
	{
		$emailId = get('email_id', 0);

		if (! get('email_id')) $email = new Email();
		$email = new Email(get('email_id'));


		// build with list of email addresses in temp directory
		$random = sprintf("%04d", rand(0, 9999));
		$tempFileName = TM_TMP_DIR."/email_list_".$random.".txt";
		$addresses = $email->getEmailAddresses();
		$tempFile = fopen($tempFileName, "w");
		foreach($addresses as $address => $values)
		{
			if(!fwrite($tempFile,$addresses[$address]['userId']."   ".$address."\n"))
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.email.get_email_addresses.error_writing_file");
				redirectTo("email_management", array("action" => "edit_email", "email_id" => $emailId)); 
			}
		}
		fclose($tempFile);

		// deliver the file
		header("Content-Type: text/plain");
		header("Content-Disposition: attachment; filename=\"email_addresses.txt\"");
		readfile($tempFileName);

		unlink($tempFileName);
	}
	
	function doEditEmail()
	{	
		require_once(CORE."types/TestRunList.php");
		
		$this->checkAllowed('edit', true);
		
		if (! get('email_id')) $email = new Email();
		$email = new Email(get('email_id'));
		
		$this->tpl->loadTemplateFile("EditEmail.html");
		$this->initTemplate("manage_emails");
		

		
		$this->tpl->setVariable('subject', $email->getSubject());
		$this->tpl->setVariable('body', $email->getBody());
		$this->tpl->setVariable('email_id', $email->getId());
		$this->tpl->setVariable('remaining', $email->getRemaining());
		if ($email->isLocked()) $this->tpl->setVariable('disabled', 'disabled="disabled"');
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('menu.user.management'));
		$this->tpl->show();
	}
	
	function doEditEmailConditions()
	{
		$this->checkAllowed('admin', true);
		
		libLoad("PEAR");
		require_once("Services/JSON.php");
		$json = new Services_JSON();
		
		//if (! getpost('id')) $email = new Email(); //REDIRECT!
		$email = new Email(get('email_id'));
		
		if ($email->isLocked()) $GLOBALS["MSG_HANDLER"]->addMsg('pages.admin.email.is_locked', MSG_RESULT_NEG);
		
		$this->tpl->loadTemplateFile("EmailConditions.html");
		$this->initTemplate("manage_emails");
		
		$ul = new UserList();
		$groups = $ul->getGroupList();
		
		$extra_groups = array(
			"0" => T("pages.email_management.all_users"),
			"-1" => T("pages.email_management.tan_users"),
			"-2" => T("pages.email_management.password_users"),
		);
		
		$participants = array(
			"1" => T("pages.email_management.participants_participated"),
			"2" => T("pages.email_management.participants_all_items"),
			"3" => T("pages.email_management.participants_all_required_items"),
			"4" => T("pages.email_management.participants_not_all_required_items"),
		);
			
		foreach($extra_groups as $key => $group)
		{
			if ($email->getParticipantsGroup(get('test_id')) == $key) $this->tpl->setVariable('selected', "selected=\"selected\"");
			$this->tpl->setVariable('group_name', $group);
			$this->tpl->setVariable('group_id', $key);
			$this->tpl->parse('groups');
		}
		
		foreach($groups as $group)
		{
			if ($email->getParticipantsGroup(get('test_id')) == $group->getId()) $this->tpl->setVariable('selected', "selected=\"selected\"");
			$this->tpl->setVariable('group_name', $group->get('groupname'));
			$this->tpl->setVariable('group_id', $group->getId());
			$this->tpl->parse('groups');
		}
		

		foreach($participants as $key => $participant) {
			if ($email->getParticipants(get('test_id')) == $key) $this->tpl->setVariable('selected', "selected=\"selected\"");
			$this->tpl->setVariable('participant_name', $participant);
			$this->tpl->setVariable('participant_id', $key);
			$this->tpl->parse('participants');
		}
		
		$this->tpl->setVariable('email_id', get('email_id'));
		$this->tpl->setVariable('test_id', get('test_id'));
		
		$needsAll = $email->needsAllConditions(get('test_id'));
		$this->tpl->setVariable('conditions_all_1_selected', $needsAll ? 'selected="selected"' : '');
		$this->tpl->setVariable('conditions_all_0_selected', $needsAll ? '' : 'selected="selected"');
		
		// Preload existing conditions
		$conditions = $this->convertConditionsToJavaScript($email->getConditions(get('test_id')));
		$this->tpl->setVariable("conditions", $json->encode($conditions));
		
		// Preload Item Blocks
		$itemBlocks = array();
		foreach($GLOBALS["BLOCK_LIST"]->getBlockById(get('test_id'))->getChildren() as $block) {
			if($block->isContainerBlock()) {
				foreach($block->getChildren() as $itemBlock) {
					$itemBlocks[] = array(utf8_encode($itemBlock->getTitle()), $itemBlock->getId());
					$items = array();
					if ($itemBlock->isItemBlock()) {
						foreach ($itemBlock->getTreeChildren() as $item) {
							$items[] = array(utf8_encode($item->getTitle(TRUE)), $item->getId());
							$answers = array();
							foreach ($item->getChildren() as $answer) {
								$answers[] = array(utf8_encode($answer->getTitle(FALSE)), $answer->getId());
							}
							$this->tpl->setVariable("preload_item_id", $item->getId());
							$this->tpl->setVariable("preload_answers", $json->encode($answers));
							$this->tpl->parse("add_answers_js");
						}
					}
					$this->tpl->setVariable("preload_item_block_id", $itemBlock->getId());
					$this->tpl->setVariable("preload_items", $json->encode($items));
					$this->tpl->parse("add_items_js");
				}
			} elseif($block->isItemBlock()) {
				$itemBlocks[] = array(utf8_encode($block->getTitle()), $block->getId());
				$items = array();
				foreach ($block->getTreeChildren() as $item) {
					$items[] = array(utf8_encode($item->getTitle(TRUE)), $item->getId());
					$answers = array();
					foreach ($item->getChildren() as $answer) {
						$answers[] = array(utf8_encode($answer->getTitle(FALSE)), $answer->getId());
					}
					$this->tpl->setVariable("preload_item_id", $item->getId());
					$this->tpl->setVariable("preload_answers", $json->encode($answers));
					$this->tpl->parse("add_answers_js");
				}
				$this->tpl->setVariable("preload_item_block_id", $block->getId());
				$this->tpl->setVariable("preload_items", $json->encode($items));
				$this->tpl->parse("add_items_js");
			}
		}
		
		$this->tpl->setVariable('preload_item_blocks', $json->encode($itemBlocks));
		if ($email->isLocked()) $this->tpl->setVariable('disabled', 'disabled="disabled"');
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('menu.user.management'));
		$this->tpl->show();
	}
	
	function doDeleteEmail() {
		Email::deleteEmail(get('email_id'));
		redirectTo("email_management", array("action" => "list_emails"));
	}
	
	private function convertConditionsToJavaScript($conditions)
	{
		for ($i = 0; $i < count($conditions); $i++) {
			if (isset($conditions[$i]["chosen"])) {
				$conditions[$i]["chosen"] = $conditions[$i]["chosen"] ? "yes" : "no";
			}
		}

		return $conditions;
	}
	
}

?>
