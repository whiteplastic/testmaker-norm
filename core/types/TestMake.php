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
 * @package Core
 */

require_once(CORE.'types/Test.php');

/**
 *
 */

class TestMake
{
	private $testSession = NULL;
	private $testRun = NULL;

	public function getTestRun()
	{
		return $this->testRun;
	}

	/**
	 * Starts test:
	 * First it checks whether a given TAN is valid, if the test can be started via TAN, or checks
	 * the password, if the test is protected by a password.
	 */
	public function startTest($accessType = "portal", $password = NULL, $subTestId = 0, $tan = NULL, $testId = NULL, $testPath = false)
	{
		$testRun = NULL;
		$mimeTypes = explode(",", preg_replace("/;[^,]*/", "", post("mime_types", "")));
		// check if tan is given & if given tan is valid
		$block = NULL;
		$testRunId = 0;
		if($tan != NULL )
		{
			require_once(CORE."types/TANCollection.php");
			if(!TANCollection::existsTAN($tan))
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.wrong_tan", MSG_RESULT_NEG);
				redirectTo("tan_login", array("action" => "register_use_tan", "resume_messages" => "true"));
			}
			$block = TANCollection::getBlockByTAN($tan);
			$testId = $block->getId();
			$testPath = TANCollection::getTestPathByTAN($tan);
			$accessType = "tan";
			$testRunId = TANCollection::getTestRun($tan);
		} else {
			if(!$testId || !$GLOBALS['BLOCK_LIST']->existsBlock($testId) || $GLOBALS['BLOCK_LIST']->getBlockType($testId) != BLOCK_TYPE_CONTAINER)
			{
				$GLOBALS['MSG_HANDLER']->addMsg("pages.id_out_of_place", MSG_RESULT_NEG);
				redirectTo("start");
			}
			$block = $GLOBALS['BLOCK_LIST']->getBlockById($testId);
		}
		if(!isset($_SESSION['accessType'])) $_SESSION['accessType'] = $accessType;

		if ($block->getOpenDate() > NOW)
		{
			$GLOBALS['MSG_HANDLER']->addMsg("pages.testmake.wrong_opendate", MSG_RESULT_NEG, array('date' => date(T('pages.core.date_time'), $block->getOpenDate())));
			redirectTo("test_listing", array("action" => "default", "resume_messages" => "true"));
		}
		if ($block->getCloseDate() && $block->getCloseDate() < NOW)
		{
			$GLOBALS['MSG_HANDLER']->addMsg("pages.testmake.wrong_closedate", MSG_RESULT_NEG);
			redirectTo("test_listing", array("action" => "default", "resume_messages" => "true"));
		}

		// Check whether a special third party plugin is necessary
		$testStructure = TestStructure::getStructure($testId);
		$required = 0;
		$requiredPlugins = array();
		foreach($testStructure as $item)
		{
			if($item['parent_type'] == "ItemBlock")
			{
				$itemObj = Item::getItem($item['id']);
				$tmpPlugins = $itemObj->getPluginRequirements();
				if(count($tmpPlugins) > 0)
				{
					foreach($tmpPlugins as $pluginName)
					{
						if(!in_array($pluginName, $mimeTypes, true))
						{
							$required = 1;
							$requiredPlugins[$pluginName] = 1;
						}
					}
				}
			}
		}
		if($required)
		{
			// Give info about the required plugins for this test; required plugins have to be mentioned in core/types/MimeTypes.php
			$msg = T("pages.testmake.plugins_required");
			$numberRequiredPlugins = count($requiredPlugins);
			$counter = 0;
			foreach($requiredPlugins as $mime => $value)
			{
				if(array_key_exists($mime, MimeTypes::$knownTypes))
				{
					$pluginName = MimeTypes::$knownTypes[$mime]["name"];
					$pluginLinkBegin = "<a href=\"".MimeTypes::$knownTypes[$mime]["url"]."\" target=\"_blank\" >";
					$pluginLinkEnd = "</a>";
				}
				else
				{
					$pluginName = $mime;
					$pluginLinkBegin = "";
					$pluginLinkEnd = "";
				}
				$msg .= $pluginLinkBegin."<embed type=\"$mime\" style=\"display:none\" width=\"1\" height=\"1\">$pluginName</embed>".$pluginLinkEnd;
				if($counter+1 != $numberRequiredPlugins) $msg .= ", ";
				$counter++;
			}
			$GLOBALS["MSG_HANDLER"]->addFinishedMsg($msg, MSG_RESULT_NEG);
			redirectTo("test_listing", array("action" => "test_view", "test_id" => $testId, ""));
		}

		// test_path is also checked for compatibility reasons
		if (! list($testBlock, $passwordRequired) = $GLOBALS["BLOCK_LIST"]->checkTestId($testId, $accessType)) redirectTo("test_listing", array());



		$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testId);

		if($passwordRequired)
		{
			$_SESSION['accessType'] = "password";
			if ($password === NULL) {
				redirectTo("test_make", array("action" => "password", "test_id" => $testId, "source" => $accessType));
			} elseif ($password != $test->getPassword()) {
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.wrong_password", MSG_RESULT_NEG);
				redirectTo("test_make", array("action" => "password", "test_id" => $testId, "source" => $accessType));
			}
		}

		require_once(CORE."types/Test.php");
		$test = new Test($testId);


		$userId = $GLOBALS["PORTAL"]->getUserId();
		$startTime = NOW;

		$_SESSION['languageOld'] = $_SESSION['language'];
		$_SESSION['language'] = $test->getLanguage();

		$availablePages = $test->getNumberPages();
		$availableItems = $test->getNumberItems();
		$availableRequiredItems = $test->getNumberRequiredItems();

		if($test->getShowSubtests() && $subTestId != 0)
		{
			$childBlock = $test->getSubtestBlock($subTestId);
			if($childBlock && $childBlock->isInactive())
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.subtest_deactivated", MSG_RESULT_NEG);
				redirectTo("test_listing", array("action" => "subtest_view", "test_run" => $testRunId, "test_id" => $testId));
			}
		}

		if($testRunId == 0)
		{
			require_once(CORE."types/TestRunList.php");
			$testRunList = new TestRunList();
			$testRun = &$testRunList->addTestRun($testId, $testPath, $userId, $startTime, $accessType, $availablePages, $availableItems, $availableRequiredItems);
			
			//anonymize client ip-addresses
			$remote_addr = explode(".", server("REMOTE_ADDR"));
			$client_ip = $remote_addr[0] . "." . $remote_addr[1] . ".x.x";
					
			$testRun->setClient($client_ip, server("REMOTE_HOST"), server("HTTP_USER_AGENT"));
			if ($accessType == "direct") {
				$testRun->setReferer(server("HTTP_REFERER"));
			}
				
			//store test run id if tan is given
			if($tan != NULL)
			{
				$block = TANCollection::getBlockByTAN($tan);
				$tanCollection = new TANCollection($block->getId());
				$tanCollection->setTestRun($tan, $testRun->getId());
			}

			//Stores the Groups, that the user is member of and that have the permission to do the test
			$testRun->setGroupIds();
		}


		// Redirect to test listing when test ist started with TAN and test is in subtest mode
		if($tan != NULL && $test->getShowSubtests() && $subTestId == 0)
		{
			redirectTo('test_listing', array('action' => 'subtest_view', 'test_run' => $testRun->getId(), 'test_id' => $testId));
		}

		// Subtest mode
		if ($test->getShowSubtests()) {
			if (!$test->existsChild($subTestId)) {
				$GLOBALS['MSG_HANDLER']->addMsg("pages.id_out_of_place", MSG_RESULT_NEG);
				redirectTo("start");
			}
		}

		$testRun->setDisplayingBlockId($subTestId);
		$trBlock = $testRun->getTestRunBlockBySubtest($subTestId);
		if (!$trBlock->isEmpty()) {
			$GLOBALS['MSG_HANDLER']->addMsg("pages.id_out_of_place", MSG_RESULT_NEG);
			redirectTo("start");
		}

		if ($subTestId != 0) {
			$trBlock->setAvailableItems($test->getNumberItemsSubtest($subTestId));
			$trBlock->setAvailableRequiredItems($test->getNumberRequiredItemsSubtest($subTestId));
			$trBlock->create();
		}
		else {
			$trBlock->setAvailableItems($availableItems);
			$trBlock->setAvailableRequiredItems($availableRequiredItems);
			$trBlock->create();
		}

		if ($accessType == 'preview') {
			$redirect = array("container_block", array("action" => "preview", "working_path" => $testPath, "reset_lang" => 1));
			$id = $testRun->prepare(array("redirectOnFinish" => $redirect, "showAbortLink" => TRUE));
		} else $id = $testRun->prepare(array("password" => $password));

		return $id;
	}

	/**
	 * Continue a test based on a certain test run
	 *
	 * @param subTestId ID of the current subtest we are executing; 0 if root test;
	 * @param testRunId ID of the test run to be continued
	 * @return ID of the test run
	 */
	public function continueTest($subTestId = 0, $testRunId = NULL)
	{
		$accessType = get("source") == "portal" ? "portal" : "direct";
		require_once(CORE."types/TestRunList.php");
		$testRunList = new TestRunList();
		$testRun = $testRunList->getTestRunById($testRunId);
		if($testRun !== NULL)
		{
			$verified = $testRun->verifyTestRun();
			if(!$verified) redirectTo("test_listing");
		} else
		{
			redirectTo("test_listing");
		}

		$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testRun->getTestId());

		$_SESSION['languageOld'] = $_SESSION['language'];
		$_SESSION['language'] = $test->getLanguage();
		if(!isset($_SESSION['accessType'])) $_SESSION['accessType'] = $accessType;

		$testBlock = $GLOBALS['BLOCK_LIST']->getBlockById($testRun->getTestId());
		if ($testBlock->getShowSubtests()) {
			if (!$testBlock->existsChild($subTestId)) {
				$GLOBALS['MSG_HANDLER']->addMsg("pages.id_out_of_place", MSG_RESULT_NEG);
				redirectTo("start");
			}
			$testRun->setDisplayingBlockId($subTestId);

			require_once(CORE."types/TestSelector.php");
			$block = $GLOBALS['BLOCK_LIST']->getBlockById($subTestId);
			$trBlock = $testRun->getTestRunBlockBySubtest($subTestId);
			if ($trBlock->isEmpty()) {
				$selector = new TestSelector($block);

				$trBlock->setAvailableItems($selector->countItems());
				$trBlock->setAvailableRequiredItems($selector->countRequiredItems());
				$trBlock->create();
			}
		}

		if($testBlock->getShowSubtests()) $redirect = array("test_listing", array("action" => "subtest_view", "test_id" => $testRun->getTestId(), "test_run" => $testRun->getId()));
		else $redirect = array("test_listing", array("action" => "test_view", "test_id" => $testRun->getTestId()));
		$id = $testRun->prepare(array("redirectOnFinish" => $redirect));

		return $id;
	}

	/**
	 * Initialize the session such that we can save the test in execution in it
	 */
	private function initSession()
	{
		$GLOBALS["PORTAL"]->startSession();

		if (! isset($_SESSION["RUNNING_TESTS"])) {
			$_SESSION["RUNNING_TESTS"] = array();
		}
	}

	/**
	 * Initialize the running test in the session
	 */
	public function initRunningTest($id)
	{
		$this->initSession();

		if (! array_key_exists($id, $_SESSION["RUNNING_TESTS"])) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.invalid_id", MSG_RESULT_NEG);
			redirectTo("test_listing", array("resume_messages" => "true"));
		}

		$this->testSession = &$_SESSION["RUNNING_TESTS"][$id];

		if (@$this->testSession["isFinished"]) {
			$this->testRun = NULL;
		}
		else
		{
			require_once(CORE."types/TestRunList.php");
			$testRunList = new TestRunList();
			$this->testRun = &$testRunList->getTestRunById($_SESSION["RUNNING_TESTS"][$id]["testRunId"]);

			if (isset($this->testSession['displayingBlockId'])) $this->testRun->setDisplayingBlockId($this->testSession['displayingBlockId']);
		}
	}

	public function countShownItems($answers, $items) {
		$count = 0;
		foreach ($items as $i => $item){
			if (isset($answers[$item->getId()])) {
				$count++;
			}
		}
		if ($count == 0)
		$count = 1;
		return $count;
	}

	/**
	 * Redirect to a certain page when test is finished
	 */
	public function redirectOnFinish($items)
	{
		if(!$this->testRun) {
			$redirect = array("test_listing", array('action' => 'finish'));
			return $redirect;
		}
		
		$displayingBlockId = $this->testRun->getDisplayingBlockId();
		$testId = $this->testRun->getTestId();
		$subTest = ($displayingBlockId !=  $testId);
		if (! $redirect = @$this->testSession["redirectOnFinish"])
		{
			if($subTest)
			{
				$redirect = array('test_listing' , array('action' => 'subtest_view', 'test_run' => $this->testRun->getId(), 'test_id' => $testId));
			}
			else
			{
				$redirect = array("test_listing", array('action' => 'finish'));
				$_SESSION['testRunId'] = $this->testRun->getId();
				$_SESSION['testId'] = $testId;
			}
		}

		//if (! $items)
		$progress = 0;
		if($subTest)
		{
			$progress = floor($this->testRun->getCurrentTestRunBlock()->getProgress());
		}
		else
		{
			$progress = floor($this->testRun->getProgress());
		}
		if($progress == 100)
		{
			if (isset($_SESSION['languageOld']))
			$_SESSION['language'] = $_SESSION['languageOld'];

			if (empty($this->testSession["isFinished"]))
			{
				if(!$subTest) {
					$redirect = array("test_listing", array('action' => 'finish'));
					$_SESSION['testRunId'] = $this->testRun->getId();
					$_SESSION['testId'] = $testId;
				}
				$this->testSession = array(
					"redirectOnFinish" => $redirect,
					"isFinished" => TRUE,
				);
				if(!$GLOBALS["MSG_HANDLER"]->isMsgQueued("pages.testrun.no_support_for_old_unfinished_test_runs"))
				{
					$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.finish", MSG_RESULT_POS);
				}
				
				
			}
			if (array_key_exists("DEBUG", $_SESSION))
			{
				$_SESSION["DEBUG"] = NULL;
			}
			redirectTo($redirect[0], $redirect[1]);
		}

		return $redirect;
	}

	/**
	 * Processing of the answers given by the user
	 *
	 * @param answers Answers given by the users
	 * @param blockId Block that we focus on
	 * @param duration Client duration
	 * @param id ID under which the test run was saved in the session
	 * @param itemIds IDs of the items the user has answered
	 * @param timeout Timeout occured
	 */
	public function processAnswer($answers = array(), $blockId = NULL, $duration = 0, $id = NULL, $itemIds = array(), $timeout = false)
	{
		$endTime = time();
		// Ignore if the user hits ESC to ensure database integrity
		ignore_user_abort(TRUE);
		$testSession = &$_SESSION['RUNNING_TESTS'][$id];

		/* BEGIN: DEBUGGING INFO */
		$debug = NULL;
		if (array_key_exists("DEBUG", $_SESSION))
		{
			$debug = array();
			$debug["number_answers"] = count($answers);
			$debug["block_id"] = $blockId;
			$debug["duration"] = $duration;
			$debug["id"] = $id;
			$debug["item_ids"] = implode("_", array_keys($itemIds));
			$debug["test_session"] = $testSession;
			$debug["timeout"] = $timeout;
		}
		/* END */

		// Get the current item of the current test run
		$this->initRunningTest($id);
		if ($this->testRun === NULL) $items = NULL;
		// restore the current items from the session
		else if (isset($_SESSION['current_items']))
		{
			$items = array();
			foreach($_SESSION['current_items'] as $index => $itemData)
			{
				$type = $itemData['type'];
				if ($type != "FeedbackPage" && $type != "InfoPage") require_once(ROOT . "upload/items/" . $type . ".php");
				$items[] = new $itemData['type']($itemData['id']);
			}
		}
		else
			return NULL;

		if (@$items["special"]) {
			trigger_error("Unexpected test run state", E_USER_ERROR);
			return NULL;
		}
		$this->redirectOnFinish($items);

		$block = $GLOBALS["BLOCK_LIST"]->getBlockById($blockId);

		/* BEGIN: DEBUGGING INFO */
		if ($debug)
		{
			$debug["item_ids_restored_from_session"] = "";
			foreach($items as $itemId => $item) $debug["item_ids_restored_from_session"] .= $item->getId()."_";
			$debug["block_loaded"] = $block ? true: false;
		}
		/* END */

		// Save ids of items whose answer is needed but not given
		$areNotSet = array();
		$areNotValid = array();
		// Unset old session variables
		unset($_SESSION['oldAnswers']);
		unset($_SESSION['missingAnswers']);

		if(isset($items) && $items != NULL)
			foreach (@$items as $i => $item)
			{
				if (!is_subclass_of($item, 'item')) continue;
				if ($item instanceof TextLineItem)
				{
					libLoad("utilities::validator");
					$restriction = $item->getRestriction();
					if($restriction)
					{
						if(Validator::validateRestriction($restriction))
						{
							$result = Validator::validateText(trim($answers[$item->getId()]), $restriction);
							if(!$result[0])
							{
								$GLOBALS["MSG_HANDLER"]->addFinishedMsg($result[1], MSG_RESULT_NEG);
								$areNotValid[] = $item->getId();
							}
						} else
						{
							$GLOBALS["MSG_HANDLER"]->addMsg('pages.test.restriction.not_found', MSG_RESULT_NEG);
						}
					}
				}
				$myAnswer = isset($answers[$item->getId()]) ? $answers[$item->getId()] : '';
				if ($item->getId() == @$itemIds[$i] && $item->isForced() && ($myAnswer === ''))
				{
					$areNotSet[] = $item->getId();
				}
			}

		if (($areNotSet || $areNotValid) && ! $timeout)
		{
			if($areNotSet)
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.test.forced', MSG_RESULT_NEG);
				$_SESSION['oldAnswers'] = $answers;
				$_SESSION['missingAnswers'] = $areNotSet;
			}
			if($areNotValid)
			{
				$_SESSION['oldAnswers'] = $answers;
				$_SESSION['missingAnswers'] = $areNotValid;
			}
		}
		else
		{
			
			for ($i = 0; $i < count($items); $i++)
			{
					
				$adaptiveTestSession = NULL;
					
				if (! isset($itemIds[$i])) {
					continue;
				}
				$item = $items[$i];
				
				// Count block time
				$parent = $item->getParent();
				if (isset($this->testSession['block_time'][$parent->getId()])) {
					$this->testSession['block_time'][$parent->getId()] -= $duration/1000;
				}

				// Only store the given answer if it relates to the current item
				if ($item)
				{
					$itemId = $itemIds[$i];

					// Error: different session
					if (post("session_id") != session_id())
					{
						$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.different_session", MSG_RESULT_NEG);
						redirectTo("test_listing");
					}
					// Error: different item
					elseif ($item->getId() != $itemId)
					{
						$gas = $this->testRun->getGivenAnswerSetsByBlockId($blockId);
						if (!isset($gas[$itemId]))
						{
							echo "The given answer is not related to the current item and has not been answered before. ";
							if($item->getId) echo "Item ID: should be ".$item->getId().", is ".$itemId.". Block ID: should be ".$block->getId().", is ".$blockId.".";
							$link = linkTo("test_make", array("id" => $id, "resume_messages" => "true"));
							echo "<p><a href=\"".htmlentities($link)."\"><b>Proceed manually</b></a></p>";
							die; //return;
						}
					}
					// Everything's alright; start processing
					else
					{
						// Each item (even an Info Page) produces a set of given answers, but the set may stay empty
						$result = array();

						$wasSkipped = TRUE;
						// Currently, only items induce usable answers
						if (is_subclass_of($item, "Item"))
						{
							list($wasSkipped, $result) = $item->interpretateAnswers($answers);
						}

						$doSave = ! is_subclass_of($item, "Item") || $timeout || ! $item->isForced() || ! $wasSkipped;

						if ($doSave)
						{
							// Delete the time tracking cookie
							$cookieId = "testMakerRun".$id."Block".$blockId."Item".$itemId;
							if (isset($_COOKIE[$cookieId])) {
								setcookie($cookieId, $_COOKIE[$cookieId], time() - 3600);
							}

							// Determine the current step number and the last saved answer set for comparison
							$itemsPerPage = (int)$block->getItemsPerPage();
							$testRunBlock = $this->testRun->getCurrentTestRunBlock();
							$trStep = $this->testRun->getStep();
							$trbStep = $testRunBlock->getStep();


							/* BEGIN: DEBUGGING INFO */
							if ($debug)
							{
								$debug["tr_step_init"] = $trStep;
								$debug["trb_step_init"] = $trbStep;
							}
							/* END */

							$lastAnswerSet = $this->testRun->getAnswerSetWithHighestStep();
							$highestSavedStep = $lastAnswerSet ? $lastAnswerSet->getStepNumber() : 0;
							$lastWasSkipped = $lastAnswerSet ? $lastAnswerSet->getWasSkipped() : false;

							// We have a skipped item and multiple items per page - handle it
							if($trStep == $highestSavedStep && $lastWasSkipped && $itemsPerPage > 1)
							{
								if($i > 0) $i--;
								$this->testRun->setStep(++$trStep);
								$testRunBlock->setStep(++$trbStep);
								continue;
							}

							// See whether the duration sent from the client is in the right range
							$clientDuration = $duration;
							if (! is_numeric($clientDuration) || $clientDuration == 0) {
								$clientDuration = NULL;
							}

							// Determine the duration measured on the server
							if (! empty($_SESSION["RUNNING_TESTS"][$id]["itemStartTime"][$blockId][$itemId])) {
								$startTime = $_SESSION["RUNNING_TESTS"][$id]["itemStartTime"][$blockId][$itemId];
								$serverDuration = round(1000*($endTime-$startTime), 0);
								unset($_SESSION["RUNNING_TESTS"][$id]["itemStartTime"][$blockId][$itemId]);
							}
							else {
								$serverDuration = NULL;
							}

							// Compare client and server duration and take the right one for saving
							// wich is the right one?! server duration doesnt respect pause-time
							if (isset($clientDuration) && isset($serverDuration) && ($serverDuration+1000) > $clientDuration) { //// && $serverDuration - $clientDuration < 6000) {
								$duration = $clientDuration;
							} else {
								$duration = $serverDuration;
							}
							
							$durationTotal = round($duration / $itemsPerPage, 2);
							// Create a new set of given answers
							if (NULL !== ($answerSet = $this->testRun->prepareGivenAnswerSet($blockId, $trStep, $itemId, NOW, $timeout, $durationTotal, $clientDuration, $serverDuration)))
							{
								// Store each given answer in the set, if any
								foreach ($result as $answerId => $value) {
									$answerSet->addGivenAnswer($answerId, $value);
								}

								// Special handling for adaptive items
								if (is_subclass_of($item, "Item") && $block->isItemBlock() && $block->isAdaptiveItemBlock())
								{
									require_once(CORE."types/AdaptiveTestSession.php");
									$adaptiveTestSession = new AdaptiveTestSession();
									$adaptiveTestSession->loadState($this->testSession["adaptiveTestSessionData"]);
									if ($adaptiveTestSession->getCurrentItemId() != $itemId) {
										trigger_error("The adaptive test session claims that item <b>".$adaptiveTestSession->getCurrentItemId()."</b> is the current item, but item <b>".$itemId."</b> is the one that was answered", E_USER_ERROR);
									}
									$adaptiveTestSession->processAnswer($item->verifyCorrectness($answerSet));
									$adaptiveTestSession->saveState($this->testSession["adaptiveTestSessionData"]);

									// Update structure to reflect the next item to be displayed
									$subtestId = $this->testRun->getDisplayingBlockId();
									if ($subtestId == $this->testRun->getTestId()) $subtestId = 0;
									$testRunBlock = $this->testRun->getTestRunBlockBySubtest($subtestId);
									$structure = $testRunBlock->getStructure();
									//calculate the next item
									$nextItemId = $adaptiveTestSession->getCurrentItemId();
									if (!$nextItemId || $adaptiveTestSession->isFinished()) $nextItemId = 0;
									$structure = TestStructure::reorderStructure($structure, $block->getId(), $item->getId(), $nextItemId);
									$testRunBlock->setStructure($structure);

									$answerSet->setThetaAndSem($adaptiveTestSession->getTheta(), $adaptiveTestSession->getSem());
								}

								// After having prepared all data structures, store them
								$subtest = $GLOBALS['BLOCK_LIST']->findParentInTest($block->getId(), $this->testRun->getTestId());
								if ($subtest === NULL) $subtest = 0;
								$this->testRun->addGivenAnswerSet($subtest, $answerSet);
								$trBlock = $this->testRun->getTestRunBlockBySubtest($subtest);

								unset($this->testSession["skip_block"]);

								// Increase statistic values
								$increase = array();
								if (is_subclass_of($item, "Item") && (!$block->isAdaptiveItemBlock() || ($adaptiveTestSession && $adaptiveTestSession->isFinished())))
								{
									$increase[] = "shown_items";
									if (! $wasSkipped) {
										$increase[] = "answered_items";
										if ($item->isForced()) {
											$increase[] = "answered_required_items";
										}
									}
								}
								if(!$timeout)
								{
									$durationTotal = round ($duration / $itemsPerPage , 2);
									$this->testRun->updateStatistics($increase, $durationTotal);
									$trBlock->updateStatistics($increase);
								}
								$structure = $this->testRun->getStructure($trBlock, TRUE);
								if($timeout)
								{
									// timeout occurred, increase step number accordingly and adjust statistics once more
									$blockId = $block->getId();
									$blockTimeout = ($block->getMaxTime() != 0) ? true : false;
									$itemTimeout = $blockTimeout ? false : true;
									while (($blockTimeout || $itemTimeout) && array_key_exists($trbStep, $structure) && ($blockId == $structure[$trbStep]['parent_id']))
									{					
										$trbStep++;
										$trStep++;
										$this->testRun->increaseShownItems();
										$trBlock->increaseShownItems();
										$itemTimeout = false;
									}
									unset($testSession['block_time'][$blockId]);
								}
								else
								{
									$trStep++;
									$trbStep++;
								}
								$this->testRun->setStep($trStep);
								$testRunBlock->setStep($trbStep);
							}
						}
					}
				}
			}
			/* BEGIN: DEBUGGING INFO */
			if ($debug)
			{
				$debug["tr_step_afterwards"] = $trStep;
				$debug["trb_step_afterwards"] = $trbStep;
			}
			/* END */

			// Page contained an item - increase the shown pages value of test run and test run block
			if($this->testRun)	{
				$subtest = $GLOBALS['BLOCK_LIST']->findParentInTest($block->getId(), $this->testRun->getTestId());
				$trBlock = $this->testRun->getTestRunBlockBySubtest($subtest);
				$this->testRun->updateStatistics(array("shown_pages"));
				$trBlock->updateStatistics(array("shown_pages"));
			}
		}
		/* BEGIN: DEBUGGING INFO */
		if ($debug)
		{
			$_SESSION["DEBUG"]["TestMake:processAnswers"] = $debug;
		}
		/* END */
	}
}
