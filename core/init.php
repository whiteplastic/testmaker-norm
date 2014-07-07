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

/*
$revision = '$Rev$';
$date     = '$Date$';

$version  = substr($revision, 6, -2);
$versiondate = substr($date, 7, 10);

define('VERSION', "3.1.1." . $revision);
define('VERSIONDATE', $versiondate); 
*/

/**
 * Represents the base directory of the core
 */
define("CORE", str_replace("\\", "/", dirname(__FILE__))."/");

/**
 * The current version number of testMaker
 */
define("TM_VERSION", "3.5");
/**
 * The version suffix for the current version (if any)
 */
define("TM_VERSION_SUFFIX", "");

/**
 * The Level for gzcompress(), higher = more CPU load but better DB use (0-9), Default is 6
 */
define("GZCOMPRESSLVL", 6);

/**
 * The max. execution time on this machine
 */
define("MAX_EXE_TIME", ini_get("max_execution_time"));

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * The Memory Limit in Bytes on this machine
 */
define("MEMORY_LIMIT", return_bytes(ini_get("memory_limit")));

/**
 * Speedmode, do not make any memory checks. May lead to memory limit timeouts. (default: FALSE) 
 */
define("SPEEDMODE", FALSE);
/**
 * Stores the current time, to have a global concept of "now".
 * Use this constant whenever you need the current time as in "time when the request started".
 */
define("NOW", time());

start("Adding translations");
libLoad("environment::Translation");
$GLOBALS["TRANSLATION"]->addRepository(CORE."translations/");
stop();

/* Load the configuration file.
 * If that fails, bail out unless we're in the installer.
 */
start("Loading configuration file");
@include_once(CORE.'init/configuration.php');
$inInstaller = strpos(@$_SERVER['REQUEST_URI'], 'installer');
if (!defined('DB_TYPE')) {
	if (!$inInstaller) {
		libLoad("html::preInitErrorPage");
		preInitErrorPage('init.error.no_config_file', array('core_dir' => CORE));
	}
	$config_loaded = false;
} else {
	$config_loaded = true;
}
if (!defined('TMP_DIR') || !TMP_DIR) {
	$tempDir = ROOT.'upload/temp';
	if (! is_dir($tempDir) && !$inInstaller) {
		mkdir($tempDir);
		copy(CORE.".htaccess", $tempDir."/.htaccess");
	}
} else {
	$tempDir = TMP_DIR;
}
// Defining TM_TMP_DIR only here shuts up phpDocumentor
/**
 * The path to the temporary directory
 */
define('TM_TMP_DIR', $tempDir);

if (!$inInstaller) {
	register_shutdown_function("cleanTempDir");
}
stop();


start("Include some files");
require_once(CORE.'init/settings.php');

require_once(CORE.'environment/DataAccess.php');
if (!file_exists(dirname(__FILE__)."/../installer_off")) {
	start("installer/Database.php");
	require_once(INSTALLER.'Database.php');
	stop();
	start("installer/InstallFeedback.php");
	require_once(INSTALLER.'InstallFeedback.php');
	stop();
	start("installer/AdvancedMigrations.php");
	require_once(INSTALLER.'AdvancedMigrations.php');
	stop();
	require_once(INSTALLER.'Installer.php');
}
start("utilities::ObjectCache");
libLoad('utilities::ObjectCache');
stop();
stop();


if ($config_loaded) {
	start("Database connection");
	$dao = new DataAccess(DB_TYPE, DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
	$dao->connect();
	stop();
	if (!file_exists(dirname(__FILE__)."/../installer_off")) {
		start("Installer");
		// Check if database needs to be updated
		if (!$inInstaller) {
			$inst = new MockInstaller();
			$dbInst = new Database($inst);
			$advMig = new AdvancedMigrations($inst);
			$insFed = new InstallFeedback($inst);
			if ($dbInst->checkDbInit() || $dbInst->checkAdvGroupsInit() || $advMig->doAllChecks() || $insFed->doAllChecks()) {
				if (!$dbInst->check_connect()) {
					libLoad('html::dbInitErrorPage');				// 	libLoad('html::preInitErrorPage');
					dbInitErrorPage('init.error.db_no_connect');	//	preInitErrorPage('init.error.db_no_connect');
				}
				$_GET['page'] = 'installer';	//echo'meh'; //b/
				unset($_GET['action']);
				$_GET['pending'] = '1';
			}
		}
		stop();
	}

	if (!$inInstaller) {
		require_once(CORE."types/DataObject.php");
		require_once(CORE."types/BlockList.php");
		require_once(CORE."types/TestStructure.php");
		require_once(CORE."types/MimeTypes.php");
		require_once(CORE.'types/TestStructure.php');
	}
}

/**
 * Cleans the temporary directory every 24 hours
 * Files and empty directories older than 24 hours will be deleted
 */
function cleanTempDir()
{
	// 24 hours
	$age = 60 * 60 * 24;
	$lastCleanFile = TM_TMP_DIR."/.lastclean";

	if (@filemtime($lastCleanFile) + $age < NOW) {
		if ($handle = fopen($lastCleanFile, "w")) {
			fclose($handle);
			libLoad("utilities::deleteOldFiles");
			deleteOldFiles(TM_TMP_DIR, NOW, $age, TRUE);
		}
	}
}

?>
