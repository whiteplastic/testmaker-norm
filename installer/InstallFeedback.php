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
 * Sends Back Feedback of TM usage (optional)
 *
 * @package Installer
 */
class InstallFeedback
{
	/**#@+
	 * @access private
	 */
	var $db;
	var $installer;

	/**
	 * @param mixed An object with a conforming status() method.
	 */
	function InstallFeedback(&$frontend)
	{
		$this->front = &$frontend;
		$this->front->addStep('enter_feedback', $this);
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


	
	function checkEnterFeedback()
	{
		$this->preinit();
		if($pretest = $this->db->getOne("SELECT * FROM `".DB_PREFIX."settings` WHERE `name` = 'installfeedback' AND `content` = '1'"))
			return false;
		else
		return true;	
	}
	
	function doEnterFeedback()
	{
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$this->db->query("INSERT INTO `".DB_PREFIX."settings` (`name`, `content`) VALUES ('installfeedback', '1')");
		return $this->front->page->renderTemplate('InstallFeedback.html', array("url" => "$host$uri", "version" => TM_VERSION), true);
	}

	function doAllChecks()
	{
		// Return OR'd combination of all checks (i.e. just one check needs to return true)
		return ($this->checkEnterFeedback());
	}

}

?>
