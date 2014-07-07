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

require_once("../../lib/utilities/StopWatch.php");
require_once(dirname(__FILE__)."/../../init.php");
require_once(ROOT.'portal/init.php');
require_once 'ProgressBar.class.php';
require_once(CORE.'init.php');
$db = $dao->getConnection();



//if(!isset($_GET["stop"]))
//checkversion();



if(isset($_GET["go"]))
startcompress();
else if(isset($_GET["stop"]))
copyandclean();
else 
startmsg();

function copyandclean() {
	global $db;
	$db->query("DROP TABLE `".DB_PREFIX."block_structure`");
	$db->query("ALTER TABLE `".DB_PREFIX."trbc_tmp2` RENAME AS `".DB_PREFIX."block_structure`");
	echo "Conversion successfull please go back to the <a href=\"../../index.php\">Testmaker Mainpage</a>";
}

function startmsg() {
	$url = selfURL()."?go=1&run=100";
	$url2 = selfURL()."?go=1&run=200";
	if(function_exists('gzcompress') && function_exists('base64_encode'))
	echo "The internal storage format of the block structure data has changed and a conversion is needed.<br>  Please do not abort the process, it may take up to 60 minutes to complete. 
	Please initiate the conversion process:<br><br>
	<u>The process is very CPU/Memory intense and <b>can timeout</b> because of that!</u><br><br>
	If you have a slow Database Computer (<= 2GB of Memory or <= 2GHz CPU) use this link: <a href='$url'><b>start slow (safe) conversion</b></a>.<br>
	If you have a fast Database Computer (more than 2GB of Memory and more than 2GHz CPU) use this link: <a href='$url2'><b>start fast conversion</b></a>.
	</a>";
	else
	echo "The internal storage format of test run data has changed. This new version needs the PHP function \"gzcompress\", but this Webserver does not support it. Please contact your Server Admin to continue.";

}

function checkversion() {

	die ("DB is already uptodate, please go back to the <a href=\"../../index.php\">Testmaker Mainpage</a>");

}

function selfURL() { 
$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['PHP_SELF']; } 

function strleft($s1, $s2) { 
return substr($s1, 0, strpos($s1, $s2));
}

function startcompress() {
	global $db;
	$bar = new ProgressBar();
	$bar->setMessage('loading ...');
	$bar->setForegroundColor('#ff0000');
	
	@$allelements = $db->getOne("SELECT COUNT(test_run_id) FROM `".DB_PREFIX."block_structure`");
	//total number of elements to process
	
	$elements = $_GET["run"]; //Number of Elements processed in one Run
	
	$sql = "SHOW TABLES";
	$result = $db->getAll($sql, DB_FETCHMODE_ORDERED);
	if (!$result) {
		echo "DB Error, could not list tables\n";
		exit;
	}
	$createtmptable = 1;
	foreach ($result as $row) {
		$tmpname = DB_PREFIX."trbc_tmp2";
		if ($row[0] == $tmpname)
		$createtmptable = 0;
	}
	if ($createtmptable == 1)
	$result = $db->query("CREATE TABLE `".DB_PREFIX."trbc_tmp2` (
							`test_run_id` INT UNSIGNED NOT NULL ,
							`subtest_id` INT UNSIGNED NULL DEFAULT NULL ,
							`test_structure` TEXT NOT NULL ,
							INDEX ( `test_run_id` , `subtest_id` )
							)");
	$result = $db->getAll("SELECT `test_run_id` , `subtest_id` FROM `".DB_PREFIX."block_structure` LIMIT ".$elements, DB_FETCHMODE_ORDERED);
	$elements = count($result);
	echo "<p>elements left: $allelements per transition: $elements</p>";
	$bar->initialize($elements); //print the empty bar
	$i = 0;
	foreach($result as $row){
		$id = $row[0];
		$sid = $row[1];
			@$oldcontent = $db->getOne("SELECT `test_structure` FROM `".DB_PREFIX."block_structure` WHERE `test_run_id`=".$id." AND `subtest_id`=".$sid." LIMIT 1 ");
		if(substr($oldcontent, 0, 2) == "eF" || empty($oldcontent)) // Check for already compressed content
		$content = $oldcontent;
		else
		$content = base64_encode(gzcompress($oldcontent, GZCOMPRESSLVL));
		$db->query("INSERT INTO `".DB_PREFIX."trbc_tmp2` ( `test_run_id` , `subtest_id` , `test_structure`) VALUES ('$id', '$sid', '$content');");
		$db->query("DELETE FROM `".DB_PREFIX."block_structure` WHERE `test_run_id`=$id AND `subtest_id`=$sid LIMIT 1");

		$i++;
		$bar->increase(); //calls the bar with every processed element
		$x = round($i/$elements*100);
		$bar->setMessage('End of run: '.$x.'%');
	 }
	 if(($allelements - $elements) > 0) {
		$url = selfURL()."?go=1&run=$elements";
		echo"<head><meta http-equiv=\"refresh\" content=\"1;url=$url\" /></head>";
	 }
	 else
	 {
		$url = selfURL()."?stop=1";
		echo"<head><meta http-equiv=\"refresh\" content=\"1;url=$url\" /></head>";
	 }
}
?>
