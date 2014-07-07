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
 * Include the test wrapper class
 */
require_once(CORE.'types/Test.php');
require_once(CORE.'types/TestMake.php');
require_once('portal/FrontendPage.php');

/**
 * Test accomplishment
 *
 * Default action: {@link doShowTest()}
 *
 * @package Portal
 */
if(!defined("ANSWER_SKIPPED"))
define("ANSWER_SKIPPED", 98);
if(!defined("ANSWER_TIMEOUT"))
define("ANSWER_TIMEOUT", 99);

class TestMakePage extends FrontendPage
{
	public $defaultAction = 'show_test';

	private $testMake = NULL;
	private $templateDir;
	private $block = NULL;
	private $debugOutput = array();

	/**
	 * Constructor: behaviour should be selfexplaining. In the future the constructor
	 * should never be invoked directly.
	 *
	 * @param string Page name
	 */
	function TestMakePage($pageName)
	{
		parent::Page($pageName);
		$this->testMake = new TestMake();
		$this->templateDir = ROOT."upload/item_templates/";
	}

	/**
	 * Loads the test document frame template and initializes it
	 *
	 * Basically, this does the following:
	 * <code>
	 * $this->tpl->loadTemplateFile("TestFrame.html");
	 * </code>
	 */
	function loadDocumentFrame($cookieId, $checkSecurity = TRUE)
	{
		$this->tpl->loadTemplateFile("TestFrame.html");
		$this->tpl->setVariable("cookieId", $cookieId);
	
		if ($checkSecurity) {
			// if the user isn't logged in, display error page
			if ($this->block && !$this->block->isPublic()) $this->checkAllowed('invalid', true, NULL);
		}

		if ($this->actionName == "preview")
			$this->tpl->hideBlock('autotester');
		else
			$this->tpl->touchBlock('autotester');
	}

	/**
	 * Starts test run:
	 */
	function doStartTest()
	{
		$password = post("password", NULL);
		$tmpWorkingPath = get('test_path', get('test-path'));
		$testId = NULL;
		$subTestId = NULL;

		$workingPath = str_replace("-", "_", $tmpWorkingPath);
		if (!WorkingPath::verify($workingPath)) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.link_invalid', MSG_RESULT_NEG);
			redirectTo('test_listing');
		} else
		{
			$testId = WorkingPath::getTestId($workingPath);
			$subTestId = WorkingPath::getSubtestId($workingPath);
		}

		// check if a test with given id exists and user has permission for it
		if(!BlockList::checkTestId($testId, NULL)){	

			if ($GLOBALS["BLOCK_LIST"]->existsBlock($testId) && !$_SESSION['userId']) 	 
				$_SESSION['direct_test'] = $tmpWorkingPath; //only if test at least exists and no user logged in
			
			redirectTo('test_listing');
		} 

		$test = new Test($testId);

		if($test->getShowSubtests() && $subTestId === false)
		{
			redirectTo('test_listing', array('action' => 'subtest_view', 'test_run' => -1, 'test_id' => $testId)); 
		}

		$tan = isset($_SESSION['tan']) ? $_SESSION['tan'] : NULL;
		switch (get('source')) {
			case 'portal':
				$accessType = 'portal';
				break;
			case 'preview':
				$accessType = 'preview';
				break;
			default:
				$accessType = 'direct';
		}
		$id = $this->testMake->startTest($accessType, $password, $subTestId, $tan, $testId, $workingPath);
		redirectTo("test_make", array("action" => "bounce_to_test", "id" => $id));
	}

	function doContinueTest()
	{
		$workingPath = get('test_path');
		$subTestId = WorkingPath::getSubtestId($workingPath);
		$testRunId = get('test_run', NULL);
		$id = $this->testMake->continueTest($subTestId, $testRunId);
		redirectTo("test_make", array("action" => "bounce_to_test", "id" => $id));
	}

	/**
	 *
	 */
	function doBounceToTest()
	{
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		$this->tpl->loadTemplateFile("Bouncer.html");
		if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], "Opera") === false) $this->tpl->setVariable("show_message", "none");
		else $this->tpl->setVariable("show_message", "block");
		$this->tpl->setVariable("target_link", linkTo("test_make", array("id" => get("id"), "tan" => get("tan"), "resume_messages" => "true"), FALSE, TRUE));
		$this->tpl->show();
	}

	function doProcessAnswer()
	{
		$id = get('id', NULL);
		$itemIds = explode("_", post("item_id"));
		$blockId = post('block_id', NULL);
		$answers = post('answer', array());
		$timeout = post('timeout', false);
		$duration = post('duration', 0);

		if ($_SERVER["REQUEST_METHOD"] == "GET" || !$_POST ) {
			redirectTo("test_make", array("id" => $id, "resume_messages" => "true"));
			return;
		}
		if (!isset($_POST['block_id'])) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.test.no_block_id', MSG_RESULT_NEG);
			redirectTo("test_make", array("id" => $id, "resume_messages" => "true"));
			return;
		}
		if (isset($_POST['send_feedback_email']))
		{
			$this->sendFeedbackEmail();
		} else
		{
			$this->testMake->processAnswer($answers, $blockId, $duration, $id, $itemIds, $timeout);
			redirectTo("test_make", array("action" => "bounce_to_test", "id" => $id));
		}
	}

	function doSkipBlock()
	{
		$id = get("id");
		$this->testMake->initRunningTest($id);
		$testRun = $this->testMake->getTestRun();
		$testId = $testRun->getTestId();
		$blockId = post("block_id");

		$this->testSession["skip_block"] = array(
			"do_skip" => !post("cancel"),
			"block_id" => post("block_id"),
			"subtest_id" => $_SESSION['RUNNING_TESTS'][$id]['displayingBlockId'],
		);
				
		// let the test run know that the block was skipped
		$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		
		//get all Ids of the testruns in that the user have alredy answered this block 
		if ($this->testSession["skip_block"]["do_skip"]) {
			$TestRundIds = $testRun->testRunList->getSourceRunsForSkipping($GLOBALS["PORTAL"]->getUserId(), $block, $testId);
			//get the latest test run id
			sort($TestRundIds, SORT_NUMERIC);
			$LastTestRunId = end($TestRundIds);
		}

		require_once(CORE."types/TestSelector.php");
		$selector = new TestSelector($block);
		$numberItemsInBlock = $selector->countItems($blockId);
		if ($this->testSession["skip_block"]["do_skip"])
		{
			$testRun->setStep($testRun->getStep() + $numberItemsInBlock);
			$testRun->setTestRunBlockStep($testRun->getTestRunBlockStep() + $numberItemsInBlock);
		}

		$_SESSION['RUNNING_TESTS'][$id]["skip_block"] = $this->testSession["skip_block"];
		$_SESSION['RUNNING_TESTS'][$id]["skipable_blocks"][] = $this->testSession["skip_block"]["block_id"];
		redirectTo("test_make", array("id" => $id));
	}

	/**
	 * Display a item/feedback/info page in a test environment
	 */
	function doShowTest()
	{
			
		$id = get("id");
		$this->testMake->initRunningTest($id);

		if($this->testMake->getTestRun() === NULL) 
		{
			$GLOBALS['MSG_HANDLER']->addMsg("pages.testmake.test_run_not_found", MSG_RESULT_NEG);
			redirectTo("test_listing");
		}
		else 
		{
			$time = time();
			$items = "notFound";
			while ($items == "notFound") {
				$items = $this->testMake->getTestRun()->getNextItems($id);
				if (time()-$time > 45)
					redirectTo("test_make", array("action" => "show_test", "id" => $id));
			}
			// save current item ids in the session (for doProcessAnswers)
			$_SESSION['current_items'] = array();
			if($items)
			{
				if(!@$items["special"]) // Don't save'em when block will be skipped
				{
					foreach($items as $item)
					{
						$_SESSION['current_items'][] = array("id" => $item->getId(), "type" => get_class($item));
					}
				}
			}
		}
		
		if (@$items["special"])
		{
			if ($items["type"] == "confirm_block_skip") 
			{
				$this->tpl->loadTemplateFile("TestMakeConfirmBlockSkip.html");
				$this->tpl->setVariable("id", $id);
				$this->tpl->setVariable("block_id", $items["block_id"]);
				$this->tpl->setVariable("block_title", $items["block_title"]);
				$this->tpl->setVariable("other_block_title", $items["other_test_title"]);
				$body = $this->tpl->get();

				$this->loadDocumentFrame(NULL, FALSE);
				$this->tpl->setVariable("style", $this->getStyle($this->testMake->getTestRun()->getTestId(), $this->testMake->getTestRun()->getTestPath()));
				$this->tpl->setVariable("body", $body);
				$this->tpl->show();
			}
			else
			{
				trigger_error("Unknown special type <b>".htmlentities($items["type"])."</b>", E_USER_ERROR);
				return;
			}
		}
		else
		{
			$redirect = $this->testMake->redirectOnFinish($items);
			$this->_createTestPage($items, $id, $redirect);	// id als 4ter Parameter ermöglicht Vorschau-Navigation, ist aber noch buggy
		}
	}

	/**
	 * Display a item page only for preview
	 */
	function doPreview()
	{
		$working_path = get("working_path");
		
		$item_id = get("item_id");

		$ids = explode("_", trim($working_path, '_'));

		$blocks = array();
		$id = $ids[1];
		$this->id = $id;

		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById($id);
		
		//Set language to test language
		$_SESSION['languageOld'] = $_SESSION['language'];
		$_SESSION['language'] = $blocks[0]->getLanguage();
		
		for($i = 2; $i < count($ids); $i++)
		{
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
		}

		$parent = end($blocks);
		$item = $parent->getTreeChildById($item_id);
		$redirect = array("item", array("working_path" => $working_path, "item_id" => $item_id, "reset_lang" => 1));
		$this->_createTestPage(array($item), $id, $redirect, $id);
	}

	function inflateTemplateName($file)
	{
		if (! isset($this->inflatedTemplateNames[$file]))
		{
			$class = preg_replace("/\\.[^\\.]*$/", "", $file);
			$variant = "";
			if (preg_match("/^([^_]+)_(.+)$/", $class, $match)) {
				$class = $match[1];
				$variant = $match[2];
			}

			$this->inflatedTemplateNames[$file] = array($file, $class, $variant);
		}

		return $this->inflatedTemplateNames[$file];
	}
	
	/**
	*Send an email with the feedback to the user
	*/	
	
	function sendFeedbackEmail()
	{
		$id = get("id");
		$this->testMake->initRunningTest($id);

		if (!defined("SYSTEM_MAIL"))
		{
			$GLOBALS['MSG_HANDLER']->addMsg("pages.testmake.no_system_mail_address", MSG_RESULT_NEG);
			redirectTo("test_make", array("action" => "bounce_to_test", "id" => $id));
		}

		if($this->testMake->getTestRun() === NULL) $items = NULL;
		// restore the current items from the session
		else
		{
			$items = array();
			foreach($_SESSION['current_items'] as $index => $itemData)
			{
				$type = $itemData['type'];
				if ($type != "FeedbackPage") require_once(ROOT . "upload/items/" . $type . ".php");
				$items[] = new $itemData['type']($itemData['id']);
			}
		}
		$email = "";
		$tan = isset($_SESSION['tan']) ? $_SESSION['tan'] : NULL;
		if($tan)
		{
			require_once(CORE.'types/TANCollection.php');
			$tanCollection = new TANCollection($this->testMake->getTestRun()->getTestId());
			$email = $tanCollection->getEmailByTestRun($this->testMake->getTestRun()->getId());
		} else
		{
			$userID = $_SESSION['userId'];
			require_once(CORE.'types/UserList.php');
			$userList = new UserList();
			$user = $userList->getUserById($userID);
			$email = $user->get('email');
		}

		// Compose and send email
		libLoad("email::Composer");
		libLoad("utilities::validator");
		if(!Validator::validateText($email, "email"))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.testmake.invalid_feedback_email_adress', MSG_RESULT_NEG);
			redirectTo("test_make", array("id" => $id));
		}
		
		$mail = new EmailComposer();

		//create the feedback for the testrun
		require_once(CORE."types/FeedbackGenerator.php");
		$this->tpl->loadTemplateFile("FeedbackEmail.html");
		foreach ($items as $key => $item)
		{
			if (is_a($item, "FeedbackPage"))
			{
				// Get the relevant feedback paragraphs
				$feedback = new FeedbackGenerator($item->getParent(), $this->testMake->getTestRun());
				$feedback->setPage($item);
				$paragraphs = $feedback->getParagraphs();

				$imageCounter = 0;
				foreach ($paragraphs as $para) {
					// Handle feedback graphs
					$mixed = $feedback->expandText($para->getContents(), array(NULL, '_getOutput'), array('request_binary' => true));
					if(is_array($mixed))
					{
						foreach($mixed[1] as $image)
						{
							$contentID = $mail->addHtmlAttachmentFromMemory($image, "image/png", 'img'.$imageCounter.'.png');
							$text = preg_replace("/\[\[graph\]\]/", '<img src="cid:'.$contentID.'">', $mixed[0], 1);
							unset($contentID);
						}
					} else $text = $mixed;
					$this->tpl->setVariable("para", $text);
					$this->tpl->parse("paragraphs");
				}	
			}
		}
		$testId = $this->testMake->getTestRun()->getTestId();
		require_once(CORE.'types/Test.php');
		$test = new Test($testId);
		$title = $test->getTitle();
		$this->tpl->setVariable("title", $title);
		$this->tpl->setVariable("date", date("d.m.Y"));
		$this->tpl->setVariable("time", date("G:i"));
		
		// Add testMaker logo
		$contentID = $mail->addHtmlAttachment(PORTAL.'images/tm-logo-xs.png', 'image/png', 'logo.png');
		$this->tpl->setVariable("logo", "cid:$contentID");

		$mail->setSubject("testMaker Feedback ".date("d.m.Y"));
		$html = $this->tpl->get();
		$mail->setHTMLMessage($html);
		$mail->setFrom(SYSTEM_MAIL);
		$mail->addRecipient($email);
		$mail->sendMail();
		redirectTo("test_make", array("action" => "bounce_to_test", "id" => $id));
	}

	/**
	 * Helper function, create test pages
	 *
	 * @param mixed Items to display
	 * @param integer Test id
	 * @param mixed Parameters for redirect link
	 */
	function _createTestPage($items, $id, $redirect, $testId = NULL)
	{
		$body = "";
		$align = "";
		$this->testSession = &$_SESSION["RUNNING_TESTS"][$id];

		if (isset($_SESSION['RUNNING_TESTS'][$id]['displayingBlockId']))
			$displayingBlockId = $_SESSION['RUNNING_TESTS'][$id]['displayingBlockId'];
		// Get parent block
		$this->block = $items[0]->getParent();
		if ($itemblock = strtolower($this->block->getBlockType()) == 3)
		{
			$defaultSettings = array(
				'align' => $this->block->getDefaultTemplateAlign(),
				'cols' => $this->block->getDefaultTemplateCols(),
			);
		}

		// Get old Answers if exists
		$oldAnswers = array();
		$missingAnswers = array();
		if (isset($_SESSION['oldAnswers']))
		{
			$oldAnswers = $_SESSION['oldAnswers'];
		}
		if (isset($_SESSION['missingAnswers']))
		{
			$missingAnswers = $_SESSION['missingAnswers'];
		}

		$displayItems = '';
		$lastItem = "";
		$lastItemType = "";

		foreach ($items as $key => $item)
		{
			if (is_a($item, "InfoPage"))
			{
				$this->tpl->loadTemplateFile("InfoPage.html");
				$this->tpl->setVariable("info", $item->getContent());
			}
			elseif (is_a($item, "FeedbackPage"))
			{
				require_once(CORE."types/FeedbackGenerator.php");
				$feedback = new FeedbackGenerator($item->getParent(), $this->testMake->getTestRun());
				$feedback->setPage($item);
				$paragraphs = $feedback->getParagraphs();
				
				// Check for users email adress
				$email = "";
				$tan = isset($_SESSION['tan']) ? $_SESSION['tan'] : NULL;
				if($tan)
				{
					require_once(CORE.'types/TANCollection.php');
					$tanCollection = new TANCollection($this->testMake->getTestRun()->getTestId());
					$email = $tanCollection->getEmailByTestRun($this->testMake->getTestRun()->getId());
				} else
				{
					$userID = $_SESSION['userId'];
					if($userID)
					{
						require_once(CORE.'types/UserList.php');
						$userList = new UserList();
						$user = $userList->getUserById($userID);
						$email = $user->get('email');
					}
				}
				
				$this->tpl->loadTemplateFile("FeedbackPage.html");
				foreach ($paragraphs as $para) {
					$text = $para->getContents();
					//if no email exits, don't show the email butten in the testpage
					if ($email == "") 
						$text = str_replace('{feedback_mail:}', '', $text);
					$text = $feedback->expandText($text);
					$this->tpl->setVariable("para", $text);
					$this->tpl->parse("paragraphs");
				}
				$feedbackPages = $this->block->getTreeChildren();
				$lastPage =  $feedbackPages[count($feedbackPages)-1];
				
				
				
				//if($email != "" && $this->block->getShowInSummary()) $this->tpl->touchBlock('send_feedback_email');
				$db = $GLOBALS['dao']->getConnection();
				$query = "SELECT  form_filled FROM ".DB_PREFIX."tans WHERE access_key = ?";
				$result = $db->query($query, array($tan));
				$result = $result->fetchRow();

				if(($item->getId() == $lastPage->getId()) && ($this->block->isCertEnabled()))
				{
						$this->tpl->setVariable('test_run_id', $this->testMake->getTestRun()->getId());
						$this->tpl->setVariable('feedback_block_id', $this->block->getId());
						$this->tpl->touchBlock('certificate');
				}

			}
			elseif (is_a($item, "Item"))
			{
				if (!$key)
				{

					$this->tpl->loadTemplateFile("ItemPage.html");
					$this->tpl->touchBlock($testId ? "submit_preview" : "submit_live");
					$this->tpl->setGlobalVariable("max_time", $item->getMaxTime());
					$this->tpl->setGlobalVariable("min_time", $item->getMinTime());

					$align = $item->getTemplateAlign() ? $item->getTemplateAlign() : $defaultSettings['align'];
					
					if ($this->block->getIntroduction())
					{
						$intro_label = $this->block->getIntroLabel() == "" ? "Information" : $this->block->getIntroLabel();
						$this->tpl->setVariable('intro_label', $intro_label);
						$this->tpl->setVariable('introduction', $this->block->getIntroduction());
						
						// check if current block is the same as last one
						if (isset($_SESSION['lastUsedBlock']) && $_SESSION['lastUsedBlock'] == $this->block->getId() && $this->block->isIntroFirstOnly())
						{
							$hideIntro = true;	// hide, if block is same as last one and firstonly is set
						} 
						else {
							$hideIntro = false;
						}

					
						if ($this->block->isHiddenIntro()) { //hide intro, show hidden-intro (button)
							if($this->block->isIntroFirstOnly() && !$hideIntro) { //but if firstonly was checked: show full intro, only hide 
								if ($this->block->getIntroPos()) {
									$this->tpl->hideBlock('down_view_intro');
									$this->tpl->touchBlock('up_view_intro');
								}
								else {
									$this->tpl->hideBlock('up_view_intro');
									$this->tpl->touchBlock('down_view_intro');
								}
								$this->tpl->hideBlock('up_view_hidden_intro');
								$this->tpl->hideBlock('down_view_hidden_intro');
							} 
							else {
								if ($this->block->getIntroPos()) {
									$this->tpl->hideBlock('down_view_hidden_intro');
									$this->tpl->touchBlock('up_view_hidden_intro');
								}
								else {
									$this->tpl->hideBlock('up_view_hidden_intro');
									$this->tpl->touchBlock('down_view_hidden_intro');
								}
								$this->tpl->hideBlock('up_view_intro');
								$this->tpl->hideBlock('down_view_intro');
							}
						}
						else {	// hide hidden-intro (button), either show intro or not.
							$this->tpl->hideBlock('up_view_hidden_intro');
							$this->tpl->hideBlock('down_view_hidden_intro');
							
							if (!$hideIntro) { // show intro
								if ($this->block->getIntroPos()) {
									$this->tpl->touchBlock('up_view_intro');
									$this->tpl->hideBlock('down_view_intro');
								}
								else {
									$this->tpl->touchBlock('down_view_intro');
									$this->tpl->hideBlock('up_view_intro');
								}
							}
							else { //hide everything (intro and hide button)
								$this->tpl->hideBlock('up_view_hidden_intro');
								$this->tpl->hideBlock('down_view_hidden_intro');
								$this->tpl->hideBlock('up_view_intro');
								$this->tpl->hideBlock('down_view_intro');
							}
							
						}
					}
					
					$_SESSION['lastUsedBlock'] = $this->block->getId();		//store current block id	
					
				}
				
				//If you have more than one Item per page, the matrix item (McsaHeaderItem) must be displayed in a special way.
				if (($lastItemType != "McsaHeaderItem") && ($item->getType() == "McsaHeaderItem"))
				{
					$beginHeader = true;
				}
				elseif (($lastItemType == "McsaHeaderItem") && (Item::compareAnswers($lastItem, $item) == false)) {
					$beginHeader = true;
				}
				else
					$beginHeader = false;
					
				$lastItem = $item;
				$lastItemType = $item->getType();
				
				if (isset($items[$key + 1])) {
					if (($items[$key + 1]->getType() != "McsaHeaderItem") && ($item->getType() == "McsaHeaderItem")) {
						$displayItems .= trim($item->parseTemplate($key, count($items), $oldAnswers, $missingAnswers, $beginHeader, true));
					}
					else {
						$closeTable = (Item::compareAnswers($item, $items[$key+1]) == false);
						$displayItems .= trim($item->parseTemplate($key, count($items), $oldAnswers, $missingAnswers, $beginHeader, $closeTable));
					}
				}
				else {
					$displayItems .= trim($item->parseTemplate($key, count($items), $oldAnswers, $missingAnswers, $beginHeader));
				}
				

				if ($key == count($items)-1)
				{
					$this->tpl->setVariable("item", $displayItems);
				}

				if(($item->getMinTime() != 0) || ($item->getMaxTime() != 0)) {
					$this->tpl->touchBlock('time_bar');
				} else {
					$this->tpl->hideBlock('time_bar');
				}
				$parent = $item->getParent();
				if ($parent->getMaxTime() && ! $item->getMaxTime() && get('action') != 'preview')
				{
					$blockTime = (isset($this->testSession['block_time'][$parent->getId()]) ? $this->testSession['block_time'][$parent->getId()] : $parent->getMaxTime());
					$this->tpl->setGlobalVariable('max_time', $blockTime);
					$this->tpl->setGlobalVariable('is_block_time', 'true');
					$this->tpl->touchBlock('time_bar');
				}
				else
				{
					$this->tpl->setVariable('is_block_time', 'false');
				}
			}
			else
			{
				$this->tpl->loadTemplateFile("UnhandledPage.html");
				$this->tpl->setVariable("item_type", get_class($item));
			}
		}

		$this->tpl->setVariable("id", $id);
		$this->tpl->setVariable("session_id", session_id());
		$this->tpl->setVariable("item_id", implode('_', $this->_returnIds($items)));
		$this->tpl->setGlobalVariable("block_id", $this->block->getId());
		$cookieId = "testMakerRun".$id."Block".$this->block->getId()."Item".$item->getId();

		$testWrapper = new Test($testId ? $testId : $this->testMake->getTestRun()->getTestId());
		$test = $testWrapper->getBlock();
		
		$this->tpl->setGlobalVariable("test_title", $testWrapper->getTitle());

		$body = $this->tpl->get();
		$checkSecurity = isset($_SESSION['accessType']) ? ($_SESSION['accessType'] != 'password') : true;
		$this->loadDocumentFrame($cookieId, $checkSecurity);
		$this->tpl->setGlobalVariable("cookieId", $cookieId);
		
		if ($itemblock)
		{
			if (count($items) > 1) $align = $defaultSettings['align'];
			if ($align == 'v')
			{
				$this->tpl->touchBlock('vertical');
			}
			else
			{
				$this->tpl->touchBlock('horizontal');
			}
		}

		if ((! empty($_SESSION["RUNNING_TESTS"][$id]["showAbortLink"]) && $this->tpl->blockExists("abort_link")) || $testId) {
			//Construct breadcrumb trail
			$titles = array();
			$titles[] = $item->getTitle();
			$parent = $item->getParent();
			
			while (isset($parent)) {
				$titles[] = $parent->getTitle();
				$parent = $parent->getParents();
				@$parent = $parent[0];
			}
			
			foreach (array_reverse($titles) as $title) {
				$this->tpl->setVariable("breadcrumb", $title);
				$this->tpl->parse("breadcrumb");
			}
			
			$this->tpl->setVariable("abort_link", linkTo($redirect[0], $redirect[1]));
			$this->tpl->touchBlock("abort_link");
		}

		if (isset($this->testRun) && $this->testRun->getAccessType() == "preview") {
			foreach ($this->debugOutput as $debugEntry) {
				$this->tpl->setVariable("debug_entry", $debugEntry );
				$this->tpl->parse("debug_entry");
			}
		}

		// Include check for $ids[1].
		if ($this->testMake->getTestRun() !== NULL) {


			$ids = explode("_", trim($this->testMake->getTestRun()->getTestPath(), '_'));

			if (isset($ids[1])) {
				$tmpId = $ids[1];
			} else {
				$tmpId = $this->testMake->getTestRun()->getDisplayingBlockId();
			}

			$block = $GLOBALS['BLOCK_LIST']->getBlockById($tmpId);
			$progressBar = $block->getShowProgressBar();
			$pauseButton = $block->getShowPauseButton();
			$media_connect_id = $block->getLogo();
		}

		if (! $testId && $progressBar) {
			$progress = 0;
			if($test->getShowSubtests())
			{
				$trBlock = $this->testMake->getTestRun()->getTestRunBlockBySubtest($displayingBlockId);
				$progress = $trBlock->getProgress();
			}
			else $progress = $this->testMake->getTestRun()->getProgress();
			$this->tpl->setVariable("progress", $progress);
			$this->tpl->touchBlock("progress_bar");
			if ($this->block->isItemBlock() && $this->block->isAdaptiveItemBlock()) {
				$this->tpl->touchBlock("progress_bar_adaptive");
			}
		}

		if (!$GLOBALS["PORTAL"]->getUser()->get('id') == 0 && (!$testId && (($pauseButton == 1) || ($pauseButton == 2 && ($this->block->isFeedbackBlock() || $this->block->isInfoBlock()))))) {
			$this->tpl->setVariable("test_run_id", $this->testMake->getTestRun()->getId());
			
			$this->tpl->setVariable("test_id", $testWrapper->getId());	
			$this->tpl->setVariable("test_title", $testWrapper->getTitle());
			$this->tpl->setVariable("progressp", $progress);
			$this->tpl->touchBlock("continue_later_button");
				
			$this->tpl->parse("pause_button");
		}
		else
			$this->tpl->hideBlock("continue_later_button");


		$parent = $GLOBALS['BLOCK_LIST']->findParentInTest($this->block->getId(), $test->getId());
		if ($parent != 0)
			$testStyle = $this->getStyle($parent);
		else
			$testStyle = $this->getStyle($test->getId());
			
		$this->tpl->setVariable("style", $testStyle);
		$this->tpl->parse("style_block");
		
		
		if (!isset($media_connect_id)) $media_connect_id = $testWrapper->getLogo();
		if ($media_connect_id)
		{
			require_once(CORE."types/FileHandling.php");
			$fileHandling = new Filehandling();
			$list = $fileHandling->listMedia($media_connect_id);
			if (count($list) && $testWrapper->getLogoShow() > 0)
			{
				$this->tpl->setVariable("filename", $list[0]->getFilePath()."/".$list[0]->getFileName());
				$this->tpl->parse("logo_style");
			}
		}

		$user = $GLOBALS['PORTAL']->getUser();
		if ($user->checkPermission('admin', $testWrapper->testBlock)) {
			$autotest = 0;
			$trid = null;
			if(isset($_SESSION["RUNNING_TESTS"][$id]["testRunId"]))
				$trid = $_SESSION["RUNNING_TESTS"][$id]["testRunId"];
			if(isset($_COOKIE["autotest$trid"]))
			$autotest = $_COOKIE["autotest$trid"];
			
			
			if($autotest == $trid)
			$body .= "<div style='position:fixed;bottom:10px;right:10px'>Autotest? <input type='checkbox' id='autotest' checked='checked' onclick='checkCookie(\"autotest$trid\",\"\");doAllandSubmit()'></div>";
			else
			$body .= "<div style='position:fixed;bottom:10px;right:10px'>Autotest? <input type='checkbox' id='autotest' onclick='checkCookie(\"autotest$trid\",\"$trid\");doAllandSubmit()'></div>";
		}
						
		$this->tpl->setGlobalVariable("body", $body);

		$blockId = $this->block->getId();

		foreach ($this->_returnIds($items) as $itemId)
		{
			if (empty($_SESSION["RUNNING_TESTS"][$id]["itemStartTime"][$blockId][$itemId])) {
				$_SESSION["RUNNING_TESTS"][$id]["itemStartTime"][$blockId][$itemId] = time();
			}
		}
				
		$this->tpl->show();
	}

	function doPassword()
	{
		$testId = get('test_id');
		$accessType = get('source');
		
		$this->tpl->loadTemplateFile("TestMakePassword.html");
		$this->tpl->setVariable("test_link", linkTo("test_make", array("action" => "start_test", "test_id" => $testId, "test_path" => "_0_{$testId}_", "source" => $accessType)));
		$body = $this->tpl->get();
		parent::loadDocumentFrame();
		$this->tpl->setVariable('page_title', T('pages.testmake.password_required'));
		$this->tpl->setVariable('body', $body);
		$this->tpl->show();
	}
	
	function _returnIds($list)
	{
		$ids = array();

		foreach ($list as $element)
		{
			$ids[] = $element->getId();
		}

		return $ids;
	}
}
