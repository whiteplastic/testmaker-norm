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
 * Load page selector widget
 */
require_once(PORTAL.'PageSelector.php');
/**
 * Loads the base class
 */
require_once(PORTAL.'DataAnalysisPage.php');

define("ANSWER_SKIPPED", 98);
define("ANSWER_TIMEOUT", 99);

/**
 * Test accomplishment
 *
 * Default action: {@link doShowOverview()}
 *
 * @package Portal
 */
class TestRunPage extends DataAnalysisPage
{
	/**$db
	 * @access private
	 */
	var $defaultAction = 'show_overview';
	var $db;
	var $twiceTitle = array(); //item names that have the same title
	var $labelVariable = array();
	var $labelValues = array();
	var $ChildrenOfItem = array();

	function doDeleteByCriterions()
	{
		$this->init();
		$this->initFilters();

		$this->checkAllowed('export', true, NULL);
		$this->checkAllowed('delete', true, NULL);

		if($this->filters["testRunID"] != 0) {
			$testRuns = array($this->testRunList->getTestRunById($this->filters["testRunID"]));
		} else {
			$testRuns = $this->getTestRuns();
		}

		foreach ($testRuns as $testRun) {
			$testRun->delete();
		}

		//add log entry
		EditLog::addEntry(LOG_OP_TESTRUN_DELETE, count($testRuns), $this->filters);

		$GLOBALS["MSG_HANDLER"]->addMsg("pages.testrun.deleted_test_runs", MSG_RESULT_POS, array("count" => count($testRuns)));
		redirectTo("test_run", array("resume_messages" => "true"));
	}
	
	//stores the constellation of the ExportVars to the database	
	function doSaveExportVars()
	{
		$this->init();
		$this->initFilters();

		$this->db = &$GLOBALS['dao']->getConnection();
		$res = $this->db->query("SELECT * FROM ".DB_PREFIX."export_var_constellation WHERE Test_id = ?", array($this->filters["testId"]));
		
		if ($res->numRows() == 0) {
				
			$res = $this->db->query("INSERT INTO ".DB_PREFIX."export_var_constellation VALUES (? , ?)", 
			array($this->filters["testId"],serialize($_POST["fields"])));
		}
		else {
			$res = $this->db->query("UPDATE ".DB_PREFIX."export_var_constellation SET const = ? WHERE Test_id = ? ", 
			array(serialize(post("fields", array())),
			$this->filters["testId"]));		
		}	
		$_SESSION["TEST_RUN_USE_COMMA"] = post("comma");
		$_SESSION["TEST_RUN_TRUNCATE_TEXT"] = post("truncate_text",false);
		$_SESSION["TEST_RUN_TRUNCATE_TEXT_CHARS"] = post("truncate_text_chars",255);
		$_SESSION["TEST_RUN_IGNORE_ANSWERLESS_ITEMS"] = post("ignore_answerless_items");
		
		redirectTo("test_run", array("resume_messages" => "true"));
	}
	
	/* *
	*shows all testruns maybe filtered.
	*/	
	function doShowOverview()
	{
		$this->tpl->loadTemplateFile("TestRunOverview.html");
		$this->initTemplate("export_data");
		$pageTitle = T("pages.data_analysis");
		
		// Load lib
		libLoad('utilities::getCorrectionMessage');

		$this->init();
		$this->initFilters();

		$this->checkAllowed('export', true, NULL);
		$user = $GLOBALS['PORTAL']->getUser();
		if (!$user->canEditSomething()) {
			$this->checkAllowed('edit', true, false);
		}

		// Initialize the page
		
		$this->keepFilterSettings();
		$testRunCount = $this->countTestRuns();
		

		if ((!$testRunCount) or ($testRunCount == 0))
		{
			$this->tpl->touchBlock("no_results");
		}
		elseif ($this->filters["testId"] != 0 || $this->filters["testRunID"] != 0){

			if (post("entries_per_page")>0)	
				$entriesPerPage = getpost("entries_per_page");
			else 
			if (isset($_SESSION["TEST_RUN_PAGE_ENTRIES"])) 
				$entriesPerPage = $_SESSION["TEST_RUN_PAGE_ENTRIES"];
			else $entriesPerPage = 5;
				$_SESSION["TEST_RUN_PAGE_ENTRIES"] = $entriesPerPage;

			$this->tpl->setVariable($entriesPerPage . "_entries_per_page", "selected");
			$pageLinkDistance = 2;
			$pageCount = ceil($testRunCount/$entriesPerPage);

			if (! isset($_SESSION["TEST_RUN_PAGE"])) {
				$_SESSION["TEST_RUN_PAGE"] = $pageCount;
			}
			$_SESSION["TEST_RUN_PAGE"] = getpost("page_number", $_SESSION["TEST_RUN_PAGE"]);
			if ($_SESSION["TEST_RUN_PAGE"] > $pageCount || $_SESSION["TEST_RUN_PAGE"] == 0 || (isset($_SESSION["TEST_RUN_PAGE_COUNT"]) && $pageCount != $_SESSION["TEST_RUN_PAGE_COUNT"])) {
				$_SESSION["TEST_RUN_PAGE"] = $pageCount;
			}
			$_SESSION["TEST_RUN_PAGE_COUNT"] = $pageCount;

			$pageSelector = new PageSelector($pageCount, $_SESSION['TEST_RUN_PAGE'], $pageLinkDistance, 'test_run');
			$pageSelector->renderDefault($this->tpl);

			// Show the Result List
			if($this->filters["testRunID"] != 0) {
				$testRuns = array($this->testRunList->getTestRunById($this->filters["testRunID"]));
			} else {
				$testRuns = $this->getTestRuns(FALSE, ($_SESSION["TEST_RUN_PAGE"]-1) * $entriesPerPage, $entriesPerPage);
			}

			$this->tpl->hideBlock("has_results");
			$this->tpl->hideBlock("no_results");
			if ($this->numberOfTests >= 1 && $this->filters["testId"] == 0) {
				$this->tpl->hideBlock("export_allowed");
				$this->tpl->touchBlock("export_choose_test");
			} else {
				$this->tpl->hideBlock("export_choose_test");
				$this->tpl->touchBlock("export_allowed");
			}

			if (! $testRunCount)
			{
				$this->tpl->touchBlock("no_results");
			}
			else
			{
				$this->tpl->touchBlock("has_results");
				$this->tpl->setVariable("result_count", $testRunCount);
				foreach ($testRuns as $testRun)
				{
					$this->_setTestRunDetails($testRun);
					$this->tpl->parse("result_row");
				}
				
				if($user->checkPermission('delete'))
					$this->tpl->touchBlock("delete_testruns");
				else
					$this->tpl->hideBlock("delete_testruns");
				
				
			}
		
			// Show the export fields
			if (! isset($_SESSION["TEST_RUN_FIELDS"])) {
				$_SESSION["TEST_RUN_FIELDS"]["HEADERS"] = array(
					"test_run_id" => TRUE,
					"test_id" => TRUE,
					"test_version" => TRUE,
					//"test_path" => FALSE,
					"user_id" => TRUE,
					"user_groups" =>FALSE,
					"start_time" => FALSE,
					"start_time_date" => TRUE,
					"start_time_clock" => TRUE,
					"end_time" => FALSE,
					"end_time_date" => TRUE,
					"end_time_clock" => TRUE,
					"total_time_seconds" => FALSE,
					"total_time_readable" => TRUE,
					"access_type" => FALSE,
					"referer" => FALSE,
					"ip" => FALSE,
					"host" => FALSE,
					"useragent" => FALSE,
					"privacy_policy_acc" => FALSE,);
				$_SESSION["TEST_RUN_FIELDS"]["ITEMS"] = array(
					
					"item_title" => FALSE,
					"item_text" => FALSE,
					"item_id" => TRUE,
					"block_id" => TRUE,
					
					"step_number" => TRUE,
					
					"answer_number" => TRUE,
					"answer_value" => TRUE,
					"answer_readable" => TRUE,
					"answer_id" => TRUE,
					"correctness" => TRUE,
					"duration" => TRUE,
					"timeout" => TRUE,
					
					"theta" => FALSE,
					"sem" => FALSE,);
				$_SESSION["TEST_RUN_FIELDS"]["TEXTITEMS"] = array(
					"info_title" => FALSE,
					"info_page_id" => TRUE,
					"info_block_id" => TRUE,
					
					"info_step_number" => FALSE,
					"info_duration" => TRUE,);
				$_SESSION["TEST_RUN_FIELDS"]["FEEDBACKS"] = array(
					"feedback_title" => FALSE,
					"feedback_page_id" => TRUE,
					"feedback_block_id" => TRUE,
					
					"feedback_step_number" => FALSE,
					"feedback_duration" => TRUE,
				);
			}
	
			$this->db = &$GLOBALS['dao']->getConnection();
			$res = $this->db->getOne("SELECT const FROM ".DB_PREFIX."export_var_constellation WHERE Test_id = ?", array($this->filters["testId"]));
				if ($res) {
					$exportVars = array();
					$exportVars = unserialize($res);
					if(!is_null($exportVars)) {
						foreach($_SESSION["TEST_RUN_FIELDS"]["HEADERS"] as $fieldTmp => $value) {
							if (in_array($fieldTmp,$exportVars))
								$_SESSION["TEST_RUN_FIELDS"]["HEADERS"][$fieldTmp] = true;
							else
								$_SESSION["TEST_RUN_FIELDS"]["HEADERS"][$fieldTmp] = false;
						}
						
						foreach($_SESSION["TEST_RUN_FIELDS"]["ITEMS"] as $fieldTmp => $value) {
							if (in_array($fieldTmp,$exportVars))
								$_SESSION["TEST_RUN_FIELDS"]["ITEMS"][$fieldTmp] = true;
							else
								$_SESSION["TEST_RUN_FIELDS"]["ITEMS"][$fieldTmp] = false;
						}
						
						foreach($_SESSION["TEST_RUN_FIELDS"]["TEXTITEMS"] as $fieldTmp => $value) {
							if (in_array($fieldTmp,$exportVars))
								$_SESSION["TEST_RUN_FIELDS"]["TEXTITEMS"][$fieldTmp] = true;
							else
								$_SESSION["TEST_RUN_FIELDS"]["TEXTITEMS"][$fieldTmp] = false;
						}
						
						foreach($_SESSION["TEST_RUN_FIELDS"]["FEEDBACKS"] as $fieldTmp => $value) {
							if (in_array($fieldTmp,$exportVars))
								$_SESSION["TEST_RUN_FIELDS"]["FEEDBACKS"][$fieldTmp] = true;
							else
								$_SESSION["TEST_RUN_FIELDS"]["FEEDBACKS"][$fieldTmp] = false;
						}
					}
				}

			$fieldsTmp = &$_SESSION["TEST_RUN_FIELDS"]["HEADERS"];

			foreach ($fieldsTmp as $field => $checked)
			{ 
				$this->tpl->setVariable("field_name", $field);
				$this->tpl->setVariable("field_checked", $checked ? " checked=\"checked\"" : "");
				$this->tpl->parse("fieldHeaders");
			}
			
			$fieldsTmp = &$_SESSION["TEST_RUN_FIELDS"]["ITEMS"];
			
			foreach ($fieldsTmp as $field => $checked)
			{
				$this->tpl->setVariable("field_name", $field);
				$this->tpl->setVariable("field_checked", $checked ? " checked=\"checked\"" : "");
				$this->tpl->parse("fieldItems");
			}
			
			$fieldsTmp = &$_SESSION["TEST_RUN_FIELDS"]["TEXTITEMS"];
			
			foreach ($fieldsTmp as $field => $checked)
			{
				$this->tpl->setVariable("field_name", $field);
				$this->tpl->setVariable("field_checked", $checked ? " checked=\"checked\"" : "");
				$this->tpl->parse("fieldTextItems");
			}
			
			$fieldsTmp = &$_SESSION["TEST_RUN_FIELDS"]["FEEDBACKS"];
			foreach ($fieldsTmp as $field => $checked)
			{
				$this->tpl->setVariable("field_name", $field);
				$this->tpl->setVariable("field_checked", $checked ? " checked=\"checked\"" : "");
				$this->tpl->parse("fieldFeedback");
			}
			
			$fields[] = &$_SESSION["TEST_RUN_FIELDS"]["HEADERS"];
			$fields[] = &$_SESSION["TEST_RUN_FIELDS"]["ITEMS"];
			$fields[] = &$_SESSION["TEST_RUN_FIELDS"]["TEXTITEMS"];
			$fields[] = &$_SESSION["TEST_RUN_FIELDS"]["FEEDBACKS"];

			if (! isset($_SESSION["TEST_RUN_USE_COMMA"])) {
				$_SESSION["TEST_RUN_USE_COMMA"] = $this->useComma;
			}
			
			
			$this->tpl->touchBlock("comma_".($_SESSION["TEST_RUN_USE_COMMA"] ? 1 : 0)."_checked");
			$this->tpl->hideBlock("comma_".($_SESSION["TEST_RUN_USE_COMMA"] ? 0 : 1)."_checked");

			if (! isset($_SESSION["TEST_RUN_TRUNCATE_TEXT"])) {
				$_SESSION["TEST_RUN_TRUNCATE_TEXT"] = $this->truncateText;
				$_SESSION["TEST_RUN_TRUNCATE_TEXT_CHARS"] = $this->truncateTextChars;
			}

			if ($_SESSION["TEST_RUN_TRUNCATE_TEXT"]) {
				$this->tpl->touchBlock("truncate_text_checked");
				$this->tpl->hideBlock("truncate_text_chars_disabled");
			} else {
				$this->tpl->hideBlock("truncate_text_checked");
				$this->tpl->touchBlock("truncate_text_chars_disabled");
			}
			$this->tpl->setVariable("truncate_text_chars", $_SESSION["TEST_RUN_TRUNCATE_TEXT_CHARS"]);
			
			
			if (isset($_SESSION["TEST_RUN_IGNORE_ANSWERLESS_ITEMS"])) {
				$this->tpl->touchBlock("ignore_answerless_items_checked");
			} else {
				$this->tpl->hideBlock("ignore_answerless_items_checked");
			}
			
		}
		else
		{
			$this->tpl->touchBlock("no_filter");
		}

		// Output
		$body = $this->tpl->get();
		$this->loadDocumentFrame();

		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", $pageTitle);
		$this->tpl->show();
	}

	function _setTestRunDetails(&$testRun)
	{
		if ($GLOBALS["BLOCK_LIST"]->existsBlock($testRun->getTestId())) {
			$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testRun->getTestId(), BLOCK_TYPE_CONTAINER);
			$testTitle = $test->getTitle();
		} else {
			$test = NULL;
			$testTitle = T("pages.testrun.deleted_test_title", array("id" => $testRun->getTestId()));
		}

		$userId = $testRun->getUserId();
		if ($userId == 0) {
			$userName = T("generic.anonymous");
			$userFullName = T("generic.anonymous_user");
			$privacyPolicy = 0;
		}
		else {
			require_once(CORE."types/UserList.php");
			$userList = new UserList();
			if (! $user = $userList->getUserById($userId)) {
				$userName = T("generic.unknown");
				$userFullName = T("generic.unknown");
				$privacyPolicy = T("generic.unknown");
			}
			else {
				$userName = $user->getUsername();
				$userFullName = $user->getDisplayFullname();
				$privacyPolicy = $user->getPrivacyPolicyAcc();
			}
		}
		
		
		$referer = $testRun->getReferer();
		if (! $referer) {
			$referer = T("generic.unknown");
		}
		$client = $testRun->getClient();

		$accessType = $testRun->getAccessType();

		libLoad("utilities::relativeTime");

		$this->tpl->setVariable("test_run_id", $testRun->getId());
		$this->tpl->setVariable("test_id", $testRun->getTestId());
		$this->tpl->setVariable("test_version", $testRun->getTestVersion());
		$this->tpl->setVariable("test_title", $testTitle);
		//$this->tpl->setVariable("test_path", $testRun->getTestPath());
		$this->tpl->setVariable("access_type", $accessType);
		$this->tpl->setVariable("access_type_description", $accessType != "" ? T("pages.testrun.access_type.".$accessType, array(), $accessType) : T("generic.unknown"));
		$this->tpl->setVariable("user_id", $userId);
		$this->tpl->setVariable("user_name", $userName);
		$this->tpl->setVariable("user_fullname", $userFullName);
		$this->tpl->setVariable("privacy_policy_acc", $privacyPolicy);
		$this->tpl->setVariable("referer", $referer);
		$this->tpl->setVariable("client_ip", $client["ip"]);
		$this->tpl->setVariable("client_host", $client["host"]);
		$this->tpl->setVariable("client_useragent", $client["useragent"]);
		$this->tpl->setVariable("start_time", htmlentities(relativeTime($testRun->getStartTime(), NOW, TRUE, 3, -1)));
		$this->tpl->setVariable("start_time_exact", strftime(T("utilities.relative_time.strftime"), $testRun->getStartTime()));
		$this->tpl->setVariable("progress_total", $testRun->getProgress());
		$this->tpl->setVariable("progress_answered_items", floor(100*$testRun->getAnsweredItemsRatio()));
		$this->tpl->setVariable("progress_required_answered_items", floor(100*$testRun->getAnsweredRequiredItemsRatio()));
	}


	/**
	*show all details for a given testrun
	*/
	function doShowDetails()
	{
		$this->init();
		$id = get("run_id");
		$testRun = &$this->testRunList->getTestRunById($id);
		if (! isset($testRun)) {
			redirectTo("test_run");
		}

		$block = $GLOBALS['BLOCK_LIST']->getBlockById($testRun->getTestId());
		$this->checkAllowed('edit', true, $block);

		$this->tpl->loadTemplateFile("TestRunDetails.html");
		$this->initTemplate("export_data");
		$this->tpl->setVariable("testrun_id",$id);
		
		$pageTitle = T("pages.data_analysis");

		$this->_setTestRunDetails($testRun);
		
		$answerSets = $testRun->getGivenAnswerSets();
		
		$this->tpl->hideBlock("no_answer_sets");
		$this->tpl->hideBlock("has_answer_sets");
		//if testrun contains answers
		if (! $answerSets) {
			
			$this->tpl->touchBlock("no_answer_sets");
		}
		else
		{
			$this->tpl->touchBlock("has_answer_sets");
			$testMakePage = $GLOBALS["PORTAL"]->loadPage("test_make");

			$lastBlockId = NULL;

			libLoad("utilities::sortAnswerSets");
			usort($answerSets, "cmpAnswerSets");
			foreach ($answerSets as $answerSet)
			{

				$block = @$GLOBALS["BLOCK_LIST"]->getBlockById($answerSet->getBlockId());
				$item = $block ? @$block->getTreeChildById($answerSet->getItemId()) : NULL;

				if ($item) {
						if  (is_subclass_of($item, 'Item')) {
							$template = $item->getEffectiveTemplate();
							list($templateFile, $templateClass, $templateVariant) = $testMakePage->inflateTemplateName($template);
						}
				}

				// add one to the step number, to avoid beginning numeration at zero
				$this->tpl->setVariable("step_number", $answerSet->getStepNumber()+1);
				$this->tpl->setVariable("block_title", $block ? $block->getTitle() : T("pages.testrun.deleted_block_title", array("id" => $answerSet->getBlockId())));

				if ($answerSet->getBlockId() != $lastBlockId) {
					$lastBlockId = $answerSet->getBlockId();
					$this->tpl->touchBlock("block_header");
				}
				else {
					$this->tpl->hideBlock("block_header");
				}

				$correct = NULL;
				if (!$item)
				{
					$this->tpl->setVariable("item_title", "#".$answerSet->getItemId());
					$this->tpl->setVariable("item", T("generic.unknown"));
				}
				elseif (is_a($item, "Item"))
				{
					$this->tpl->setVariable("item_title", $item->getTitle());
					$this->tpl->setVariable("item", strip_tags($item->getQuestion(), "<br />"));

					if (is_a($item, "MapItem")) $correct = NULL;
					else
						$correct = $item->verifyCorrectness($answerSet);
					
					
				}
				elseif (is_a($item, "InfoPage"))
				{
					$this->tpl->setVariable("item_title", $item->getTitle());
				}
				elseif (is_a($item, "FeedbackPage"))
				{
					$this->tpl->setVariable("item_title", $item->getTitle());
				}

				if ($correct !== NULL)
				{
				    if (($correct!==99) && ($correct!==98)) {
					   $this->tpl->touchBlock("correct");
					   $this->tpl->touchBlock($correct ? "is_correct" : "is_not_correct");
					}
				}

				$duration = $answerSet->getDuration();
				$clientDuration = $answerSet->getClientDuration();
				$serverDuration = $answerSet->getServerDuration();

				if ($duration == $clientDuration) {
					$durationSource = "client";
				}
				elseif ($duration == $serverDuration) {
					$durationSource = "server";
				}
				else {
					$durationSource = T("generic.unknown");
				}

				$this->tpl->setVariable("duration_ms", $duration);
				$this->tpl->setVariable("duration_s", $duration/1000);
				$this->tpl->setVariable("server_duration", $serverDuration);
				$this->tpl->setVariable("client_duration", $clientDuration);
				$this->tpl->setVariable("duration_source", $durationSource);

				if ($answerSet->hadTimeout()) {
					$this->tpl->touchBlock("timeout");
				}
				
				$answers = $answerSet->getAnswers();

				$this->tpl->hideBlock("no_answers");
				$this->tpl->hideBlock("has_answers");
				$this->tpl->hideBlock("answerjump");

				if ($correct === 98) {
					$answerBlock = "no_answers";
					$this->tpl->touchBlock($answerBlock);
				}

				if ((!$answers) && ($correct !== 98) && ($correct !== 99) && $duration < 0.1 ) {
					$this->tpl->touchBlock("answerjump");
				}

				if ($answers)
				{
					$this->tpl->touchBlock("has_answers");
					$countAnswers = 0;
					foreach ($answers as $answerId => $answer)
					{
						$countAnswers++;
						$value = $answer;

						$this->tpl->hideBlock("empty_answer");
						$this->tpl->hideBlock("simple_answer");
						$this->tpl->hideBlock("mc_answer_yes");
						$this->tpl->hideBlock("mc_answer_no");

						$answerBlock = "simple_answer";

						if ($templateClass == "map") {
							if (isset($value[0]) && $value[0] == 'a') {
								$value = $item->formateAnswer($value);
							}
						}

						if ($item && $answerId != 0)
						{
							$child = @$item->getChildById($answerId);
							if (is_a($child, "ItemAnswer")) {
								if ($templateClass == "mcma") {
									$answerBlock = "mc_answer_".($value == 1 ? "yes" : "no");
								}
								else {
									$answerBlock = "simple_trusted_answer";
								}
								$value = strip_tags($child->getAnswer());
							}
						}

						if ($value == "") {
							$answerBlock = "empty_answer";
						}

						$this->tpl->setVariable("answer", $value);

						$this->tpl->touchBlock($answerBlock);
						$this->tpl->parse("answer");
					}
				}

				$this->tpl->parse("given_answer_set");
			}
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();

		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", $pageTitle);
		$this->tpl->show();
	}
	/**
	* deletes a given testrun
	*/
	function doDeleteRun()
	{
		$this->init();

		$id = get("run_id");

		if (! $testRun = &$this->testRunList->getTestRunById($id)) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testrun.not_found", MSG_RESULT_NEG, array("id" => htmlspecialchars($id)));
			redirectTo("test_run", array("resume_messages" => "true"));
		}
		elseif (post("delete_unless_cancelled", FALSE))
		{
			$block = $GLOBALS['BLOCK_LIST']->getBlockById($testRun->getTestId());
			$this->checkAllowed('edit', true, $block);

			if (! post("cancel_delete", FALSE)) {
				$testRun->delete();
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testrun.delete_run", MSG_RESULT_POS, array("id" => $id));
			}
			redirectTo("test_run", array("resume_messages" => "true"));
		}
		else {
			$this->tpl->loadTemplateFile("TestRunDelete.html");
			$this->tpl->setVariable("test_run_id", $id);
			$body = $this->tpl->get();
			$this->loadDocumentFrame();

			$this->tpl->setVariable("body", $body);
			$this->tpl->setVariable("page_title", T("pages.testrun.delete.title"));
			$this->tpl->show();
		}
	}

	var $useComma = TRUE;
	var $truncateText = TRUE;
	var $truncateTextChars = 255;
	var $ignoreAnswerlessItems = FALSE;
	
	function escapeTextValue($value)
	{
		if ($value === NULL) {
			return "";
		}
		if (is_float($value)) {
			if ($this->useComma) {
				$value = str_replace(".", ",", $value);
			} else {
				return $value;
			}
			return $value;
		}
		if (gettype($value) == "string") {
			if ($this->truncateText) {
				$value = substr($value, 0, $this->truncateTextChars);
			}
			// Replace &nbsp; with actual whitespace
			$value = strtr($value, array(" " => " ", "/" => " "));
			return "'".strtr(trim($value), array("'" => "''", "\r" => "", "\n" => "|"))."'";
		}
		if (is_numeric($value)) {
			return $value;
		}
		if (is_array($value)) {
			return array_map(array(&$this, "escapeTextValue"), $value);
		}
	}

	function showExportTemplate($block, $variables = array(), $redirectOnFinish = TRUE)
	{
		$this->tpl->loadTemplateFile("TestRunExport.html");

		foreach ($variables as $name => $value) {
			$this->tpl->setVariable($name, $value);
		}

		$this->tpl->hideBlock("header");
		$this->tpl->hideBlock("footer");
		$this->tpl->hideBlock("please_wait");
		$this->tpl->hideBlock("soon_file");
		$this->tpl->hideBlock("update_progress_bar");
		$this->tpl->hideBlock("set_finish");

		if ($block == "header") {
			if ($redirectOnFinish) {
				$this->tpl->touchBlock("redirect_on_load");
			} else {
				$this->tpl->hideBlock("redirect_on_load");
			}
		}

		$this->tpl->touchBlock($block);
		$this->tpl->show();
	}

	/**
	*Get the structure for the export and store it in $structure.
	*Every choosen variable and its structure for the export is stored.
	*/
	
	function getStructure($testId, $fields)
	{
		$_SESSION["TIME"][0] = time();
		$testMakePage = $GLOBALS["PORTAL"]->loadPage("test_make");
		$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testId);
		
		$timeFormats = array(
			"start_time" => "%d-%b-%y %H:%M:%S",
			"start_time_date" => "%d-%m-%Y",
			"start_time_clock" => "%H:%M:%S",
			"end_time" => "%d-%b-%y %H:%M:%S",
			"end_time_date" => "%d-%m-%Y",
			"end_time_clock" => "%H:%M:%S",
		);

		require_once(CORE."types/TestSelector.php");
		$selector = new TestSelector($test);

		$itemCount = 0;
		$items = array();
		$itemIds = array();
		$itemsOfBlock = array();
		$itemBlocks = array();
		$itemBlockIds = array();
		$blockIdsByItem = array();

		$infoCount = 0;
		$infos = array();
		$infoIds = array();
		$infosOfBlock = array();
		$infoBlocks = array();
		$blockIdsByInfo = array();

		$feedbackCount = 0;
		$feedbacks = array();
		$feedbackIds = array();
		$feedbacksOfBlock = array();
		$feedbackBlocks = array();
		$blockIdsByFeedback = array();
		$_SESSION['lockedIds'] = array();
		
		$structure = array();
		$SpssStructure = array();
		$itemTitles = array();
		$infoTitles = array();
		$feedbackTitles = array();
		
		$this->twiceTitle["ITEM_TITLES"] = array();
		$this->twiceTitle["INFO_TITLES"] = array();
		$this->twiceTitle["FEEDBACK_TITLES"] = array();

		foreach ($selector->getParentTreeBlockIds() as $blockId)
		{
			$block = $GLOBALS["BLOCK_LIST"]->getBlockById($blockId);
			if ($block->isItemBlock()) {
				$itemBlocks[] = $block;
				$itemBlockIds[] = $block->getId();
				$itemsOfBlock[$block->getId()] = array();
			}
			elseif ($block->isInfoBlock()) {
				$infoBlocks[] = $block;
				$infosOfBlock[$block->getId()] = array();
			}
			elseif ($block->isFeedbackBlock()) {
				$feedbackBlocks[] = $block;
				//get the item id's of the certificate data. (name , lastname, date of birth)
				//the content of this items will not be exported
				if ($block->isCertEnabled()) {
					$lockedIds = $block->acquireCertItemIds();
					$_SESSION['lockedIds'] = array_merge($_SESSION['lockedIds'], $lockedIds);	
				}
				$feedbacksOfBlock[$block->getId()] = array();
			}

			foreach ($block->getTreeChildren() as $child)
			{
				if ($block->isItemBlock()) {
					$childsChildren = $child->getChildren();
					if(!empty($childsChildren) || !$_SESSION["TEST_RUN_IGNORE_ANSWERLESS_ITEMS"]) //b/
					{							
						$itemCount++;
						$itemsOfBlock[$block->getId()][$itemCount] = $child;
						$items[$itemCount] = $child;
						$itemIds[$itemCount] = $child->getId();
						$this->ChildrenOfItem[$child->getId()] = $childsChildren; //$child->getChildren();
						$blockIdsByItem[$itemCount] = $block->getId();
						if (isset($itemTitles[$child->getTitle()])) {
							$this->twiceTitle["ITEM_TITLES"][] = $child->getTitle();
						}
						else
							$itemTitles[$child->getTitle()] = 0;
					}					
				}
				elseif ($block->isInfoblock())
				{
					$infoCount++;
					$infosOfBlock[$block->getId()][$infoCount] = $child;
					$infos[$infoCount] = $child;
					$infoIds[$infoCount] = $child->getId();
					$blockIdsByInfo[$infoCount] = $block->getId();
					if (isset($infoTitles[$child->getTitle()])) {
						$this->twiceTitle["INFO_TITLES"][] = $child->getTitle();
					}
					else
						$infoTitles[$child->getTitle()] = 0;
				}
				elseif ($block->isFeedbackBlock())
				{
					$feedbackCount++;
					$feedbacksOfBlock[$block->getId()][$feedbackCount] = $child;
					$feedbacks[$feedbackCount] = $child;
					$feedbackIds[$feedbackCount] = $child->getId();
					$blockIdsByFeedback[$feedbackCount] = $block->getId();
					if (isset($feedbackTitles[$child->getTitle()])) {
						$this->twiceTitle["FEEDBACK_TITLES"][] = $child->getTitle();
					}
					else
						$feedbackTitles[$child->getTitle()] = 0;
				}
			}
		}

		$itemCount = count($items);
		
		//Check which variable is used and creates the structure for it.
		foreach ($fields as $field)
		{
			$fieldTitle = T("pages.testrun.export.field.".$field.".title");
			if ($field == "test_run_id") {
				$structure[$fieldTitle] = array(
					array("type" => "get_test_run"),
					array("type" => "method_call", "method" => "getId", "params" => array()),
					array("type" => "cast", "data_type" => "int"),
				);
				$SpssStructure[$fieldTitle] = "F6.0";
			}
			elseif (in_array($field, array(
				"test_run_id",
				"test_version",
				"test_id",
				"user_id",
			))) {
				$structure[$fieldTitle] = array(
					array("type" => "get_test_run"),
					array("type" => "method_call", "method" => "get".snakeToCamel($field), "params" => array()),
					array("type" => "cast", "data_type" => "int"),
				);
				$SpssStructure[$fieldTitle] = "F6.0";
			}
			elseif (in_array($field, array(
				"privacy_policy_acc",
			))) {
				$structure[$fieldTitle] = array(
					array("type" => "get_user"),
					array("type" => "method_call", "method" => "get".snakeToCamel($field), "params" => array()),
					array("type" => "cast", "data_type" => "int"),
				);
				$SpssStructure[$fieldTitle] = "F6.0";
			}
			elseif ($field == "user_groups") {
					$structure[$fieldTitle] = array(
					array("type" => "get_test_run"),
					array("type" => "method_call", "method" => "getGroupNames", "params" => array()),
					array("type" => "cast", "data_type" => "string")
				);
				$SpssStructure[$fieldTitle] = "A";
			}
			elseif (in_array($field, array(
				"access_type",
				"referer",
			))) {
				$structure[$fieldTitle] = array(
					array("type" => "get_test_run"),
					array("type" => "method_call", "method" => "get".snakeToCamel($field), "params" => array()),
					array("type" => "cast", "data_type" => "string"),
				);
				$SpssStructure[$fieldTitle] = "A";
			}
			elseif (in_array($field, array(
				"start_time",
				"start_time_date",
				"start_time_clock",
			))) {
				$structure[$fieldTitle] = array(
					array("type" => "get_test_run"),
					array("type" => "method_call", "method" => "getStartTime", "params" => array()),
					array("type" => "format_time", "format" => $timeFormats[$field]),
				);
				if ($field == "start_time") 
					$SpssStructure[$fieldTitle] = "DATETIME23.2";
				if ($field == "start_time_date") 
					$SpssStructure[$fieldTitle] = "DATE11";
				if ($field == "start_time_clock") 
					$SpssStructure[$fieldTitle] = "TIME11.2";
			}
			elseif (in_array($field, array(
				"end_time",
				"end_time_date",
				"end_time_clock",
			))) {
				$structure[$fieldTitle] = array(
					array("type" => "get_last_answer_set"),
					array("type" =>  "getFinishTime"),
					array("type" => "format_time", "format" => $timeFormats[$field]),
				);
				if ($field == "end_time") 
					$SpssStructure[$fieldTitle] = "DATETIME23.2";
				if ($field == "end_time_date") 
					$SpssStructure[$fieldTitle] = "DATE11";
				if ($field == "end_time_clock") 
					$SpssStructure[$fieldTitle] = "TIME11.2";
			}
			elseif (in_array($field, array(
				"total_time_seconds",
				"total_time_readable",
			))) {
				$structure[$fieldTitle] = array(
			        array("type" => "get_test_run"),
					array("type" => "method_call", "method" => "getTotalTime", "params" => array()),
					array("type" => "divide", "divisor" => 1000),
			    );
			    if ($field == "total_time_readable") {
			    	$structure[$fieldTitle][] = array("type" => "format_hms_duration");
					$SpssStructure[$fieldTitle] = "TIME11.2";
			    }
				if ($field == "total_time_seconds") {
					$SpssStructure[$fieldTitle] = "F9.2";
					
				}
			}
			elseif (in_array($field, array(
				"ip",
				"host",
				"useragent",
			))) {
				$structure[$fieldTitle] = array(
					array("type" => "get_test_run"),
					array("type" => "method_call", "method" => "getClient", "params" => array()),
					array("type" => "use_array_entry", "name" => $field),
				);
				if ($field == "ip")
					$SpssStructure[$fieldTitle] = "A16";
				else
					$SpssStructure[$fieldTitle] = "A";
			}
			elseif (in_array($field, array(
				"step_number",
				"block_id",
				"item_id",
				"duration",
				"timeout",
				"theta",
				"sem",
			))) {
				foreach ($items as $itemNumber => $item)
				{
					$itemId = $item->getId();
					$key = $this->generateKey($fieldTitle, $item, $structure);
					 $structure[$key] = array(
						array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $itemId),
						array("type" => "method_call", "method" => "get".snakeToCamel($field), "params" => array()),
					);
					if ($field == "duration") {
						$structure[$key][] = array("type" => "divide", "divisor" => 1000);
						$SpssStructure[$key] = "F8.2";
					}
					elseif ($field == "timeout") {
						$structure[$key][1]["method"] = "hadTimeout";
						$SpssStructure[$key] = "F2.0";
						
					}

					if (in_array($field, array("step_number", "block_id", "item_id", "timeout"))) {
						$structure[$key][] = array("type" => "cast", "data_type" => "int");
						$SpssStructure[$key] = "F5.0";
					}
					else {
						$structure[$key][] = array("type" => "cast", "data_type" => "double");
						$SpssStructure[$key] = "F8.2";
					}
				}
			}
			elseif ($field == "item_title")
			{
				foreach ($items as $itemNumber => $item)
				{
				    $key = $this->generateKey($fieldTitle, $item, $structure);
					$structure[$key] = array(
						array("type" => "set_value", "value" => $item->getTitle()),
					);
					$SpssStructure[$key] = "A";
				}
			}
			elseif ($field == "item_text")
			{
				foreach ($items as $itemNumber => $item)
				{
					$key = $this->generateKey($fieldTitle, $item, $structure);
					$structure[$key] = array(
						array("type" => "set_value", "value" => trim(html_entity_decode(strip_tags($item->getQuestion()), ENT_COMPAT, "ISO8859-15"))),
					);
					$SpssStructure[$key] = "A";
				}
			}
			elseif ($field == "correctness")
			{
				foreach ($items as $itemNumber => $item) {

					$itemId = $item->getId();
					
				    list($templateFile, $templateClass, $templateVariant) = $testMakePage->inflateTemplateName($item->getEffectiveTemplate());

					if ($templateClass == "map") {
						for ($i=0; $i<10; $i++) {
						
							$key = $this->generateKeyExp($fieldTitle, $item, $structure, $i+1);
							

							$structure[$key] = array (
								array("type" => "get_answer_set", "block_id"   => $blockIdsByItem[$itemNumber], "item_id" => $itemId),
								array("type" => "verify_correctness_map", "block_id"  => $blockId, "item_id" => $itemId, "location" => $i),
								array("type" => "cast", "data_type" => "int"),
							);
							$SpssStructure[$key] = "F3.0";
						}
					}
					else {
					    $key = $this->generateKey($fieldTitle, $item, $structure, "ITEM_TITLES", 2);
						$structure[$key] = array(
							array("type" => "get_answer_set", "block_id"   => $blockIdsByItem[$itemNumber], "item_id" => $itemId),
							array("type" => "verify_correctness", "block_id"  => $blockId, "item_id" => $itemId),
							array("type" => "cast", "data_type" => "int"),
						);
						$SpssStructure[$key] = "F3.0";
					}
				}
			}
			elseif (in_array($field, array(
				"answer_number",
				"answer_id",
				"answer_value",
				"answer_readable",
			))) {
				foreach ($items as $itemNumber => $item)
				{
					list($templateFile, $templateClass, $templateVariant) = $testMakePage->inflateTemplateName($item->getEffectiveTemplate());
					
					if (($templateClass == "mcma"))
					{
						foreach ($this->ChildrenOfItem[$item->getId()] as $i => $answer)
						{
							switch($field)
							{
								case "answer_number":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 1);
									$structure[$key] = array(
										array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_answer_by_item_answer_id", "item_answer_id" => $answer->getId()),
										array("type" => "set_value_mcma", "value" => ($i+1)),
									);
									$SpssStructure[$key] = "F3.0";
									break;
								case "answer_id":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 1);
									$structure[$key] = array(
										array("type" => "set_value", "value" => $answer->getId()),
									);
									$SpssStructure[$key] = "F7.0";
									break;
								case "answer_value":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 1);
									$structure[$key] = array(
										array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_answer_by_item_answer_id", "item_answer_id" => $answer->getId()),
										array("type" => "set_value_mcma", "value" => ($i+1)),
										/*array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_answer_by_item_answer_id", "item_answer_id" => $answer->getId()),*/
									);
									$SpssStructure[$key] = "F3.0";
									break;
								case "answer_readable":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 0);
									if ("" == ($title = trim(html_entity_decode(strip_tags($answer->getTitle()), ENT_COMPAT, "ISO8859-15")))) {
										$title = "#".($i+1);
									}
									$structure[$key] = array(
										array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_answer_by_item_answer_id", "item_answer_id" => $answer->getId()),
										array("type" => "set_value_mcma", "value" => $title)
									);
									$SpssStructure[$key] = "A";
									break;
							}
						}
					}
					elseif ($templateClass == "mcsa")
					{
						switch($field)
						{	
							case "answer_number":
								$key = $this->generateKey($fieldTitle, $item, $structure, "ITEM_TITLES", 1);
								$idToNumber = array();
							
								foreach ($this->ChildrenOfItem[$item->getId()]  as $i => $answer) {
									$idToNumber[$answer->getId()] = ($i+1);
								}
								
								$structure[$key] = array(
									array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
									array("type" => "get_first_answer"),
									array("type" => "get_answer_number", "id_to_number" => $idToNumber),
								);
								$SpssStructure[$key] = "F3.0";
								break;
							case "answer_id":
								$key = $this->generateKey($fieldTitle, $item, $structure, "ITEM_TITLES", 1);
								$structure[$key] = array(
									array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
									array("type" => "get_first_answer"),
									array("type" => "getAnswerId"),
									array("type" => "cast", "data_type" => "int"),
								);
								$SpssStructure[$key] = "F7.0";
								break;
							case "answer_value":
								$key = $this->generateKey($fieldTitle, $item, $structure, "ITEM_TITLES", 1);
								$idToNumber = array();
							
								foreach ($this->ChildrenOfItem[$item->getId()]  as $i => $answer) {
									$idToNumber[$answer->getId()] = ($i+1);
								}
								$structure[$key] = array(
									array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
									array("type" => "get_first_answer"),
									array("type" => "get_answer_number", "id_to_number" => $idToNumber),
								);
								$SpssStructure[$key] = "F3.0";
								break;
							case "answer_readable":
								$key = $this->generateKey($fieldTitle, $item, $structure, "ITEM_TITLES", 0);
								$idToReadable = array();
								//save time because of databaseQuerries with $item->getChildren()
								
								foreach ($this->ChildrenOfItem[$item->getId()]  as $i => $answer) {
									if ("" == ($title = trim(html_entity_decode(strip_tags($answer->getTitle()), ENT_COMPAT, "ISO8859-15")))) {
										$title = "#".($i+1);
									}
									$idToReadable[$answer->getId()] = $title;
								}
								
								$structure[$key] = array(
									array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
									array("type" => "get_first_answer"),
									array("type" => "get_answer_readable", "id_to_readable" => $idToReadable)
								);
								$SpssStructure[$key] = "A";
								break;
						}
					}
					
					if (($templateClass == "mcaa"))
					{
						foreach ($this->ChildrenOfItem[$item->getId()] as $i => $answer)
						{
							if ("" == ($title = trim(html_entity_decode(strip_tags($answer->getTitle()), ENT_COMPAT, "ISO8859-15")))) {
										$title = "#".($i+1);
							}
							$idToReadable[$answer->getId()] = $title;
						}
				
						foreach ($this->ChildrenOfItem[$item->getId()] as $i => $answer)
						{
										
							switch($field)
							{
								case "answer_number":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 1);
									$structure[$key] = array(
										array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_all_answers"),
										array("type" => "getValueMcaa",  "item_answer_id" => $answer->getId()),
									);
									$SpssStructure[$key] = "F3.0";
									break;
								case "answer_id":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 1);
									$structure[$key] = array(
										array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_all_answers"),
										array("type" => "getAnswerIdMcaa", "number" => $i),
									);
									$SpssStructure[$key] = "F7.0";
									break;
								case "answer_value":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 1);
									$structure[$key] = array(
										array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_all_answers"),
										array("type" => "getValueMcaa",  "item_answer_id" => $answer->getId()),
									);
									$SpssStructure[$key] = "F3.0";
									break;
								case "answer_readable":
									$key =  $this->generateKeyExp($fieldTitle, $item, $structure, $i+1, 0);
									$structure[$key] = array(
										array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
										array("type" => "get_all_answers"),
										array("type" => "get_answer_readableMcaa", "number" => $i, "id_to_readable" => $idToReadable),
								);
									$SpssStructure[$key] = "A";
									break;
							}
						}
					}
					elseif ($templateClass == "text")
					{
						$key = $this->generateKey($fieldTitle, $item, $structure);
						$itemId = $item->getId();
						//Set getValue to getValueXXX for locked items
						if (in_array($itemId, $_SESSION['lockedIds']))
							$getValue = 'getValueXXX';
						else
							$getValue = 'getValue';
							
						switch($field)
						{					
							case "answer_number":
								$structure[$key] = array(
								);
								$SpssStructure[$key] = "F3.0";
								break;
							case "answer_id":
								$structure[$key] = array(
									array("type" => "set_value", "value" => 0),
								);
								$SpssStructure[$key] = "F3.0";
								break;
							case "answer_value":
								$structure[$key] = array(
									array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $itemId),
									array("type" => "get_first_answer"),
									array("type" => $getValue),
								);
								$SpssStructure[$key] = "A";
								break;
							case "answer_readable":
								$structure[$key] = array(
									array("type" => "get_answer_set", "block_id"  => $blockIdsByItem[$itemNumber], "item_id" => $itemId),
									array("type" => "get_first_answer"),
									array("type" => $getValue),
								);
								$SpssStructure[$key] = "A8192";
								break;
						}
					}
					elseif ($templateClass == "map")
					{
						$key = $this->generateKey($fieldTitle, $item, $structure);
						 switch($field)
                        {
                            case "answer_number":
									$structure[$key] = array(
                                );
								$SpssStructure[$key] = "F3.0";
                                break;
                            case "answer_id":
                                $structure[$key] = array(
									array("type" => "set_value", "value" => 0),
                                 );
								$SpssStructure[$key] = "F3.0";
                                break;
                            case "answer_value":
								for ($myI=1; $myI<=33; $myI++) {
									$key = $this->generateKeyExp($fieldTitle,$item,$structure,$myI);
									$SpssStructure[$key] = "F7.0";
                                     switch($myI)
                           			 {
										case 1:
									 		$structure[$key] = array(
												array("type" => "set_satLocations_value", "item_id" => $item->getId()),
											);
											break;
                                     	case 2:
									 		$structure[$key] = array(
                                        		array("type" => "set_HomeTime_value", "item_id" => $item->getId()),
											);
											break;
                                     	case 3:
									 		$structure[$key] = array(
                                        		array("type" => "set_WorkTime_value", "item_id" => $item->getId()),
											);
											break;
                                     	case 4:
									 		$structure[$key] = array(
                                        		array("type" => "set_WaitTime_value", "item_id" => $item->getId()),
											);
											break;
										case 5:
									 		$structure[$key] = array(
                                        		array("type" => "set_travelTime_value", "item_id" => $item->getId()),
											);
											break;
										case 6:
									 		$structure[$key] = array(
												array("type" => "number_of_mods", "item_id" => $item->getId()),
											);
											break;
										// Begin of modification-extend output
										case 7: //Market
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 1),
											);
											break;
										case 8: //Cafeteria
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 2),
											);
											break;
										case 9: //Seminar room
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 3),
											);
											break;
										case 10: //Learning room
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 4),
											);
											break;
										case 11: //Company
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 5),
											);
											break;
										case 12: //Auditorium
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 6),
											);
											break;
										case 13: //Student council
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 7),
											);
											break;
										case 14: //Examination office
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 8),
											);
											break;
										case 15: //Library
									 		$structure[$key] = array(
												array("type" => "mod_extent", "item_id" => $item->getId(), "no_of_col" => 9),
											);
											break;
										//end of modification-extend output
										
										//Begin of longest mod-Phase output
										case 16: //Market
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 1),
											);
											break;
										case 17: //Cafeteria
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 2),
											);
											break;
										case 18: //Seminar room
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 3),
											);
											break;
										case 19: //Learning room
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 4),
											);
											break;
										case 20: //Company
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 5),
											);
											break;
										case 21: //Auditorium
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 6),
											);
											break;
										case 22: //Student council
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 7),
											);
											break;
										case 23: //Examination office
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 8),
											);
											break;
										case 24: //Library
									 		$structure[$key] = array(
												array("type" => "longest_mod_phase", "item_id" => $item->getId(), "no_of_col" => 9),
											);
											break;
											//end of longest mod-phase output
										case 25:
									 		$structure[$key] = array(
												array("type" => "avg_mod_length", "item_id" => $item->getId()),
											);
											break;
										case 26:
									 		$structure[$key] = array(
												array("type" => "no_of_restarts", "item_id" => $item->getId()),
											);
											break;
										case 27:
									 		$structure[$key] = array(
												array("type" => "possibility_of_mods", "item_id" => $item->getId()),
											);
											break;
										case 28:
									 		$structure[$key] = array(
												array("type" => "possibility_of_mods_negative", "item_id" => $item->getId()),
											);
											break;
										case 29:
									 		$structure[$key] = array(
												array("type" => "IdoneUsedCorrectlyCount", "item_id" => $item->getId()),
											);
											break;
										case 30:
									 		$structure[$key] = array(
												array("type" => "IdoneUsedFalseCount", "item_id" => $item->getId()),
											);
											break;
										case 31:
									 		$structure[$key] = array(
												array("type" => "IdoneNotUsedCorrectlyCount", "item_id" => $item->getId()),
											);
											break;
										case 32:
									 		$structure[$key] = array(
												array("type" => "IdoneNotUsedFalseCount", "item_id" => $item->getId()),
											);
											break;
										case 33:
											$structure[$key] = array(
												array("type" => "variety_of_mods", "item_id" => $item->getId()),
											);
											break;
                                     }
								}
								
                            break;
								 
                            case "answer_readable":
								    $key = $this->generateKey($fieldTitle, $item, $structure);
									$structure[$key] = array(
                                  	array("type" => "get_answer_set", "block_id" => $blockIdsByItem[$itemNumber], "item_id" => $item->getId()),
									//array("type" => "method_call", "method" => "getValue", "params" => array()),
									array("type" => "getPathPlan", "item_id" => $item->getId()),
                                  );
								  $SpssStructure[$key] = "A8192";
                            break;
                        }
					}
				}
			}
			
			elseif (in_array($field, array(
				"info_step_number",
				"info_block_id",
				"info_page_id",
				"info_duration",
			))) {
				foreach ($infos as $infoNumber => $info)
				{
					$key = $this->generateKey($fieldTitle, $info, $structure, "INFO_TITLES");
					$infoId = $info->getId();
					if ($field == "info_page_id") {
						$method = "getItemId";
					} else {
						$method = "get".snakeToCamel(substr($field, 5));
					}
					$structure[$key] = array(
						array("type" => "get_answer_set", "block_id"  => $blockIdsByInfo[$infoNumber], "item_id" => $infoId),
						array("type" => "method_call", "method" => $method, "params" => array()),
					);
					$SpssStructure[$key] = "F7.0";
					if ($field == "info_duration") {
						$structure[$key][] = array("type" => "divide", "divisor" => 1000);
						$SpssStructure[$key] = "F7.0";
					}
					if (in_array($field, array("info_step_number", "info_block_id", "info_page_id"))) {
						$structure[$key][] = array("type" => "cast", "data_type" => "int");
						$SpssStructure[$key] = "F7.0";
					}
					else {
						$structure[$key][] = array("type" => "cast", "data_type" => "double");
						$SpssStructure[$key] = "F7.0";
					}
				}
			}
			elseif ($field == "info_title") {
				foreach ($infos as $infoNumber => $info)
				{
					$key = $this->generateKey($fieldTitle, $info, $structure, "INFO_TITLES");
					$structure[$key] = array(
						array("type" => "set_value", "value" => $info->getTitle()),
					);
					$SpssStructure[$key] = "A";
				}
			}
			elseif (in_array($field, array(
				"feedback_step_number",
				"feedback_block_id",
				"feedback_page_id",
				"feedback_duration",
			))) {
				foreach ($feedbacks as $feedbackNumber => $feedback)
				{
					$feedbackId = $feedback->getId();
					$key = $this->generateKey($fieldTitle, $feedback, $structure, "FEEDBACK_TITLES");
					if ($field == "feedback_page_id") {
						$method = "getItemId";
					} else {
						$method = "get".snakeToCamel(substr($field, 9));
					}
					$structure[$key] = array(
						array("type" => "get_answer_set", "block_id"  => $blockIdsByFeedback[$feedbackNumber], "item_id" => $feedbackId),
						array("type" => "method_call", "method" => $method, "params" => array()),
					);
					$SpssStructure[$key] = "F7.0";
					if ($field == "feedback_duration") {
						$structure[$key][] = array("type" => "divide", "divisor" => 1000);
					}
					if (in_array($field, array("feedback_step_number", "feedback_block_id", "feedback_page_id"))) {
						$structure[$key][] = array("type" => "cast", "data_type" => "int");
						$SpssStructure[$key] = "F7.0";
					}
					else {
						$structure[$key][] = array("type" => "cast", "data_type" => "double");
						$SpssStructure[$key] = "F7.0";
					}
				}
			}
			elseif ($field == "feedback_title") {
				foreach ($feedbacks as $feedbackNumber => $feedback)
				{
					$key = $this->generateKey($fieldTitle, $feedback, $structure, "FEEDBACK_TITLES");
					$structure[$key] = array(
						array("type" => "set_value", "value" => $feedback->getTitle()),
					);
					$SpssStructure[$key] = "A";
				}
			}
		}

		$_SESSION["SPSS_STRUCTURE"] = $SpssStructure; 
		return array($structure, array("item_block_ids" => $itemBlockIds));
	}
	
	/**
	*evaluates a Testrun and returns the line for the export file
	*/
	function evalTestrun($testRun, $export, $items) {
		$info = array();
			$sets = $testRun->getGivenAnswerSets();

			foreach ($export["structure"] as $variableName => $procedure)
			{
				$value = NULL;
				foreach ($procedure as $task)
				{
					switch ($task["type"]) {
						case "get_answer_set":
							$value = (isset($sets[TestRunBlock::getKey($task)]) ? $sets[TestRunBlock::getKey($task)] : NULL);
							break;
						case "cast":
							switch($task["data_type"]) {
								case "int":
									$value = (int)$value;
									break;
								case "double":
									$value = (double)$value;
									break;
							}
							break;
						case "method_call": 
							$method = $task["method"];
							if ($value!=NULL) $value = call_user_func_array(array(&$value, $method), $task["params"]);
							break;
						case "divide":
							$value /= $task["divisor"];
							break;
						case "get_first_answer":
							if ($answers = $value->getAnswers()) {
								$value = $answers;
								reset($value);
								}
							else $value = NULL;
							break;
						case "get_answer_number":
							if (isset($task["id_to_number"][key($value)])) $value = $task["id_to_number"][key($value)];	
							else $value = NULL;
							break;
						case "get_answer_by_item_answer_id":
							$value = $value->getGivenAnswerByItemAnswerId($task["item_answer_id"]);
							break;
						case "set_value_mcma":
							if ($value != NULL)
								$value = $task["value"];
							break;
						case "verify_correctness":
							$value = $items[$task["item_id"]]->verifyCorrectness($value);
							if (is_a($items[$task["item_id"]],"MapItem")) $value = NULL;
							break;
						case "getValue":
							$value = current($value);
							break;
						case "get_test_run":
							$value = $testRun;
							break;
						case "format_time":
							setlocale (LC_TIME, 'C');
							$value = strftime($task["format"], $value);
							break;
						case "getFinishTime":
							if ($value != NULL) $value = $value->getFinishTime();
							else $value = $testRun->getStartTime();
							break;
						case "format_hms_duration":
							$value = gmstrftime("%H:%M:%S", $value);
							break;
						case "use_array_entry":
							$value = $value[$task["name"]];
							break;
						case "get_user":
							require_once(CORE.'types/UserList.php');
							$userList = new UserList();
							if (! $value = $userList->getUserById($testRun->getUserId())) $value = NULL;
							break;
						case "get_last_answer_set":
							$value = end($sets);
							break;
						case "set_value":
							$value = $task["value"];
							break;
						case "getPathPlan":
							$answers = $value->getAnswers();
							$temp = '';
							foreach ($answers as $answer) {
								$myString = $answer;
								if (($myString) && ($myString[0] == 'a') && ($myString[1]==':'))
									$temp =  $temp.$items[$task["item_id"]]->formateAnswer($answer).' ; ';
							}
							$value = $temp;
							break;
						case "set_satLocations_value":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getSatisfyLocationsCount($answer_set);
							break;
						case "set_travelTime_value":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getTravelTime($answer_set);
							break;
						case "set_Locations_value":
							$value = $items[$task["item_id"]]->getMaxLocationsCount();
							break;
						case "set_HomeTime_value":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getHomeTimeCount($answer_set);
							break;
						case "set_WorkTime_value":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getWorkTimeCount($answer_set);
							break;
						case "set_WaitTime_value":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getWaitTimeCount($answer_set);
							break;
						case "number_of_mods":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getNumberOfMods($answer_set);
							break;
						case "mod_extent":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getModExtent($answer_set, $task["no_of_col"]);
							break;
						case "longest_mod_phase":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getLongestModPhase($answer_set, $task["no_of_col"]);
							break;
						case "avg_mod_length":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getAvgModLength($answer_set);
							break;
						case "no_of_restarts":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getNoOfRestarts($answer_set);
							break;
						case "possibility_of_mods":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getPossibilityOfMods($answer_set);
							break;
						case "possibility_of_mods_negative":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getPossibilityOfModsNegative($answer_set);
							break;
						case "IdoneUsedCorrectlyCount":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getIdoneUsedCorrectlyCount($answer_set);
							break;
						case "IdoneUsedFalseCount":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getIdoneUsedFalseCount($answer_set);
							break;
						case "IdoneNotUsedCorrectlyCount":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getIdoneNotUsedCorrectlyCount($answer_set);
							break;
						case "variety_of_mods":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getVarietyOfMods($answer_set);
							break;
						case "IdoneNotUsedFalseCount":
							$answer_set = $testRun->getGivenAnswerSetByItemId($task["item_id"]);
							$value = $items[$task["item_id"]]->getIdoneNotUsedFalseCount($answer_set);
							break;
						case "verify_correctness_map":
							$value = $items[$task["item_id"]]->verifyCorrectness($value, $items[$task["item_id"]], $task["location"]);
							break;
						case "get_all_answers":
							if ($answers = $value->getAnswers()) $value = $answers;
							else $value = NULL;
							break;
						case "get_answer_readable":
							if (isset($task["id_to_readable"][key($value)])) $value = $task["id_to_readable"][key($value)];
							else $value = null;
							break;
						case "get_answer_readableMcaa":
							reset($value);
							for ($j = 0; $j < $task["number"]; $j++) {
								next($value);
							}		
							if (isset($task["id_to_readable"][key($value)])) $value = $task["id_to_readable"][key($value)];
							else $value = null;
							break;
						case "getValueXXX":
							$value = 'XXX';
							break;
						case "getValueMcaa":
							$j = 1;
							reset($value); 
							while ((key($value) != $task["item_answer_id"]) && ($j!=-1)) {
								$j++;
								if (next($value) == false)
									$j = - 1;
							}
							$value = $j; 
							break;
						case "getAnswerIdMcaa":
							reset($value);
							for ($j = 0; $j < $task["number"]; $j++) {
								next($value);
							}		
							$value = key($value);
							break;
						case "getAnswerId":
							$value = key($value);
							break;
						case "implode":
							$value = implode($task["glue"], $value);
							break;
						default:
							die("Don't know what to do");
					}
					
					if ($value === NULL) {
							break;
						}
					
					//$tasktype = $task['type'];					
					//$_SESSION["tasktype"]["$tasktype"] = isset($_SESSION["tasktype"]["$tasktype"]) ? ($_SESSION["tasktype"]["$tasktype"]+1) : 1;
				}

				$info[$variableName] = $value;
				
			}
			$line = ""; $myCounter = 0;
			foreach (array_values($info) as $value)
			{
				if ($myCounter != 0) {
					$line .= "/";
				}
				$myCounter++;
				$line .= $this->escapeTextValue($value);
			}
			$line .= "\r\n";
			return $line;
	}
	/**
	*exports the choosen variables.
	*/
	function doExport()
	{
		$timestart = time();
		$timeLimit = ini_get("max_execution_time");
		if($timeLimit == 0)
			$timeLimit = 99999;

		$this->init();
		
		if (! isset($_SESSION["RUNNING_EXPORTS"])) {
			$_SESSION["RUNNING_EXPORTS"] = array();
		}
		else {
			foreach (array_keys($_SESSION["RUNNING_EXPORTS"]) as $id) {
				if (isset($_GET["id"]) && $id == $_GET["id"]) {
					continue;
				}
				// delete all running exports that are not currently requested and have a timeout
				if (NOW - $_SESSION["RUNNING_EXPORTS"][$id]["last_contact"] > $timeLimit + 60) {
					$_SESSION["RUNNING_EXPORTS"][$id] = NULL;
				}
			}
		}
		
		//1st Run (before any timeout)
		if (! isset($_GET["id"]))
		{
			$this->initFilters();
			$_SESSION["TEST_RUN_USE_COMMA"] = post("comma") ? TRUE : FALSE;
			$_SESSION["TEST_RUN_TRUNCATE_TEXT"] = post("truncate_text") ? TRUE : FALSE;
			$_SESSION["TEST_RUN_TRUNCATE_TEXT_CHARS"] = min(65536, max(100, (int)post("truncate_text_chars")));
			$_SESSION["TEST_RUN_IGNORE_ANSWERLESS_ITEMS"] = post("ignore_answerless_items") ? TRUE : FALSE;
			
			if (count($this->getTests()) > 1 && $this->filters["testId"] == 0) {
				die("More than one test to export");
			}

			$count = $this->countTestRuns();
			$fields = post("fields");
			if (! $fields || ! $count) {
				$GLOBALS['MSG_HANDLER']->addMsg("pages.testrun.export.no_fields_selected", MSG_RESULT_NEG);
				redirectTo("test_run");
			}

			$exportTime = strftime("%Y-%m-%d_%H-%M", NOW);
			$id = 1 + end(array_keys($_SESSION["RUNNING_EXPORTS"]));
			$export = array(
				"count" => $count,
				"offset" => 0,
				"tr_ids" => $this->getTestRunIds(FALSE, 0, 20000),
				"fields" => $fields,
				"quantities" => array(),
				"rows" => array(),
				"last_contact" => NOW,
				"filters" => $this->filters,
				"use_comma" => $_SESSION["TEST_RUN_USE_COMMA"],
				"truncate_text" => $_SESSION["TEST_RUN_TRUNCATE_TEXT"],
				"truncate_text_chars" => $_SESSION["TEST_RUN_TRUNCATE_TEXT_CHARS"],
				"ignore_answerless_items" => $_SESSION["TEST_RUN_IGNORE_ANSWERLESS_ITEMS"],
				"file_name" => "export_".strftime("%Y-%m-%d_%H-%M", NOW).".txt",
				"file_path" => TM_TMP_DIR."/".$GLOBALS["PORTAL"]->getUserId()."_export_".$id."_".$exportTime.".txt",
				"file_pathSpss" => TM_TMP_DIR."/".$GLOBALS["PORTAL"]->getUserId()."_export_".$id."_".$exportTime.".sps",
				"file_nameSpss" => "export_".strftime("%Y-%m-%d_%H-%M", NOW).".sps",
			);
			
			list($export["structure"], $export["precache"]) = $this->getStructure($this->filters["testId"], $fields);
			$_SESSION["RUNNING_EXPORTS"][$id] = &$export;
	
		
			$line = implode("/", array_keys($export["structure"]))."\r\n";
			

			if (! $fileHandle = fopen($export["file_path"], "wb")) {
				trigger_error("Could not open <code>".$export["file_path"]."</code> for writing", E_USER_ERROR);
				return;
			}
			fwrite ($fileHandle, $line);
			fclose($fileHandle);
			
			$this->doExportSyntax($export,$exportTime,$id);
			
		}
		else {
			$id = $_GET["id"];
			$export = &$_SESSION["RUNNING_EXPORTS"][$id];
		}
		
		if (! @$export) {
			if(@$export == NULL)
			{
				$GLOBALS['MSG_HANDLER']->addMsg("pages.testrun.export.expired", MSG_RESULT_NEG);
				redirectTo("test_run");
			} else
			{
				trigger_error("There is no running export with the ID <b>".htmlspecialchars($_GET["id"])."</b>", E_USER_ERROR);
				return;
			}
		}
		
		$fields = $export["fields"];
		$this->filters = $export["filters"];
		$this->useComma = $export["use_comma"];
		$this->truncateText = $export["truncate_text"];
		$this->truncateTextChars = $export["truncate_text_chars"];
		$this->ignoreAnswerlessItems = $export["ignore_answerless_items"];
		$filterstring = $this->filters;		
		$testRunIds = $export["tr_ids"];
		
		//1st Run or not all test runs have been progressed
		if ($export["offset"] < $export["count"])
		{
			$testRun = $this->testRunList->_getTestRun($testRunIds[$export["offset"]]);
			
			$items = array();
			foreach ($export["precache"]["item_block_ids"] as $itemBlockId) {
				$block = $GLOBALS["BLOCK_LIST"]->getBlockById($itemBlockId);
				foreach ($block->getTreeChildren() as $child) {
						$items[$child->getId()] = $child;
				}
			} 
		} 
		// All test runs have been progressed
		else {
			$testRun = NULL;
		}
		$baseProgress = $export["offset"] / $export["count"];

		if (! @$_GET["send"])
		{
			while (@ob_end_flush()) {}
			ob_implicit_flush(TRUE);

			if ($export["offset"] < $export["count"]) {
				$nextLink = linkTo("test_run", array("action" => "export", "id" => $id), FALSE, TRUE);
			} elseif ($export["offset"] == $export["count"]) {
				$nextLink = linkTo("test_run", array("action" => "export", "id" => $id, "send" => 1), FALSE, TRUE);
				$nextLinkSpss = linkTo("test_run", array("action" => "export", "id" => $id, "send" => 2), FALSE, TRUE);
			} else {
				$nextLink = linkTo("test_run", array("action" => "export", "id" => $id, "send" => 1), FALSE, TRUE);
				$nextLinkSpss = linkTo("test_run", array("action" => "export", "id" => $id, "send" => 2), FALSE, TRUE);
			}

			if ($export["offset"] < $export["count"]) {
				$this->showExportTemplate("header", array("next_link_js" => addslashes($nextLink), "progress" => round($baseProgress *100, 0)), TRUE);
				$this->showExportTemplate("please_wait");
			}
			elseif ($export["offset"] == $export["count"]) {
				$this->showExportTemplate("header", array("progress" => 100), FALSE);
				$this->showExportTemplate("soon_file", array("next_link" => $nextLink , "next_linkSpss" => $nextLinkSpss));
			}
			else {
				$this->showExportTemplate("header", array("next_link_js" => addslashes($nextLink), "progress" => 100), file_exists($export["file_path"]));
				$this->showExportTemplate("close_window");
			}
		}

		$completedTestRuns = 0;
		$trans_remain = T("pages.testrun.remaining");
		//$testRun = current single testRun object
		//evaluate the testrun
		//if the timelimit is reached in the next 5s restart
		while($testRun AND ((time() - $timestart) < ($timeLimit - 5)))
		{			
			if((time() - $timestart) == 0)
				$runningTime = 1;
			else
				$runningTime = time() - $timestart;
				
			$trm = round($completedTestRuns / $runningTime * 60, -1);
			$timeremaining = "";
			if ($trm != 0 && $trm > 100) {
				$timeremaining = @round(($export["count"] - ($export["offset"]+ $completedTestRuns)) / $trm);
				if($timeremaining < 1)
					$timeremaining = "<1";
				$timeremaining = "~ $timeremaining min $trans_remain";
			}
			
			
			//generates the output for 1 testrun
			$line = $this->evalTestrun($testRun, $export, $items);
			
			if (! $fileHandle = fopen($export["file_path"], "ab")) {
				trigger_error("Could not open <code>".$export["file_path"]."</code> for writing", E_USER_ERROR);
				return;
			}

			fwrite ($fileHandle, $line);
			fclose($fileHandle);
			
			$completedTestRuns++;

			$export["offset"]++;

			//get the next testrun
			$testRun = @$this->testRunList->_getTestRun($testRunIds[$export["offset"]]);
			//update progress bar
			$this->showExportTemplate("update_progress_bar", array("progress" => round(($export["offset"] / $export["count"]) *100, 0), "trm" => " - $trm TR/min $timeremaining"));			
			
			if ($completedTestRuns == 1) {
				$this->showExportTemplate("set_finish");
			}
		}
		
		
		//if not finished and file requested
		if (! @$_GET["send"])
		{
			if ($export["offset"] < $export["count"]) {
				$export["offset"]++;
			}
			$this->showExportTemplate("set_finish");
			$this->showExportTemplate("footer");
			return;
		}
		else
		{
			//if finished and file requested
			$newExport = array();
			foreach (array("count", "offset", "file_path", "file_name", "last_contact","file_pathSpss","file_nameSpss") as $key) {
				$newExport[$key] = $export[$key];
			}
			//add log entry
			EditLog::addEntry(LOG_OP_DATA_EXPORT, $newExport["count"], $filterstring);
			
			libLoad("http::sendFile");
			if ($_GET["send"] == 1 )
				sendFile($newExport["file_path"], "text/plain", $newExport["file_name"]);
			if ($_GET["send"] == 2)
				sendFile($newExport["file_pathSpss"], "text/plain", $newExport["file_nameSpss"]);
		}
	}

	/**
	*Create a SPSS-Syntax file
	*
	*@param $export Data to export. Contains the structure for the SPSS syntax
	*@param $exportTime Time wich the Syntax file was exported
	*@param $id Id of the export. Only for tmp file.
	*/
	function doExportSyntax($export, $exportTime, $id) {
		$SpssStructure = $_SESSION["SPSS_STRUCTURE"];
		$missings = array();
		//create the header 
		$sytaxLine = "** ANSWER ENCODING INFO:\n\t".
					 "**    _v : AnswerValue - Encoded form of the selected answer (e.g. 3 for the third answer, 1/0 for checked/not checked) \n\t".
					 "**    _n : AnswerNumber - same as _v \n\t".
					 "**    _r : ReadableAnswer - User input for text items or the text of the chosen answer \n\t".
					 "**    _c : ItemCorrectness - Whether the answer was correct (1) or not correct (0). There are three types of missing data:	\n\t".
					 "**         (Empty) if the correctness is indeterminable, (98) if item was shown but not filled out and user jumped to next item, (99) if item was shown but answer timeout \n\t".
					 "**    _d : ItemDuration - How long the user needed to answer the item \n\t".
					 "**    _t : ItemTimeout - Whether the item was automatically aborted (1) or not (0) \n".
 					 " \r\n";
		
		$sytaxLine .= "GET DATA  /TYPE = TXT\r\n";
		$sytaxLine .= " /FILE = 'export_".$exportTime.".txt'             /* path to the data text file.*/ \r\n";
		$sytaxLine .= " /DELCASE = LINE\r\n";
		$sytaxLine .= " /DELIMITERS = \"/\" \r\n";
		$sytaxLine .= " /QUALIFIER = \"'\"\r\n";
		$sytaxLine .= " /ARRANGEMENT = DELIMITED\r\n";
		$sytaxLine .= " /FIRSTCASE = 2\r\n";
		$sytaxLine .= " /IMPORTCASE = ALL\r\n";
		$sytaxLine .= " /VARIABLES =\r\n";
		
		//create for every variable the type for SPSS (e.g. integer, string, ...)
		foreach ($export["structure"] as $key => $variable)
		{	
			if (isset($SpssStructure[$key])) {
				$variableName = $key;
				if ((strpos($variableName, "**c**") != false)) {
					$variableName = str_replace("**","",$variableName);
					$missings[] = $variableName;
				}
				$variableName = preg_replace("/[^a-zA-Z0-9-_]/", "", $variableName);
				$sytaxLine .= " ".$variableName." ".$SpssStructure[$key]."\r\n";
			}
		}
		$sytaxLine .= ".\r\n";
		$sytaxLine .= "CACHE.\r\n";
		$sytaxLine .= "EXECUTE.\r\n";
		$sytaxLine .= "DATASET NAME DatenSet1 WINDOW=FRONT.\r\n";
		
		//create labels for each variable (variable labels)
		$sytaxLine = $sytaxLine. "VARIABLE LABELS\r\n";
		foreach ($export["structure"] as $key => $variable)
		{	
			if ((isset($SpssStructure[$key])) && isset($this->labelVariable[$key]) && ($this->labelVariable[$key] !='')) {
				$variableName = $key;
				//$variableName = str_replace("**","",$variableName);
				$variableName = preg_replace("/[^a-zA-Z0-9-_]/", "", $variableName);
				
				$label = strip_tags($this->labelVariable[$key]);
				$label = preg_replace("/({)(\w+)([^}]*})/e","",$label);
				$label = str_replace("&nbsp;"," ",$label);
				$label = str_replace("\n"," ",$label);
				$label = preg_replace("/\s+/"," ",$label);
				$label = str_replace(".","",$label);
				$label = html_entity_decode($label);
				
				$label = substr($label, 0, 240 - strlen($variableName));
				
				$sytaxLine .= $variableName." '".$label."' \r\n";
			}
		}
		$sytaxLine .= " .\r\n";
		

		$sytaxLine = $sytaxLine. "VALUE LABELS\r\n";
		foreach ($export["structure"] as $key => $variable)
		{	
			if ((isset($SpssStructure[$key])) && isset($this->labelValues[$key])) {
				$variableName = $key;
				$variableName = str_replace("**","",$variableName);
				
				$label = strip_tags($this->labelValues[$key]);
				$label = preg_replace("/({)(\w+)([^}]*})/e","",$label);
				$label = str_replace("&nbsp;"," ",$label);
				$label = str_replace("\n"," ",$label);
				$label = preg_replace("/\s+/"," ",$label);
				$label = str_replace("--n--","\r\n",$label);
				
				$sytaxLine .= $variableName." ".$label."/ \r\n";
			}
		}
		$sytaxLine .= " .\r\n";
		
		//Set for the correctnes variables the missing values to  98 ,99 if this variable was selected
		if (count($missings)>0) {
			$sytaxLine .= "MISSING VALUES ";
			foreach ($missings as $miss) {
				$sytaxLine .= " ".$miss."\r\n";
			}
			$sytaxLine .= " (97, 98, 99) \r\n";
			$sytaxLine .= ".\r\n";
		}
		
		$file_path = TM_TMP_DIR."/".$GLOBALS["PORTAL"]->getUserId()."_export_".$id."_".$exportTime.".sps";
		
		if (! $fileHandle = fopen($file_path, "wb")) {
			trigger_error("Could not open <code>".$file_path."</code> for writing", E_USER_ERROR);
			return;
		}
		fwrite($fileHandle, $sytaxLine);
		fclose($fileHandle);
	}
	
	/* 
	generate the keyValues for the assoziative structure array when the key value depends on the ItemName
	This is importent for the SPSS syntaxfile.
	*/
	function generateKey($fieldTitle, $node, $structure, $nodeType = "ITEM_TITLES", $labelValues = 0) {

		if (($nodeType!="ITEM_TITLES") && ($nodeType!="INFO_TITLES") && ($nodeType!="FEEDBACK_TITLES")) {
	
			exit;
		}
		$haystack = $this->twiceTitle[$nodeType];
		
		$fieldTitle = str_replace("AnswerValue(v)","v",$fieldTitle);
		$fieldTitle = str_replace("AnswerNumber(n)","n",$fieldTitle);
		$fieldTitle = str_replace("ItemCorrectness(c)","**c**",$fieldTitle);
		$fieldTitle = str_replace("ReadableAnswer(r)","r",$fieldTitle);
		$fieldTitle = str_replace("ItemDuration(d)","d",$fieldTitle);
		$fieldTitle = str_replace("ItemTimeout(t)","t",$fieldTitle);
		//replace forbidden variable names and chars for SPSS
		$itemTitle =  $node->getTitle();
		$itemTitle = str_replace("-","_",$itemTitle);
		$itemTitle = str_replace(" ","",$itemTitle);
		$itemTitle = str_replace("/","_",$itemTitle);
		$itemTitle = str_replace(".","",$itemTitle);
		$itemTitle = str_replace("<","",$itemTitle);
		$itemTitle = str_replace(">","",$itemTitle);
		
		$itemTitle = str_replace("ä","ae",$itemTitle);
		$itemTitle = str_replace("Ä","AE",$itemTitle);
		$itemTitle = str_replace("ö","oe",$itemTitle);
		$itemTitle = str_replace("Ö","OE",$itemTitle);
		$itemTitle = str_replace("ü","ue",$itemTitle);
		$itemTitle = str_replace("Ü","UE",$itemTitle);
		$itemTitle = str_replace("ß","ss",$itemTitle);
		$itemTitle = str_replace(",","_",$itemTitle);
		$itemTitle = str_replace("?","_",$itemTitle);
		$itemTitle = str_replace("&","_and_",$itemTitle);
		//SPSS Variables must not have a number in the first caracter
		if ((is_numeric($itemTitle[0])))
			$itemTitle = str_replace($itemTitle, "I_".$itemTitle, $itemTitle);
		
		$key = $itemTitle."_".$fieldTitle;
		$n=1;
		//if more items have the same name, create a number behind the first keyValue and delete the old
		if(in_array($node->getTitle(), $haystack)) {
			$itemTitle =  $node->getTitle();
			$itemTitle = str_replace("-","_",$itemTitle);
			$itemTitle = str_replace(" ","",$itemTitle);
			$itemTitle = str_replace("/","_",$itemTitle);
			$itemTitle = str_replace(".","",$itemTitle);
			$key = $itemTitle."_".$fieldTitle."_1";
		}
		//if key for the array exist, create a new one with a number
		while (isset($structure[$key])) {
			$n++;
			$itemTitle =  $node->getTitle();
			$itemTitle = str_replace("-","_",$itemTitle);
			$itemTitle = str_replace(" ","",$itemTitle);
			$itemTitle = str_replace("/","_",$itemTitle);
			$itemTitle = str_replace(".","",$itemTitle);
			$key = $itemTitle."_".$fieldTitle."_".$n;
		}		
		
		if ($nodeType == "ITEM_TITLES") {
			$question = $node->getQuestion();
			$files = $node->getMediaFiles($question);
		
			if ($files != '') {
				$this->labelVariable[$key] = $question." ".$files;
				$this->labelVariable[$key] = str_replace(".","_",$this->labelVariable[$key]);
			}
			else
				$this->labelVariable[$key] = $question;
			$this->labelVariable[$key] = strip_tags($this->labelVariable[$key]);
			$this->labelVariable[$key] = str_replace("/"," ",$this->labelVariable[$key]);
			$this->labelVariable[$key] = str_replace(".","",$this->labelVariable[$key]);
		}
		//1 = Normal Label
		if ($labelValues == 1) {
			$this->labelValues[$key] = "";
			$i = 1;
			$readableAnswer = '';
			foreach ($node->getChildren() as $answer) {
				$readableAnswer = $answer->getAnswer();
				$files = $node->getMediaFiles($readableAnswer);
				if ($files != '') {
					$readableAnswer = $readableAnswer." ".$files;
					$readableAnswer = str_replace(".", "_", $readableAnswer);
				}
				$readableAnswer = strip_tags($readableAnswer);
				$readableAnswer = str_replace("/"," ",$readableAnswer);
				$readableAnswer = str_replace(".","",$readableAnswer);
			
				$readableAnswer = html_entity_decode($readableAnswer);
				$readableAnswer = substr($readableAnswer, 0, 120);
				$this->labelValues[$key].= $i." '".$readableAnswer."' --n--";
				$i++;
			}
		}
		//2 = correctnes label
		if ($labelValues == 2) {
			$this->labelValues[$key] = "0 'incorrect' 1 'correct' 98 'Seen but not answered' 99 'Seen but timeout'";
		}
		return $key;
	}

	/*
	for mapItem and mcma and mcaa Item
	*/
	function generateKeyExp($fieldTitle, $node, $structure, $correctValNum, $labelValues = 0) {	
			
		$haystack = $this->twiceTitle["ITEM_TITLES"];
		
		$fieldTitle = str_replace("AnswerValue(v)","v",$fieldTitle);
		$fieldTitle = str_replace("AnswerNumber(n)","n",$fieldTitle);
		$fieldTitle = str_replace("ItemCorrectness(c)","c",$fieldTitle);
		$fieldTitle = str_replace("ReadableAnswer(r)","r",$fieldTitle);
		$fieldTitle = str_replace("ItemDuration(d)","d",$fieldTitle);
		$fieldTitle = str_replace("ItemTimeout(t)","t",$fieldTitle);
		//replace forbidden variable names and chars for SPSS
		$itemTitle =  $node->getTitle();
		$itemTitle = str_replace("-","_",$itemTitle);
		$itemTitle = str_replace(" ","",$itemTitle);
		$itemTitle = str_replace("/","_",$itemTitle);
		$itemTitle = str_replace(".","",$itemTitle);
		
		$itemTitle = str_replace("ä","ae",$itemTitle);
		$itemTitle = str_replace("Ä","AE",$itemTitle);
		$itemTitle = str_replace("ö","oe",$itemTitle);
		$itemTitle = str_replace("Ö","OE",$itemTitle);
		$itemTitle = str_replace("ü","ue",$itemTitle);
		$itemTitle = str_replace("Ü","UE",$itemTitle);
		$itemTitle = str_replace("ß","ss",$itemTitle);
		$itemTitle = str_replace(",","_",$itemTitle);
		$itemTitle = str_replace("?","_",$itemTitle);
		$itemTitle = str_replace("&","_and_",$itemTitle);
		$itemTitle = str_replace("<","",$itemTitle);
		$itemTitle = str_replace(">","",$itemTitle);
		if ((is_numeric($itemTitle[0])))
			$itemTitle = str_replace($itemTitle, "I_".$itemTitle, $itemTitle);
			
		$key = $itemTitle."_".$fieldTitle."_".$correctValNum;
			
		//if more items have the same name, create a number behind the first keyValue and delete the old
		if(in_array($node->getTitle(),$haystack)) {
			$itemTitle =  $node->getTitle();
			$itemTitle = str_replace("-","_",$itemTitle);
			$itemTitle = str_replace(" ","",$itemTitle);
			$itemTitle = str_replace("/","_",$itemTitle);
			$itemTitle = str_replace(".","",$itemTitle);
			$key = $itemTitle."_".$correctValNum."_".$fieldTitle."_1";
		}
		$n=1;
			
		//if key for the array exist, create a new one with a number
		while (isset($structure[$key])) {
			$n++;
			$itemTitle =  $node->getTitle();
			$itemTitle = str_replace("-","_",$itemTitle);
			$itemTitle = str_replace(" ","",$itemTitle);
			$itemTitle = str_replace("/","_",$itemTitle);
			$itemTitle = str_replace(".","",$itemTitle);
			$key = $itemTitle."_".$correctValNum."_".$fieldTitle."_".$n;
		}
		
		$this->labelVariable[$key] = $node->getQuestion();
		$this->labelVariable[$key] = strip_tags($this->labelVariable[$key]);
		$this->labelVariable[$key] = str_replace("/"," ",$this->labelVariable[$key]);
		$this->labelVariable[$key] = str_replace(".","",$this->labelVariable[$key]);
		
		if ($labelValues == 1) {
			$this->labelValues[$key] = "";
			$i = 1;
			foreach ($node->getChildren() as $answer) {
				$readableAnswer = $answer->getAnswer();
				
				$readableAnswer = strip_tags($readableAnswer);
				$readableAnswer = str_replace("/"," ",$readableAnswer);
				$readableAnswer = str_replace(".","",$readableAnswer);
				$this->labelValues[$key].= $i." '".$readableAnswer."' --n--";
				$i++;
			}
		}	
		return $key;
	}
}
