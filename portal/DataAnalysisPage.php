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
 * Base class for all pages dealing with user administration
 *
 * @package Portal
 */
 
 
class DataAnalysisPage extends Page
{
	var $numberOfTests;

	function run($actionName = NULL)
	{	
		$this->checkAllowed('export', true, NULL);
		parent::run($actionName);
	}

	function initTemplate($activeTab)
	{
		$tabs = array(
			"export_data" => array("title" => "pages.data_analysis.tabs.export_data", "link" => linkTo("test_run", array("action" => "show_overview"))),
			"result" => array("title" => "pages.data_analysis.tabs.show_results", "link" => linkTo("test_runresult", array("action" => "result")))		
		);

		$this->initTabs($tabs, $activeTab);
	}
	
	function initTemplateCronjobs($activeTab)
	{
		$tabs = array(
				"cronjob_status" => array("title" => "pages.data_analysis.tabs.cronjob_status", "link" => linkTo("cronjob_status", array("action" => "show_status"))),	
				"survey" => array("title" => "pages.data_analysis.tabs.test_survey", "link" => linkTo("test_survey", array("action" => "filter"))),	
				"cronjob_settings" => array("title" => "pages.data_analysis.tabs.cronjob_settings", "link" => linkTo("cronjob_settings", array("action" => "show_settings")))
		);
	
		$this->initTabs($tabs, $activeTab);
	}
	
	function init()
	{
		$GLOBALS["PORTAL"]->startSession();

		// Load the Test Run List
		require_once(CORE."types/TestRunList.php");
		$this->testRunList = new TestRunList();
	}

	function initFilters($callbackFunction = "test_run")
	{
		// Initialize the filters
		if (! isset($_SESSION["TEST_RUN_FILTERS"])) {
			$_SESSION["TEST_RUN_FILTERS"] = array(
				"testId" => 0,
				"groupName" => NULL,
				"accessType" => NULL,
				"completed" => NULL,
				"relation" => "greater_than",
				"completed_percent" => NULL,
				"testRunID" => 0,
				"test_date" => NULL,
				"test_date2" => NULL,
				"date_relation" => "greater_than",
			);
		}

		$filters = &$_SESSION["TEST_RUN_FILTERS"];

		$filters["testId"] = post("test_id", $filters["testId"]);
		
		$filters["groupName"] = post("group_name", $filters["groupName"]);
		if ($filters["groupName"][0] == "*") {
			$filters["groupName"] = NULL;
		}
		
		$filters["accessType"] = post("access_type", $filters["accessType"]);
		if ($filters["accessType"] == "*") {
			$filters["accessType"] = NULL;
		}
		$filters["completed"] = post("completed", $filters["completed"]);
		if ($filters["completed"] == "*") {
			$filters["completed"] = NULL;
		}
		$filters["completed_percent"] = post("completed_percent", $filters["completed_percent"]);
		if (!($filters["completed_percent"] >= 0) || !($filters["completed_percent"] <= 100)) {
			$filters["completed_percent"] = NULL;
		}
		$filters["relation"] = post("relation", $filters["relation"]);
		$filters["testRunID"] = post("testrun_id",get("test_run", $filters["testRunID"]));
		
		$filters["test_date"] = post("test_date", $filters["test_date"]);
		$filters["test_date2"] = post("test_date2", $filters["test_date2"]);
		$filters["date_relation"] = post("date_relation", $filters["date_relation"]);

		$error = false;

		if (post("filters_set", FALSE)) {
			$_SESSION["TEST_RUN_PAGE"] = NULL;
			if($error)
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.testrun.filters.testrun_id.wrong_input", MSG_RESULT_NEG);
				redirectTo($callbackFunction, array("resume_messages" => "true"));
			}
			else
			{
				redirectTo($callbackFunction);
			}
		}

		// Validity and security checks

		if (! $GLOBALS["BLOCK_LIST"]->existsBlock($filters["testId"])) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.testmake.test_not_found', MSG_RESULT_NEG);
			$filters["testId"] = 0;
		} else {
			$block = $GLOBALS['BLOCK_LIST']->getBlockById($filters['testId']);
			if (!$this->checkAllowed('edit', false, $block)) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.testmake.test_not_found', MSG_RESULT_NEG);
				$filters["testId"] = 0;
			}
		}

		$trl = new TestRunList();
		if ($filters['testRunID'] && !$trl->existsTestRunByID($filters['testRunID'])) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.testmake.test_run_not_found', MSG_RESULT_NEG);
			$filters['testRunID'] = 0;
		} elseif ($filters['testRunID']) {
			$tr = $trl->getTestRunById($filters['testRunID']);
			$block = $GLOBALS['BLOCK_LIST']->getBlockById($tr->getTestId());
			if (!$this->checkAllowed('edit', false, $block)) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.testmake.test_run_not_found', MSG_RESULT_NEG);
				$filters["testRunID"] = 0;
			}
		}

		if (!is_numeric($filters["completed_percent"]))
			$filters["completed_percent"] = 0;

		$this->filters = &$filters;
	}
	
	/**
	* this function get all testruns or filtered testruns 
	*/
	function getTestRuns($orderDescending = TRUE, $pageOffset = 0, $pageLength = NULL)
	{
		if ($pageOffset < 0) {
			$pageOffset = 0;
		}
		
		if (!$this->filters["groupName"]) {
			return $this->testRunList->getTestRunsByFilter($this->filters["testId"], $this->filters["groupName"], $this->filters["accessType"], 
				$this->filters["completed"], $this->filters["completed_percent"], $this->filters["relation"], $this->filters["testRunID"], 
				$this->filters["test_date"], $this->filters["test_date2"], $this->filters["date_relation"], $pageOffset, $pageLength, $orderDescending);
			}
		else {
			$testRuns = array();
			for($i=0; $i < count($this->filters["groupName"]); $i++) {
			 $testRuns = array_merge($testRuns, $this->testRunList->getTestRunsByFilter($this->filters["testId"], $this->filters["groupName"][$i], $this->filters["accessType"], 
				$this->filters["completed"], $this->filters["completed_percent"], $this->filters["relation"], $this->filters["testRunID"], 
				$this->filters["test_date"], $this->filters["test_date2"], $this->filters["date_relation"], $pageOffset, $pageLength, $orderDescending));
				}
			return $testRuns;
			}
	}
	
	function getTestRunIds($orderDescending = TRUE, $pageOffset = 0, $pageLength = NULL)
	{
		if ($pageOffset < 0) {
			$pageOffset = 0;
		}
		if (!$this->filters["groupName"]) {
			return $this->testRunList->getTestRunIdsByFilter($this->filters["testId"], $this->filters["groupName"], $this->filters["accessType"], 
				$this->filters["completed"], $this->filters["completed_percent"], $this->filters["relation"], $this->filters["testRunID"], 
				$this->filters["test_date"], $this->filters["test_date2"], $this->filters["date_relation"], $pageOffset, $pageLength, $orderDescending);
			}
		else {
			$testRunIds = array();
			for($i=0; $i < count($this->filters["groupName"]); $i++) {
			 $testRunIds = array_merge($testRunIds, $this->testRunList->getTestRunIdsByFilter($this->filters["testId"], $this->filters["groupName"][$i], $this->filters["accessType"], 
				$this->filters["completed"], $this->filters["completed_percent"], $this->filters["relation"], $this->filters["testRunID"], 
				$this->filters["test_date"], $this->filters["test_date2"], $this->filters["date_relation"], $pageOffset, $pageLength, $orderDescending));
				}
			return $testRunIds;
			}
	}

	function getJoinedTestRuns($orderDescending = TRUE)
	{
		return $this->testRunList->getJoinedTestRunsByFilter($this->filters["testId"], $this->filters["accessType"], $this->filters["completed"], $orderDescending);
	}

	
	function countTestRuns()
	{
		if($this->filters["testRunID"] != 0)
		{
			if($this->testRunList->existsTestRunByID($this->filters["testRunID"])) return 1;
		} else
		{
			if (!$this->filters["groupName"]) {
				return $this->testRunList->countTestRunsByFilter($this->filters["testId"], $this->filters["groupName"], $this->filters["accessType"], 
						$this->filters["completed"], $this->filters["completed_percent"], $this->filters["relation"], $this->filters["test_date"], $this->filters["test_date2"], $this->filters["date_relation"]);
			}
			else {
			$count = 0;
			for($i=0; $i < count($this->filters["groupName"]); $i++) {
				$count = $count + $this->testRunList->countTestRunsByFilter($this->filters["testId"], $this->filters["groupName"][$i], $this->filters["accessType"], 
						$this->filters["completed"], $this->filters["completed_percent"], $this->filters["relation"], $this->filters["test_date"], $this->filters["test_date2"], $this->filters["date_relation"]);
				}
			return $count;
			}
		}
	}
	
	/**
	*get all tests
	*/
	function getTests()
	{
		/*$tests = array();
		foreach ($this->testRunList->getAvailableTests() as $testId) {
			if ($GLOBALS["BLOCK_LIST"]->existsBlock($testId)) {
				$tests[] = $testId;
			}
		}
		return $tests;*/
		return $this->testRunList->getAvailableTests();
	}
	
	
	function keepFilterSettings()
	{
		libLoad("utilities::shortenString");
		if($this->filters["testRunID"] != "")
			{
				$checkValues = array(
					array("pages.testrun.testrun_id", $this->filters["testRunID"], array(CORRECTION_CRITERION_NUMERIC_INTEGER_POS => 1)),
				);

				$tmp = getCorrectionMessage(T($checkValues[0][0]), $checkValues[0][1], $checkValues[0][2]);
				if(count($tmp) > 0)
				{
					$this->tpl->touchBlock("correction_".substr($checkValues[0][0], (strrpos($checkValues[0][0], ".") + 1)));
					$this->filters["testRunID"] = 0;
				}
			}

			// Show the Test filter
			$tests = array();
			foreach ($this->getTests() as $testId) {
				$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testId, BLOCK_TYPE_CONTAINER);
				$title = $test->getTitle();
				$tests[$testId] = $title != "" ? $title : "(empty)";
			}
			uasort($tests, "strnatcasecmp");
			
				$query = mysql_query("SELECT count(test_id) FROM ".DB_PREFIX."test_runs");
				$idCount = mysql_fetch_array($query);
				$this->tpl->setVariable("select_test_id", 0);
				$this->tpl->setVariable("select_test_title", T("pages.testrun.all_tests")." (".$idCount[0].")");						
				$this->tpl->parse("select_test");

			//$this->tpl->setVariable("select_test_first", $this->filters["testId"] == 0 ? " selected" : "");
		
			
			foreach ($tests as $testId => $testTitle)
			{	
				$query = mysql_query("SELECT count(test_id) FROM ".DB_PREFIX."test_runs WHERE test_id=$testId");
				$idCount = mysql_fetch_array($query);
				
				$testTitle = shortenString($testTitle,64);
				$this->tpl->setVariable("select_test_id", $testId);
				
				$this->tpl->setVariable("select_test_title", $testTitle." (".$idCount[0].")");
				//$this->tpl->setVariable("select_test_title", $testTitle." (".$row['COUNT(id)'].")");
				
				$this->tpl->setVariable("select_test_current", $this->filters["testId"] == $testId ? " selected" : "");
				
				$this->tpl->parse("select_test");
			}
			
			$userList = new UserList();
			$groups = $userList->getGroupList();
			$this->tpl->setVariable("select_group_first", $this->filters["groupName"] == NULL ? " selected" : "");
			foreach ($groups as $group)
			{
				$groupName = $group->get('groupname');
				$groupName = shortenString($groupName,64);
				$this->tpl->setVariable("select_group_name", $groupName);
				$this->tpl->setVariable("select_group_title", $groupName);
				for($i=0; $i < count($this->filters["groupName"]); $i++) {
					if($this->filters["groupName"][$i] == $groupName)
						$this->tpl->setVariable("select_group_current"," selected");
				}
				$this->tpl->parse("select_group");
			}
			

			// Show the Access Type filter
			$accessTypes = $this->testRunList->getAvailableAccessTypes();

			$this->tpl->setVariable("select_access_type_first", $this->filters["accessType"] === NULL ? " selected" : "");
			foreach ($accessTypes as $accessType)
			{
				$this->tpl->setVariable("select_access_type", $accessType);
				$this->tpl->setVariable("select_access_type_description", $accessType != "" ? T("pages.testrun.access_type.".$accessType, array(), $accessType) : T("generic.unknown"));
				$this->tpl->setVariable("select_access_type_current", $this->filters["accessType"] === $accessType ? " selected" : "");
				$this->tpl->parse("select_access_type");
			}

			// Show the Completeness filter
			$completed = $this->filters["completed"];
			if (! isset($completed)) {
				$completed = "any";
			}
			$selected = array(
				"any" => "",
				"all" => "",
				"all_required" => "",
				"not_all_required" => "",
			);
			$selected[$completed] = " selected=\"selected\"";
			foreach ($selected as $name => $value) {
				$this->tpl->setVariable("select_completed_".$name, $value);			
			}
			
			$completed_percent = $this->filters["completed_percent"];
			
			$relation = $this->filters["relation"];
			if (! isset($relation)) {
				$relation = "greater_than";
			}
				
			$selected = array(
				"greater_than" => "",
				"equal_as" => "",
				"less_than" => "",
			);

			$selected[$relation] = " selected=\"selected\"";
			foreach ($selected as $name => $value) {
				$this->tpl->setVariable("relation_".$name, $value);
			}
			
			$completed_percent = $this->filters["completed_percent"];
			if (! isset($completed_percent)) {
				$completed_percent = NULL;
			}
			$this->tpl->setVariable("completed_percent", $completed_percent);
			
			
			$selected = array(
				"greater_than" => "",
				"equal_as" => "",
				"less_than" => "",
				"between" => "",
			);
			
			$date_relation = $this->filters["date_relation"];
			if (! isset($date_relation)) {
				$date_relation = "greater_than";
			}

			$selected[$date_relation] = " selected=\"selected\"";
			foreach ($selected as $name => $value) {
				$this->tpl->setVariable("relation_date_".$name, $value);
			}
			
			$testDate = $this->filters["test_date"];
			$testDate2 = $this->filters["test_date2"];
			
			if (isset($testDate))
				$this->tpl->setVariable("test_date", $testDate);
			if (isset($testDate2))
				$this->tpl->setVariable("test_date2", $testDate2);
			
			// Show the id filter
			if($this->filters["testRunID"] != 0) {
				$this->tpl->setVariable("input_testrun_id", $this->filters["testRunID"]);
			} else {
				$this->tpl->setVariable("input_testrun_id", "");
		}
		
		$this->numberOfTests = count($tests);
	}

}

