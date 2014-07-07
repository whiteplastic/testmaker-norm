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

require_once("../lib/utilities/StopWatch.php");
require_once(dirname(__FILE__)."/../init.php");
require_once(ROOT.'portal/init.php');
require_once(CORE.'init.php');

function selfURL() { 
$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['PHP_SELF']; } 

function strleft($s1, $s2) { 
return substr($s1, 0, strpos($s1, $s2));
}

$db = $dao->getConnection();
// start
$start_time = array_sum(explode(' ',microtime()));
// end
if($pretest = $db->getOne("SELECT * FROM `".DB_PREFIX."settings` WHERE `name` = 'groupstoid' AND `content` = '1'")) {
	echo "Script has already been run, you can close this window/tab now";
	die();
}

$result = $db->getAll("SELECT `id`,`permission_groups` FROM `".DB_PREFIX."test_runs` ORDER BY `id`", array(), DB_FETCHMODE_ORDERED);
if (PEAR::isError($result)) {

	die($data->getMessage());

}
$count = count($result);
echo "Testruns: $count";

$limit = 25000;

if(!isset($_GET['start'])) {
	$start = "";
	$limitcount = $limit;
	}
else {
	$start = $_GET['start'].",";
	$limitcount = $_GET['start'];
	$limitcount += $limit;
	}
	
if ($count > $limitcount) {
	$url = selfURL()."?start=$limitcount";
		echo"<head><meta http-equiv=\"refresh\" content=\"0;url=$url\" /></head>";
}
$result = $db->getAll("SELECT `id`,`permission_groups` FROM `".DB_PREFIX."test_runs` ORDER BY `id` LIMIT $start $limit", array(), DB_FETCHMODE_ORDERED);
if (PEAR::isError($result)) {

	die($data->getMessage());

}

foreach ($result as $row) {
	$id = $row[0];
	$name = $row[1];
	$names = explode(",",$name);
	$i = 0;
	if(empty($name))
	$groupids = "NULL";
	else
	foreach ($names as $n) {
		//$allquerys[] = "SELECT `id` FROM `".DB_PREFIX."groups` WHERE `groupname` = '".$n."' LIMIT 1";
		$row2 = $db->getOne("SELECT `id` FROM `".DB_PREFIX."groups` WHERE `groupname` = '".$n."' LIMIT 1");
		if (PEAR::isError($row2)) {

			die($data->getMessage());

		}
		if ($i == 0)
		$groupids = $row2;
		else
		$groupids = $groupids.",".$row2;
		$i++;
	}
	if (empty($groupids) && $groupids != "NULL") {
		echo "<br>Failed to determinate Group ID for Testrun: $id (reset to NULL)";
		$groupids = "NULL";
	}
	
	if(!empty($name)) {
		$allconverts[] = "$name -> $groupids";
		//echo "<br>$name -> $groupids";
	}
	$fin = $db->query("UPDATE `".DB_PREFIX."test_runs` SET `permission_groups` = '$groupids' WHERE `id` = $id LIMIT 1");
	if (PEAR::isError($fin)) {

		die($data->getMessage());

	}
}
if(isset($allconverts))
$allconverts = array_unique($allconverts);
//$allquerys = array_unique($allquerys);
printvar($allconverts);
//printvar($allquerys);
if ($count <= $limitcount) {
echo "<p>Finished! you can close this window/tab now<p>";
$q = $db->query("INSERT INTO `".DB_PREFIX."settings` (`name`, `content`) VALUES ('groupstoid', '1')");
}
$query_took = round(array_sum(explode(' ',microtime())) - $start_time);
echo "<p>Duration: $query_took secs</p>";
?>