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

/* This skript kill redudantentries in table block_structure and reduce so the size
of the database*/

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
define('NUM_STEPS', 10);

$numTR = 0;
$db = $dao->getConnection();

$finished = $db->getOne('SELECT content FROM  '.DB_PREFIX.'settings WHERE  `name` LIKE  \'ktsr\'');
if ($finished == 1) {
	echo "</p><p><b>All finished. Bye!</b><br>(you can now close this window and proceed with installation)</p>";
	exit();
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
			 document.forms[0].submit(); // --></script>';
	}
	exit();
}


$db = $dao->getConnection();
	
ob_implicit_flush();
$num = 0;
if (!isset($_SESSION['lastId'])) {
	$_SESSION['lastId'] = 0;
	
	$_SESSION['cnt'] = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'block_structure');
}
?>


<h1>Block structure migrator</h1>
<p>
	This script will kill redundant test structure data. Please note that this may take a long time. <br>
	If the server has timelimit do not use more than 10 testruns per cycle.</p>
<p>
	<b>Beware:</b> JavaScript must be enabled for this to work.</p>
	
<?php

if (!post('goahead', false)) {
	fancyForm(false);
}

echo "<p>TestRunBlocks to be processed: <span id=\"num\" style=\"font-weight:bold;\"></span>

<script language=\"JavaScript\"><!--
function u(t) { document.getElementById('num').innerHTML = t }
// --></script>

";

function step()
{
	global $db;
	
	$cnt = $_SESSION['cnt'];
	echo "<script>u('$cnt');</script>";
	@$count = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'tmp_blkstr');
	if (PEAR::isError($count)) {
		/*create a temporary lookup table for checking similar teststructures
		lookup table have much less entries than the original table (much faster)
		A new index length is add to the table, to make the search in the table faster. */
		$db->query("CREATE TABLE ".DB_PREFIX."tmp_blkstr (
				test_run_id int(11) NOT NULL DEFAULT 0,
				subtest_id int(11) NOT NULL DEFAULT 0,
				test_structure text,
				length int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (test_run_id, subtest_id),
				INDEX (length)
				)	
 		");	
	}
	
	if (isset($_SESSION['cnt']) AND $_SESSION['cnt'] <= 0) {

		//script has finished

		echo "</p><p><b>All finished. Bye!</b><br>(you can now close this window and proceed with installation)</p>";

		$db->query('OPTIMIZE TABLE '.DB_PREFIX.'block_structure');
		$db->query("INSERT INTO `".DB_PREFIX."settings` VALUES ('ktsr', '1')");
		$db->query("DROP TABLE ".DB_PREFIX."tmp_blkstr");
		$_SESSION['lastId'] = NULL;
		exit();
	}
	

	$numSteps = post('num_steps', NUM_STEPS);
	
	$query = 'SELECT DISTINCT test_run_id FROM '.DB_PREFIX.'block_structure WHERE test_run_id > ? ORDER BY test_run_id ASC LIMIT ?';	
	@$res = $db->query($query, array(intval($_SESSION['lastId']), intval($numSteps)));
	
	if (isset($_SESSION['lastStructure']))
		$lastStructure = $_SESSION['lastStructure'];
	else
		$lastStructure = array();
		
	if (isset($_SESSION['StructKey']))
		$StructKey = $_SESSION['StructKey'];
	else
		$StructKey = 0;
		
	if (isset($_SESSION['lastPointer']))
		$lastPointer = $_SESSION['lastPointer'];
	else
		$lastPointer = array();
		
	$countConverts = 0;
	
echo "<BR>BufferSize:".$StructKey;	

	if (!PEAR::isError($res)) {

		while ($res->fetchInto($row)){

			if (!isset($_SESSION['firstTestRunID'])) {
				$_SESSION['firstTestRunID'] = $row['test_run_id'];
				$TestRunIdSpan = 1;
			}
			else
				$TestRunIdSpan = $row['test_run_id'] - $_SESSION['firstTestRunID'];
			//calculating segments. (Searching segment after segment in the lookup table is faster than search in in the entire lookup table) 
			$TestRunSpan10 = ceil($TestRunIdSpan / 10) + 1;
			$threshold_1 = $row['test_run_id'] - $TestRunSpan10;
			$threshold_2 = $row['test_run_id'] - $TestRunSpan10 * 2;
			$threshold_3 = $row['test_run_id'] - $TestRunSpan10 * 3;
			$threshold_4 = $row['test_run_id'] - $TestRunSpan10 * 4;
			$threshold_5 = $row['test_run_id'] - $TestRunSpan10 * 5;
			$threshold_6 = $row['test_run_id'] - $TestRunSpan10 * 6;
			
			//get the block test structures for a test run
			$res2 = $db->query('SELECT * FROM '.DB_PREFIX.'block_structure WHERE test_run_id = ? ORDER BY subtest_id ASC', array($row['test_run_id']));
			
			$countSubtests = 0;
			$foundSubtest = False;
			while ($res2->fetchInto($row2)) {

				$countConverts++;
				$lastId = $row['test_run_id'];
				$segment = 0;
				/*if more than 3 subtests in a test have no equal structure to other tests, the probability is very high, that the 
				  other subtest have also no eual structure to another tests*/
				if (($countSubtests > 3) && ($foundSubtest == False))
					continue;
				$countSubtests++;
				$foundKey = array_search($row2['test_structure'], $lastStructure);
				$length = strlen($row2['test_structure']);
				
				//if structure is NOT found in cache
				if (($foundKey === FALSE) AND ($length > 17)) {	
		
					//most equal structure are between the same test_rund_id and different subtest ids
					//so check this first to save time during comparision
					$resFound = $db->query('SELECT * FROM '.DB_PREFIX.'tmp_blkstr WHERE test_run_id = ? AND
											NOT subtest_id = ? AND test_structure = ? ORDER BY test_run_id ASC, subtest_id ASC LIMIT ?', 
								array($row2['test_run_id'], $row2['subtest_id'], $row2['test_structure'], 1));	
					//if no similar structure is found yet check part for part of the lookup table			
					if ($resFound->numRows() == 0) 
						$resFound =  getRow($db, $row2['test_run_id'], $threshold_1, $row2['test_structure'], $length);	
						
					if ($resFound->numRows() == 0) 				
						$resFound =  getRow($db, $threshold_1, $threshold_2, $row2['test_structure'], $length);
						
					if ($resFound->numRows() == 0) 		
						$resFound =  getRow($db, $threshold_2, $threshold_3, $row2['test_structure'], $length);	
						
					if ($resFound->numRows() == 0) 			
						$resFound =  getRow($db, $threshold_3, $threshold_4, $row2['test_structure'], $length);
						
					if ($resFound->numRows() == 0) 			
						$resFound =  getRow($db, $threshold_4, $threshold_5, $row2['test_structure'], $length);	
						
					if ($resFound->numRows() == 0) 			
						$resFound =  getRow($db, $threshold_5, $threshold_6, $row2['test_structure'], $length);
						
					if ($resFound->numRows() == 0) 			
						$resFound = $db->query('SELECT * FROM '.DB_PREFIX.'tmp_blkstr WHERE test_run_id < ?  AND test_structure = ? AND length = ?
												ORDER BY test_run_id ASC , subtest_id ASC LIMIT  ?', 
												array($threshold_6, $row2['test_structure'], $length, 1));									
					//if an equal structure is Found and structure is no pointer to another structure					
					if ($resFound->numRows() != 0) {
						$resFound->fetchInto($row3);
						//calculating the new pointer
						$pointer = $row3['test_run_id']."+".$row3['subtest_id'];
					
						$db->query('UPDATE '.DB_PREFIX.'block_structure SET test_structure = ? WHERE subtest_id = ? AND test_run_id = ?', 
						array($pointer, $row2['subtest_id'], $row['test_run_id']));
						
						//cache structure
						$lastStructure[$StructKey] = $row2['test_structure'];
						$lastPointer[$StructKey] = $pointer;
						$StructKey++;
						if (($StructKey) > 500)
							$StructKey = 0;
							
						$foundSubtest = true;
						
					}
					//if no similar structure is found insert the structure in the lookup table
					else {	
				
						$db->query("INSERT INTO ".DB_PREFIX."tmp_blkstr VALUES(?,?,?,?)",
									array($row['test_run_id'], $row2['subtest_id'], $row2['test_structure'], $length));
					}
				}
				if (!($foundKey === FALSE) && ($length > 17)) {

					$db->query('UPDATE '.DB_PREFIX.'block_structure SET test_structure = ? WHERE subtest_id = ? AND test_run_id = ?', 
						array($lastPointer[$foundKey], $row2['subtest_id'], $row['test_run_id']));
					$foundSubtest = true;
				}

			}
		}
	}
	else {
		echo "Error occur";
		exit();
	}
	$_SESSION['cnt'] = $cnt - $countConverts;
	$_SESSION['lastId'] = $lastId;
	$_SESSION['lastStructure'] = $lastStructure;
	$_SESSION['StructKey'] = $StructKey;
	$_SESSION['lastPointer'] = $lastPointer;
	echo " \n";
}

function getRow($db, $thresholdHeigh, $thresholdLow, $testStructure, $length) 
{
	$length = strlen($testStructure);
	
	$resFound = $db->query('SELECT * FROM '.DB_PREFIX.'tmp_blkstr WHERE test_run_id < ?  AND 
							test_run_id >= ? AND test_structure = ? AND length = ?
							ORDER BY test_run_id ASC, subtest_id ASC LIMIT  ?', 
							array($thresholdHeigh, $thresholdLow, $testStructure, $length, 1));
	return $resFound;
}

step();
fancyForm();
