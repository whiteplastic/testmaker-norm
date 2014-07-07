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

/* This script will migrate your test run records to the new database structure*/

require_once("../lib/utilities/StopWatch.php");
start("Initializing");
require_once(dirname(__FILE__)."/../init.php");
start("Initializing Portal");
require_once(ROOT.'portal/init.php');
require_once(CORE.'types/TestRunList.php');
stop();
stop();

ignore_user_abort(true);
define('NUM_STEPS', 50);

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
	exit;
}

function deleteOld($trid)
{
	global $db;

	// Performance hack: don't delete given answers; worry about that afterwards
	//$db->query('DELETE FROM '.DB_PREFIX.'given_answers WHERE set_id IN (SELECT id FROM '.DB_PREFIX.'given_answer_sets WHERE test_run_id = ?)', array($trid));
	$db->query('DELETE FROM '.DB_PREFIX.'given_answer_sets WHERE test_run_id = ?', array($trid));
}

$db = $dao->getConnection();
$tables = $db->getListOf('tables');

if (in_array(DB_PREFIX.'given_answer_sets', $tables)) {
	$cnt = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'given_answer_sets');
	if (!$cnt) {
		die('This script does not need to be executed anymore.');
	}
} else {
	die('This script does not need to be executed anymore.');
}

ob_implicit_flush();
$num = 0;

?>
<h1>Test run migrator</h1>
<p>
	This script will migrate your test run records to the new database structure. Please note that this may take a really long time.</p>
<p>
	<b>Beware:</b> JavaScript must be enabled for this to work.</p><?php

if (!post('goahead', false)) {
	fancyForm(false);
}

echo "<p>Test runs left to process: <span id=\"num\" style=\"font-weight:bold;\"></span>

<script language=\"JavaScript\"><!--
function u(t) { document.getElementById('num').innerHTML = t }
// --></script>

";

function step()
{
	global $BLOCK_LIST, $db;
	unstore_all();

	$cnt = $db->getOne('SELECT COUNT(DISTINCT test_run_id) FROM '.DB_PREFIX.'given_answer_sets');
	if ($cnt == 0) {
		echo "</p><p><b>All finished. Bye!</b><br>(you can now close this window and proceed with installation)</p>";
		exit;
	}
	$trid = $db->getOne('SELECT tr.id FROM '.DB_PREFIX.'test_runs tr INNER JOIN '.DB_PREFIX.'given_answer_sets gas ON (gas.test_run_id = tr.id) GROUP BY tr.id', array(), DB_FETCHMODE_ORDERED);
	$trlist = new TestRunList();
	$tr = new TestRun($trlist, $trid);

	if (!($cnt % 5)) echo "<script>u('$cnt');</script>";

	$trinfo = $tr->getGivenAnswerSets(NULL);
	$gas_cnt = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'given_answer_sets WHERE test_run_id = ?', array($trid));
	if (is_array($trinfo) && count($trinfo) == $gas_cnt) {
		// We were interrupted between having imported data and deleting old records, delete now
		deleteOld($trid);
		step();
		return;
	}

	$tr_data = $db->query('SELECT * FROM '.DB_PREFIX.'given_answer_sets gas LEFT JOIN '.DB_PREFIX.'given_answers ga ON (ga.set_id = gas.id) WHERE gas.test_run_id = ? ORDER BY step_number', array($trid));
	$subtests = array();
	$blocks = array();

	$mainBlock = $tr->getTestRunBlockBySubtest(0);
	$mainBlock->clear(false);
	$row = NULL;
	while (true) {
		if ($row === NULL && !$tr_data->fetchInto($row)) break;
		if ($row['block_id'] === NULL) break;

		$gas = $tr->prepareGivenAnswerSet($row['block_id'], $row['step_number'], $row['item_id'], $row['finish_time'], $row['timeout'], $row['duration'], $row['client_duration'], $row['server_duration']);
		$gas->setThetaAndSem($row['theta'], $row['sem']);

		# Find subtest this GivenAnswerSet belongs to
		if (!isset($subtests[$row['block_id']])) {
			$subtestId = intval($BLOCK_LIST->findParentInTest($row['block_id'], $tr->getTestId()));
			if (!$subtestId) $subtestId = 0;
			$subtests[$row['block_id']] = $subtestId;
		} else {
			$subtestId = $subtests[$row['block_id']];
		}

		# Possibly insert link to subtest
		if ($subtestId != 0) {
			$mainBlock = $tr->getTestRunBlockBySubtest(0);
			$mainBlock->addLinkToSub($subtestId, false);
		}

		$block = $tr->getTestRunBlockBySubtest($subtestId);
		if (!isset($blocks[$subtestId])) {
			if ($subtestId != 0) $block->clear(false);
			$blocks[$subtestId] = 1;
		}

		if ($row['answer_id'] !== NULL) {
			// Gather answers
			$itemId = $row['item_id'];
			while ($row['item_id'] == $itemId) {
				$gas->addGivenAnswer($row['answer_id'], $row['answer_value']);
				if (!$tr_data->fetchInto($row)) break;
			}
		} else {
			$row = NULL;
		}

		# Add to the right test run block
		$block->add($gas->getData(), false, false);
	}
	$tr_data->free();

	foreach ($blocks as $blockId => $_foo) {
		$block = $tr->getTestRunBlockBySubtest($blockId);
		$block->commit();
	}
	deleteOld($trid);

	echo " \n";
}

for ($i = 0; $i < intval(post('num_steps', NUM_STEPS)); $i++) {
	step();
}
fancyForm();
