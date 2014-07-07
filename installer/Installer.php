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
 * @package Installer
 */

/**
 * Coordinates the installation process.
 * @package Installer
 */
class Installer
{
	/**#@+
	 * @access private
	 */
	var $advMigrate;
	var $config;
	var $database;
	var $installFeedback;
	var $isVerbose = false;
	var $page;
	var $showLink = true;
	var $steps = array();
	/**#@-*/

	function Installer(&$page)
	{
		$this->page = &$page;
		$this->config = new Config($this);
		$this->database = new Database($this);
		$this->advMigrate = new AdvancedMigrations($this);
		$this->installFeedback = new InstallFeedback($this);
	}

	/**
	 * Makes the installer forward verbose information to the client.
	 */
	function setVerbose()
	{
		$this->isVerbose = true;
	}

	/**
	 * Disables "next step" link for the current view.
	 */
	function disableLink()
	{
		$this->showLink = false;
	}

	/**
	 * Used as a callback by installation objects.
	 *
	 * @param integer One of MSG_RESULT_(POS, NEUTRAL, NEG).
	 * @param string The name of the translation string to be displayed.
	 * @param array An optional hash of parameters for the translation engine.
	 */
	function status($type, $message)
	{
		// get optional args to message
		$args = (func_num_args() >= 3) ? func_get_arg(2) : array();

		// in production, we don't want annoying messages for every step
		if ($type != MSG_RESULT_NEG && !$this->isVerbose) return;

		// if we got here, we'd better log the problem
		$GLOBALS['MSG_HANDLER']->addMsg($message, $type, $args);
	}

	function addStep($name, &$object)
	{
		$this->steps[$name] = &$object;
	}

	function findNextStep()
	{
		foreach ($this->steps as $key => $_foo) {
			if (isset($_SESSION['install_steps']) &&
				isset($_SESSION['install_steps'][$key])) {
				continue;
			}
			if (!$this->checkStepNecessity($key)) {
				$this->markStepDone($key);
				continue;
			}
			return $key;
		}
		return NULL;
	}

	function checkStepNecessity($step)
	{
		$method = 'check'. snakeToCamel($step);
		return $this->steps[$step]->$method();
	}

	function executeStep($step)
	{
		$method = 'do'. snakeToCamel($step);
		return $this->steps[$step]->$method();
	}

	function markStepDone($name)
	{
		if (!isset($_SESSION['install_steps'])) {
			$_SESSION['install_steps'] = array();
		}
		$_SESSION['install_steps'][$name] = '1';
	}

	function markAllStepsUndone()
	{
		unset($_SESSION['install_steps']);
	}

	function getSteps()
	{
		return $this->steps;
	}
}

/**
 * Mock frontend for the installer that does nothing.
 *
 * @package Installer
 */
class MockInstaller
{
	function status($foo, $bar) {}
	function addStep($foo, $bar) {}
}

?>
