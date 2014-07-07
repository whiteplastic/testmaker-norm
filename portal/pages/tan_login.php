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
require_once('portal/FrontendPage.php');

/**
 * Test accomplishment
 *
 * Default action: {@link doUseTan()}
 *
 * @package Portal
 */

class TanLoginPage extends FrontendPage
{
	var $defaultAction = 'use_tan';
	
	/**
	 * Loads the associated test run for a certain TAN, or, if no test run is available,
	 * redirects to the page for registering the TAN and starting the test.
	 */
	function doUseTan()
	{
		require_once(CORE."types/TestRunList.php");
		require_once(CORE."types/TANCollection.php");

		!Setting::get('curr_privacy_policy') ? $no_privacy=1 : $no_privacy=0;
	
		$tan =  post('tan', get('tan'));
		if(!TANCollection::existsTAN($tan))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.wrong_tan", MSG_RESULT_NEG);
			redirectTo("start", array("action" => "start", "resume_messages" => "true"));
		}

		$_SESSION['accessType'] = 'tan';
		$block = TANCollection::getBlockByTAN($tan);
		if (! list($test, $passwordRequired) = $GLOBALS["BLOCK_LIST"]->checkTestId($block->id, 'tan')) redirectTo("test_listing", array("resume_messages" => "true"));
		
		require_once(CORE."types/TestSelector.php");
		$selector = new TestSelector($test);
		$pages = $selector->getChildrenOfSpecificBlocks(BLOCK_TYPE_FEEDBACK);
		
		$_SESSION['tan'] = $tan;
		$tanCollection = new TANCollection($block->getId());

		$testRunId = $tanCollection->getTestRun($tan);
		if(!$testRunId)
		{
			if($block->getTanAskEmail())
			{
				$this->tpl->loadTemplateFile("RegisterTAN.html");
				
				$this->tpl->setVariable("ppversion", PrivacyPolicy::getCurrentVersion());
				if ($no_privacy) $this->tpl->hideBlock("pp_check");
				else $this->tpl->touchBlock("pp_check");
				
				$this->tpl->setVariable("tan", $tan);
				if (!$pages) {
					$this->tpl->hideBlock("has_pages");
					$this->tpl->touchBlock("no_pages");
				}
				else {
					$this->tpl->hideBlock("no_pages");
					$this->tpl->touchBlock("has_pages");
				}
				$body = $this->tpl->get();
				parent::loadDocumentFrame();
				$this->tpl->setVariable("body", $body);
				$this->tpl->setVariable("page_title", T("pages.testmake.register_tan"));
				
				$this->tpl->show();
			} else
			{
				redirectTo("tan_login", array("action" => "register_tan", "tan" => $tan));
			}
		}
		else
		{
			$testRunList = new TestRunList();
			$testRun = &$testRunList->getTestRunById($testRunId);

			if($testRun == NULL) {
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.deleted", MSG_RESULT_NEG);
				redirectTo("test_listing", array("resume_messages" => "true"));
			} else{
				if ($testRun->getProgress() == 100) {
					redirectTo("test_listing", array("action" => "show_feedback", "test_run" => $testRunId, "final" =>'1'));
				} else {
					if($block->getShowSubtests())
					{
						redirectTo("test_listing", array("action" => "subtest_view", "test_id" => $test->getId(), "test_run" => $testRunId));
					} else
					{
						$redirect = array("start", array());
						$id = $testRun->prepare(array("redirectOnFinish" => $redirect));
						redirectTo("test_make", array("action" => "bounce_to_test", "id" => $id, "tan" => $tan));
					}
				}
			}
		}
	}
	
	/**
	 * First this function proofs if the TAN is valid. Then it tries to find
	 * the associated block for this TAN. When the TAN has already been used 
	 * for absolving a test, the user is redirected to the feedback (doUseTan);
	 * otherwise his email address is saved and an email with the link to
	 * the feedback page is send to the user. Afterwards the test run is started.
	 */
	function doRegisterTan()
	{
		require_once(CORE."types/TANCollection.php");

		$tan =  post('tan', get('tan'));
		
		!Setting::get('curr_privacy_policy') ? $no_privacy=1 : $no_privacy=0;
	
		
		if (post('mail') && !post('accepted') && !$no_privacy){
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.tan_accept_pp", MSG_RESULT_NEG);
				redirectTo("tan_login", array("action" => "use_tan", "tan" => $tan,"resume_messages" => "true"));
		}
		

		if(!TANCollection::existsTAN($tan))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.wrong_tan", MSG_RESULT_NEG);
			redirectTo("start", array("action" => "start", "resume_messages" => "true"));
		}

		$_SESSION['accessType'] = 'tan';
		$block = TANCollection::getBlockByTAN($tan);
		if (! list($test, $passwordRequired) = $GLOBALS["BLOCK_LIST"]->checkTestId($block->id, 'tan')) redirectTo("test_listing", array("resume_messages" => "true"));

		$_SESSION['tan'] = $tan;
		$tanCollection = new TANCollection($block->getId());
		$testRunId = $tanCollection->getTestRun($tan);

		if($testRunId) {
			return $this->doUseTan();
		}
		
		require_once(CORE."types/TestSelector.php");
		$selector = new TestSelector($test);
		$pages = $selector->getChildrenOfSpecificBlocks(BLOCK_TYPE_FEEDBACK);

		if (post('accepted')) TANCollection::setPrivacyPolicyAcc($tan, Setting::get('curr_privacy_policy'));

		if(post('mail', '') != '') {
			if (!$tanCollection->setMail($tan, post('mail'))) {
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.wrong_mail", MSG_RESULT_NEG);
				redirectTo("tan_login", array("action" => "use_tan", "tan" => $tan,"resume_messages" => "true"));
			}
			else
			{
				if ($pages) {
					$bodies = array(
						"html" => "UserTanMail.html",
						"text" => "UserTanMail.txt",
					);
				}
				else {
					$bodies = array(	
						"html" => "UserTanMailNoFeedback.html",
						"text" => "UserTanMail.txt",
					);
				}
				foreach ($bodies as $type => $templateFile) {
					$this->tpl->loadTemplateFile($templateFile);
					$this->tpl->setVariable("tan", $tan);
					$this->tpl->setVariable("tan_direct_link", linkToFile("start.php", array("tan" => $tan), FALSE, TRUE));
					$this->tpl->setVariable("tan_portal_link", linkTo('test_listing', array(), FALSE, TRUE, TRUE));
					$this->tpl->setVariable("email_address", post('mail'));
					$bodies[$type] = $this->tpl->get();
				}

				libLoad('email::Composer');
				$mail = new EmailComposer();
				$mail->setSubject(T('pages.testmake.tan_subject'));
				$mail->setFrom(SYSTEM_MAIL);
				$mail->addRecipient(post('mail'));
				$mail->setHtmlMessage($bodies["html"]);
				$mail->setTextMessage($bodies["text"]);
				$mail->sendMail();
			}
		}
		
		$working_path = "_0_" . $test->getId() . "_";
		redirectTo("test_make", array("action" => "start_test", "test_path" => $working_path, "resume_messages" => "true"));
	}
}