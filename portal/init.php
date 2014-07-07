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
 * Sets up the portal
 *
 * @package Portal
 */

/**
 * Represents the base directory of the library
 */
define("PORTAL", str_replace("\\", "/", dirname(__FILE__))."/");

libLoad("utilities::printVar");

// Only install an error handler if the client is not on the same machine (so it's probably not a developer)
// and if a system email address ist set up (the reports need to go somewhere)
if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && defined('SYSTEM_MAIL'))
{
	// Use the verbose error handler if the necessary details are set
	if (defined("ERROR_MAILS_TO") && defined("ERROR_TRACKER_URL"))
	{
		libLoad("environment::VerboseErrorHandler");

		$projectName = defined("PROJECT_NAME") ? PROJECT_NAME : "testMaker";
		// This is the same logic as in Portal.php, but we accept this duplication because we want to catch errors as early as possible
		$dirName = dirName($_SERVER["SCRIPT_NAME"]);
		if($dirName == "/" || $dirName == "\\\\") $dirName = "";
		$projectLink = (@$_SERVER["HTTPS"] == "on" ? "https://" : "http://").$_SERVER['HTTP_HOST'].$dirName."/";
		ErrorHandler::install($projectName, TM_VERSION, $projectLink, ERROR_TRACKER_URL, array(SYSTEM_MAIL, ERROR_MAILS_TO), array(SYSTEM_MAIL, $projectName));

		require_once(CORE."environment/ErrorInterestChecker.php");
		ErrorHandler::useInterestChecker(array(new ErrorInterestChecker(3600 * 24 * 5), "isInterestingReport"));

		function notifyError()
		{
			static $notificationSent = FALSE;
			if (! $notificationSent && isset($GLOBALS["MSG_HANDLER"])) {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.core.system_error', -1);
				$notificationSent = TRUE;
			}
		}
		ErrorHandler::useNotifier("notifyError");
	}
	// Fallback to the old error handler
	else {
		libLoad("environment::ErrorHandler");
		ErrorHandler::install(SYSTEM_MAIL);
	}
}
else {
	// We have some "position: absolute" elements that would partially cover error messages
	ini_set("error_prepend_string", '<div style="position:relative;z-index:9999;background-color:white;opacity:0.8">');
	ini_set("error_append_string", '</div>');
}

libLoad("environment::Translation");
$GLOBALS["TRANSLATION"]->addRepository(PORTAL."translations/");

libLoad("environment::MsgHandler");
MsgHandler::install();

libLoad('environment::Plugin');
Plugin::register('feedback', 'Fb');
Plugin::register('extconds', 'Ec');
require_once(PORTAL.'DynamicDataPlugin.php');
require_once(PORTAL.'ExtCondPlugin.php');

require_once(CORE.'types/Setting.php');
require_once(CORE.'types/WorkingPath.php');
require_once(CORE.'types/PrivacyPolicy.php');

if (!$inInstaller) {
	require_once(CORE.'types/UserList.php');
}

/**
 * Load the Portal class
 */
require_once(PORTAL."Portal.php");
$GLOBALS["PORTAL"] = new Portal();

session_name("TMID");
$GLOBALS["PORTAL"]->setDefaultPage("start");

?>
