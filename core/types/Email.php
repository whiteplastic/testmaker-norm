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


class Email
{
	private $db;
	private $id;
	private $tests = array();
	private $conditions = array();
	private $subject;
	private $body;
	private $testRunSent;
	private $sendStart;
	
	/**
	 * Creates a new email object.
	 */
	function Email($id = NULL)
	{
		if (! isset($GLOBALS["dao"])) return;
		$this->db = &$GLOBALS['dao']->getConnection();
		
		$query = "SELECT * FROM ".DB_PREFIX."emails WHERE id=?";
		$res = $this->db->query($query, array($id));

		if ($res->numRows() == 1)
		{
			while($row = $res->fetchRow())
			{
				$this->id = $row['id'];
				$this->subject = $row['subject'];
				$this->body = $row['body'];
				$this->testRunSent = $row['testrun_sent'];
				$this->sendStart = $row['send_start'];
			}
			$res->free();
		}
		
		$query = "SELECT * FROM ".DB_PREFIX."email_conditions WHERE email_id=?";
		$res = $this->db->query($query, array($id));
		
		while($row = $res->fetchRow())
		{
			$this->conditions[$row['test_id']]['conditions'][] = $row;
		}
		$res->free();
		
		$query = "SELECT test_id, conditions_need_all, participants, participants_group FROM ".DB_PREFIX."emails_connect WHERE email_id=?";
		$res = $this->db->query($query, array($id));
		
		while($row = $res->fetchRow())
		{
			$this->tests[] = $row['test_id'];
			$this->conditions[$row['test_id']]['need_all'] = $row['conditions_need_all'];
			$this->conditions[$row['test_id']]['participants'] = $row['participants'];
			$this->conditions[$row['test_id']]['participants_group'] = $row['participants_group'];
		}
		$res->free();
	}
	
	function getId()
	{
		return $this->id;
	}
	
	function getParticipants($test_id)
	{
		if (isset($this->conditions[$test_id]))
			return $this->conditions[$test_id]['participants'];
		else return NULL;
	}
	
	
	function getParticipantsGroup($test_id)
	{
		if (isset($this->conditions[$test_id]))
			return $this->conditions[$test_id]['participants_group'];
		else return NULL;
	}
	
	
	function getSubject()
	{
		return $this->subject;
	}
	
	function getBody()
	{
		return $this->body;
	}
	
	function setSubject($subject)
	{
		$this->subject = $subject;
	}
	
	function setBody($body)
	{
		$this->body = $body;
	}
	
	function setTests($test_ids)
	{
		if (! is_array($test_ids)) $this->tests = array();
		else $this->tests = $test_ids;
	}
	
	function getTests()
	{
		return $this->tests;
	}
	
	function setConditions($conditions, $test_id, $need_all = 0, $groups = 0, $participants = 1) {
		if (is_array($conditions)) { 
			$this->conditions[$test_id]['conditions'] = $conditions;
			$this->conditions[$test_id]['need_all'] = $need_all;
			$this->conditions[$test_id]['participants_group'] = $groups;
			$this->conditions[$test_id]['participants'] = $participants;
			return true;
		} else return false;
	}
	
	function deleteConditions($test_id) {
		$sql = "DELETE FROM ".DB_PREFIX."email_conditions WHERE email_id=? AND test_id=?";
		$this->db->query($sql, array($this->getId(), $test_id));
	}
	
	function getConditions($test_id = NULL)
	{
		if ($test_id != NULL AND isset($this->conditions[$test_id]['conditions'])) return $this->conditions[$test_id]['conditions'];
		else if ($test_id == NULL) return $this->conditions;
		else return array();
	}
	
	function needsAllConditions($test_id)
	{
		if (isset($this->conditions[$test_id])) return $this->conditions[$test_id]['need_all'] == 1 ? true : false;
		else return false;
	}
	
	function save()
	{
		if (! isset($GLOBALS["dao"])) return false;
		$this->db = &$GLOBALS['dao']->getConnection();
		
		// insert/update email table
		if (isset($this->id)) $query = "UPDATE ".DB_PREFIX."emails SET id=?, subject=?, body=?, testrun_sent=? WHERE id=$this->id";
		else {
			$this->id = $this->db->nextId(DB_PREFIX.'emails');
			$query = "INSERT INTO ".DB_PREFIX."emails SET id=?, subject=?, body=?, testrun_sent=?";
		}
		if (! $this->db->query($query, array($this->id, $this->subject, $this->body, $this->testRunSent))) return false;
		
		// update connection table
		$query = "DELETE FROM ".DB_PREFIX."emails_connect WHERE email_id=?";
		$this->db->query($query, array($this->id));
		
		foreach($this->tests as $test) {
			$query = "INSERT INTO ".DB_PREFIX."emails_connect SET email_id=?, test_id=?";
			if(! $this->db->query($query, array($this->id, $test))) return false;
		}
		
		$this->saveConditions();
		return true;
	}
	
	function saveConditions() {
		// save conditions
		foreach ($this->conditions as $test_id => $test_condition) {
			// remove old conditions
			$sql = "DELETE FROM ".DB_PREFIX."email_conditions WHERE test_id=? AND email_id=?";
			if (!$this->db->query($sql, array($test_id, $this->id))) return false;
			// store new conditions
			if (isset($test_condition['conditions'])) foreach ($test_condition['conditions'] as $condition) {
				// only save conditions for selected tests
				if (in_array($test_id, $this->tests)) {
					$sql = "INSERT INTO ".DB_PREFIX."email_conditions (".implode(",", array_keys($condition)).") VALUES (".implode(", ", array_fill(0, count($condition), "?")).")";
					$values = array_values($condition);
					if (!$this->db->query($sql, $values)) return false;
				}
			}
			// all conditions needed?
			$sql = "UPDATE ".DB_PREFIX."emails_connect SET conditions_need_all=?, participants=?, participants_group=? WHERE email_id=? AND test_id=?";
			if (!$this->db->query($sql, array($test_condition['need_all'], $test_condition['participants'], $test_condition['participants_group'], $this->id,$test_id))) return false;
		}
		return true;
	}
	
	static function getEmailList()
	{
		$emails = array();
		if (! isset($GLOBALS["dao"])) return false;
		$db = &$GLOBALS['dao']->getConnection();
		$query = "SELECT id FROM ".DB_PREFIX."emails";
		$res = $db->getAll($query);
		if ($res && !PEAR::isError($res)) {
			foreach ($res as $email) {
				$emails[] = new Email($email['id']);
			}
			return $emails;
		} else return false;
	}
	
	/**
	Deletes an email
	*/
	static function deleteEmail($email_id)
	{
		if (! isset($GLOBALS["dao"])) return false;
		$db = &$GLOBALS['dao']->getConnection();
		$sql = "DELETE FROM ".DB_PREFIX."emails WHERE id=?";
		if (!$db->query($sql, array($email_id))) return false;
		$sql = "DELETE FROM ".DB_PREFIX."emails_connect WHERE email_id=?";
		if (!$db->query($sql, array($email_id))) return false;
		$sql = "DELETE FROM ".DB_PREFIX."email_conditions WHERE email_id=?";
		if (!$db->query($sql, array($email_id))) return false;
		return true;
	}
	
	function send($amount = 0)
	{
		if ($amount == '') $amount = 0;
		
		libLoad("email::Composer");
		$sent_amount = 0;
		$addresses = $this->getEmailAddresses();
		array_multisort($addresses, SORT_ASC);
		
		if (!isset($this->sendStart)) $this->setSendStart(time());
		
		foreach($addresses as $email => $recipient) {
			if ($sent_amount >= $amount) break;
				$mail = new EmailComposer();
				$mail->setSubject($this->getSubject());
				$mail->setTextMessage($this->getBody());
				$mail->setFrom(SYSTEM_MAIL);
				$mail->addRecipient($email);
				if(!$mail->sendMail()) 
					return false;
				$this->setTestRunSent($recipient['testRunId']);
				$sent_amount++;
		}
		if ($this->testRunSent === null) 
			$this->setSendStart(null);
		if ($sent_amount == 0) 
			return false;
		else 
			return true;
	}
	
	function setTestRunSent($testRunId)
	{	
		$sql = "UPDATE ".DB_PREFIX."emails SET testrun_sent=? WHERE id=?";
		if (!$this->db->query($sql, array($testRunId, $this->id))) return false;
		else $this->testRunSent = $testRunId;
		return true;
	}
	
	function setSendStart($timestamp)
	{
		$sql = "UPDATE ".DB_PREFIX."emails SET send_start=? WHERE id=?";
		if (!$this->db->query($sql, array($timestamp, $this->id))) return false;
		else $this->sendStart = $timestamp;
		return true;
	}
	
	/**
	Returns an array of emailAdresses that apply to the conditions.
	*/
	public function getEmailAddresses($fromTestRun = null)
	{
		require_once(CORE."types/TestRunList.php");
		require_once(CORE."types/UserList.php");
		$testRunList = new TestRunList();
		$userList = new UserList();
		$emailAddresses = array();
		// Loop through all associated tests
		foreach ($this->getTests() as $testId) {
			// Get TestRuns for each test
			foreach ($testRunList->getTestRunsForTest($testId, TRUE, $this->sendStart) as $testRun) {
				// Get the Email-Address for the TestRun, based on the participants group
				if ($this->getParticipantsGroup($testId) == -1 OR $this->getParticipantsGroup($testId) == 0 AND $testRun->getAccessType() == 'tan') {
					require_once(CORE."types/TANCollection.php");
					$email = TANCollection::getEmailByTestRun($testRun->getId());
				} else if (! $userList->getUserById($testRun->getUserId())) {
					continue; 
				} else if ($this->getParticipantsGroup($testId) == 0 OR in_array($this->getParticipantsGroup($testId), $userList->getUserById($testRun->getUserId())->getGroupIds())) {
					$email = $userList->getUserById($testRun->getUserId())->getEmail();
				}
				
				if (! isset($email)) continue; // TestRun did not deliver Email
				
				if (
					($this->getParticipants($testId) == 1)
					OR ($this->getParticipants($testId) == 2 AND $testRun->getAnsweredItemsRatio() == 1)
					OR ($this->getParticipants($testId) == 3 AND $testRun->getAnsweredRequiredItemsRatio() == 1)
					OR ($this->getParticipants($testId) == 4 AND $testRun->getAnsweredRequiredItemsRatio() < 1)
				)
				{
					// If no conditions are set for this test, add the email address to the array
					if (count($this->getConditions($testId)) == 0) {
						$emailAddresses[$email]['testRunId'] = $testRun->getId();
						$emailAddresses[$email]['userId'] = $testRun->getUserId();
					}
					// If conditions are set, check if the answers given in the TestRun apply
					else {
						require_once(CORE."types/Item.php");
						$conditions_fullfilled = false;
						$first = true;
						
						foreach($this->getConditions($testId) as $condition) {
							$answerset = $testRun->getGivenAnswerSetByItemId($condition['item_id']);
							$item = Item::getItem($condition['item_id']);
							if ($item) $correct = $item->evaluateAnswer($answerset);
							
							if (!isset($correct) && $answerset != NULL) {
								$answers = $answerset->getAnswers();
								reset($answers);
								$correct = key($answers) == $condition['answer_id'];
								
							}
							
							if ($this->needsAllConditions($testId) && $first) {
								$conditions_fullfilled = $correct;
								$first = false;
							} elseif ($this->needsAllConditions($testId) && !$first) {
								$conditions_fullfilled = $conditions_fullfilled && $correct;
							} elseif (!$this->needsAllConditions($testId) && $first) {
								$conditions_fullfilled = $correct;
								$first = false;
							} elseif (!$this->needsAllConditions($testId) && !$first) {
								$conditions_fullfilled = $conditions_fullfilled || $correct;
							}
						}
						
						if ($conditions_fullfilled) {
							$emailAddresses[$email]['testRunId'] = $testRun->getId();
							$emailAddresses[$email]['userId'] = $testRun->getUserId();
						}
					}
				}
			}
		}
		foreach($emailAddresses as $email => $info) {
			// Ignore email addresses that have already received the mail
			if ($this->testRunSent >= $info['testRunId']) unset($emailAddresses[$email]);
		}
		
		if (count($emailAddresses > 0)) return $emailAddresses;
		else return false;
	}
	
	public function getRemaining()
	{
		return count($this->getEmailAddresses($this->testRunSent));
	}
	
	public function isLocked()
	{
		return $this->testRunSent === null ? false : true;
	}
}