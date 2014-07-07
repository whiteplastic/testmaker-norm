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
 * Load the GivenAnswer class
 */


/**
 * Represents the answers to a test item.
 *
 * Please note that this object is an encapsulation of data stored somewhere else;
 * therefore, updates performed here will not be saved automatically. Typically,
 * you create test run blocks, then add data about given answers to it and
 *
 * @package Core
 */
class GivenAnswerSet
{
	/**#@+
	 * @access private
	 */
	var $testRun;
	var $testRunId;
	var $data;
	var $stepNumber = NULL;
	var $db;
	/**#@-*/

	/**
	 * Constructor (to be called by <kbd>{@link TestRun}</kbd>)
	 * @see TestRun
	 * @param mixed[] The data row.
	 * @param
	 */
	function GivenAnswerSet(&$testRun, $dataRow)
	{
		$this->testRun = &$testRun;
		$this->testRunId = $testRun->getId();
		$this->db = &$GLOBALS['dao']->getConnection();

		if (!isset($dataRow['answers']) || !is_array($dataRow['answers'])) {
			$dataRow['answers'] = array();
		}
		$this->data = $dataRow;
	}

	/**
	 * Creates a data row from specified parameters.
	 * @param int The ID of the block.
	 * @param int The ID of the item.
	 * @param int The unix timestamp of when the user finished the item.
	 * @param boolean Whether the item was aborted due to an exceeded timeout.
	 * @param int The time taken to answer the item in milliseconds.
	 * @param int The time taken as tracked by the client.
	 * @param int The time taken as tracked by the server.
	 * @param mixed[] An associative array mapping item answer ID to the answer values given by the user.
	 * @return mixed[] A data row as can be used by TestRunBlock and GivenAnswerSet.
	 */

	function prepare($blockId, $stepNumber, $itemId, $finishTime = NULL, $timeout = FALSE, $duration = NULL, $clientDuration = NULL, $serverDuration = NULL, $answers = array(), $wasSkipped = TRUE)
	{
		return array(
			'block_id'			=> $blockId,
			'step_number'		=> $stepNumber,
			'item_id'			=> $itemId,
			'finish_time'		=> $finishTime,
			'timeout'			=> $timeout,
			'duration'			=> $duration,
			'client_duration'	=> $clientDuration,
			'server_duration'	=> $serverDuration,
			'answers'			=> $answers,
		);
	}

	// Test Run
	/**
	 * Returns the ID of the test run this answer set belongs to
	 * @return int The ID of the test run this answer set belongs to
	 */
	function getTestRunId()
	{
		return $this->testRunId;
	}

	/**
	 * Returns the test run this answer set belongs to
	 * @return TestRun The test run this answer set belongs to
	 */
	function getTestRun()
	{
		return $this->testRun;
	}


	// Block
	/**
	 * Returns the ID of the block this answer set refers to
	 * @return int The ID of the block this answer set refers to
	 */
	function getBlockId()
	{
		return $this->data['block_id'];
	}


	// Item
	/**
	 * Returns the ID of the item this set refers to
	 * @return int The ID of the item this set refers to
	 */
	function getItemId()
	{
		return $this->data['item_id'];
	}


	// Duration
	/**
	 * Returns how long the user needed to answer the item this set refers to
	 * @return int How long the user needed to answer the item this set refers to (in ms, so 1500 means 1.5 seconds)
	 */
	function getDuration()
	{
		return $this->data['duration'];
	}
	
	function setDuration($duration)
	{
		return $this->data['duration'] = $duration;
	}


	/**
	 * Returns how long the user needed to answer the item this set refers to (as tracked by the client)
	 * @return int How long the user needed to answer the item this set refers to (as tracked by the client)
	 */
	function getClientDuration()
	{
		return $this->data['client_duration'];
	}

	/**
	 * Returns how long the user needed to answer the item this set refers to (as tracked by the server)
	 * @return int How long the user needed to answer the item this set refers to (as tracked by the server)
	 */
	function getServerDuration()
	{
		return $this->data['server_duration'];
	}


	// Timeout
	/**
	 * Returns whether it took the user to long to answer the item
	 * @return boolean TRUE if there was a timeout, FALSE otherwise
	 */
	function hadTimeout()
	{
		return $this->data['timeout'] == 1 ? TRUE : FALSE;
	}

	// Answers
	/**
	 * Adds an answer to this set
	 * @param int The ID of the answer (the child object of the item)
	 * @param string Any string that somehow represents the answer of the user
	 * @return GivenAnswer The new answer
	 */
	function addGivenAnswer($answerId, $value)
	{
		$this->data['answers'][$answerId] = $value;
		//$ans = new GivenAnswer($this, $answerId, $value);
		//return $ans;
	}

	/**
	 * Returns a list of <kbd>{@link GivenAnswer}</kbd>s that belong to this test run
	 * @return GivenAnswer[] The answers that belong to this test run
	 */
	function getGivenAnswers()
	{
		$answers = array();
		foreach ($this->data['answers'] as $id => $value) {
			$answers[] = new GivenAnswer($this, $id, $value);
		}
		return $answers;
	}
	
	function getAnswers() {
		if (! is_array($this->data['answers'])) return array();
		else return $this->data['answers'];
	}
	
	/**
	 * Returns the last Answer
	 * @return answer The answers that belong to this test run
	 */
	 
	function getLastAnswer()
	{		
		$answers = array();
		foreach ($this->data['answers'] as $id => $value) {
			$answers[] = new GivenAnswer($this, $id, $value);
		}
		$numAnswers = count($answers); 
		return $answers[$numAnswers-1];
	}

	/**
	 * Returns the answer that belongs to a certain item answer
	 * @param int The answer ID
	 */
	function getGivenAnswerByItemAnswerId($itemAnswerId)
	{
		if (isset($this->data['answers'][$itemAnswerId])) {
			return $this->data['answers'][$itemAnswerId];
		}
		return NULL;
	}

	/**
	 * Return the theta value
	 * @return double
	 */
	function getTheta()
	{
		if (isset($this->data['theta'])) {
			return $this->data['theta'];
		}
		else
			return NULL;
	}

	/**
	 * Return the standard error of measurement
	 * @return double
	 */
	function getSem()
	{
		if (isset($this->data['theta'])) {
			return $this->data['sem'];
			}
		else
			return NULL;
	}

	/**
	 * Sets the theta value and the standard error of measurement
	 * @param double Theta value
	 * @param double Standard error of measurement
	 */
	function setThetaAndSem($theta, $sem)
	{
		$this->data['theta'] = $theta;
		$this->data['sem'] = $sem;
	}

	/**
	 * Returns the timestamp for when the user finished processing the item
	 * @return int Unix timestamp
	 */
	function getFinishTime()
	{
		return $this->data['finish_time'];
	}

	function getStepNumber()
	{
		return $this->data['step_number'];
	}

	/**
	 * Returns an encoded version of this given answer set suitable for storage in the database.
	 */
	function getData()
	{
		return $this->data;
	}

	function getWasSkipped()
	{
			$answers = array();
			$answers = $this->getAnswers();
		// item was skipped by user or did not fulfill the conditions and was never shown
	    	if ($this->getDuration() > 0 && !$this->hadTimeout() && !count($answers)>0)
	    		return TRUE;
	    	else
	    		return FALSE;
	}
}
?>
