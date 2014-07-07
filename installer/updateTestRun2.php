<?php
session_start();
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


/* This script will migrate your test run records to include duration data. */


require_once("../lib/utilities/StopWatch.php");
start("Initializing");
require_once(dirname(__FILE__)."/../init.php");
start("Initializing Portal");
require_once(ROOT.'portal/init.php');
require_once(CORE.'types/TestRunList.php');
require_once(CORE.'types/TestRunBlock.php');
stop();
stop();

ignore_user_abort(true);
define('NUM_STEPS', 50);
$durationHistogram = array();
$numTR = 0;
$db = $dao->getConnection();

$finished = $db->getOne('SELECT content FROM  '.DB_PREFIX.'settings WHERE  `name` LIKE  \'testDuration\'');
if ($finished == 1) {
	echo "</p><p><b>All finished. Bye!</b><br>(you can now close this window and proceed with installation)</p>";
	exit();
}

@$count = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'tmp_duration2');
if (PEAR::isError($count)) {
	$db->query("CREATE TABLE ".DB_PREFIX."tmp_duration2 (
				test_run_id int(11) default '0'
				)
 			");	
	$db->query("INSERT INTO ".DB_PREFIX."tmp_duration2 VALUES(?)", array(0));
}

function fancyForm($autoSubmit = true)
{
	echo "\n\n";
	if ($autoSubmit) {
		echo '<div style="display:none;">';
	}
	$numSteps = intval(post('num_steps', NUM_STEPS));
	echo '<form method="post" action="'.$_SERVER['REQUEST_URI'].'">Number of steps per cycle: <input name="num_steps" size="3" value="'.$numSteps.'" /> | <input type="submit" value="Start test run migration" /><input type="hidden" name="goahead" value="1" /></form>';
	if ($autoSubmit) {
		echo '</div><script type="text/javascript"><!--
document.forms[0].submit();
// --></script>';
	}
	exit();
}

//$tables = $db->getListOf('tables');

$cnt = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'test_runs WHERE start_time > ?' , array("1243504800"));


if (!$cnt) {
	die('This script does not need to be executed anymore.');
}

ob_implicit_flush();
$num = 0;
//If no Session exists get the last test_run_id from the temp table. 
//Also importent when skript was interrupted. No testrun should modified twice.
if (!isset($_SESSION['lastId']) OR ($_SESSION['lastId'] == 0)) {
	$count = $db->getOne('SELECT test_run_id FROM '.DB_PREFIX.'tmp_duration2');
	$_SESSION['lastId'] = $count["test_run_id"];
}
?>

<h1>Test duration migrator</h1>
<p>
	This script will migrate your test run records to include duration data. Please note that this may take a long time. <br>
	If the server has timelimit do not use more than 50 testruns per cycle.</p>
<p>
	<b>Beware:</b> JavaScript must be enabled for this to work.</p>
	
<?php

if (!post('goahead', false)) {
	fancyForm(false);
}

echo "<p>Test runs to be processed: <span id=\"num\" style=\"font-weight:bold;\"></span>

<script language=\"JavaScript\"><!--
function u(t) { document.getElementById('num').innerHTML = t }
// --></script>

";

function step()
{
	global $BLOCK_LIST, $db, $durationHistogram;
	
	$numSteps = post('num_steps', NUM_STEPS);
	$idMax = $_SESSION['lastId'];
	//Get all Testruns after 28-May-2009 12:00:0
	$cnt = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'test_runs WHERE id > ? AND start_time > ?' , array($idMax, "1243504800"));
	if ($cnt == 0) {
		echo "</p><p><b>All finished. Bye!</b><br>(you can now close this window and proceed with installation)</p>";
		$db->query("INSERT INTO `".DB_PREFIX."settings` VALUES ('testDuration', '1')");
		$_SESSION['lastId'] = 0;
		$db->query('DROP TABLE '.DB_PREFIX.'tmp_duration2');

		exit();
	}
	
	echo "<script>u('$cnt');</script>";
	//Get all Testruns after 28-May-2009 12:00:0
	$trid = $db->getAll('SELECT tr.id FROM '.DB_PREFIX.'test_runs tr WHERE tr.id > ? AND tr.start_time > ? LIMIT '.intval($numSteps), array($idMax, "1243504800"), DB_FETCHMODE_ORDERED);
	$trlist = new TestRunList();
	foreach ($trid as $id) {
		$id = $id[0];
		$duration = 0;
		$tr_data = $db->getAll('SELECT * FROM '.DB_PREFIX.'test_run_block_content trb WHERE trb.test_run_id = ?', array($id));
		foreach ($tr_data as $trb) {
			$result = TestRunBlock::decodeBlockData($trb["content"]);
			makeHistogram($result);
			foreach ($result as $key => $data) {
				if (isset($data['duration'])) {
					$duration = $duration + round($data['duration'] / $durationHistogram[$data['duration']], 2);
					$result[$key]['duration'] = round($data['duration'] / $durationHistogram[$data['duration']], 2);
				}
			}
			$result = TestRunBlock::encodeBlockDataZip($result);
			$db->query("UPDATE ".DB_PREFIX."test_run_block_content SET content = ? WHERE subtest_id = ? AND test_run_id = ?", array($result, $trb['subtest_id'], $id));
			$durationHistogram = array();
		}
		$db->query("UPDATE ".DB_PREFIX."test_runs SET duration = ? WHERE id = ?", array($duration, $id));
		$db->query('UPDATE '.DB_PREFIX.'tmp_duration2 SET test_run_id = ?',array($id));
	}
	$_SESSION['lastId'] = $id;

	echo " \n";
}
//"1243504800"
//make a histogram of the occurness of a duration
function makeHistogram($result) {
	global $BLOCK_LIST, $db, $durationHistogram;
	
	$durationOld = 0;
	$Block_IdOld = 0;
	foreach ($result as $data) {
		if (isset($data['block_id']) && isset($data['duration'])) {
			if ($Block_IdOld != $data['block_id']) {
				$Block_IdOld = $data['block_id'];
				$block_data = $db->getAll('SELECT * FROM '.DB_PREFIX.'item_blocks  WHERE id = ?', array($data['block_id']));
			}
			if ($data['duration'] != $durationOld) {
				 $durationOld = $data['duration'];
				 $durationHistogram[$data['duration']] = 1;
			}
			else {
				$durationHistogram[$data['duration']] = $durationHistogram[$data['duration']] + 1;
			}
			if (isset($block_data['items_per_page'])) {
				if ($block_data['items_per_page'] < $durationHistogram[$data['duration']]) {
					$durationHistogram[$data['duration']] = $block_data['items_per_page'];
				}
			}
		}
	}
}

step();
fancyForm();
