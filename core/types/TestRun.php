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
 * Load the GivenAnswerSet class
 */
require_once(CORE."types/GivenAnswerSet.php");



/**
 * For direct accesses to a test (like start_test.php?tid=23)
 */
define("ACCESS_TYPE_DIRECT", "direct");

/**
 * For test runs started from the normal interface
 */
define("ACCESS_TYPE_PORTAL", "portal");
/**
 * For test runs started as a preview
 */
define("ACCESS_TYPE_PREVIEW", "preview");

libLoad("utilities::snakeToCamel");

/**
 * Represents a test run
 *
 * This class allows you to store which test was run ({@link getTestId()}, {@link getTestPath()}),
 * by whom ({@link getUserId()}, {@link getClient()}), how ({@link getAccessType()}, {@link getReferer()})
 * and when ({@link getStartTime()}).
 * It also tracks the progress of the test run ({@link getShownPagesRatio()}, {@link getShownItemsRatio()},
 * {@link getAnsweredItemsRatio()}, {@link getAnsweredRequiredItemsRatio()}, {@link getLastAnswerSet()})
 * and what the user answered ({@link getGivenAnwerSets()}).
 *
 * @package Core
 */
class TestRun
{
	/**#@+
	 * @access private
	 */
	var $testRunList;
	var $id;
	var $db;
	/**#@-*/

	/**
	 * Constructor (to be called by <kbd>{@link TestRunList}</kbd>)
	 * @see TestRunList
	 * @param TestRunList The list of test runs this one is a part of
	 * @param int|array The ID of this test run, or the data to use
	 */
	function TestRun(&$testRunList, $id)
	{
		$this->testRunList = &$testRunList;
		$this->haveAllTestRunBlocks = false;
		$this->db = &$GLOBALS['dao']->getConnection();

		if (is_array($id)) {
			$result = $id;
		} else {
			$query = $this->db->query("SELECT * FROM ".DB_PREFIX."test_runs WHERE id=?", array($id));
			if (! $result = $query->fetchRow()) {
				trigger_error("There is no Test Run with the ID <b>".$id."</b>", E_USER_ERROR);
			}
		}

		foreach ($result as $name => $value) {
			$name = snakeToCamel($name, FALSE);
			$this->$name = $value;
		}

		$this->displayingBlockId = NULL;
	}


	// ID
	/**
	* Returns the ID of this test run
	* @return int ID of this test run
	*/
	function getId()
	{
		return $this->id;
	}


	// Test ID
	/**
	* Returns the ID of the test this test run refers to
	* @return int The ID of the test this test run refers to
	*/
	function getTestId()
	{
		return $this->testId;
	}

	function getTestVersion()
	{
		return $this->structureVersion;
	}

	/**
	 * Returns the current step number of the test run block
	 * @return int The current step number
	 */
	function getTestRunBlockStep()
	{
		$subtest_mode = $this->getDisplayingBlockId() != $this->getTestId();
		if ($subtest_mode) $subtest_id = $this->getDisplayingBlockId();
		else $subtest_id = 0;

		$testRunBlock = $this->getTestRunBlockBySubtest($subtest_id);
		$step = $testRunBlock->getStep();
		if($step == NULL) $step = 0;

		return $step;
	}

	/**
	 * Returns the current step number of the test run
	 * @return int The current step number
	 */
	function getStep()
	{
		return intval($this->step);
	}

	// Displaying block ID
	/**
	* Returns the ID of the block the current execution of this test run displays
	* @return int
	*/
	function getDisplayingBlockId()
	{
		return $this->displayingBlockId ? $this->displayingBlockId : $this->testId;
	}

	function setDisplayingBlockId($id)
	{
		$this->displayingBlockId = $id;
	}


	// Test Path
	/**
	* Returns the path to the test this test run refers to
	* @return int The path to the test this test run refers to
	*/
	function getTestPath()
	{
		return $this->testPath;
	}


	// User ID
	/**
	* Returns the ID of the user that started this test run
	* @return int The ID of the user that started this test run
	*/
	function getUserId()
	{
		return intval($this->userId);
	}


	// Access Type
	/**
	* Returns how this test run was started
	*
	* - direct: The test run was started by clicking on a test-specific link
	* - portal: The test run was started by selecting the test in the test listing
	* - preview: The test run was started to preview the test
	*
	* @return string How this test run was started (one of "direct", "portal" and "preview")
	*/
	function getAccessType()
	{
		return $this->accessType;
	}

	/**
	 * Sets the access type
	 * @see getAccessType
	 * @param string Any string describing an access type
	 */
	function setAccessType($accessType)
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET access_type=? WHERE id=?", array($accessType, $this->id));
		$this->accessType = $accessType;
	}


	// Referer
	/**
	* Returns the address of the website that contained the direct link
	*
	* Most likely only set if the access type is "direct"
	* @return string The referring website's address
	*/
	function getReferer()
	{
		return $this->referer;
	}

	/**
	 * Sets the address of the website that contained the direct link
	 *
	 * @param string The referring website's address
	 */
	function setReferer($referer)
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET referer=? WHERE id=?", array($referer, $this->id));
		$this->referer = $referer;
	}


	// Client Information
	/**
	* Returns information about the computer used to start the test run
	*
	* - IP: The IP of the computer the requests came from
	* - Host: The Hostname of the computer the requests came from
	* - Useragent: The identification string of the browser
	*
	* Note that IP and host do not necessarily refer to the actual machine the user is using.
	* The requests might be tunneled, e.g. by a proxy server.
	*
	* @return array Associative array with the keys "ip", "host" and "useragent"
	*/
	function getClient()
	{
		return array(
			"ip" => $this->clientIp,
			"host" => $this->clientHost,
			"useragent" => $this->clientUseragent,
		);
	}

	/**
	 * Sets information about the computer used to start the test run
	 * @see getClient()
	 * @param string The IP address of the computer
	 * @param string The host name of the computer
	 * @param string The identification string of the browser
	 */
	function setClient($ip, $host, $useragent)
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET client_ip=?,client_host=?,client_useragent=? WHERE id=?", array($ip, $host, $useragent, $this->id));

		$this->clientIp = $ip;
		$this->clientHost = $host;
		$this->clientUseragent = $useragent;
	}

	// Start Time
	/**
	* Returns the time the test run was started
	* @return int Unix timestamp
	*/
	function getStartTime()
	{
		return $this->startTime;
	}

	/**
	 * Returns the current step number of the test run block
	 * @return int The current step number
	 */
	function setTestRunBlockStep($step)
	{
		$subtest_mode = $this->getDisplayingBlockId() != $this->getTestId();
		if ($subtest_mode) $subtest_id = $this->getDisplayingBlockId();
		else $subtest_id = 0;

		$testRunBlock = $this->getTestRunBlockBySubtest($subtest_id);
		$testRunBlock->setStep($step);
	}

	/** Change step of this test run
	 *  @param int New step number
	 */
	function setStep($step)
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET step=? WHERE id=?", array($step, $this->id));
		$this->step = $step;
	}

	/**
	 * Sets the time the test run was started
	 * @param int Unix timestamp
	 */
	function setStartTime($time)
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET start_time=? WHERE id=?", array($time, $this->id));

		$this->startTime = $time;
	}

	function getTotalTime()
	{
		return $this->duration;
	}


	// Progress / Completion
	/**
	* Returns the number of available pages
	* @return int The number of available pages or NULL
	*/
	function getAvailablePages()
	{
		return $this->availablePages();
	}

	/**
	 * Returns the number of available items
	 * @return int The number of available items or NULL
	 */
	function getAvailableItems()
	{
		return $this->availableItems;
	}

	/**
	 * Returns the number of available required items
	 * @return int The number of available required items or NULL
	 */
	function getAvailableRequiredItems()
	{
		return $this->availableRequiredItems;
	}

	/**
	 * Returns the number of pages shown so far
	 * @return int The number of pages shown so far
	 */
	function getShownPages()
	{
		return $this->shownPages();
	}

	/**
	 * Returns the number of items shown so far
	 * @return int The number of items shown so far
	 */
	function getShownItems()
	{
		return $this->shownItems;
	}

	/**
	 * Returns the number of items answered so far
	 * @return int The number of items answered so far
	 */
	function getAnsweredItems()
	{
		return $this->answeredItems;
	}

	/**
	 * Returns the number of required items answered so far
	 * @return int The number of required items answered so far
	 */
	function getAnsweredRequiredItems()
	{
		return $this->answeredRequiredItems;
	}

	/**
	 * Returns the ratio between shown pages and available pages
	 *
	 * Returns NULL if the number of available pages is not available.
	 * If this number is known, but zero, 1 is returned (as in "all pages have been shown")
	 *
	 * @return float A number between 0 and 1 or NULL
	 */
	function getShownPagesRatio()
	{
		if ($this->availablePages === NULL) {
			return NULL;
		}
		if ($this->availablePages == 0) {
			return 1;
		}
		return max(0, min(1, $this->shownPages / $this->availablePages));
	}

	/**
	 * Returns the ratio between shown items and available items
	 *
	 * Returns NULL if the number of available items is not available.
	 * If this number is known, but zero, 1 is returned (as in "all items have been shown")
	 *
	 * @return float A number between 0 and 1 or NULL
	 */
	function getShownItemsRatio()
	{
		if ($this->availableItems === NULL) {
			return NULL;
		}
		if ($this->availableItems == 0) {
			return 1;
		}
		return max(0, min(1, $this->shownItems / $this->availableItems));
	}

	/**
	 * Returns the ratio between answered items and available items
	 *
	 * Returns NULL if the number of available items is not available.
	 * If this number is known, but zero, 1 is returned (as in "all items have been answered")
	 *
	 * @return float A number between 0 and 1 or NULL
	 */
	function getAnsweredItemsRatio()
	{
		if ($this->availableItems === NULL) {
			return NULL;
		}
		if ($this->availableItems == 0) {
			return 1;
		}
		return max(0, min(1, $this->answeredItems / $this->availableItems));
	}

	/**
	 * Returns the ratio between answered required items and available required items
	 *
	 * Returns NULL if the number of available required items is not available.
	 * If this number is known, but zero, 1 is returned (as in "all required items have been answered")
	 *
	 * @return float A number between 0 and 1 or NULL
	 */
	function getAnsweredRequiredItemsRatio()
	{
		if ($this->availableRequiredItems === NULL) {
			return NULL;
		}
		if ($this->availableRequiredItems == 0) {
			return 1;
		}
		return max(0, min(1, $this->answeredRequiredItems / $this->availableRequiredItems));
	}

	/**
	 * Calculates the test run progress by means of the step
	 * 
	 * @returns Percentage value of test run progress
	 */
	function getProgress()
	{
		$step = $this->getStep();
		$shownItemsRatio = $this->getShownItemsRatio();
		if($step == 0 && $shownItemsRatio > 0)			// test run has been made before implementation of step
		{
			return floor($shownItemsRatio*100);
		}
		else
		{
			$maxStep = count($this->getStructure(NULL, true));
			if ($maxStep == 0)
				return 100;
			return floor((max(0, min(1, $step/$maxStep))) * 100);
		}
	}

	/**
	 * Increases the number of shown pages by 1
	 */
	function increaseShownPages()
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET shown_pages=shown_pages+1 WHERE id=?", array($this->id));
		$this->shownPages++;
	}

	/**
	 * Increases the number of shown items by 1
	 */
	function increaseShownItems()
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET shown_items=shown_items+1 WHERE id=?", array($this->id));
		$this->shownItems++;
	}

	/**
	 * Increases the number of answered items by 1
	 */
	function increaseShownItemsN($num)
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET shown_items=shown_items+? WHERE id=?", array($num, $this->id));
		$this->answeredItems++;
	}

	/**
	 * Increases the number of answered items by 1
	 */
	function increaseAnsweredItems()
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET answered_items=answered_items+1 WHERE id=?", array($this->id));
		$this->answeredItems++;
	}

	/**
	 * Increases the number of answered required items by 1
	 */
	function increaseAnsweredRequiredItems()
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET answered_required_items=answered_required_items+1 WHERE id=?", array($this->id));
		$this->answeredRequiredItems++;
	}

	/**
	 * @access private
	 */
	var $validIncreaseColumns = array(
		"shown_pages",
		"shown_items",
		"answered_items",
		"answered_required_items",
	);

	/**
	 * Increases multiple counters at once
	 *
	 * These counters are available:
	 * - shown_pages
	 * - shown_items
	 * - answered_items
	 * - answered_required_items
	 *
	 * @param string[] The counters to increase
	 */
	function updateStatistics($increase, $duration = 0)
	{
		if (! $increase) {
			return;
		}
		$sets = array();
		foreach ($increase as $column) {
			if (! in_array($column, $this->validIncreaseColumns)) {
				trigger_error("Invalid increase column <b>".htmlspecialchars($column)."</b>, use one of the following: ".implode(", ", $this->validIncreaseColumns), E_USER_ERROR);
			}
			$sets[] = $column."=".$column."+1";
		}
		if ($duration > 0) {
			$sets[] = "duration = duration + ". intval($duration);
		}
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET ".implode(",", $sets)." WHERE id=?", array($this->id));
	}


	/**
	 * Returns a test run block for a specific subtest.
	 * @param ContainerBlock The subtest (or NULL to get top-level test data).
	 * @param boolean Whether to use the cache for obtaining the data.
	 * @return TestRunBlock The test run block (or NULL on failure).
	 */
	function getTestRunBlockBySubtest($subtest, $cache = false)
	{
		return new TestRunBlock($this, $subtest, $cache);
	}

	/**
	 * Returns an array of test run blocks in this test run.
	 * @return TestRunBlock[]
	 */
	function getTestRunBlocks($noCache = false)
	{
		if ($this->haveAllTestRunBlocks) {
			return retrieve('TestRunBlock', array($this->getId()));
		}
		$this->haveAllTestRunBlocks = true;

		$query = "SELECT subtest_id, content FROM ".DB_PREFIX."test_run_block_content WHERE test_run_id = ?";
		$result = $this->db->getAll($query, array($this->getId()));

		$retval = array();

		foreach ($result as $block) {
			$subtest = ($block['subtest_id'] != 0 ? new ContainerBlock($block['subtest_id']) : NULL);
			$retval[$block['subtest_id']] = new TestRunBlock($testRun, $subtest, $block['content']);
		}
		return $retval;
	}

	/**
	 * Returns the current test run block
	 * @return Object Current test run block
	 */
	function getCurrentTestRunBlock()
	{
		$subtest_mode = $this->getDisplayingBlockId() != $this->getTestId();
		if ($subtest_mode) $subtest_id = $this->getDisplayingBlockId();
		else $subtest_id = 0;

		return $this->getTestRunBlockBySubtest($subtest_id);
	}

	// Answer Sets
	/**
	* Prepares a <kbd>{@link GivenAnswerSet}</kbd> instance.
	*
	* @param int The ID of the block the set refers to
	* @param int The step number
	* @param int The ID of the page the set refers to
	* @param int Unix timestamp for when the user finished processing the item
	* @param boolean Whether it took the user to long to answer the item
	* @param int The time it took the user to answer the item (in ms, so 1500 means 1.5 seconds)
	* @param int The time it took the user to answer the item as tracked by the client
	* @param int The time it took the user to answer the item as tracked by the server
	*/
	function prepareGivenAnswerSet($blockId, $stepNumber, $itemId, $finishTime = NULL, $timeout = FALSE, $duration = NULL, $clientDuration = NULL, $serverDuration = NULL)
	{
		return new GivenAnswerSet($this, GivenAnswerSet::prepare($blockId, $stepNumber, $itemId, $finishTime, $timeout, $duration, $clientDuration, $serverDuration));
	}

	/**
	 * Adds a <kbd>{@link GivenAnswerSet}</kbd> to the appropriate test run block.
	 * @param int The subtest ID to use (or 0 to indicate a top-level test element).
	 * @param GivenAnswerSet The data.
	 */
	function addGivenAnswerSet($subtest, $set)
	{
		$block = $this->getTestRunBlockBySubtest($subtest);
		$data = $set->getData();

		// In case it's required, add symlink
		if ($subtest != 0) {
			$topBlock = $this->getTestRunBlockBySubtest(NULL);
			$topBlock->addLinkToSub($subtest);
		}
		$block->add($data);
	}

	/**
	 * Creates a copy of a given answer set
	 *
	 * This is used to skip previously answered blocks.
	 * Copied sets are marked as a copy and store the ID of the source set.
	 */
	function copyGivenAnswerSet($givenAnswerSet, $testRunBlock, $newStepNumber)
	{
		$data = $givenAnswerSet->getData();
		$data["step_number"] = $newStepNumber;

		$subtestId = $testRunBlock->getSubtestId();
		if ($subtestId != 0) {
			$topBlock = new TestRunBlock($this, 0);
			$topBlock->addLinkToSub($subtestId);
		}

		$testRunBlock->add($data);
	}

	/**
	 * Returns a list of <kbd>{@link GivenAnswerSet}</kbd>s that belong to this test run
	 * @return GivenAnswerSet[] The answer sets that belong to this test run
	 */
	function getGivenAnswerSets()
	{
		$block = $this->getTestRunBlockBySubtest(0);
		return $block->getGivenAnswerSets(true);
	}

	/**
	 * Returns a list of <kbd>{@link GivenAnswerSet}</kbd>s that belong to this test run and relate to the block with the given ID
	 * @param ContainerBlock The subtest to consider (or NULL to denote top-level test items)
	 * @return GivenAnswerSet[]
	 */
	function getGivenAnswerSetsBySubtest($subtest, $includeSub = FALSE)
	{
		$block = $this->getTestRunBlockBySubtest($subtest);
		return $block->getGivenAnswerSets($includeSub);
	}

	/**
	 * Returns a list of <kbd>{@link GivenAnswerSet}</kbd>s that belong to this test run and relate to the (non-container) block with the given ID
	 * @param integer The block ID
	 * @return GivenAnswerSet[]
	 */
	function getGivenAnswerSetsByBlockId($blockId)
	{
		$answerSets = array();
		foreach ($this->getGivenAnswerSets() as $gas) {
			if ($gas->getBlockId() == $blockId) $answerSets[$gas->getItemId()] = $gas;
		}
		return $answerSets;
	}

	/**
	 * Retrieves a given answer set based on its item ID and the subtest ID.
	 *
	 * @param int ID of the item.
	 * @param int The ID of the subtest containing the item (or NULL if the item is not part of a subtest).
	 * @return GivenAnswerSet
	 */
	function getGivenAnswerSetByItemId($itemId, $subtest = NULL)
	{
		$sets = $this->getGivenAnswerSetsBySubtest($subtest, TRUE);
		foreach($sets as $key => $value) {
			if ($value->getItemId() == $itemId) return $value;
		}
		return NULL;
	}

	function getGivenAnswerSetByStepNumber($stepNumber, $blockId)
	{
		$sets = $this->getGivenAnswerSetsByBlockId($blockId);
		if(!empty($sets))
		{
			foreach($sets as $set) if($set->getStepNumber() == $stepNumber) return $set;
		}
		return NULL;
	}

	/**
	 * Deletes this test run
	 */
	function delete()
	{
		$this->deleteById($this->id);
	}

	/**
	 * Static function to improve delete performance
	 * @param int ID of the test run to delete
	 * @static
	 */
	function deleteById($id)
	{
		$res = $this->db->query('DELETE FROM '.DB_PREFIX.'test_run_blocks WHERE test_run_id=?', array($id));
		$res = $this->db->query('DELETE FROM '.DB_PREFIX.'test_run_block_content WHERE test_run_id=?', array($id));
		
		//check if real structure is saved or just a link
		$res = $this->db->query('SELECT test_structure FROM '.DB_PREFIX.'block_structure WHERE test_run_id=? AND LENGTH(test_structure)>15', array($id));
		if($res) 
		{		
			//find other testrun with same structure and change all other links to this one
			$res = $this->db->query('SELECT * FROM '.DB_PREFIX.'block_structure WHERE test_structure REGEXP ? AND test_run_id<>? LIMIT 0,1', array('^'.((string)$id).'+', $id));
			$other_run = $res->fetchRow(); 
			$other_subtest = (string)$other_run["subtest_id"];
			$old_link = (string)$other_run["test_structure"];
			$other_run = (string)$other_run["test_run_id"];
			
			$res = $this->db->query('SELECT test_structure FROM '.DB_PREFIX.'block_structure WHERE test_run_id=? AND LENGTH(test_structure)>15', array($id));		
			$this_struct = $res->fetchRow(); 
			$this_struct = (string)$this_struct["test_structure"];
						
			$res = $this->db->query('UPDATE '.DB_PREFIX.'block_structure SET test_structure=? WHERE test_run_id=? AND subtest_id=?', array($this_struct, $other_run, $other_subtest));
			$res = $this->db->query('UPDATE '.DB_PREFIX.'block_structure SET test_structure=? WHERE test_structure=? AND NOT (test_run_id = ? AND subtest_id = ?)', array($other_run.'+'.$other_subtest, $old_link, $other_run, $other_subtest));
		}
		
		$res = $this->db->query('DELETE FROM '.DB_PREFIX.'block_structure WHERE test_run_id=?', array($id));
		
		if (PEAR::isError($res)) return false;
		return !PEAR::isError($this->db->query('DELETE FROM '.DB_PREFIX.'test_runs WHERE id=?', array($id)));
	}


	// Position

	/**
	 * Returns the last answer set
	 * @return GivenAnswerSet The last answer set if found, NULL otherwise
	 */
	function &getLastAnswerSet()
	{
		$sets = $this->getGivenAnswerSets();
		$null = NULL;
		if (!$sets) return $null;
		$set = &$sets[end(array_keys($sets))];
		return $set;
	}

	/**
	 * Returns the last answer set corresponding to the step
	 * @return GivenAnswerSet The last answer set if found, NULL otherwise
	 */
	function &getAnswerSetWithHighestStep()
	{
		$sets = $this->getGivenAnswerSets();
		$null = NULL;
		if(!$sets) return $null;
		$maxStep = 0;
		$highest = null;
		foreach($sets as $set)
		{
			$step = $set->getStepNumber();
			if($step >= $maxStep)
			{
				$maxStep = $step;
				$highest = $set;
			}
		}
		return $highest;
	}

	/**
	 * Determines the structure of the test and returns it
	 */
	function getStructure($testRunBlock = NULL, $complete = false)
	{
		$testRunBlock = $this->getCurrentTestRunBlock();
		return $testRunBlock->getStructure($testRunBlock, $complete);
	}

	/**
	 * Stores the Groupnames, that have the permission to run the test
	 */
	function setGroupIds()
	{
		$userList = new UserList();
		$user = $userList->getUserById($this->getUserId());
		if (!$user) $user = DataObject::getById('User', 0);
		$uid = $user->getId();
		if($uid == SUPERUSER_ID)
			$groupIds = $user->getGroupIds();
		else
			$groupIds = $user->getGroupIdsByPermissonToTest($this->getTestId());
		$groups = '';
		foreach($groupIds as $value) {
			$groups = $groups.",".$value;
		}
		$groups =  substr($groups,1);
		$this->db->query("UPDATE ".DB_PREFIX."test_runs SET permission_groups=? WHERE id=?", array($groups, $this->id));
	}

	/**
	 * Recalls the group names, that have permission to run the test
	 */
	function getGroupNames()
	{
		$data = array();
		$groupids = substr($this->permissionGroups,0);
		$myString = explode(',',$groupids);
		foreach($myString as $id) {
			$data[] = $this->db->getOne('SELECT `groupname` FROM '.DB_PREFIX.'groups WHERE id = ?',$id);
		}
		$myString = implode(',',$data);
		
		return $myString;
	}

	/**
	 * Receive a test run, which will be verified additionally
	 */
	function verifyTestRun()
	{
		require_once(CORE."types/TANCollection.php");

		$userId = $this->getUserId();
		$verified = false;
		$tan = isset($_SESSION['tan']) ? $_SESSION['tan'] : NULL;

		// Test has been accessed with a TAN
		if($this->getAccessType() == 'tan') {
			$block = TANCollection::getBlockByTAN($tan);
			if($block != NULL) {
				$tanCollection = new TANCollection($block->getId());
				$testRunIdTan = $tanCollection->getTestRun($tan);
				if($this->getId() == $testRunIdTan) {
					$verified = true;
				}
			}
		}

		// Avoid continuation of anonymous test runs
		if ($userId == 0 && !$verified) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.anonymous_test_run_not_continuable", MSG_RESULT_NEG);
			//redirectTo("test_listing", array("resume_messages" => "true"));
			return false;
		}

		// Avoid continuation of test, if the current user id doesn't match the one in the test run
		if(!$verified && $userId != $GLOBALS["PORTAL"]->getUserId()) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testmake.user_id_differs", MSG_RESULT_NEG);
			//redirectTo("test_listing", array("resume_messages" => "true"));
			return false;
		}
		return true;
	}

	/*
	 * Prepage session for saving the relevant data about the running tests
	 */
	function initSession()
	{
		$GLOBALS["PORTAL"]->startSession();

		if (! isset($_SESSION["RUNNING_TESTS"])) {
			$_SESSION["RUNNING_TESTS"] = array();
		}
	}

	function prepare($options = array())
	{
		$this->initSession();
		$id = 1 + end(array_keys($_SESSION["RUNNING_TESTS"]));

		$_SESSION["RUNNING_TESTS"][$id] = $options;
		$_SESSION["RUNNING_TESTS"][$id]["testRunId"] = $this->getId();
		if ($this->getDisplayingBlockId()) {
			$_SESSION["RUNNING_TESTS"][$id]["displayingBlockId"] = $this->getDisplayingBlockId();
		}

		$test = $GLOBALS["BLOCK_LIST"]->getBlockById($this->getDisplayingBlockId(), BLOCK_TYPE_CONTAINER);

		require_once(CORE."types/TestSelector.php");
		$selector = new TestSelector($test);
		$_SESSION["RUNNING_TESTS"][$id]["sequence"] = $selector->getParentTreeBlockIds();
		$_SESSION["RUNNING_TESTS"][$id]["skipped_blocks"] = array();
		$_SESSION["RUNNING_TESTS"][$id]["block_time"] = array();

		return $id;
	}

	/**
	 * This function returns the next items to display, with respect to skipping of the items
	 * and optionally the adaptive test strategy.
	 *
	 * @param $sessionItemId The continuous id of the current item saved in the session.
	 * @return Array The next items in the test run.
	 */
	function getNextItems($sessionItemId, $flag = false)
	{
		$testSession = &$_SESSION['RUNNING_TESTS'][$sessionItemId];
		$testRunBlock = $this->getCurrentTestRunBlock();
		$test = new Test($this->getTestId());
		$subtest = $test->getShowSubtests();
		$structure = $subtest ? $this->getStructure() : $this->getStructure(NULL, true);
		$increase = array();

		// Get the current step for test run and test run block
		$trStep = $this->getStep();
		$trbStep = $this->getTestRunBlockStep();	// ...if step avaiable from test run block...
		$step = $trbStep;

		if ($step == 0 && ((!$subtest && $this->getShownItems() > 0) || ($subtest && $testRunBlock->getShownItems() > 0)))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.testrun.no_support_for_old_unfinished_test_runs", MSG_RESULT_NEG);
			return NULL;
		}

		/* BEGIN: DEBUGGING INFO */
		$debug = NULL;
		if (array_key_exists("DEBUG", $_SESSION))
		{
			$debug = array();
			$debug["tr_step_init"] = $trStep;
			$debug["trb_step_init"] = $trbStep;
			if (@$stepFromAnswerSet) $debug["step_from_structure"] = false;
			$debug["steps_in_structure"] = implode("_", array_keys($structure));
		}
		/* END */

		// Step of trb is bigger than number of steps in structure of trb => there are no items
		if(!$structure) $structure = array();
		if (!array_key_exists($trbStep, $structure)) return null;

		/* BEGIN: DEBUGGING INFO */
		if ($debug)
		{
			$debug["session_item_id"] = $sessionItemId;
			$debug["parent_id_from_structure"] = $structure[$trbStep]["parent_id"];
		}
		/* END */

		// Load block mentioned in structure
		$test = $GLOBALS['BLOCK_LIST']->getBlockById($this->getTestId());
		if ($structure[$trbStep]['subtest_id'] != 0) $subtest = $GLOBALS['BLOCK_LIST']->getBlockById($structure[$trbStep]['subtest_id']);
		else $subtest = FALSE;
		$block = @$GLOBALS['BLOCK_LIST']->getBlockById($structure[$trbStep]['parent_id']);

		// Init itemblock time tracking
		if ($block->isItemBlock() && $block->getMaxTime() && ! isset($testSession['block_time'][$block->getId()])) {
			$testSession['block_time'][$block->getId()] = $block->getMaxTime();
		}

		// Handle skipping of blocks / subtests
		$entering_block = ($trbStep == 0 || $structure[$trbStep - 1]['parent_id'] != $structure[$trbStep]['parent_id']);
		$entering_subtest = ($subtest && ($trbStep == 0 || $structure[$trbStep - 1]['subtest_id'] != $structure[$trbStep]['subtest_id']));
		if ($test->getEnableSkip() && ($entering_block || $entering_subtest)) {
			$consider_skipping_subtest = $consider_skipping_block = FALSE;
			// Retrieve Ids of TestRuns that contain previously given answers for the current block
			if ($entering_subtest) {
				if(array_key_exists('skip_block', $testSession) && isset($testSession['skip_block']['do_skip']) && $testSession['skip_block']['do_skip'] == true) 
				{
					$subtest = $GLOBALS['BLOCK_LIST']->getBlockById($testSession['skip_block']['block_id']);
				}
				$oldTestRunIds = $this->testRunList->getSourceRunsForSkipping($GLOBALS["PORTAL"]->getUserId(), $subtest, $test->getId());
				$consider_skipping_subtest = !empty($oldTestRunIds);
			}
			if (!$consider_skipping_subtest && $entering_block) {
				if(array_key_exists('skip_block', $testSession) && isset($testSession['skip_block']['do_skip']) && $testSession['skip_block']['do_skip'] == true)
				{
					$block = $GLOBALS['BLOCK_LIST']->getBlockById($testSession['skip_block']['block_id']);
				}
				$oldTestRunIds = $this->testRunList->getSourceRunsForSkipping($GLOBALS["PORTAL"]->getUserId(), $block, $test->getId());
				$consider_skipping_block = !empty($oldTestRunIds);
			}
				
			// Check whether parent block was skippable, then don't let the items be skippable too
			$checkToSkip = true;
			$parents = $block->getParents();
			foreach($parents as $parent) {
				if (isset($_SESSION['RUNNING_TESTS'][$sessionItemId]["skipable_blocks"])) {
					if (in_array($parent->getId(), $_SESSION['RUNNING_TESTS'][$sessionItemId]["skipable_blocks"])) {
						$checkToSkip = false;
					}
				}
			}

			// Something (block) was skipped, handle it
			if (($consider_skipping_subtest || $consider_skipping_block) && ($checkToSkip)) {
				// Use data of the most recent TestRun
				$oldTestRunId = array_shift($oldTestRunIds);
				$oldTestRun = $this->testRunList->getTestRunById($oldTestRunId);

				if (!array_key_exists('skip_block', $testSession)) {
					return array(
							"special" => TRUE,
							"type" => "confirm_block_skip",
							"block_id" => ($consider_skipping_subtest ? $subtest->getId() : $block->getId()),
							"block_title" => ($consider_skipping_subtest ? $subtest->getTitle() : $block->getTitle()),
							"other_test_title" => @$GLOBALS["BLOCK_LIST"]->getBlockById($oldTestRun->getTestId())->getTitle(),
					);
				} else if (($testSession['skip_block']['do_skip'])) {
					if ($consider_skipping_subtest) $oldAnswerSets = $oldTestRun->getGivenAnswerSetsBySubtest($testSession['skip_block']['subtest_id']);
					else $oldAnswerSets = $oldTestRun->getGivenAnswerSetsByBlockId($testSession['skip_block']['block_id']);
					// Copy AnswerSets of most current TestRunBlock
					foreach($oldAnswerSets as $oldAnswerSet) {
						$item = $GLOBALS['BLOCK_LIST']->getBlockById($oldAnswerSet->getBlockId())->getTreeChildById($oldAnswerSet->getItemId());
						$this->copyGivenAnswerSet($oldAnswerSet, $testRunBlock, ++$trStep );
						if (!is_a($item, "Item") || !$block->isItemBlock() || !$block->isAdaptiveItemBlock() || $isLast) {
							$increase[] = "shown_pages";
						}
						if (is_a($item, "Item") && (!$block->isItemBlock() || !$block->isAdaptiveItemBlock() || $isLast)) {
							$increase[] = "shown_items";
							$increase[] = "answered_items";
							if ($item->isForced() && ! $oldAnswerSet->hadTimeout()) {
								$increase[] = "answered_required_items";
							}
						}
					}
					$this->updateStatistics($increase);
					$testRunBlock->updateStatistics($increase);
					unset($testSession['skip_block']);
				}
			}
		}

		/* BEGIN: DEBUGGING INFO */
		if ($debug)
		{
			if ($block) $debug["block_loaded"] = true;
		}
		/* END */

		// Use normal test strategy
		$items = array();
		if ($block->isItemBlock() && !$block->isAdaptiveItemBlock())
		{
			$itemsPerPage = (int)$block->getItemsPerPage();
			$tmpStep = $trbStep;
			for ($i = 0; $i < $itemsPerPage; $i++)
			{
				$item = NULL;
				$stepincreased = 0;
				while (isset($structure[$tmpStep]) && $structure[$tmpStep]['parent_id'] == $block->getId())
				{
					$item = Item::getItem($structure[$tmpStep]['id']);
					if ($item->fullfillsConditions($this))
					{	
						$tmpStep++;
						break;
					} else
					{
						$skipAnswerSet = $this->prepareGivenAnswerSet($block->getId(), $tmpStep, $item->getId());
						$tmpStep++;

							$this->setStep(++$trStep);
							$testRunBlock->setStep(++$trbStep);
							$stepincreased = 1;
						
						$subtestId = (int)$GLOBALS['BLOCK_LIST']->findParentInTest($block->getId(), $this->getTestId());
						$this->addGivenAnswerSet($subtestId, $skipAnswerSet);
						$testRunBlock->increaseShownItems();
						unset($skipAnswerSet);

						$increase = array();
						if($itemsPerPage == 1)
						{
							$increase = array('shown_pages', 'shown_items', 'answered_items', 'answered_required_items');
						}
						else
						{
							$increase = array('shown_items', 'answered_items', 'answered_required_items');
						}
						if (!$item->isForced()) array_pop($increase);
						$this->updateStatistics($increase);
						unset($item);
						$item = NULL;
					}
				}
				if ($item === NULL) break;
				$items[] = $item;
			}

			/* BEGIN: DEBUGGING INFO */
			if ($debug)
			{
				$debug["trStep_after_item_loop"] = $trStep;
				$debug["trbStep_after_item_loop"] = $trbStep;
				$debug["tmp_step_after_item_loop"] = $tmpStep;
				$debug["number_items_after_normal_strategy"] = count($items);
				$_SESSION["DEBUG"]["TestRun:getNextItems"] = $debug;
			}
			/* END */

			// If we couldn't find enough items for this page we'll have to retry.
			if (!$items)
			{
				if ($stepincreased == 0)
				{
					$this->setStep(++$trStep);
					$testRunBlock->setStep(++$trbStep);
				}
			
				unset($oldTestRunIds);
				unset($oldTestRun);
				unset($block);
				unset($testSession);
				unset($testRunBlock);
				unset($test);
				unset($subtest);
				unset($structure); 
				unset($increase);
				unset($parent);
				unset($step);
				unset($debug);
				unset($trbStep);
				unset($items);
				
				//Evil hack to avoid memory problem on extremly long tests
				$memory = memory_get_usage();
				if (($memory + 16000000) > MEMORY_LIMIT && !SPEEDMODE)
					unstore_all();
			
				//return $this->getNextItems($sessionItemId);
				return "notFound";
			}
				
			return $items;
		} else if ($block->isItemBlock() && $block->isAdaptiveItemBlock()) {
			$blockId = $block->getId();
			$isFresh = FALSE;
			if (! isset($testSession["adaptiveTestSessionData"]) || $testSession["adaptiveTestSessionBlockId"] != $blockId) {
				$testSession["adaptiveTestSessionBlockId"] = $blockId;
				$testSession["adaptiveTestSessionData"] = array();
				unset($testSession["adaptiveTestSessionChildId"]);
				$isFresh = TRUE;
			}

			require_once(CORE."types/AdaptiveTestSession.php");
			$adaptiveTestSession = new AdaptiveTestSession();
			$adaptiveTestSession->loadState($testSession["adaptiveTestSessionData"]);

			// Session has not been initialized
			if ($isFresh) {
				$adaptiveTestSession->init($block);

				// Old answers now have to be re-processed;
				$givenAnswerSets = $this->getGivenAnswerSetsByBlockId($blockId);
				$itemIds = array();
				$answers = array(NULL);
				foreach ($givenAnswerSets as $givenAnswerSet) {
					$itemIds[] = $givenAnswerSet->getItemId();
					$item = $block->getTreeChildById($givenAnswerSet->getItemId());
					$answers[] = $item->verifyCorrectness($givenAnswerSet);
				}
				// Reuse already determined ID for next item
				// only if applicable
				if (count($givenAnswerSets) > 0)
				$itemIds[] = $structure[$trbStep]['id'];
				else
				$itemIds[] = NULL;

				for ($i = 0; $i < count($answers); $i++) {
					$adaptiveTestSession->processAnswer($answers[$i], $itemIds[$i]);
				}

				$adaptiveTestSession->saveState($testSession["adaptiveTestSessionData"]);
			}

			// Most of the time, we already decided earlier which
			// item to display next. However, when we first show
			// an adaptive item for a block, we pick one at random.
			$set = $this->getLastAnswerSet();
			if (!$set || $set->getBlockId() != $blockId) {
				$nextItemId = $adaptiveTestSession->getCurrentItemId();
				if ($nextItemId !== NULL) {
					$structure = TestStructure::reorderStructure($structure, $blockId, 0, $nextItemId);
					$testRunBlock->setStructure($structure);
					# $adaptiveTestSession->display();
					return array(Item::getItem($nextItemId));
				} else {
					// Normally we'd want to display at
					// least one item but apparently we
					// can't even do that, so handle that
					// gracefully
					$structure = TestStructure::reorderStructure($structure, $blockId, 0, 0);
					return $this->getNextItems($sessionItemId);
				}
			}

			return array(Item::getItem($structure[$trbStep]['id']));
		} 
		else {

			$tmpStep = $trbStep;	
			
			while (isset($structure[$tmpStep]) && $structure[$tmpStep]['parent_id'] == $block->getId()) {
				$infoPage = InfoPage::getInfoPage($structure[$tmpStep]['id']);
				if ($infoPage->fullfillsConditions($this))
				{	
					$tmpStep++;
					break;
				} 
				else {
					$tmpStep++;

					$this->setStep(++$trStep);
					$testRunBlock->setStep(++$trbStep);
					$stepincreased = 1;
					
					$subtestId = (int)$GLOBALS['BLOCK_LIST']->findParentInTest($block->getId(), $this->getTestId());
					
					$testRunBlock->increaseShownItems();

					$increase = array();
				
					$increase = array('shown_pages');

					//if (!$page->isForced()) 
					array_pop($increase);
					$this->updateStatistics($increase);

					$item = NULL;
				}
			}
			if ($structure[$trbStep] == NULL) {
				
				//$memory = (integer) round(memory_get_usage() / 1024);
				//if ($memory > 45000)
					
				$memory = memory_get_usage();
				if (($memory + 16000000) > MEMORY_LIMIT && !SPEEDMODE)
					unstore_all();

				unset($testSession);
				unset($testRunBlock);
				unset($test);
				unset($subtest);
				unset($structure); 
				unset($increase);
				unset($parent);
				unset($step);
				unset($debug);

				//return $this->getNextItems($sessionItemId);
				return "notFound";
			}
			else
				if(file_exists(ROOT."upload/items/".$structure[$trbStep]['type'].".php"))		//current 'item' is actually an Item, not an InfoPage as it should..
					//require_once(ROOT."upload/items/".$structure[$trbStep]['type'].".php");	//////////////////
					return "notFound"; 
				else
					return array(new $structure[$trbStep]['type']($structure[$trbStep]['id']));
			/* BEGIN: DEBUGGING INFO */
			if ($debug)
			{
				$_SESSION["DEBUG"]["TestRun:getNextItems"] = $debug;
			}
			/* END */
		}
	}

	/*
	 * Writes the structure version on which the TestRun is based to the database.
	 * @param int Version to store
	 * @return boolean
	 */
	function setStructureVersion($version)
	{
		$query = "UPDATE " . DB_PREFIX . "test_runs SET structure_version = ? WHERE id = ?";
		return $this->db->query($query, array($version, $this->getId()));
	}
}

?>
