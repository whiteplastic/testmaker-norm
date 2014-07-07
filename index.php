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
 * Initializes the system and starts Portal
 * @package Portal
 */

// Load developer options from dev.conf
$GLOBALS["is_dev_machine"] = false;
$GLOBALS["enable_stopwatch"] = false;
$GLOBALS["enable_debug"] = false;
$GLOBALS["enable_post"] = false; 
$GLOBALS["enable_query"] = false; 

$isDevMachine = (file_exists(dirname(__FILE__)."/dev.conf") && !isset($_REQUEST["nodebug"]));

if ($isDevMachine) {
	$GLOBALS["is_dev_machine"] = true;
	$devConf = dirname(__FILE__)."/dev.conf";
	$stopWatchPrintReport = false;
	if (file_exists($devConf)) {
		$file = file($devConf);
		$devOptions = array();
		foreach ($file as $lineNum => $line)
		{
			$temp = explode("=", $line);
			$devOptions[$temp[0]] = str_replace("\n", "", $temp[1]);
		}
		if (array_key_exists("stop_watch", $devOptions) && ($devOptions["stop_watch"] == "1" )) 
			$GLOBALS["enable_stopwatch"] = true; 
		if (array_key_exists("test_run_debug_info", $devOptions) && ($devOptions["test_run_debug_info"] == "1" )) 
			$GLOBALS["enable_debug"] = true; 
		if (array_key_exists("show_post", $devOptions) && ($devOptions["show_post"] == "1" )) 
			$GLOBALS["enable_post"] = true; 
		if (array_key_exists("query_debug", $devOptions) && ($devOptions["query_debug"] == "1" )) 
			$GLOBALS["enable_query"] = true; 
	}
}

// Load the StopWatch
require("lib/utilities/StopWatch.php");

start("Initializing");

// Initialize the system
require(dirname(__FILE__)."/init.php");

// Initialize Portal
start("Initializing Portal");
require(ROOT.'portal/init.php');
stop();

stop();

// Start Portal
start("Running Portal");
$GLOBALS["PORTAL"]->run();
stop();

// Show debug output at the end of the page (can be dangerous e.g. with ajax code)
if ($isDevMachine) {
	
	echo "<script language='javascript'>
	<!--
	function showHide(id)
	{
	    if (document.getElementById(id).style.display == \"none\")
		  { 
		  	document.getElementById(id).style.display = \"block\";

		  } 
		 else 
		  { 
		    document.getElementById(id).style.display = \"none\";
		  } 
	}
	//-->
	</script> 
	<div style=\"text-align: left\";>";
	
	// Show script Memory Usage
	echo "<p>PHP memory usage for this site: <a href='javascript://' onclick=\"showHide('mem');\">[show/hide]</a></p>";
	echo "<span id='mem' style=\"display: none\";>";
	$memory = (integer) round(memory_get_peak_usage(true) / 1024)." KB";
	printVar($memory);
	echo "</span>";
	
	// Show $_SESSION
	echo "<p>_SESSION for this site: <a href='javascript://' onclick=\"showHide('ses');\">[show/hide]</a></p>";
	echo "<span id='ses' style=\"display: none\";>";
	printVar($_SESSION);
	echo "</span>";
	
	//Show Querys
	if(isset($GLOBALS["enable_query"]) && $GLOBALS["enable_query"] == TRUE) {
		echo "<p>query number & querys: <a href='javascript://' onclick=\"showHide('qu');\">[show/hide]</a></p></p>";
		echo "<span id='qu' style=\"display: none\";>";
		$GLOBALS['all_the_queries'] = @array_unique($GLOBALS['all_the_queries']);
		printVar($GLOBALS['global_query_counter']);
		printVar($GLOBALS['all_the_queries']);
		echo "</span>";
	}
	
	// Show $_POST Vars
	if(!empty($_POST) && isset($GLOBALS["enable_post"]) && $GLOBALS["enable_post"] == TRUE) {
		echo "<p>PHP _POST vars : <a href='javascript://' onclick=\"showHide('postv');\">[show/hide]</a></p>";
		echo "<span id='postv' style=\"display: none\";>";
		printVar($_POST);
		echo "</span>";
	}
	
	$found = FALSE;
	foreach (headers_list() as $item) {
		if(!$found) $found = strpos($item,"text/html");
	}
	
	// Show stop watch report
	if($found && isset($GLOBALS["enable_stopwatch"]) && $GLOBALS["enable_stopwatch"] == TRUE)
	{
		echo "<p>page generation time (stopwatch): <a href='javascript://' onclick=\"showHide('sw');\">[show/hide]</a></p></p>";
		echo "<span id='sw' style=\"display: none\";>";
		$stopWatch = getStopWatch();
		$stopWatch->printReport();
		echo "</span>";
	}

	// Print additional debug infos
	if(array_key_exists("DEBUG", $_SESSION) && !empty($_SESSION["DEBUG"])) {
		echo "<p>additional debug infos: <a href='javascript://' onclick=\"showHide('addb');\">[show/hide]</a></p></p>";
		echo "<span id='addb' style=\"display: none\";>";
		printVar($_SESSION["DEBUG"]);
		echo "</span>";
	}
	// Unix only
	if(function_exists("sys_getloadavg")) {
		$load = sys_getloadavg();
		echo "Server load: (".round($load[0],1).", ".round($load[1],1).", ".round($load[2],1).")";
	}
	echo "</div>";
}