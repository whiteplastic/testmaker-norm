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
 * Load required classes
 */
require_once(INSTALLER.'AdvancedMigrations.php');
require_once(INSTALLER.'Config.php');
require_once(INSTALLER.'Database.php');
require_once(INSTALLER.'Installer.php');

/**
 * Guides the user through the task of installing or updating testMaker.
 *
 * Default action: {@link doWelcome()}
 *
 * @package Installer
 */
class InstallerPage extends Page
{
	/**#@+
	 * @access private
	 */
	var $body = '';
	var $defaultAction = 'welcome';
	var $header;
	var $inst;
	/**#@-*/

	function doWelcome()
	{
		if (!$this->init()) exit;
		$this->inst->markAllStepsUndone();

		$langs = array();
		$pending = get('pending') ? 1 : 0;
		foreach ($GLOBALS['PORTAL']->getAvailableLanguages() as $name => $code) {
			if ($code == $GLOBALS['TRANSLATION']->getLanguage()) continue;
			$langs[] = array(
				array('lang_code', $code),
				array('lang', $name),
				array('pending', $pending),
			);
		}
	
		if ($pending) {
			$welcome = $this->renderTemplate("InstallPending.html",
			array('lang_select' => $langs), true);
		}
		else {
			$welcome = $this->renderTemplate("InstallWelcome.html",
			array('lang_select' => $langs), true);
		}
		
		$logo = "portal/images/tm-logo-sm.png";

		$this->renderTemplate('BareFrame.html', array(
			'page_title' => T('ui.t_welcome'),
			'body' => $welcome,
			'logo' => $logo,
		));
	}

	function doFinish()
	{
		$this->init();
		$finish = $this->renderTemplate("InstallFinish.html",
		array(), true);		

		$logo = "portal/images/tm-logo-sm.png";

		$this->renderTemplate('BareFrame.html', array(
			'page_title' => T('ui.t_finish'),
			'body' => $finish,
			'logo' => $logo,
		));
	}

	function runInstall()
	{
		// Verbose mode
		if (get('verbose', false)) $this->inst->setVerbose();

		// Find next step
		$step = $this->inst->findNextStep();
		if (!$step) {
			$this->doFinish();
			return;
		}

		// Perform step
		$res = $this->inst->executeStep($step);
		if ($res === false) {
			$this->inst->markStepDone($step);
			$GLOBALS['MSG_HANDLER']->addMsg("ui.step.$step.success",
				MSG_RESULT_POS);
		}

		if ($res !== false) {
			$template_vars = array();

			if (is_string($res)) {
				$template_vars['install_body'] = $res;
			}

			// Output link to next step
			if ($this->inst->showLink) $template_vars['install_auto'] = true;

			$this->body = $this->renderTemplate(
				"InstallFrame.html", $template_vars, true
			);

			$logo = "portal/images/tm-logo-sm.png";
				
			$this->renderTemplate("BareFrame.html", array(
				'body' => $this->body,
				'page_title' => T("ui.step.$step"),
				'logo' => $logo,
			));

			return;
		}
		$this->runInstall();
	}

	function doAuto()
	{
		$this->init();
		$this->runInstall();
	}

	/**
	 * The constructor's small brother. Inherited constructor demingling,
	 * anyone?
	 * @access private
	 */
	function init()
	{
		if ($lang = get('language')) {
			$GLOBALS['PORTAL']->setLanguage($lang);
		}

		if (version_compare(PHP_VERSION, "5", "<")) {
			$logo = "portal/images/tm-logo-sm.png";

			$this->renderTemplate('BareFrame.html', array(
			'page_title' => T('ui.t_welcome'),
			'body' => "<p>".T('ui.phpversion')."</p>",
			'logo' => $logo,
			));
			return false;
		}

		if (!isset($this->inst)) $this->inst = new Installer($this);
		if (!isset($this->header)) {
			header("Cache-Control: no-cache");
			$this->header = true;
		}
		return true;
	}
}

?>
