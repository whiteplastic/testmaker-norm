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
 * Install or update this testMaker site.
 * @package Installer
 */

ob_implicit_flush(TRUE);

libLoad('utilities::snakeToCamel');
libLoad('environment::MsgHandler');

/**
 * Handles creating and updating table structures.
 *
 * @package Installer
 */
class AdvancedMigrations
{
	/**#@+
	 * @access private
	 */
	var $db;
	var $installer;

	/**
	 * Initializes the migrator component and connects it to the
	 * installer frontend.
	 *
	 * @param mixed An object with a conforming status() method.
	 */
	function AdvancedMigrations(&$frontend)
	{
		$this->front = &$frontend;
		$this->front->addStep('blob_test_runs', $this);
		$this->front->addStep('test_run_duration', $this);
		$this->front->addStep('test_run_gn2id', $this);
		$this->front->addStep('kill_test_structure_redu', $this);
		$this->front->addStep('test_run_duration2', $this);
		$this->front->addStep('cleanMedia', $this);
	}

	function preinit()
	{
		if (!isset($GLOBALS['dao'])) {
			if (!$this->front->database->try_connect()) {
				trigger_error("This error message should not occur. Please report it to the developers team.");
				exit;
			}
		}
		$this->db = &$GLOBALS['dao']->getConnection();
	}

	function checkBlobTestRuns()
	{
		$this->preinit();

		$tables = $this->db->getListOf('tables');
		if (!in_array(DB_PREFIX.'given_answer_sets', $tables)) return false;

		$cnt = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'given_answer_sets');
		return ($cnt > 0);
	}
	
	function checkTestRunDuration()
	{
		$this->preinit();
		// Checks if Table t_r_b_content exists and contains compressed data, then we are > 3.0 (much faster then the other query)
		@$zpped = $this->db->getOne('SELECT content FROM '.DB_PREFIX.'test_run_block_content LIMIT 1');
		if(!substr($zpped, 0, 2) == "eF") { 
			$cnt = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'test_runs WHERE duration IS NULL');
			return ($cnt > 0);
		}
		else
			return false;
		
	}
	
	function checkTestRunDuration2()
	{
		$this->preinit();
		$res = $this->db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."settings WHERE name = 'testDuration' AND content = '1'");
		if ($res == 0) {
			$cnt = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'test_runs');
			if ($cnt == 0) {
				$this->db->query("INSERT INTO `".DB_PREFIX."settings` VALUES ('testDuration', '1')");
				return false;
				}
			else
				return true;
		}
		else
			return false;
		
	}
	
	function checkTestRunGn2id()
	{
		$this->preinit();
		if($pretest = $this->db->getOne("SELECT * FROM `".DB_PREFIX."settings` WHERE `name` = 'groupstoid' AND `content` = '1'"))
			return false;
		else {
			$cnt = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'test_runs');
			if ($cnt == 0) {
				$this->db->query("INSERT INTO `".DB_PREFIX."settings` VALUES ('groupstoid', '1')");
				return false;
				}
			else
				return true;
			}
		
	}
	
	function checkKillTestStructureRedu()
	{
		$this->preinit();
		$res = $this->db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."settings WHERE name = 'ktsr' AND content = '1'");
		if ($res == 0) {
			$cnt = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'block_structure');
			if ($cnt == 0) {
				$this->db->query("INSERT INTO `".DB_PREFIX."settings` VALUES ('ktsr', '1')");
				return false;
				}
			else
				return true;
			
		}
		else
			return false;
	}
	
	function checkCleanMedia()
	{
		$this->preinit();
		$res = $this->db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."settings WHERE name = 'cleanMedia' AND content = '1'");
		if ($res == 0) {
			$cnt = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'media');
			if ($cnt == 0) {
				$this->db->query("INSERT INTO `".DB_PREFIX."settings` VALUES ('cleanMedia', '1')");
				return false;
				}
			else
				return true;
			
		}
		else
			return false;
	}

	function doBlobTestRuns()
	{
		return $this->front->page->renderTemplate('InstallMigrateBlobTestRuns.html', array(), true);
	}
	
	function doTestRunDuration()
	{
		return $this->front->page->renderTemplate('InstallTestRunDuration.html', array(), true);
	}
	
	function doTestRunDuration2()
	{
		return $this->front->page->renderTemplate('TestRunDuration2.html', array(), true);
	}
	
	function doTestRunGn2id()
	{
		return $this->front->page->renderTemplate('InstallTestRunGn2id.html', array(), true);
	}
	
	function doKillTestStructureRedu()
	{
		return $this->front->page->renderTemplate('InstallKillTestStructureRedu.html', array(), true);
	}
	
	function doCleanMedia()
	{
		return $this->front->page->renderTemplate('cleanMedia.html', array(), true);
	}

	function doAllChecks()
	{
		// Return OR'd combination of all checks (i.e. just one check needs to return true)
		return ($this->checkBlobTestRuns() OR $this->checkTestRunDuration() OR $this->checkTestRunGn2id() OR 
		$this->checkKillTestStructureRedu() OR $this->checkTestRunDuration2() OR $this->checkCleanMedia());
	}

}

?>
