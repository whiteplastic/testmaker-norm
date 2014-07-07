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
 * Load the BlockPage class
 */
require_once(PORTAL.'BlockPage.php');

/**
 * Load the FeedbackGenerator class
 */
require_once(CORE.'types/FeedbackGenerator.php');

require_once(CORE.'types/TestRunList.php');

require_once(CORE.'types/TestSelector.php');

/**
 * Lists tests this user may start, including progress information.
 *
 * Default action: {@link doDefault()}
 *
 * @package Portal
 */
class TestListingPage extends BlockPage
{
	function getRelevantTestRuns($testId, $testRunId, $userId)
	{
		$testRunList = new TestRunList();
		$testRuns = array();
		$test = new ContainerBlock($testId);
		$perm = $GLOBALS['PORTAL']->getUser()->checkPermission(array('direct', 'portal', 'tan'), $test);

		if ($testRunId === NULL) {
			$testRuns = $testRunList->getTestRunsForUserAndAccessTypes($userId, array("direct", "portal", "tan"), $testId);
			// Reduce to most complete test run
			while (count($testRuns) > 1) {
				$ir0 = $testRuns[0]->getAnsweredRequiredItemsRatio();
				if ($ir0 == 1) break;
				if ($ir0 < $testRuns[1]->getAnsweredRequiredItemsRatio() || ($ir0 != 1 && !$perm)) {
					array_shift($testRuns);
				} else {
					array_splice($testRuns, 1, 1);
				}
			}
		} elseif ($testRunId > 0) {
			$tr = new TestRun($testRunList, $testRunId);
			if ($tr->getUserId() == $userId && $tr->getTestId() == $testId) $testRuns[] = $tr;
		}
		// Thus negative testRunIds mean no testrun is returned

		return $testRuns;
		return array();
	}

	function displayList($testParentId, $testRunId = NULL, $subTestView = false)
	{
		
		// Proof whether the a TAN is saved in the session and whether it's related to the current test
		if(isset($_SESSION['accessType']) && $_SESSION['accessType'] == 'tan' && isset($_SESSION['tan']))
		{
			require_once(CORE."types/TANCollection.php");
			$tanBlockId = TANCollection::getBlockIdByTAN($_SESSION['tan']);
			if($testParentId != $tanBlockId) 
			{
				unset($_SESSION['accessType']);
				unset($_SESSION['tan']);
			}
		}
		else 
		{
			//if direct test-link present: start test
			if(isset($_SESSION['direct_test']) && $GLOBALS['PORTAL']->getUserId()) 
			{	
				$test_path = $_SESSION['direct_test'];
				unset($_SESSION['direct_test']);
				redirectTo('test_make', array('action' => 'start_test', 'test-path' => $test_path)); 
				return;			
			}
			
		}
		// Get access type from session or assume "portal" otherwise
		$accessType = isset($_SESSION['accessType']) ? $_SESSION['accessType'] : "portal";
		$userId = $GLOBALS['PORTAL']->getUserId();
		$parentBlock = $GLOBALS['BLOCK_LIST']->getBlockById($testParentId);
		$parentInSubtests = $parentBlock->getShowSubtests();
		$subTest = $subTestView && $parentInSubtests;
		// Do not allow guest access (with password or not) to test in subtest mode
		if($subTest && !$userId && !$accessType == 'tan')
		{
			$GLOBALS['MSG_HANDLER']->addMsg('pages.testmake.subtest_not_available', MSG_RESULT_NEG);
			redirectTo("test_listing");
		}
		$tests = $GLOBALS["BLOCK_LIST"]->getTestList($testParentId);

		$this->tpl->loadTemplateFile("TestListing.html");

		if($subTest) {
			$this->tpl->setVariable('parent_title', $parentBlock->getTitle());
			$this->tpl->touchBlock('sub_info');
		}

		$testCount = 0;
		$testCompleteCount = 0;
		$countAllowed = 0;
		
		foreach ($tests AS $test) {
			$block = $test->getBlock();
			$blockInSubtests = $block->getShowSubtests();
			$userId = $GLOBALS['PORTAL']->getUserId();
			$accessAllowed = $block->isAccessAllowed('portal') || $block->isAccessAllowed('review') || $subTest;
			if($accessAllowed && !($parentInSubtests && $block->isInactive())) $countAllowed++;
			elseif ((!$accessAllowed && !$block->isAccessAllowed('portal', 'password')) || ($parentInSubtests && $block->isInactive())) continue;
			elseif (! $accessAllowed && $block->getPassword() != "" && ($block->isAccessAllowed('portal', 'password') || $block->isAccessAllowed('run', 'password')) && !$testParentId) 
			{
				$this->tpl->touchBlock("password_required");
				$countAllowed++;
			}
			$userId = $GLOBALS['PORTAL']->getUserId();
			if ($userId || $accessType == 'tan') {
				// Check test completion
				$trBlockId = ($parentInSubtests ? $parentBlock->getId() : $block->getId());
				$testRuns = $this->getRelevantTestRuns($trBlockId, $testRunId, $userId);

				if ($parentInSubtests && count($testRuns) > 0) {
					$tr = $testRuns[0];
					$trBlock = $tr->getTestRunBlockBySubtest($block->getId());
					if ($trBlock->getAvailableItems() === NULL) {
						$testRuns = array();
					} else {
						$testRuns = array($trBlock);
					}
				}

				if (count($testRuns) > 0 && $testRuns[0]->getAnsweredRequiredItemsRatio() == 1) {
					$testCompleteCount++;
				}
				if(count($testRuns) == 0 && !$block->isAccessAllowed('portal') && !$block->isAccessAllowed('review') && !$block->isAccessAllowed('portal', 'password') && !$subTest) continue;
	
				// For display purposes, we really want the same progress as displayed on the test_view page
				$perc = (count($testRuns) > 0) ? $testRuns[0]->getProgress() : 0;
				if ($perc > 0) {
				$this->tpl->setVariable('progress', $perc);
				$this->tpl->parse('test_status');
				}

				if ($testParentId) {
					$testPath = "_0_".$testParentId."_".$test->getId()."_";
				} else {
					$testPath = "_0_".$test->getId()."_";
				}

				if($blockInSubtests) $this->tpl->setVariable('subs', '<span class="testoverview">'. T('pages.test_listing.subs') .'</span>');
				if ($parentInSubtests)
				{
					$testRunId = getpost('test_run', -1);
					if ($perc<100) {
						$this->tpl->hideBlock("NoLink");
						$this->tpl->setVariable("testlink", linkTo("test_make", array("action" => ($testRunId > 0 ? "continue_test" : "start_test"), "test_run" => $testRunId, "source" => "portal", "test_path" => "_0_".$parentBlock->getId()."_".$block->getId()."_")));
					}
					else {
						$this->tpl->hideBlock("Link");
						$this->tpl->touchBlock("NoLink");
						
					}
					
				} else {
					if ($perc > 0)
						$this->tpl->setVariable("testlink", linkTo("test_listing", array("action" => "test_view", "test_id" => $test->getId(), "test_path" => $testPath)));
					else
						$this->tpl->setVariable("testlink", linkTo("test_make", array("action" => "start_test", "id" => $test->getId(), "source" => "portal", "test_path" => "_0_".$test->getId()."_")));
				}
			} else {
				$this->tpl->setVariable("testlink", linkTo("test_make", array("action" => "start_test", "id" => $test->getId(), "source" => "portal", "test_path" => "_0_".$test->getId()."_")));
			}
			
			$testCount++;
			$this->tpl->setVariable("id", $test->getId());
			$this->tpl->setVariable("testtitle", $test->getTitle());
			$testDescription = $test->getDescription();
			
			//Removes the Added <p> and </p> from FCKEditor
			$i = strpos($testDescription, "<p>");
			if ($i === 0) {
				$testDescription = substr_replace($testDescription, '', 0, 3);
			}
			$j = strrpos($testDescription, "</p>");
			if ($j == strlen($testDescription) - 4) {
			$testDescription = substr_replace($testDescription, '', -4, 4);
			}

			if($testDescription) $this->tpl->setVariable("testdescription",$testDescription);
			else $this->tpl->touchBlock("test_no_description");
			
			$fileHandling = new Filehandling();
			$list = $fileHandling->listMedia($test->getLogo());
			if (count($list) && $test->getLogoShow() != 1) {
				$this->tpl->setVariable("testlogo", "upload/media/".$list[0]->getFilePath()."/".$list[0]->getFileName());
			}
			else
				$this->tpl->setVariable("testlogo", "portal/images/testlogo.jpg");

			// Needs Tons of Time, remove it and only show in on the Tests? 
			// Display notice if not really published 
			/*if (!$block->isAccessAllowed('portal', false) && !$testParentId) {
				$this->tpl->setVariable("publication", '<span class="testoverview">'. T('pages.test_listing.unpublished') .'</span>');
			}*/
			$this->tpl->parse("test");
			
		}
		
		if($countAllowed == 0) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.test.notavailable', MSG_RESULT_NEUTRAL);
			return $this->finishUp($parentBlock);
		}
		
		if ($testParentId && $testCompleteCount == $testCount && $testCount > 0 && $testCompleteCount > 0) {
			// Show feedback information
			$this->tpl->setVariable('parent_id', $testParentId);
			$this->tpl->setVariable('test_run', $testRunId);
			$this->tpl->touchBlock('feedback_info');
		}
		$this->finishUp($parentBlock);
	}

	function finishUp($parentTest)
	{
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		if ($parentTest->getId()) {
			$this->tpl->setVariable('page_title', $parentTest->getTitle());
		} else {
			$this->tpl->setVariable("page_title", T('pages.test.listing'));
		}
		$this->tpl->show();
	}

	function doDefault()
	{

		//check privacy policy (not necessary if test accessed via tan or direct-link
		if(isset($_SESSION['accessType']) && (($_SESSION['accessType'] == 'tan' && isset($_SESSION['tan'])) || ($_SESSION['accessType'] == 'direct')))
		{
			$this->displayList(0);
		} else {
			
			// check if a privacy policy exists and user has accepted the latest version
			$current_pp = PrivacyPolicy::getCurrentVersion();
			
			if(isset($_SESSION['userId']) && ($_SESSION['userId'] != 0)) {
				$userID = $_SESSION['userId'];
				require_once(CORE.'types/UserList.php');
				$userList = new UserList();
				$user = $userList->getUserById($userID);				
			
				if(($current_pp != 0) && ($current_pp > $user->getPrivacyPolicyAcc())) {
					redirectTo('show_privacy_policy', array('resume_messages' => 'true'));			
				} else {	
					$this->displayList(0);
				}
			} else {	
					$this->displayList(0);
			}
		}
	}

	function doSubtestView()
	{
		$testParentId = get('test_id', get('test-id', 0));
		$testRunId = get('test_run', get('test-run', -1));
		$this->displayList($testParentId, $testRunId, true);
	}

	function doFinish()
	{
		$this->tpl->loadTemplateFile('TestFinish.html');
		if ($GLOBALS['PORTAL']->getUserId() != 0) {
			$this->tpl->touchBlock('logged_in');
			
		} else {
			$this->tpl->touchBlock('guest');
		}
		
		if(!isset($_SESSION['testRunId'])) $this->doDefault();
		else {
			
			list($test, $passwordRequired) = $GLOBALS["BLOCK_LIST"]->checkTestId($_SESSION['testId'], NULL);

			$selector = new TestSelector($test);
			$pages = $selector->getChildrenOfSpecificBlocks(BLOCK_TYPE_FEEDBACK);
			
			$testRunList = new TestRunList();
			$testRun = $testRunList->getTestRunById($_SESSION['testRunId']);
			if($testRun) $userId = $testRun->getUserId();
			require_once(CORE.'types/UserList.php');
			$userList = new UserList();
			if ($userId) {
				$user = $userList->getUserById($userId);
				$email = $user->get('email');
			}
			else
				$email = NULL;
			
			$tan = isset($_SESSION['tan']) ? $_SESSION['tan'] : NULL;
			if (($pages) && ($tan == NULL) ) {
				if ($email)
					$this->tpl->touchBlock('send_feedback_email');
				else {
					$this->tpl->hideBlock('send_feedback_email');
					$this->tpl->hideBlock('print_feedback');
				}
					
				$this->tpl->setVariable("test_run_id", $_SESSION['testRunId']);
			}
			
			if (($tan) && ($pages)){
					require_once(CORE.'types/TANCollection.php');
					$tanCollection = new TANCollection($_SESSION['testId']);
					$email = $tanCollection->getEmailByTestRun($_SESSION['testRunId']);
					if ($email) {
						$this->tpl->touchBlock('send_feedback_email');
						$this->tpl->setVariable("test_run_id", $_SESSION['testRunId']);
					}
					else {
						$this->tpl->hideBlock('send_feedback_email');
						$this->tpl->touchBlock('print_feedback');
						$this->tpl->setVariable("test_run_id", $_SESSION['testRunId']);
					}
				
			}
			
			$body = $this->tpl->get();

			$this->loadDocumentFrame();
			$this->tpl->setVariable("body", $body);
			$this->tpl->setVariable("page_title", T('pages.test.finished'));
			$this->tpl->show();
		}
	}

	/**
	 * @access private
	 */
	function &_initTest()
	{
		$testId = get('test_id', NULL);
		if (!$testId) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.test.missing', MSG_RESULT_NEG);
			$this->loadDocumentFrame();
			$this->tpl->setVariable('page_title', 'Error');
			$this->tpl->show();
			exit;
		}
		$test = new Test($testId);
		$descr = $test->getDescription();
		$this->tpl->setVariable('description', $descr ? $descr : T('pages.test_listing.no_description'));
		$this->tpl->setGlobalVariable('test_id', $testId);
		if (get("test_path")) {
			$this->tpl->setVariable("test_path", get("test_path"));
		}
		else {
			$this->tpl->setVariable("test_path", "_0_".$testId."_");
		}
		return $test;
	}

	function doTestView()
	{
		$body = "";
		$this->tpl->loadTemplateFile("TestMake.html");
		$userId = $GLOBALS["PORTAL"]->getUserId();

		// ... not for guests
		if (!$userId) {
			$this->checkAllowed('foo', true);
		}
		$user = $GLOBALS['PORTAL']->getUser();
		$test = &$this->_initTest();
		$block = $test->getBlock();

		$this->tpl->touchBlock("plugin_test");

		

		if (!$user->checkPermission('admin')) {
			$this->tpl->hideBlock('admin_info_header');
			$this->tpl->hideBlock('admin_info');
		} else {
			$this->tpl->touchBlock('admin_info_header');
			$this->tpl->touchBlock('admin_info');
		}	
		
		if (!$user->checkPermission(array('direct', 'tan', 'portal'), $block) && $user->checkPermission('review', $block)) {
			$this->tpl->hideBlock('is_subbed');
			$this->tpl->hideBlock('regular_test');
			$this->tpl->hideBlock('participate_allowed');
			$this->tpl->touchBlock('participate_forbidden');
		} else {
			if ($block->getShowSubtests()) {
				$this->tpl->touchBlock('is_subbed');
				$this->tpl->hideBlock('regular_test');
			} else {
				$this->tpl->hideBlock('is_subbed');
				$this->tpl->touchBlock('regular_test');
			}
			$this->tpl->touchBlock('participate_allowed');
			$this->tpl->hideBlock('participate_forbidden');
		}

		if (isset($userId)) {
			$testRunList = new TestRunList();
			$testRunIds = $testRunList->getTestRunIdsForUserAndAccessTypes($userId, array("direct", "portal", "tan"), $test->getId());
		}
		
		// check if certificate is enabled for this test
		require_once(CORE."types/TestSelector.php");
		$selector = new TestSelector($block);
		$pages = $selector->getChildrenOfSpecificBlocks(BLOCK_TYPE_FEEDBACK);
		$blocksF = $selector->getBlocksOfType(BLOCK_TYPE_FEEDBACK);
		
		$isCertificate = false;		
		foreach($blocksF as $blockF) {
			if($blockF->isCertEnabled()) {
				$isCertificate = true;
				$certBlock = $blockF;
			}
		}

		foreach ($pages as $page)
		{
			if ($page->getDisabled())
				continue;
			$parentBlock = $page->getParent();
			if($parentBlock->isCertEnabled()) {
				$isCertificate = true;
			}
		}

		$hasCompletedOneID = NULL;
		
		if (! $testRunIds) {
			$this->tpl->touchBlock("no_runs");
			$this->tpl->hideBlock("has_runs");
		}
		else
		{
			$this->tpl->touchBlock("has_runs");
			
			foreach ($testRunIds as $testRunId)
			{
				// only admins will see the test run id
				if (!$user->checkPermission('admin')) {
					$this->tpl->hideBlock('admin_info_header');
					$this->tpl->hideBlock('admin_info');
				} else {
					$this->tpl->touchBlock('admin_info_header');
					$this->tpl->touchBlock('admin_info');
				
				}	
				//beta might need to be there
				//unstore_all();
				$testRun = $testRunList->getTestRunById($testRunId);
				$progress = $testRun->getProgress();
				if ($block->isAccessAllowed('review') && !$user->checkPermission(array('portal', 'direct', 'tan'), $block) && $progress != 100) continue;
				$this->tpl->setVariable("progress", $progress);
				$this->tpl->setVariable("date", date(T("pages.core.date_time"), $testRun->getStartTime()));
				
				$this->tpl->setVariable("test_run_id", $testRunId);	//$testRun->getId());
			
				if ($progress == 100) {
					$this->tpl->hideBlock("continue_link");
					$this->tpl->hideBlock("continue_subbed_link");
					$this->tpl->touchBlock("details_link");
					$hasCompletedOneID = $testRunId;
				} else {
						$b1 = 'continue_link'; $b2 = 'continue_subbed_link';
						if ($block->getShowSubtests()) { $b1 = $b2; $b2 = 'continue_link'; }
						$this->tpl->touchBlock($b1);
						$this->tpl->hideBlock($b2);
						$this->tpl->hideBlock("details_link");
				}
				
				if(!$hasCompletedOneID){
					$db = &$GLOBALS['dao']->getConnection();
					if($db->getOne('SELECT `id` FROM '.DB_PREFIX.'certificates WHERE test_run_id = ?', $testRunId))
						$hasCompletedOneID = $testRunId;
				}
				$this->tpl->parse("run");
			}
		}

		//show certificate link
		if (($hasCompletedOneID || ($progress == 100)) && $isCertificate) 
		{
			$this->tpl->touchBlock("certificate");
			$this->tpl->setVariable('cert_test_run_id', $hasCompletedOneID);
			$this->tpl->setVariable('cert_block_id', $certBlock->getId());
		}
		else
			$this->tpl->hideBlock("certificate");
		
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", $test->getTitle());
		$this->tpl->show();
	}
	
	
	
	function doShowFeedback()
	{
		$testId = get("test_id", 0);
		$FeedBackEmail = get("email", 0);
		$final = get("final", 0);
		$testRunId = get("test_run", 0);
		require_once(CORE."types/TestRunList.php");
		$testRunList = new TestRunList();
		$userId = $GLOBALS['PORTAL']->getUserId();
		$db = $GLOBALS['dao']->getConnection();

		require_once(CORE.'types/UserList.php');
		$userList = new UserList();
		$user = $userList->getUserById($userId);
		
		if ($testId) {
			if (! list($test, $passwordRequired) = $GLOBALS["BLOCK_LIST"]->checkTestId($testId, NULL)) redirectTo("test_listing", array("resume_messages" => "true"));

			if ($test->getShowSubtests())
			{	
				$testRun = $testRunList->getTestRunById($testRunId);
				$children = $test->getChildren();
				foreach ($children as $child) {
					if (!$child->isContainerBlock()) continue;
					$testRunBlocks = $testRun->getTestRunBlockBySubtest($child->getId());
					if(!$testRunBlocks && $testRunBlocks[0]->getAnsweredRequiredItemsRatio() != 1)
					{
						$GLOBALS['MSG_HANDLER']->addMsg('pages.test.feedback.incomplete', MSG_RESULT_NEG);
						redirectTo('test_listing', array('parent_id' => $testId, 'resume_messages' => 'true'));
					}
				}
			}
		}

		if ($testRunId) {
			$testRun = $testRunList->getTestRunById($testRunId);

			if($testRun !== NULL) 
			{
				$verified = $testRun->verifyTestRun();
				if(!$verified) redirectTo("test_listing");
			} else
			{
				redirectTo("test_listing");
			}
	
			if (! list($test, $passwordRequired) = $GLOBALS["BLOCK_LIST"]->checkTestId($testRun->getTestId(), NULL)) 
				redirectTo("test_listing", array("resume_messages" => "true"));
				
			$testId = $test->getId();

			// Make sure the test run is complete
			if (($testRun->getProgress() < 100) && (!$FeedBackEmail == 3)) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.test.feedback.incomplete', MSG_RESULT_NEG);
				redirectTo('test_listing', array('action' => 'test_view', 'test_id' => $test->getId(), 'resume_messages' => 'true'));
			}
		}
		
		require_once(CORE."types/TestSelector.php");
		$selector = new TestSelector($test);
		$pages = $selector->getChildrenOfSpecificBlocks(BLOCK_TYPE_FEEDBACK);

		$this->tpl->loadTemplateFile("TestFeedback.html");
		$this->tpl->setVariable("test_id", $test->getId());
		if (is_array($testRun)) {
			// Output for sub-tests
			$this->tpl->setVariable('test_link', linkTo('test_listing', array('parent_id' => $testId), true));
			$trInfo = &$testRun[0];
		} else {
			// Output for regular test
			$this->tpl->setVariable('test_link', linkTo('test_listing', array('action' => 'test_view', 'test_id' => $testId), true));
			$trInfo = &$testRun;
		}
		$this->tpl->setVariable("test_run_date", date(T("pages.core.date"), $trInfo->getStartTime()));
		$this->tpl->setVariable("test_run_time", date(T("pages.core.time"), $trInfo->getStartTime()));
		
		if ($testRun->getAccessType() == "tan")
		{
			$this->tpl->touchBlock("test_per_tan");
			$this->tpl->hideBlock("test_normal");
				
			if (! $pages) {
				$this->tpl->touchBlock("no_pages_tan");
				$this->tpl->hideBlock("print_link1");
				//$this->tpl->hideBlock("email");
			}
		} else
		{
			$this->tpl->touchBlock("test_normal");
			$this->tpl->hideBlock("test_per_tan");
			
			if (! $pages) {
				$this->tpl->touchBlock("no_pages_normal");
				$this->tpl->hideBlock("print_link1");
				//$this->tpl->hideBlock("email");
			}
		}

		$certificate = false;
		
		$blocksF = $selector->getBlocksOfType(BLOCK_TYPE_FEEDBACK);
		foreach($blocksF as $blockF) {
			if($blockF->isCertEnabled()) {
				$certificate = true;
				$certBlock = $blockF;
			}
		}

		foreach ($pages as $page)
		{
			if ($page->getDisabled())
				continue;
			$fblock = $page->getParent();
			if (!$fblock->getShowInSummary()) continue;

			$feedback = new FeedbackGenerator($page->getParent(), $testRun);
			$feedback->setPage($page);
			$paragraphs = $feedback->getParagraphs();

			$parentBlock = $page->getParent();
			if($parentBlock->isCertEnabled()) {
				$certificate = true;
				$certBlock = $parentBlock;
			}

			foreach ($paragraphs as $para)
			{
				$text = $para->getContents();
				//Dont't show the email button in the testpage
				if ($final == 1 || $FeedBackEmail == 1 || $FeedBackEmail == 2) {
					$text = str_replace('{feedback_mail:}', '', $text);					
				}
				$text = $feedback->expandText($text);
				$this->tpl->setVariable("paragraph", $text);
				$this->tpl->parse("paragraph");
			}

			$this->tpl->parse("page");
		}
		$tan = isset($_SESSION['tan']) ? $_SESSION['tan'] : NULL;
		
		$form_filled = NULL;
		// if tan check if user have already get the certificate
		if ($tan) {
			$query = "SELECT form_filled FROM ".DB_PREFIX."tans WHERE access_key = ?";
			$result = $db->query($query, array($tan));
			$result = $result->fetchRow();

		}
		else {
		  $result['form_filled'] = NULL;
		  $form_filled = $user->get('form_filled');
		}

		if($certificate) {
			
			if (( $tan && $result['form_filled'] == NULL) || ($user != FALSE) || isset($_SESSION["cert_for_$testRunId"])) {
				$this->tpl->setVariable('test_run_id', $testRunId);
				$this->tpl->setVariable('block_id', $certBlock->getId());
				$this->tpl->touchBlock('certificate');
			}
			else {
				$this->tpl->touchBlock('no_certificate');
			}
		}

		if($GLOBALS['PORTAL']->getUserId() == 0 || !$test->isAccessAllowed('portal')) {
			$this->tpl->touchBlock('anonymous_link1');
			//$this->tpl->touchBlock('anonymous_link2');
			$this->tpl->hideBlock('user_link1');
			//$this->tpl->hideBlock('user_link2');
		} else {
			$this->tpl->touchBlock('user_link1');
			//$this->tpl->touchBlock('user_link2');
			$this->tpl->hideBlock('anonymous_link1');
			//$this->tpl->hideBlock('anonymous_link2');
		}
		$this->tpl->setVariable("test_run_id", $testRunId);
		if ($pages)  {
			$this->tpl->touchBlock('print_link1');
			$this->tpl->touchBlock('print_link2');
		}
		
		//if no email in tan test is entered, hide email button
		require_once(CORE.'types/TANCollection.php');
		$tanCollection = new TANCollection($testRun->getTestId());
		$email = $tanCollection->getEmailByTestRun($testRun->getId());
		if (($email == NULL) && ($testRun->getAccessType() == "tan"))
				$this->tpl->hideBlock("print_link2");
				
		$body = $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("page_title", T("pages.test_listing.feedback.title", array("title" => $test->getTitle())));
		$this->tpl->setVariable("body", $body);

		if ((getpost('layout') == 'print')) {
			$this->tpl->setVariable('css_file', 'portal/css/main_print.css');
			$this->tpl->parse('css_file');
		}
		
		//Make feedback email and send it.
		if (isset($_POST['feedback_email']) || $FeedBackEmail == 1 || $FeedBackEmail == 2) {

			$this->tpl->loadTemplateFile("FeedbackEmail.html");
			require_once('lib/email/Composer.php');
			$mail = new EmailComposer();
			
			$email = "";
			$tan = isset($_SESSION['tan']) ? $_SESSION['tan'] : NULL;
			if($tan)
			{
				require_once(CORE.'types/TANCollection.php');
				$tanCollection = new TANCollection($testRun->getTestId());
				$email = $tanCollection->getEmailByTestRun($testRun->getId());
			} 
			else {
				$email = $user->get('email');
			}
			
			$imageCounter = 0;	
			
			foreach ($pages as $page)
			{
				if ($page->getDisabled())
					continue;
					
				$fblock = $page->getParent();
				if (!$fblock->getShowInSummary()) 
					continue;

				$feedback = new FeedbackGenerator($page->getParent(), $testRun);
				$feedback->setPage($page);
				$paragraphs = $feedback->getParagraphs();
					
				foreach ($paragraphs as $para) {
					// Handle feedback graphs
					$text = $para->getContents();
				
					$text = str_replace('{feedback_mail:}', '', $text);
				
					$mixed = $feedback->expandText($text, array(NULL, '_getOutput'), array('request_binary' => true));

					if(is_array($mixed))
					{
						$text = $mixed[0];
						
						foreach($mixed[1] as $image)
						{
							$contentID = $mail->addHtmlAttachmentFromMemory($image, "image/png", 'img'.$imageCounter.'.png');
							$text = preg_replace("/\[\[graph\]\]/", '<img src="cid:'.$contentID.'">', $text, 1);
							unset($contentID);
							$imageCounter++;


						}
					} else 
						$text = $mixed;									
					$this->tpl->setVariable("para", $text);
					$this->tpl->parse("paragraphs");
				}
			}
				
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
			
			if ($FeedBackEmail == 1 || $FeedBackEmail == 2 || $final == 1) {
				redirectTo("test_listing", array("action" => "mail_sent", "MSG" => "feedback.mail.send"));
			}
			else
				redirectTo("test_listing", array("action" => "finish", "MSG" => "feedback.mail.send"));
			
		}
		$this->tpl->show();
	}
	
	function doMailSent() {
		$this->tpl->loadTemplateFile("EmailSent.html");
		$this->tpl->touchBlock("msg");
		$this->tpl->show();
	}
}

?>
