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
//require_once(CORE.'types/DataObject.php');

/**
 * Handles cronjobs.
 *
 * @package Core
 */
class CronJob
{
	
	/* Master: works as header, contains information for cronjob
	 * Slaves: (optional) defines different tasks for one master
	 * 
	 * Job Master-Entry has all DB fields set except for slave=NULL
	 * Slave-Entrys are not executed without present master
	 * 
	 * (example for mail sending: 
	 *   one master-entry with custom_content = E-Mail-Text
	 *   slave entrys for every email-recipient with destination = a@b.cd
	 * 
	 * master is set "done" when all slaves are done
	 * 
	 */
	
	protected $JobDetails =  array(
				"id" => NULL,
				"start" => NULL,
				"type" => NULL,
				"destination" => NULL,
				"content" => NULL,
				"done" => NULL,
				"task_count" => NULL,
				"tasks_done" => NULL,
				"tasks_notdone" => NULL,			
			);

	protected $gotDetails = 0;
	protected $db;

	function __construct($data = NULL)
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		
		if (isset($data['id'])) {
			$this->JobDetails["id"] = $data['id'];
		}
	}
	

	/**
	 * returns details of current job
	 * @return array JobDetails
	 */
	function getJobInfo()
	{
		if(!$this->gotDetails)
			$this->readJobInfoFromDB();
	
		if(!$this->gotDetails)	//still nothing?
			return NULL;
		else
			return $this->JobDetails;
			
	}
	
	/**
	 * updates details of current job from db
	 */
	function readJobInfoFromDB()
	{
		if($this->JobDetails["id"]) {
		
			// get Job Info from master entry
			if ($res = $this->db->query("SELECT * FROM ".DB_PREFIX."cron_jobs WHERE id = ? AND slave IS NULL", array($this->JobDetails["id"]))->fetchRow()) {
				$this->JobDetails["start"] = $res["tstart"];
				$this->JobDetails["type"] = $res["type"];
				$this->JobDetails["destination"] = $res["destination"];
				$this->JobDetails["content"] = $res["content"];
				$this->JobDetails["done"] = $res["done"];
			}
			else
				return;
		
			// count slaves not done
			$res = $this->db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."cron_jobs WHERE id = ? AND slave IS NOT NULL AND done IS NULL", array($this->JobDetails["id"]));
			$this->JobDetails["tasks_notdone"] = intval($res);
			
			// count slaves done
			$res = $this->db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."cron_jobs WHERE id = ? AND slave IS NOT NULL AND done IS NOT NULL", array($this->JobDetails["id"]));
			$this->JobDetails["tasks_done"] = intval($res);
			
			$this->JobDetails["task_count"] = $this->JobDetails["tasks_done"] + $this->JobDetails["tasks_notdone"];

			$this->gotDetails = 1;
		}

	}
	
		
	/**
	 * Searches Jobs in Database and returns array of job-ids
	 * @return array jobsFound
	 */
	function findJobs()
	{	
		$db = &$GLOBALS['dao']->getConnection();
		if ($res = $db->query("SELECT id FROM ".DB_PREFIX."cron_jobs WHERE slave IS NULL ORDER BY tstart DESC", array())) {
			$jobsFound = array();
			
			while($val = $res->fetchRow()) {
				$jobsFound[] = $val["id"];
			}
			return($jobsFound);
		}		
		else
			return NULL;
	}
	
	
	
	/**
	 * Create a new Job
	 * @param type of job
	 * @param destination / identifyer
	 * @param content of job
	 * @param optional array of array("content"=>?, "destination"=>?) 
	 * @return string
	 * 
	 * BE CAREFUL WITH CREATING JOBS
	 * Once they are written into the DB they will be executed by running cronjob
	 * 
	 */
	function createJob($type, $destination, $content, &$slaves=NULL)
	{

		if($this->gotDetails)
			return NULL;
				
		$timestamp = NOW;	
		$newID = $this->_getNextFreeID();
	
		$this->JobDetails["id"] = $newID;
/*		$this->JobDetails["start"] = $timestamp;
		$this->JobDetails["type"] = $type;
		$this->JobDetails["destination"] = $destination;
		$this->JobDetails["content"] = serialize($content);
*/
		//create slaves (before master, else they would be executed
		if($slaves) {
			$this->JobDetails["task_count"] = sizeof($slaves);
			$slavecounter = 0;
			foreach($slaves as $slave){
				$slavecounter++;
				
				if(!array_key_exists("content", $slave))
					$slave["content"] = NULL;
				else
					$slave["content"] = serialize($slave["content"]);
				
				if(!array_key_exists("destination", $slave))
					$slave["destination"] = NULL;
			
				if (!$this->_createSingleSlave($newID, $timestamp, $type, $slave["destination"], $slave["content"], $slavecounter))
					return 0;		
			}
		}
		
		
		//create master
		if (!$this->_createSingleMaster($newID, $timestamp, $type, $destination, serialize($content)))
			return NULL;
		else
			return 1;
	}
	
	
	
/*	//better use new function above with seperated master/slave creation => less likely to produce timeouts
	function createJob($type, $destination, $content, &$slaves=NULL)
	{
	
		if($this->gotDetails)
			return NULL;
	
		$timestamp = NOW;
		$lastID = $this->db->getOne("SELECT id FROM ".DB_PREFIX."cron_jobs ORDER BY id DESC LIMIT 1", array());
	
		$this->JobDetails["id"] = $lastID + 1;
		$this->JobDetails["start"] = $timestamp;
		$this->JobDetails["type"] = $type;
		$this->JobDetails["destination"] = $destination;
		$this->JobDetails["content"] = serialize($content);
	
		//create slaves (before master, else they would be executed
		if($slaves) {
			$this->JobDetails["task_count"] = sizeof($slaves);
			$slavecounter = 0;
			foreach($slaves as $slave){
				$slavecounter++;
	
				if(!array_key_exists("content", $slave))
					$slave["content"] = NULL;
	
				if(!array_key_exists("destination", $slave))
					$slave["destination"] = NULL;
	
				$res = $this->db->query("INSERT INTO ".DB_PREFIX."cron_jobs VALUES(?, ?, ?, ?, ?, ?, ?)", array(
						$this->JobDetails["id"], $this->JobDetails["start"], $this->JobDetails["type"], $slavecounter, $slave["destination"], $slave["content"], NULL));
	
				if (!$res)
					return 0;
			}
		}
	
	
		//create master
		if (!$this->db->query("INSERT INTO ".DB_PREFIX."cron_jobs VALUES(?, ?, ?, ?, ?, ?, ?)", array(
				$this->JobDetails["id"], $this->JobDetails["start"], $this->JobDetails["type"], NULL, $this->JobDetails["destination"], $this->JobDetails["content"], NULL)))
			return NULL;
	
		return 1;
	}
	
*/	
	

	/**
	 * Delete Job
	 * @param id of job to delete
	 *
	 */
	function deleteJob($id)
	{
	
		$db = &$GLOBALS['dao']->getConnection();
	
		//delete slaves
		$db->query("DELETE FROM ".DB_PREFIX."cron_jobs WHERE slave IS NOT NULL AND id=".$id);
	
		//delete master
		if ( !$db->query("DELETE FROM ".DB_PREFIX."cron_jobs WHERE slave IS NULL AND id=".$id) )
			return 0;
	
	
		return 1;
	}
	
	
	
	
	function _getNextFreeID()
	{
		if(isset($this->db))
			return 1 + $this->db->getOne("SELECT id FROM ".DB_PREFIX."cron_jobs ORDER BY id DESC LIMIT 1", array());
		else {
			$db = &$GLOBALS['dao']->getConnection();
			return 1 + $db->getOne("SELECT id FROM ".DB_PREFIX."cron_jobs ORDER BY id DESC LIMIT 1", array());
		}
	}
	

	
	/**
	 * Create a single master for existing slaves
	 * @param job-id
	 * @param type of job
	 * @param destination / identifyer
	 * @param content of job
	 * 
	 * 
	 */
	function _createSingleMaster($id, $start, $type, $destination, $content)
	{
		
		if (!$this->db->query("INSERT INTO ".DB_PREFIX."cron_jobs VALUES(?, ?, ?, ?, ?, ?, ?)", array(
				$id, $start, $type, NULL, $destination, $content, NULL)))
			return NULL;
		else
			return 1;
		
	}
	

	/**
	 * Create a single slave
	 * @param job-id
	 * @param type of job
	 * @param destination / identifyer
	 * @param content of job
	 * @param slavecounter: id of current slave
	 *
	 */
	function _createSingleSlave($id, $start, $type, $destination, $content, $slavecounter)
	{
		if (!$res = $this->db->query("INSERT INTO ".DB_PREFIX."cron_jobs VALUES(?, ?, ?, ?, ?, ?, ?)", array(
			$id, $start, $type, $slavecounter, $destination, $content, NULL)))
			return NULL;
		else
			return 1;
	
	}
	
	
	
	
}

?>
