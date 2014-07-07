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


/**
 * Include the TestRun class
 */
require_once(CORE."types/TestRun.php");
/**
 * Include the TestRunBlock class
 */
require_once(CORE."types/TestRunBlock.php");

/**
 * Provides access to test runs
 *
 * Use <kbd>{@link addTestRun()}</kbd> to create a new test run and the various <kbd>getTestRun*</kbd> functions to access stored test runs.
 *
 * <kbd>{@link TestRun}</kbd> provides meta data about test runs and stores sets of <kbd>{@link GivenAnswer}</kbd>s, wrapped by <kbd>{@link GivenAnswerSet}</kbd>.
 * Each of these sets represents a page shown to the user; not all of them refer to actual items.
 * If so, an answer set does not contain any answers.
 *
 * @package Core
 * @see TestRun
 * @see GivenAnswerSet
 * @see GivenAnswer
 */
class TestRunList
{
	/**#@+
	 * @access private
	 */
	var $db;
	/**#@-*/

	/**
	 * Constructor
	 */
	function TestRunList()
	{
		$this->db = &$GLOBALS['dao']->getConnection();
	}

	/**
	 * Prepares a <kbd>{@link TestRun}</kbd> object and stores it in the database
	 *
	 * @param int The ID of the test to run
	 * @param string The path to the test to run (not really used ATM)
	 * @param int The ID of the user which started the test
	 * @param int The time the test run was started
	 * @param string Access Type (one of "direct", "portal" or "preview")
	 * @param int Number of available pages
	 * @param int Number of available items
	 * @param int Number of available required items
	 * @return TestRun The new <kbd>{@link TestRun}</kbd> instance
	 */
	function &addTestRun($testId, $testPath, $userId = 0, $startTime = NOW, $accessType = "", $availablePages = NULL, $availableItems = NULL, $availableRequiredItems = NULL)
	{
		if (! $userId) {
			$userId = 0;
		}

		$db = &$GLOBALS['dao']->getConnection();

		$id = $db->nextId(DB_PREFIX."test_runs");
		$query = $db->query("INSERT INTO ".DB_PREFIX."test_runs (id, test_id, test_path, user_id, start_time, access_type, available_pages, available_items, available_required_items, duration, step) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($id, $testId, $testPath, $userId, $startTime, $accessType, $availablePages, $availableItems, $availableRequiredItems, 0, 0));

		if (PEAR::isError($query)) {
			return NULL;
		}

		$testRun = new TestRun($this, $id);
		return $testRun;
	}

	/**
	 * Returns an array of test runs identified by a test ID
	 * @param int The ID of the test of interest
	 * @param boolean Whether to order the list by ID in descending (<kbd>TRUE</kbd>) or ascending (<kbd>FALSE</kbd>) order
	 * @return TestRun[] An array of <kbd>{@link TestRun}</kbd> instances
	 */
	function getTestRunsForTest($testId, $orderDescending = TRUE, $uptotime = NULL)
	{
		$params = array($testId);
		$time = '';
		if ($uptotime !== NULL) {
			$time = "AND start_time <=? ";
			$params[] = $uptotime;
		}
		$order = $orderDescending ? "DESC" : "ASC";
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."test_runs WHERE test_id=? ".$time."ORDER BY id ".$order, $params);
		return $this->_getTestRuns($query);
	}

	/**
	 * Returns an array of test runs identified by a user ID
	 * @param int The ID of the user of interest
	 * @param boolean Whether to order the list by ID in descending (<kbd>TRUE</kbd>) or ascending (<kbd>FALSE</kbd>) order
	 * @return TestRun[] An array of <kbd>{@link TestRun}</kbd> instances
	 */
	function getTestRunsForUser($userId, $orderDescending = TRUE)
	{
		$order = $orderDescending ? "DESC" : "ASC";
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."test_runs WHERE user_id=? ORDER BY id ".$order, array($userId));
		return $this->_getTestRuns($query);
	}

	/**
	 * Returns the TestId that associated with the TestRun
	 * @param int The id of the TestRun
	 * @return int The id of the Test
	 */
	static function getTestForTestRun($testRunId)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$query = "SELECT test_id from ".DB_PREFIX."test_runs WHERE id=?";
		if ($res = $db->getOne($query, array($testRunId))) return $res;
		else return false;

	}

	/**
	 * Returns IDs of test runs we may consider using for skipping the current block in a test participation.
	 *
	 * @param int User ID
	 * @param Block Block object
	 * @param int ID of the test that the returned test runs should NOT relate to
	 * @param boolean Whether to order the list by ID in descending (<kbd>TRUE</kbd>) or ascending (<kbd>FALSE</kbd>) order
	 */
	function getSourceRunsForSkipping($userId, $block, $testId, $orderDescending = TRUE)
	{
		$parentIds = array();
		$order = $orderDescending ? "DESC" : "ASC";

		// Skip an entire subtest?
		if ($block->isContainerBlock()) {
			$query = $this->db->query("SELECT tr.id FROM ".DB_PREFIX."test_runs AS tr JOIN ".DB_PREFIX."test_run_blocks AS trb ON (tr.id = trb.test_run_id) WHERE tr.test_id <> ? AND tr.user_id = ? AND tr.access_type <> 'preview' AND trb.subtest_id = ? ORDER BY tr.id $order", array($testId, $userId, $block->getId()));
			$ids = $this->_getTestRunIds($query);

			$resultIds = array();
			foreach ($ids as $id) {
				$tr = $this->getTestRunById($id);
				$trb = $tr->getTestRunBlockBySubtest($block);
				if (count($trb->getGivenAnswerSets()) > 0 && (count($trb->getStructure()) / count($trb->getGivenAnswerSets()) == 1)) $resultIds[] = $id;
			}
			return $resultIds;
		}

		foreach ($block->getParents() as $parent) {
			$parentIds[] = intval($parent->getId());
		}
		$parentIdList = '('. implode(', ', $parentIds) .')';

		$query = $this->db->query("SELECT tRuns.id FROM ".DB_PREFIX."test_runs AS tRuns INNER JOIN ".DB_PREFIX."test_run_blocks AS trBlocks ON tRuns.id=trBlocks.test_run_id WHERE tRuns.test_id<>? AND tRuns.user_id=? AND tRuns.access_type <> 'preview' AND (trBlocks.subtest_id IN $parentIdList OR tRuns.test_id IN $parentIdList) ORDER BY tRuns.id ".$order, array($testId, $userId));
		$ids = $this->_getTestRunIds($query);

		// Filter out those that don't have our block
		$resultIds = array();
		foreach ($ids as $id) {
			$tr = $this->getTestRunById($id);
			$gas = $tr->getGivenAnswerSetsByBlockId($block->getId());
			// Consider only those testRuns that contain answerSets for the entire block
			if (count($gas) >= 0)
			if (count($gas) < count($block->getItemIds())) continue;

			$resultIds[] = $id;
		}
		return $resultIds;

	}

	/**
	 * Returns an array of test runs identified by a user ID, an access type and optionally a test ID
	 * @param int The ID of the user of interest
	 * @param string The access type of the user of interest
	 * @param int The ID of the the test of interest
	 * @param boolean Whether to order the list by ID in descending (<kbd>TRUE</kbd>) or ascending (<kbd>FALSE</kbd>) order
	 * @return TestRun[] An array of <kbd>{@link TestRun}</kbd> instances
	 */
	function getTestRunsForUserAndAccessTypes($userId, $accessTypes, $testId = NULL, $orderDescending = TRUE)
	{
		$sql = "SELECT * FROM ".DB_PREFIX."test_runs WHERE user_id=? ";
		$params = array($userId);

		if ($testId !== NULL) {
			$sql .= "AND test_id=? ";
			$params[] = $testId;
		}

		$sql .= "AND (";

		$first = TRUE;
		foreach ($accessTypes as $accessType) {
			if ($first) {
				$first = FALSE;
			} else {
				$sql .= " OR ";
			}
			$sql .= "access_type=?";
			$params[] = $accessType;
		}

		$sql .= ") ORDER BY id ".($orderDescending ? "DESC" : "ASC");
		$query = $this->db->query($sql, $params);
		return $this->_getTestRuns($query);
	}
	
	/**
	 * Returns an array of test run id's identified by a user ID, an access type and optionally a test ID
	 * @param int The ID of the user of interest
	 * @param string The access type of the user of interest
	 * @param int The ID of the the test of interest
	 * @param boolean Whether to order the list by ID in descending (<kbd>TRUE</kbd>) or ascending (<kbd>FALSE</kbd>) order
	 * @return TestRun[] An array of <kbd>{@link TestRun}</kbd> instances
	 */
	function getTestRunIdsForUserAndAccessTypes($userId, $accessTypes, $testId = NULL, $orderDescending = TRUE)
	{
		$sql = "SELECT * FROM ".DB_PREFIX."test_runs WHERE user_id=? ";
		$params = array($userId);

		if ($testId !== NULL) {
			$sql .= "AND test_id=? ";
			$params[] = $testId;
		}

		$sql .= "AND (";

		$first = TRUE;
		foreach ($accessTypes as $accessType) {
			if ($first) {
				$first = FALSE;
			} else {
				$sql .= " OR ";
			}
			$sql .= "access_type=?";
			$params[] = $accessType;
		}

		$sql .= ") ORDER BY id ".($orderDescending ? "DESC" : "ASC");
		$query = $this->db->query($sql, $params);
		return $this->_getTestRunIds($query);
	}

	/**
	 * @access private
	 */
	function _getWhereQueryForFilter($testId = NULL, $groupName = NULL, $accessType = NULL, $completed = NULL,
	$completed_percent = NULL, $junktor = NULL, $unixTime = 0, $unixTime2 = 0, $dateRelation = NULL)
	{

		$conditions = array();
		$user = $GLOBALS['PORTAL']->getUser();
		$values = array();

		if (!isset($this->userVisibleTests)) {
			$this->userVisibleTests = array();
			if (!$user->checkPermission('edit')) {
				$query = "(SELECT c.id AS id FROM ".DB_PREFIX."container_blocks AS c
				, ".DB_PREFIX."groups_connect AS gc
					JOIN ".DB_PREFIX."groups AS g ON (gc.group_id = g.id)
					JOIN ".DB_PREFIX."group_permissions AS gp ON (gp.group_id = g.id AND gp.permission_name = 'edit' AND gp.permission_value = '1')
					WHERE gc.user_id = ? AND c.id = gp.block_id
					GROUP BY c.id)
				UNION
					(SELECT c.id FROM ".DB_PREFIX."container_blocks AS c WHERE owner = ?)
				";
				$res = $this->db->getAll($query, array($user->getId(), $user->getId()));
				foreach ($res as $row) {
					$this->userVisibleTests[] = $row['id'];
				}
			}
		}
		 
		if (is_numeric($testId) && $testId > 0) {
			$conditions[] = "test_id=?";
			$values[] = $testId;
				
		}

		if ($groupName != NULL) {
			$conditions[] = "permission_groups REGEXP ?";
			$id = $this->db->getOne('SELECT `id` FROM '.DB_PREFIX.'groups WHERE groupname = ?',$groupName);
			$values[] = "[[:<:]]".$id."[[:>:]]";			
		}


		if (isset($accessType)) {
			$conditions[] = "access_type=?";
			$values[] = $accessType;
		}

		if (isset($completed) && $completed != "any")
		{
			$conditions[] = "available_items IS NOT NULL";
			if ($completed == "all") {
				$conditions[] = "answered_items>=available_items";
			}
			elseif ($completed == "all_required") {
				$conditions[] = "answered_required_items>=available_required_items";
			}
			elseif ($completed == "not_all_required") {
				$conditions[] = "answered_required_items<available_required_items";
			}
		}

		if ($completed_percent) {
			$completed_percent = intval($completed_percent);
			if ($junktor == "greater_than")
			$conditions[] = "shown_items>($completed_percent/100)*available_items";
			if ($junktor == "less_than")
			$conditions[] = "shown_items<($completed_percent/100)*available_items";
			if ($junktor == "equal_as")
			$conditions[] = "shown_items=ROUND(($completed_percent/100)*available_items)";
		}

		if ($unixTime != 0) {
			if ($dateRelation == "greater_than")
			$conditions[] = "start_time>$unixTime";
			if ($dateRelation == "less_than")
			$conditions[] = "start_time<$unixTime";
			if ($dateRelation == "equal_as") {
				$conditions[] = "start_time>=$unixTime";
				$nextday = $unixTime+86400; // 0:00 till 24:00
				$conditions[] = "start_time<=$nextday";
			}
			if ($dateRelation == "between") {
				$conditions[] = "start_time>=$unixTime";
				$unixTime2 += 86400; // 0:00 till 24:00
				$conditions[] = "start_time<=$unixTime2";
			}
		}

		$myConds = array();
		if (count($conditions) > 0) $myConds[] = implode(' AND ', $conditions);
		if (count($this->userVisibleTests) > 0) $myConds[] = 'test_id IN ('. implode(', ', $this->userVisibleTests) .')';
		if (count($myConds) == 0) return array('', array());

		$sql = ' WHERE '. implode(' AND ', $myConds);

		return array($sql, $values);
	}

	/**
	 * Returns the number of <kbd>{@link TestRun}</kbd>s that {@link getTestRunsByFilter()} would return
	 * @see getTestRunsByFilter()
	 * @return int The number of <kbd>{@link TestRun}</kbd>s that {@link getTestRunsByFilter()} would return
	 */
	function countTestRunsByFilter($testId = NULL, $groupName = NULL, $accessType = NULL, $completed = NULL, $completed_percent = NULL,
	$junktor = NULL, $testDate = NULL, $testDate2 = NULL, $dateRelation = NULL,  $offset = 0, $listLength = NULL, $orderDescending = TRUE)
	{
		//get the unixTime from the date
		$unixTime = 0;
		$unixTime2 = 0;

		if(($testDate) && (preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', $testDate) > 0))  {
			list ($day, $month, $year) = preg_split('/[.-]/', $testDate);
			$unixTime = mktime(1, 0, 0, $month, $day,  $year);
		}
		if (($testDate2) && (preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', $testDate2) > 0)) {
			list ($day, $month, $year) = preg_split('/[.-]/', $testDate2);
			$unixTime2 = mktime(1, 0, 0, $month, $day,  $year);
		}


		list($where, $values) = $this->_getWhereQueryForFilter($testId, $groupName, $accessType, $completed, $completed_percent,
		$junktor, $unixTime, $unixTime2, $dateRelation);

		$sql = "SELECT COUNT(tr.id) FROM ".DB_PREFIX."test_runs AS tr".$where;

		return $this->db->getOne($sql, $values);

	}

	/**
	 * Proofs if a certain <kbd>{@link TestRun}</kbd> exists in database
	 * @return boolean True, if <kbd>{@link TestRun}</kbd> exists, false if not.
	 */
	function existsTestRunByID($testRunID)
	{
		$sql = "SELECT id FROM ".DB_PREFIX."test_runs WHERE id=?";
		$query = $this->db->query($sql, array($testRunID));
		if($query->fetchInto($result) == DB_OK) return true;
		else return false;
	}

	/**
	 * Returns an array of all test runs filtered by various attributes
	 *
	 * The meanings of the values for <kbd>completed</kbd> are:
	 * - any: don't filter by completion (same as <kbd>NULL</kbd>)
	 * - all: show only test runs where all items have been answered
	 * - all_required: show only test runs where all <i>required</i> items have been answered
	 * - not_all_required: show only test runs where <i>not</i> all required items have been answered
	 *
	 * @param int If set, show only the test runs for the test with this ID
	 * @param string If set, show only the test runs with this access type
	 * @param string One of "any", "all", "all_required" and "not_all_required"
	 * @param int Start with test run <kbd>$offset</kbd> (starting with 0)
	 * @param int Show a maximum of <kbd>$listLength</kbd> test runs
	 * @param boolean Whether to order the list by ID in descending (<kbd>TRUE</kbd>) or ascending (<kbd>FALSE</kbd>) order
	 * @return TestRun[] An array of <kbd>{@link TestRun}</kbd> instances
	 */
	function getTestRunsByFilter($testId = NULL, $groupName = NULL, $accessType = NULL, $completed = NULL, $completed_percent = NULL,
	$junktor, $testRunId = NULL, $testDate = NULL , $testDate2 = NULL , $dateRelation = NULL, $offset = 0, $listLength = NULL, $orderDescending = TRUE)
	{
		//get the unixTime from the date
		$unixTime = 0;
		$unixTime2 = 0;

		if (($testDate) && (preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', $testDate) > 0)) {
			list ($day, $month, $year) = preg_split('/[.-]/', $testDate);
			$unixTime = mktime(1, 0, 0, $month, $day,  $year);
		}
		if (($testDate2) && (preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', $testDate2) > 0)) {
			list ($day, $month, $year) = preg_split('/[.-]/', $testDate2);
			$unixTime2 = mktime(1, 0, 0, $month, $day,  $year);
		}

		if ((int)$testRunId > 0) {
			return array($this->getTestRunById($testRunId));
		}
		else {
			list($where, $values) = $this->_getWhereQueryForFilter($testId, $groupName, $accessType,
			$completed, $completed_percent, $junktor, $unixTime, $unixTime2, $dateRelation);

			$sql = "SELECT tr.id AS id FROM ".DB_PREFIX."test_runs AS tr".$where." ORDER BY tr.id ".($orderDescending ? "DESC" : "ASC");
			if ($listLength)  {
				$sql .= " LIMIT $offset,$listLength";
			}
			
			$query = $this->db->query($sql, $values);
			return $this->_getTestRuns($query);
		}
	}
	
	/**
	 * Returns an array of all test run ids filtered by various attributes
	 *
	 */
	function getTestRunIdsByFilter($testId = NULL, $groupName = NULL, $accessType = NULL, $completed = NULL, $completed_percent = NULL,
	$junktor, $testRunId = NULL, $testDate = NULL , $testDate2 = NULL , $dateRelation = NULL, $offset = 0, $listLength = NULL, $orderDescending = TRUE)
	{
		//get the unixTime from the date
		$unixTime = 0;
		$unixTime2 = 0;

		if (($testDate) && (preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', $testDate) > 0)) {
			list ($day, $month, $year) = preg_split('/[.-]/', $testDate);
			$unixTime = mktime(1, 0, 0, $month, $day,  $year);
		}
		if (($testDate2) && (preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', $testDate2) > 0)) {
			list ($day, $month, $year) = preg_split('/[.-]/', $testDate2);
			$unixTime2 = mktime(1, 0, 0, $month, $day,  $year);
		}

		if ((int)$testRunId > 0) {
			return array($testRunId);
		}
		else {
			list($where, $values) = $this->_getWhereQueryForFilter($testId, $groupName, $accessType,
			$completed, $completed_percent, $junktor, $unixTime, $unixTime2, $dateRelation);

			$sql = "SELECT tr.id AS id FROM ".DB_PREFIX."test_runs AS tr".$where." ORDER BY tr.id ".($orderDescending ? "DESC" : "ASC");
			if ($listLength)  {
				$sql .= " LIMIT $offset,$listLength";
			}
			
			$query = $this->db->query($sql, $values);
			return $this->_getTestRunIds($query);
		}
	}

	/**
	 * Returns a test run identified by its ID
	 * @param int The ID of the test run to return
	 * @return TestRun The <kbd>{@link TestRun}</kbd> instance representing the requested test run
	 */
	function getTestRunById($testRunId)
	{
		$sql = "SELECT * FROM ".DB_PREFIX."test_runs WHERE id=? LIMIT 1";
		$query = $this->db->query($sql, array($testRunId));
		if ($testRuns = $this->_getTestRuns($query)) {
			return $testRuns[0];
		}
		return NULL;
	}

	/**
	 * Returns a list of all test runs
	 * @param boolean Whether to order the list by ID in descending (<kbd>TRUE</kbd>) or ascending (<kbd>FALSE</kbd>) order
	 * @return TestRun[] An array of <kbd>{@link TestRun}</kbd> instances
	 */
	function getTestRuns($orderDescending = TRUE)
	{
		$order = $orderDescending ? "DESC" : "ASC";
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."test_runs ORDER BY id ".$order);
		return $this->_getTestRuns($query);
	}

	/**
	 * @access private
	 */
	function _getTestRuns($query)
	{
		$testRuns = array();
		while ($query->fetchInto($result)) {
			$testRuns[] = new TestRun($this, $result["id"]);
		}
		return $testRuns;
	}
	
	/**
	 * @access private
	 */
	function _getTestRun($id)
	{
		if($id) {
			$testRun = new TestRun($this, $id);
			return $testRun;
		}
		else
		return FALSE;
	}

	/**
	 * @access private
	 */
	function _getTestRunIds($query)
	{
		$testRunsIds = array();
		while ($query->fetchInto($result)) {
			$testRunsIds[] = $result["id"];
		}
		return $testRunsIds;
	}

	/**
	 * Returns a list of test IDs for which test runs exist
	 * @return int[] A list of test IDs
	 */
	function getAvailableTests()
	{
		list($where, $values) = $this->_getWhereQueryForFilter();
		$query = $this->db->query("SELECT test_id AS id FROM ".DB_PREFIX."test_runs$where ORDER BY test_id ASC");
		$ids = array();
		while ($query->fetchInto($result)) {
			$ids[] = $result["id"];
		}
		return $ids;
	}

	/**
	 * Returns a list of access types for which test runs exist
	 * @return string[] A list of access types
	 */
	function getAvailableAccessTypes()
	{
		list($where, $values) = $this->_getWhereQueryForFilter();
		$query = $this->db->query("SELECT DISTINCT access_type FROM ".DB_PREFIX."test_runs$where ORDER BY access_type ASC");
		$types = array();
		while ($query->fetchInto($result)) {
			$types[] = $result["access_type"];
		}
		return $types;
	}

	/**
	 * Completely deletes all test runs
	 */
	function clearDatabase()
	{
		$testRuns = $this->getTestRuns();
		for ($i = 0; $i < count($testRuns); $i++)
		{
			$testRun = &$testRuns[$i];
			$testRun->delete();
		}
	}

	/**
	 * Determines whether a test has been answered in a non-preview test run
	 * @param int The ID of the test to check for
	 * @return boolean TRUE if the test has been answered in a non-preview test run, FALSE otherwise
	 */
	function hasPublicAnswersForBlock($blockId, $testId)
	{
		$subtestId = $GLOBALS['BLOCK_LIST']->findParentInTest($blockId, $testId);
		if ($subtestId === NULL) return FALSE;

		$query = $this->db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."test_run_blocks AS t1 INNER JOIN ".DB_PREFIX."test_runs AS t2 ON t1.test_run_id=t2.id WHERE t1.subtest_id=? AND t2.test_id = ? AND t2.access_type<>? LIMIT 1", array($subtestId, $testId, "preview"));
		if ($query && !PEAR::isError($query)) {
			return TRUE;
		}
		return FALSE;
	}

}

?>
