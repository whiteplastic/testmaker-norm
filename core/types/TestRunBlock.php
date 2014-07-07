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
 * TestRunBlock references TestRun
 */
require_once(CORE.'types/TestRun.php');

/**
 * Represents a test data block in a test run.
 *
 * Linked to a test run and, optionally, a subtest (if no subtest is
 * referenced, the data block is assumed to describe the test's direct
 * content), this class deals with storing information about the subject's
 * participation in the current block, including timing information, given
 * answers etc.
 *
 * @package Core
 */
class TestRunBlock
{
	/**#@+
	 * @access private
	 */
	var $testRun;
	var $testRunId;
	var $subtestId;
	var $db;
	// "Why this opaque structure?" I hear you asking. That's simple: "clean" design yielded undesirable performance.
	// For example, exporting ~300 test data records took more than 20 minutes with the old design.
	var $data = NULL;
	var $plainData = array();
	var $links = array();
	var $createPending = false;
	var $commitPending = false;
	var $availableItems = NULL;
	var $availableRequiredItems = NULL;
	var $shownItems = NULL;
	var $answeredRequiredItems = NULL;
	var $testStructure;
	var $step = 0;
	/**#@- */

	/**
	 * Initializes a test run block object from pre-fetched data.
	 * @param TestRun The associated test run.
	 * @param ContainerBlock The associated subtest (or NULL to denote top-level test elements)
	 * @param mixed The data as an associative array (or a boolean value to fetch it from the database; TRUE to consider the cache).
	 */
	function TestRunBlock(&$testRun, $subtest, $data = true)
	{
		$this->db = $GLOBALS['dao']->getConnection();

		$this->testRun = &$testRun;
		$this->testRunId = $testRun->getId();
		if (is_numeric($subtest) || $subtest === NULL) {
			$this->subtestId = intval($subtest);
		} else {
			$this->subtestId = ($subtest ? $subtest->getId() : 0);
		}
		if (is_string($data)) {
			$this->data = $data;
			$this->plainData = self::decodeBlockData($data);
			$this->_store();
			return;
		}
		
		elseif ($data === true && ($res = retrieve('TestRunBlock', array($this->testRunId, $this->subtestId)))) {
			$this->createPending = $res->createPending;
			$this->commitPending = $res->commitPending;
			$this->data = $res->data;
			$this->plainData = $res->plainData;
			$this->links = $res->links;
			$this->availableItems = $res->availableItems;
			$this->answeredRequiredItems = $res->answeredRequiredItems;
			$this->availableRequiredItems = $res->availableRequiredItems;
			$this->shownItems = $res->shownItems;
			$this->testStructure = $res->testStructure;
			$this->step = $res->step;
			return;
		}

		$res = $this->db->query('SELECT * FROM '.DB_PREFIX.'test_run_blocks WHERE test_run_id=? AND subtest_id=?', array($this->testRunId, $this->subtestId));
		$res2 = $this->db->query('SELECT * FROM '.DB_PREFIX.'test_run_block_content WHERE test_run_id=? AND subtest_id=?', array($this->testRunId, $this->subtestId));
		$res3 = $this->db->query('SELECT * FROM '.DB_PREFIX.'block_structure WHERE test_run_id=? AND subtest_id=?', array($this->testRunId, $this->subtestId));
		
		if (!PEAR::isError($res))  { 
			if ($res->fetchInto($row)){
				foreach ($row as $key => $value) {
					$key = snakeToCamel($key, false);
					$this->$key = $value;
				}
			}
		}
		if (!PEAR::isError($res2))  {
			if ($res2->fetchInto($row)) {
				foreach ($row as $key => $value) {
					$key = snakeToCamel($key, false);
					if ($key == 'content') $key = 'data';
					$this->$key = $value;
				}
			}
		}
		if (!PEAR::isError($res3)) { 
			if ($res3->fetchInto($row)) {
				if ($row) {
					foreach ($row as $key => $value) {
						$key = snakeToCamel($key, false);
						//if it is a pointer to a structure use the pointer to get the structure
						//pointer consists test_rund_id and subtest_id
						if ((strlen($value) < 17) && ($key = 'testStructure')) {
							//if old pointer is used on server spielwiese (Test Server RWTH Aachen only)
							if (strstr($row['test_structure'], "+") === FALSE)
								$res4 = $this->db->query('SELECT * FROM '.DB_PREFIX.'block_structure WHERE 
														  test_run_id = ? AND subtest_id = ?', array($row['test_run_id'], $row['test_structure']));
							else {
								$value = explode("+", $row['test_structure']);
								$res4 = $this->db->query('SELECT * FROM '.DB_PREFIX.'block_structure WHERE 
													      test_run_id = ? AND subtest_id = ?', array($value[0], $value[1]));
							}
							if ($res4->fetchInto($row2)) 
									$value = $row2['test_structure'];
						}
						$this->$key = $value;
					}
				}
			}

			$memory = memory_get_usage();
			if (($memory + 18000000) > MEMORY_LIMIT && !SPEEDMODE)
				unstore_all();
			if(!empty($this->testStructure) && is_string($this->testStructure)) {
				$this->testStructure = gzuncompress(base64_decode($this->testStructure));
				$this->testStructure = unserialize($this->testStructure);
			}
			$this->plainData = $this->decodeBlockData($this->data);
			$this->_store();
		}
	}

	/**
	 * Returns the structure of this TestRunBlock
	 * @return array
	 */
	function getStructure($steps = NULL, $complete = false)
	{
		$completeStructure = $this->testStructure;
		if($complete) return $completeStructure;
		$trbStructure = array();
		foreach($completeStructure as $item)
		{
			if($this->getSubtestId() == $item['subtest_id']) $trbStructure[] = $item;
		}
		return $trbStructure;
	}

	function setStructure($structure)
	{
		$this->testStructure = $structure;

		$query = 'UPDATE '.DB_PREFIX.'block_struture SET test_structure = ? WHERE test_run_id=? AND subtest_id=?';
		$this->db->query($query, array(serialize($structure), $this->getTestRunId(), $this->getSubtestId()));
		$this->_store();
	}

	function getSubtestId()
	{
		return $this->subtestId;
	}
	
	function getTestRunId()
	{
		return $this->testRunId;
	}
	
	/**
	 * Get the step of the current test run block
	 * @return int Step number
	 */
	function getStep()
	{
		return $this->step;
	}

	/**
	 * Checks if this test run block is empty.
	 * @return boolean
	 */
	function isEmpty()
	{
		return (!$this->data);
	}

	/**
	 * Creates a database record to go with the object.
	 * @param string Optional data to add to the test run block.
	 */
	function create($data = '')
	{
		$test = $GLOBALS['BLOCK_LIST']->getBlockById($this->testRun->getTestId());
		
		if (TestStructure::wantToTrackStructure($test->getId())) {
			$accessType = $this->testRun->getAccessType();
			$structure = TestStructure::loadStructure($test->getId(), 0, $accessType);
			
			$version = $structure['version'];
			$structure = $structure['content'];

		} else {
			$structure = TestStructure::getStructure($test->getId());
			$version = NULL;
		}
		$memory = memory_get_usage();
			if (($memory + 16000000) > MEMORY_LIMIT && !SPEEDMODE)
				unstore_all();
		if ($this->subtestId != 0) $tmpStructure = TestStructure::filterStructureBySubtestId($structure, $this->subtestId);
		$tmpStructure = TestStructure::randomizeStructure($structure, $test->getId());
		// normalize structure array
		$structure = array();
		foreach($tmpStructure as $elem) 
			$structure[] = $elem;
		
		$testStructure = serialize($structure);
		$query = "INSERT INTO ".DB_PREFIX."test_run_blocks VALUES(?,?,?,?,?,?,?)";
		$this->db->query($query, array($this->testRunId, $this->subtestId, $this->availableItems, $this->shownItems, $this->availableRequiredItems, $this->answeredRequiredItems, $this->step));
		$query = "INSERT INTO ".DB_PREFIX."test_run_block_content VALUES(?,?,?)";
		$data = base64_encode(gzcompress($data, GZCOMPRESSLVL));
		$this->db->query($query, array($this->testRunId, $this->subtestId, $data));
		
		$testStructure = base64_encode(gzcompress($testStructure, GZCOMPRESSLVL));
		
		$query = "SELECT * FROM ".DB_PREFIX."block_structure WHERE test_structure = ? ORDER BY test_run_id ASC, subtest_id  ASC";
		$res = $this->db->query($query, array($testStructure));

		if ($res->fetchInto($row)) {
			$testStructure = $row['test_run_id']."+".$row['subtest_id'];
		}
		$query = "INSERT INTO ".DB_PREFIX."block_structure VALUES(?,?,?)";
		
		$this->db->query($query, array($this->testRunId, $this->subtestId, $testStructure));
		$this->testRun->setStructureVersion($version);
	}

	/**
	 * Returns a list of GivenAnswerSet objects containing the information
	 * stored in this test run block.
	 * @param boolean For top-level test run blocks, include the data of subtest test run blocks (set to NULL to keep subtest links in a list in array key 'subtests')
	 * @return GivenAnswerSet[]
	 */
	function getGivenAnswerSets($includeSub = false)
	{
		$sets = array();
		foreach ($this->plainData as $row) {
			if (isset($row['subtest'])) {
				if ($includeSub === FALSE) continue;

				# Keep link
				if (!$includeSub) {
					if (!isset($sets['subtests'])) $sets['subtests'] = array();
					$sets['subtests'][] = $row['subtest'];
					continue;
				}

				$subBlock = new TestRunBlock($this->testRun, $row['subtest']);
				if ($subBlock->isEmpty()) continue;
				$sets = array_merge($sets, $subBlock->getGivenAnswerSets());
				continue;
			}

			// Compensate for old bug
			if (!$row['block_id']) continue;

			// $iid = $row['item_id'];
			// $sets["$row[block_id]:$iid"] = new GivenAnswerSet($this->testRun, $row);
			$sets[$this->getKey($row)] = new GivenAnswerSet($this->testRun, $row);
		}
		return $sets;
	}

	/**
	 * Adds more datasets to this test run block.
	 * @param mixed[] The dataset(s) to add.
	 * @param boolean True if the first parameter is an array of datasets, false if it's a single set.
	 * @param boolean True to commit the change to the database; otherwise, call commit() later.
	 * @return boolean
	 */
	function add($data, $isMany = false, $commit = true)
	{
		if (!$isMany) {
			$data = array($data);
		}
		// Filter out duplicate steps
		while (count($data) > 1) {
			$row = reset($data);
			$oldRow = end($this->plainData);
			if (!$oldRow || !isset($oldRow['step_number'])) break;
			if (!isset($row['step_number']) || $row['step_number'] > $oldRow['step_number']) break;

			array_shift($data);
		}

		$codedData = self::encodeBlockData($data);
		foreach ($data as $row) {
			$this->plainData[$this->getKey($row)] = $row;
		}

		// If our data is *really* empty, we need to create the database row first
		if ($this->data === NULL || $this->createPending) {
			$codedData = strval($this->data) . $codedData;
			if ($commit) {
				$this->create($codedData);
			} else {
				$this->createPending = true;
			}
			$this->data = $codedData;
			$this->_store();

			return;
		}
		$this->data .= $codedData;
		if ($commit) {
			if ($this->commitPending) {
				$this->commit();
			} else {
				$res = $this->db->getOne('SELECT content FROM '.DB_PREFIX.'test_run_block_content WHERE test_run_id=? AND subtest_id=?', array($this->getTestRunId(), $this->getSubtestId()));
				if (!empty($res) && is_string($res))
					$res = gzuncompress(base64_decode($res));
				$concat = $res.$codedData; 
				$concat = base64_encode(gzcompress($concat, GZCOMPRESSLVL));
				
				$query = 'UPDATE '.DB_PREFIX.'test_run_block_content SET content = ? WHERE test_run_id=? AND subtest_id=? ';
				$res = $this->db->query($query, array($concat, $this->getTestRunId(), $this->getSubtestId()));
			}
		} else {
			$this->commitPending = true;
		}
		$this->_store();
	}

	/**
	 * In the top-level test run block, add a "symlink" to a subtest test run block (if none exists yet).
	 * @param int The ID of the subtest.
	 * @param boolean True to commit the change to the database; otherwise, call commit() later.
	 */
	function addLinkToSub($subtest, $commit = true)
	{
		// Ensure we're dealing with the top-level block
		if ($this->subtestId != 0) {
			trigger_error("Tried to add a symlink to non-toplevel test run block #{$this->testRunId}:{$this->subtestId}", E_USER_ERROR);
			return;
		}

		if (is_numeric($subtest) || $subtest === NULL) {
			$subtestId = intval($subtest);
		} else {
			$subtestId = $subtest->getId();
		}
		if (isset($this->links[$subtestId])) return;
		$this->links[$subtestId] = 1;

		$this->add(array('subtest' => $subtestId), false, $commit);
	}

	/**
	 * Empties the block.
	 * @param boolean True to commit the change to the database; otherwise, call commit() later.
	 */
	function clear($commit = true)
	{
		if (!$this->data) return;

		$this->data = '';
		$this->links = array();
		$this->plainData = array();
		$this->commitPending = true;

		if ($commit) {
			$this->commit();
		}
		$this->_store();
	}

	/** Commits previously queued database changes.
	 */
	function commit()
	{
		if ($this->createPending) {
			$this->create($this->data);
			$this->commitPending = false;
			$this->createPending = false;
			//$this->_store();
		} elseif ($this->commitPending) {
			$query = 'UPDATE '.DB_PREFIX.'test_run_block_content SET content = ? WHERE test_run_id=? AND subtest_id=?';
			$this->db->query($query, array($this->data, $this->getTestRunId(), $this->getSubtestId()));
			$this->commitPending = false;
			//$this->_store();
		}
	}

	/**
	 * Gets plain data array key for this entry.
	 */
	static function getKey($arr)
	{
		if (isset($arr['subtest'])) {
			return "subtest $arr[subtest]";
		}
		return $arr['block_id'] .'_'. $arr['item_id'];
	}

	/**
	 * Stores self in object cache.
	 * @access private
	 */
	function _store()
	{
		$memory = memory_get_usage();
		if (($memory + 16000000) < MEMORY_LIMIT  || SPEEDMODE)
		store('TestRunBlock', array($this->testRunId, $this->subtestId), $this);
	}

	/**
	 * Invalidates the object cache entry for this object.
	 */
	function invalidate()
	{
		unstore('TestRunBlock', array($this->testRunId, $this->subtestId));
	}

	/**
	 * Returns the number of available items
	 * @return int The number of available items or NULL
	 */
	function getAvailableItems()
	{
		return $this->availableItems;
	}
	function setAvailableItems($value)
	{
		$this->availableItems = $value;
		$this->shownItems = 0;
	}

	/**
	 * Returns the number of available required items
	 * @return int The number of available required items or NULL
	 */
	function getAvailableRequiredItems()
	{
		return $this->availableRequiredItems;
	}
	function setAvailableRequiredItems($value)
	{
		$this->availableRequiredItems = $value;
		$this->answeredRequiredItems = 0;
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
	 * Returns the number of required items answered so far
	 * @return int The number of required items answered so far
	 */
	function getAnsweredRequiredItems()
	{
		return $this->answeredRequiredItems;
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
	 * Calculates the test run block (subtest) progress by means of the step
	 * 
	 * @returns Percentage value of test run block (subtest) progress
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
			$maxStep = count($this->getStructure());
			if($maxStep == 0)
				return 0;
			else	
				return floor((max(0, min(1, $step/$maxStep))) * 100);
		}

	}

	/**
	 * Increases the number of shown items by 1
	 */
	function increaseShownItems()
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_run_blocks SET shown_items=shown_items+1 WHERE test_run_id=? AND subtest_id=?", array($this->testRunId, $this->subtestId));
		$this->shownItems++;
	}

	/**
	 * Increases the number of answered required items by 1
	 */
	function increaseAnsweredRequiredItems()
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_run_blocks SET answered_required_items=answered_required_items+1 WHERE test_run_id=? AND subtest_id=?", array($this->testRunId, $this->subtestId));
		$this->answeredRequiredItems++;
	}

	/**
	 * @access private
	 */
	var $validIncreaseColumns = array(
		"shown_items",
		"answered_required_items",
	);

	/**
	 * Increases multiple counters at once
	 *
	 * These counters are available:
	 * - shown_items
	 * - answered_required_items
	 *
	 * @param string[] The counters to increase
	 */
	function updateStatistics($increase)
	{
		if (! $increase) {
			return;
		}
		$sets = array();
		foreach ($increase as $column) {
			if (! in_array($column, $this->validIncreaseColumns)) continue;
			$sets[] = $column."=".$column."+1";

			$column = snakeToCamel($column, FALSE);
			$this->$column++;
		}
		if (count($sets) == 0) return;
		$this->db->query("UPDATE ".DB_PREFIX."test_run_blocks SET ".implode(",", $sets)." WHERE test_run_id=? AND subtest_id=?", array($this->testRunId, $this->subtestId));
	}

	/** Change step of this test run block
	 *  @param int New step number
	 */
	function setStep($step)
	{
		$this->db->query("UPDATE ".DB_PREFIX."test_run_blocks SET step=? WHERE test_run_id=? AND subtest_id=?", array($step, $this->testRunId, $this->subtestId));
		$this->step = $step;
	}

	/**
	 * Decodes data pulled from the database.
	 * @param string The data.
	 * @return string The decoded data.
	 * @static
	 */
	 
	 static function decodeBlockDataV1($blurb) //without gzcompress
	{
		$result = array();
		$blurb = explode("\n", $blurb);
		foreach ($blurb as $line) {
			if (!$line) continue;

			$line = unserialize(urldecode($line));

			$result[self::getKey($line)] = $line;
		}
		return $result;
	}
	 
	static function decodeBlockData($blurb) // current Version
	{
		if(!empty($blurb) && is_string($blurb))
			$blurb = gzuncompress(base64_decode($blurb));
		$result = array();
		$blurb = explode("\n", $blurb);
		foreach ($blurb as $line) {
			if (!$line) continue;

			$line = unserialize(urldecode($line));

			$result[self::getKey($line)] = $line;
		}
		return $result;
	}

	/**
	 * Encodes data into the database format.
	 * @param string The data.
	 * @return string The decoded data.
	 * @static
	 */
	static function encodeBlockData($blurb)
	{	
		$result = '';
		foreach ($blurb as $record) {
			$result .= urlencode(serialize($record)) ."\n";
		}
		return $result;
	}
	
	/**
	 * Encodes data into the database format with zip.
	 * @param string The data.
	 * @return string The decoded data.
	 * @static
	 */
	static function encodeBlockDataZip($blurb)
	{	
		$result = '';
		foreach ($blurb as $record) {
			$result .= urlencode(serialize($record)) ."\n";
		}
		$result = base64_encode(gzcompress($result, GZCOMPRESSLVL));
		return $result;
	}

}

