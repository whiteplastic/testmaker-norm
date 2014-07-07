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

require_once(PORTAL.'AdminPage.php');
require_once(PORTAL.'DataAnalysisPage.php');
require_once(PORTAL.'IntroEditor.php');

require_once(CORE.'types/CronJob.php');

libLoad('PEAR');



/**
 * Test accomplishment
 *
 * Default action: {@link doFilter()}
 *
 * @package Portal
 */
 
class TestSurveyPage extends DataAnalysisPage
{
	var $defaultAction = 'filter';
	
		
	function _parseMailText($mailtext, $user, $test_id)
	{
		$testlink = linkToFile("start.php", array("test_path" => "-0-".$test_id."-"), FALSE, TRUE);
		$pwlink = linkToFile("index.php", array("page" => "user_login", "action" => "forgot_password", "user" => $user->getUsername()), FALSE, TRUE);
		
		$text = $mailtext;
		$text = str_replace("[NAME]", htmlentities($user->fields["full_name"]), $text);
		$text = str_replace("[TEST]", $testlink, $text);		//"<a href=\"".$testlink."\" target=new>".$testlink."</a>", $text); 
		$text = str_replace("[USERNAME]", $user->getUsername(), $text);
		$text = str_replace("[PWLINK]", $pwlink, $text);		//"<a href=\"".$pwlink."\" target=new>".$pwlink."</a>", $text);

		return $text;
	}
	
	
	/**
	 *show overview: emails and sample-text before final submit
	*/
	function doConfirmSurvey()
	{
		$errors = NULL;
		
		if(isset($_POST['fckcontent'])) {
			$mailText = $_POST['fckcontent'];	
			$_SESSION['mailText'] = $mailText;
		} 
		else
			$errors[] = 'pages.test_survey.enter_subject';
			
			
		if(!$_POST["survey_test"] && strpos($mailText, "[TEST]"))	
			$errors[] =  'pages.test_survey.select_test';	
		else {
			$survey_test = $_POST["survey_test"];
			//setPost('survey_test', $survey_test);
			$_SESSION['survey_test'] =  $survey_test;
		}
			
		if(isset($_POST["subject"]) && $_POST["subject"] != "") {
			$subject = $_POST["subject"];
			//setPost('subject', $subject); 
			$_SESSION['subject'] =  $subject;
		} else
			$errors[] = 'pages.test_survey.enter_subject';	
			
		if($errors)
		{
			foreach($errors as $error)
				$GLOBALS["MSG_HANDLER"]->addMsg($error, MSG_RESULT_NEG);	
				
			$this->doFilter();
			//redirectTo("test_survey", array("action" => "filter"));
		}	
		else 
		{
			//preview with current user details
			
			$user = $GLOBALS["PORTAL"]->getUser();
			
			$mailText = $this->_parseMailText($mailText, $user, $survey_test);
		
			//save mail-text in file
			if(isset($_POST["save_text"])) {
				file_put_contents(ROOT."portal/templates/TestSurveyMail.txt", $_SESSION['mailText']);
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.test_survey.text_saved", MSG_RESULT_POS);
			}
				
			$this->tpl->loadTemplateFile("confirmTestSurvey.html");

			$this->tpl->touchBlock("confirm");
			$this->tpl->hideBlock("done");
			
			$this->tpl->setVariable('mails_count', count($_SESSION['survey_users']));
			$this->tpl->setVariable('survey_recipient', $user->getEMail());
			$this->tpl->setVariable('survey_subject', $subject);
			$this->tpl->setVariable('survey_content', $mailText);
					
			require_once(CORE."types/UserList.php");
			$userList = new UserList();
								
			foreach ($_SESSION['survey_users'] as $user_id)
			{
				if($user = $userList->getUserById($user_id)) {
					$this->tpl->setVariable("user_email", $user->getEMail());
					$this->tpl->parse('email_row');
				}
			}
			
			$body = $this->tpl->get();
			$this->loadDocumentFrame();
			$this->tpl->setVariable("body", $body);
			$this->tpl->setVariable("page_title", T('menu.test.survey'));
			
			$this->tpl->show();
			
		}
		
		
	}
	
	

	
	/**
	 *Send survey mail to selected users
	 *
	 *
	 * USE ONLY IF YOU HAVE NO CRONJOB AND NO EMAIL-LIMITS!!!!!
	 * THERE MIGHT BE PROBLEMS WITH YOUR PROVIDER WHEN SENDING A MASSIVE AMOUNT OF EMAILS AT ONCE
	 *
	*/
	  
	function doSendEmails()
	{
			
	//	isset($_POST["survey_test"]) ? $survey_test_id = $_POST["survey_test"] : $survey_test_id = NULL;
	//	if ($survey_test_id == NULL && $_SESSION['survey_test'] != NULL)
		$survey_test_id = $_SESSION['survey_test'];
		$mailText = $_SESSION['mailText'];

		isset($_SESSION['subject']) ? $subject = $_SESSION['subject'] : $subject = "Testmaker Userbefragung";
		
		if(!isset($_SESSION['skipped']))
			$_SESSION['skipped'] = 0;
		
		$skipped =	$_SESSION['skipped'];
		
		$userlist = new Userlist();

		$selectedUsers = $_SESSION['survey_users'];
			
		if (isset($_SESSION['countEmail']))			// have mails already been sent?
			$countEmail = $_SESSION['countEmail'];
		else
			$countEmail = 0;

		$userCount = count($selectedUsers);	//total mails to send
		
//		if ($countEmail >= $userCount)		// ??!?!
//			$countEmail = 0;
			
		$offset = $countEmail;		// mails already sent
		
		$time1 = time();
		$newPackage = false;
		$count = 0;
		
		$chunklimit = 150;

		foreach ($selectedUsers as $userId) 
		{

			if($count >= $offset)		//skip mails that have already been sent
			{
				$user = $userlist->getUserById($userId);
				$username = $user->getUsername();				
				$emailAddress = $user->getEmail();
				
				$text = $this->_parseMailText($mailText, $user, $survey_test_id);

				require_once('lib/email/Composer.php');
				$mail = new EmailComposer();
				$mail->setHtmlMessage($text);
				$mail->setSubject($subject);
				$mail->setFrom(defined('SYSTEM_MAIL_B') ? SYSTEM_MAIL_B : SYSTEM_MAIL);
				$mail->addRecipient($emailAddress);
//echo $emailAddress.'<br>'.$subject.'<br>'.$text.'<p>';	

				$db = &$GLOBALS['dao']->getConnection();		//temporaryly store email-adresses and check if already sent
				$res = $db->getRow("SELECT * FROM tmp_surveylog WHERE mail_sent = '".$emailAddress."'");
				if(!$res){			
					$mail->sendMail();
				//	echo $emailAddress;
					$res = $db->query("INSERT INTO tmp_surveylog (mail_sent) VALUES ('".$emailAddress."')");
				}
				else {
					$skipped++;
				}
			
				$countEmail++;
			}
			
			$count++;
		
			
			//if near server timeout store number of sent mails and make redirect
			if ((time() - $time1 > 30) or (($count-$offset) >= $chunklimit)) {
				$_SESSION['countEmail'] = $countEmail;
				$_SESSION['skipped'] = $skipped;
				$newPackage = true;
				break;
			}
			//}
		}

//		$newPackage = true;
			
		if ($newPackage) {
			$this->tpl->loadTemplateFile("countMails.html");
			$this->tpl->hideBlock("count_mail");
			$this->tpl->hideBlock("finished");
			$this->tpl->touchBlock("click");
			$link = "index.php?page=test_survey&action=send_emails";
			$this->tpl->setVariable("link", $link);
			$this->tpl->setVariable("mailcount", $countEmail);
			$this->tpl->setVariable("toSendMails", $userCount);
			$this->tpl->setVariable("skipped", $skipped);
 
			$this->tpl->show();
		}
		else {
			$this->tpl->loadTemplateFile("countMails.html");
			$this->tpl->hideBlock("click");
			$this->tpl->touchBlock("count_mail");
			$this->tpl->setVariable("mailcount", $countEmail);
			$this->tpl->setVariable("toSendMails", $userCount);
			$this->tpl->setVariable("skipped", $skipped);
			$this->tpl->touchBlock("finished");
			$this->tpl->show();	
			
			//add log entry
			$details = array_merge($_SESSION["TEST_RUN_FILTERS"], array("totalmails" => $userCount, "skippedmails" => $skipped, "survey_test" => $_SESSION['survey_test'], "mailtext" => $_SESSION['mailText']));
			EditLog::addEntry(LOG_OP_TEST_SURVEY, $userCount, $details);
			
// session variables alle genutzt/verfügbar/gelöscht hinterher?!?	
			
		//	unset($_SESSION['survey_users']);		// only on success
			unset($_SESSION['countEmail']);
		//	unset($_SESSION['survey_test']);
		//	unset($_SESSION['mailText']);
		//	unset($_SESSION['subject']);
		//	unset($_SESSION["TEST_RUN_FILTERS"]);
			
		}
		
	}
	
	/**
	 *Add survey mails to send to cronjob-table
	 *
	 *BETTER USE THIS INSTEAD OF REGULAR doSendEmails()
	 *
	 */	 
	function doSendEmailsCron()
	{
		$time1 = NOW;
		
		$survey_test_id = $_SESSION['survey_test'];
		$mailText = $_SESSION['mailText'];
	
		isset($_SESSION['subject']) ? $subject = $_SESSION['subject'] : $subject = "Testmaker Userbefragung";
		
		$userlist = new Userlist();
	
		$selectedUsers = $_SESSION['survey_users'];
		
		$userCount = count($selectedUsers);	//total mails to send

		
		$count = 0;
		$offset = 0;
		$timeout = false;
		
		
		
		if(isset($_SESSION["jobID"])) {
			//recall
			$jobID = $_SESSION["jobID"];
			$offset = $_SESSION['countEmail'];
		}		
		else {
			//new job id
			$jobID = CronJob::_getNextFreeID();
			$_SESSION['countEmail'] = 0;
		}
			
		$job = new CronJob($jobID);

		//for each recipient create one slave for cronjob
		$cronSlaves = array();
		foreach ($selectedUsers as $userId)
		{
			if( $count < $offset ) {
				//skip already sent mails
			}
			else {

				$user = $userlist->getUserById($userId);
				$username = $user->getUsername();
				$emailAddress = $user->getEmail();
				
				$text = $this->_parseMailText($mailText, $user, $survey_test_id);
	
				$mail_content= serialize(array("subject"=>$subject, "body"=>$text));
				//$cronSlaves[] = array("destination"=>$emailAddress, "content"=>$mail_content);
				
				$job->_createSingleSlave($jobID, $time1, "mail_custom", $emailAddress, $mail_content, $count+1);
				
				// 	normally we use createJob, but with a lot of slaves we get a timeout
				//		$job->createJob("mail_custom", "Nachbefragung TestID #".$survey_test_id, "custom Mails", &$cronSlaves);
				//	therefore: create job manually. job is only executed with present master.
				
				
			}
			
			$count++;
			
					
		//restrict execution time to prevent timeout
			if( ($count > $_SESSION['countEmail']) && $count%10 < 1 ) {
//b/		if( time() - $time1 > 3 ) {
				$timeout = true; 
				break;
			}

		}
		
		
		$this->tpl->loadTemplateFile("confirmTestSurvey.html");
		
		$this->tpl->hideBlock("confirm");
		
		$this->tpl->hideBlock("done");
		$this->tpl->touchBlock("doing");
			
		
		$this->tpl->setVariable("count", $count);
		$this->tpl->setVariable("usercount", $userCount);
		$this->tpl->setVariable("link",linkTo("cronjob_status", array("action" => "show_status")));

		if($timeout) {
			
			$_SESSION['countEmail'] = $count;
			$_SESSION["jobID"] = $jobID;
			
			
			//self-execution
			$this->tpl->setVariable("autolink", "onload= \"document.location = 'index.php?page=test_survey&action=send_emails_cron'\"");
			
			$this->tpl->show();
		}
		else {
		
			if(!isset($_SESSION['testId'])) 
				$_SESSION['testId'] = "?";
			
			//create master
			$job->_createSingleMaster($jobID, $time1, "mail_custom", "Nachbefragung TestID #".$_SESSION['testId'], serialize("This is Master of Job".$jobID));
					
			//add log entry
			$details = array_merge($_SESSION["TEST_RUN_FILTERS"], array("totalmails" => $userCount, "survey_test" => $_SESSION['survey_test'], "mailtext" => $_SESSION['mailText']));
			EditLog::addEntry(LOG_OP_TEST_SURVEY, $userCount, $details);

			// only on success: delete session variables
			// or keep them... seems better for user experience..			
				//	unset($_SESSION['survey_users']);				
				//	unset($_SESSION['survey_test']);
				//	unset($_SESSION['mailText']);
				//	unset($_SESSION['subject']);
				//	unset($_SESSION['testId']);
				//	unset($_SESSION["TEST_RUN_FILTERS"]);
			unset($_SESSION["jobID"]);
			unset($_SESSION['countEmail']);
		
			$this->tpl->hideBlock("doing");
			$this->tpl->touchBlock("done");
		
			$body = $this->tpl->get();
			$this->loadDocumentFrame();
			$this->tpl->setVariable("body", $body);
			$this->tpl->setVariable("page_title", T('menu.test.survey'));
			
			$this->tpl->show();
		}
				
		
		
	}
	
	
	
	
	function doFilter() {

		$this->tpl->loadTemplateFile("TestSurvey.html");
		$this->initTemplateCronjobs("survey");
		$pageTitle = T("menu.test.survey");
		
		// Load lib
		libLoad('utilities::getCorrectionMessage');

		$this->init();
		$this->initFilters("test_survey");
		
		// Initialize the page
		
		$this->keepFilterSettings();
		$testRunCount = $this->countTestRuns();
	
		$this->tpl->hideBlock("has_results");
		$this->tpl->hideBlock("no_results");
	
		if ((!$testRunCount) or ($testRunCount == 0))
		{
			$this->tpl->touchBlock("no_results");
		}
		elseif ($this->filters["testId"] != 0 || $this->filters["testRunID"] != 0)
		{

			// Show the Result List
			if($this->filters["testRunID"] != 0) {
				$testRuns = array($this->testRunList->getTestRunById($this->filters["testRunID"]));
			} else {
				$testRuns = $this->getTestRuns(FALSE, 0, NULL);
			}


			if (! $testRunCount)
			{
				$this->tpl->touchBlock("no_results");
			}
			else
			{
				$this->tpl->touchBlock("has_results");
				$this->tpl->setVariable("result_count", $testRunCount);
				
				require_once(CORE."types/UserList.php");
				$userList = new UserList();
								
				$selectedUsers = array();
				foreach ($testRuns as $testRun)
				{		
					if ($testRun->getAccessType() == "tan") continue;			// skip TAN-users. TODO: write generic mail to tan-users
					$uid = $testRun->getUserId();		
					if(!in_array($uid, $selectedUsers) && $uid != 0)			//no email dublettes
						$selectedUsers[] = $uid;

				}
				
				$_SESSION['survey_users'] = $selectedUsers;
				$this->tpl->setVariable("users_count", count($_SESSION['survey_users']));
			}
		
		}
		else
		{
			$this->tpl->touchBlock("no_filter");
		}

		
		// show survey text editor		
		
		
		$_SESSION['testId'] = $this->filters["testId"];
		
		if(isset($_SESSION['subject']))
			$this->tpl->setVariable('survey_subject', $_SESSION['subject']);

		$survey_test_id = isset($_SESSION['survey_test']) ? $_SESSION['survey_test'] : 0;
					
		$tests = array();
		foreach ($this->getTests() as $testId) {
			$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testId, BLOCK_TYPE_CONTAINER);
			$title = $test->getTitle();
			$tests[$testId] = $title != "" ? $title : "(empty)";
		}
		uasort($tests, "strnatcasecmp");
		
		$this->tpl->setVariable("select_survey_test_id", 0);
		$this->tpl->setVariable("select_survey_test_title", "Select Test");						
		$this->tpl->parse("select_survey_test");

		foreach ($tests as $testId => $testTitle)
		{	
			$testTitle = shortenString($testTitle,64);
			$this->tpl->setVariable("select_survey_test_id", $testId);		
			$this->tpl->setVariable("select_survey_test_title", $testTitle);
	
			if($testId == $survey_test_id)
				$this->tpl->setVariable("survey_selected", "selected");
				
			$this->tpl->parse("select_survey_test");
		}

		// Output
			
		if(isset($_SESSION['mailText']))
		{
			$editor = new IntroEditor($_SESSION['mailText']);
			unset($_SESSION['mailText']);
		}
		else {
			$temp= file_get_contents(ROOT."portal/templates/TestSurveyMail.txt");
			$editor = new IntroEditor($temp);
		}	

		$this->tpl->setVariable('mail_content', $editor->CreateHtml());
		
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
				
		$this->tpl->setVariable("page_title", $pageTitle);
		$this->tpl->show();
		
	}
	
}
?>