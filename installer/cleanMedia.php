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

/* This script will clean the mediadirectory*/

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
define('NUM_STEPS', 25);

$numTR = 0;
$db = $dao->getConnection();
$finished = 0;
$finished = $db->getOne('SELECT content FROM  '.DB_PREFIX.'settings WHERE  `name` LIKE  \'cleanmedia\'');
if ($finished == 1) {
	echo "</p><p><b>All finished. Bye!</b><br>(you can now close this window and proceed with installation)</p>";
	exit();
}
	
@$count = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'tmp_cleanMedia');
if (PEAR::isError($count)) {
	$db->query("CREATE TABLE ".DB_PREFIX."tmp_cleanMedia (media_id int(11) default '0', block_id int(11) default '0', block_type varchar(24))");	
	$db->query("INSERT INTO ".DB_PREFIX."tmp_cleanMedia VALUES(?,?,?)", array(0,0,""));
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
		//Make a log file for update script
		$dirname = ROOT."updateScriptsLogs";
		if (!is_dir($dirname)) {
			mkdir($dirname);
		}
		$_SESSION['fileHandle'] = fopen(ROOT."updateScriptsLogs/cleanMedia.log", "a+");
		echo '</div><script type="text/javascript"><!--
			 document.forms[0].submit(); // --></script>';
	}
	exit();
}


$db = $dao->getConnection();
	
ob_implicit_flush();
$num = 0;

if (!isset($_SESSION['lastIdMedia'])) {
	$_SESSION['Warning'] = FALSE;
	$tmpMedia = $db->getOne('SELECT media_id FROM '.DB_PREFIX.'tmp_cleanMedia');
	$_SESSION['lastIdMedia'] = $tmpMedia;

	$_SESSION['cnt'] = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'media WHERE id > ?',array($tmpMedia));

	$_SESSION['fileHandle'] = fopen(ROOT."updateScriptsLogs/cleanMedia.log", "a+");
	if ($_SESSION['lastIdMedia'] > 0) {
		$_SESSION['Warning'] = TRUE;
		$_SESSION['fileHandle'] = fopen(ROOT."updateScriptsLogs/cleanMedia.log", "a+");
		fwrite($_SESSION['fileHandle'], "Warning!!! Skript was interrupted by mediaId ".$_SESSION['lastIdMedia']." before. \n");
		$_SESSION['ErrorInfo'] = $db->getRow('SELECT block_id, block_type FROM '.DB_PREFIX.'tmp_cleanMedia WHERE media_id = ?',array($tmpMedia));
	}
	fwrite($_SESSION['fileHandle'], "Script starts at ".date('Y-M-d H:i:s')."\n");
}
?>


<h1>Clean media Structure</h1>
<p>
	This script will clean the mediadirectory Please note that this may take a long time. <br>
	If the server has timelimit do not use more than 25 testruns per cycle.</p>
<p>
	<b>Beware:</b> JavaScript must be enabled for this to work.</p>
	
<?php

if (!post('goahead', false)) {
	fancyForm(false);
}

if (isset($_SESSION['Warning']) AND $_SESSION['Warning'] == TRUE) {
	//$BlockInfo = $db->getOne('SELECT * FROM '.DB_PREFIX.$ErrorInfo['block_type'].' WHERE id = ?',array($ErrorInfo['block_id']));
	echo "WARNING !!! <BR>
		  The Updatescript was interrupted before. Maybe the ".$_SESSION['ErrorInfo']['block_type']." with the Id ".$_SESSION['ErrorInfo']['block_id']." has corrupted mediaLinks.<BR>
		  Yout must them correct manually.";
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
	$_SESSION['fileHandle'] = fopen(ROOT."updateScriptsLogs/cleanMedia.log", "a+");
	
	if (isset($_SESSION['cnt']) AND $_SESSION['cnt'] <= 0) {
		echo "</p><p><b>All finished. Bye!</b><br>(you can now close this window and proceed with installation)</p>";
		$db->query('OPTIMIZE TABLE '.DB_PREFIX.'media');
		$db->query("INSERT INTO `".DB_PREFIX."settings` VALUES ('cleanMedia', '1')");
		$_SESSION['lastIdMedia'] = NULL;
		$_SESSION['blockId'] = array();
		fwrite($_SESSION['fileHandle'], "Script ends at ".date('Y-M-d H:i:s')."\n");
		$db->query('DROP TABLE '.DB_PREFIX.'tmp_cleanMedia');
		exit();
	}
	
	$numSteps = post('num_steps', NUM_STEPS);
	$query = 'SELECT * FROM '.DB_PREFIX.'media WHERE id > ? ORDER BY id ASC LIMIT ?';	
	@$res = $db->query($query, array(intval($_SESSION['lastIdMedia']), intval($numSteps)));
	if (!PEAR::isError($res)) {
		while ($res->fetchInto($row)){
			//Store the current media id, which is in progress in a temp table 
			$db->query('UPDATE '.DB_PREFIX.'tmp_cleanMedia SET media_id = ?',array($row['id']));
			//Check if a item block with the media connect id exist.
			@$count1 = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'item_blocks WHERE media_connect_id = ?', array($row['media_connect_id']));
			@$count2 = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'info_blocks WHERE media_connect_id = ?', array($row['media_connect_id']));
			@$count3 = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'feedback_blocks WHERE media_connect_id = ?', array($row['media_connect_id']));
			@$count4 = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'item_style WHERE logo = ?', array($row['media_connect_id']));
			//no item block with the media connect id is found. Delete entry in table media.
			if ($count1 == 0 and $count2 == 0 and $count3 == 0 and $count4 == 0) {
				//$query = 'DELETE FROM '.DB_PREFIX.'media WHERE media_connect_id = ?';	
				//@$res = $db->query($query, array($row['media_connect_id']));
				//$_SESSION['delNum']++;
				
				$fileSource =  ROOT."upload/media/".$row['filename'];
				echo "<BR>Not Copied:".$fileSource;
				fwrite($_SESSION['fileHandle'], "Following file was not copied because the mediaConnectId was not found in any block: ".$fileSource."\n");
			}
			//if an item block is found file is moved to the new structure
			else
			{
				@$res2 = getBlockTypeTable($row['media_connect_id']);
				//Only one, because media connect id is unique
				$res2->fetchInto($row2);
		
				$modulo = $row['media_connect_id'] % 100;
				$dirname = ROOT."upload/media/".$modulo;
				
				if (!is_dir($dirname))
					mkdir($dirname);
				$dirname2 = ROOT."upload/media/".$modulo."/".$row['media_connect_id'];
				if (!is_dir($dirname2))
					mkdir($dirname2);

				$fileSource =  ROOT."upload/media/".$row['filename'];
				$fileDest = $dirname2."/".$row['filename'];
			
				if(!copy($fileSource, $fileDest)) {
					$_SESSION['lastIdMedia'] = $row['id'];

					fwrite($_SESSION['fileHandle'], "Fail to copy file: ".$fileSource."\n");
					echo "<BR>Fail to copy ".$fileSource;
					continue;
				}
				fwrite($_SESSION['fileHandle'], "Copied file with MediaId: ".$row['id']."\n");
				unlink($fileSource);
				
				//update the links in questions an answers
				if ($count1 > 0 and (! in_array($row2['id'], $_SESSION['blockId']))) {
					$db->query('UPDATE '.DB_PREFIX.'tmp_cleanMedia SET block_id = ?, block_type = ?',array($row2['id'], "item_blocks"));
					//replace also the links in field introduction for itemblocks
					$res3 = $db->getOne('SELECT introduction FROM '.DB_PREFIX.'item_blocks WHERE id = ? ', array($row2['id']));
					$replaceQuestion = str_replace("upload/media", "upload/media/".$modulo."/".$row['media_connect_id'], $res3);
					$db->query('UPDATE '.DB_PREFIX.'item_blocks SET introduction = ? WHERE id = ?', array($replaceQuestion, $row2['id']));
					
					$res3 = $db->query('SELECT * FROM '.DB_PREFIX.'items WHERE block_id = ? ORDER BY id', array($row2['id']));
					while ($res3->fetchInto($row3)) {
						$replaceQuestion = str_replace("upload/media", "upload/media/".$modulo."/".$row['media_connect_id'], $row3['question']);	
					
						$res4 = $db->query('SELECT answer, id FROM '.DB_PREFIX.'item_answers WHERE item_id = ?', array($row3['id']));
						$db->query('UPDATE '.DB_PREFIX.'items SET question = ? WHERE id = ?', array($replaceQuestion, $row3['id']));
						
						while ($res4->fetchInto($row4)) {
							$replaceAnswer= str_replace("upload/media", "upload/media/".$modulo."/".$row['media_connect_id'], $row4['answer']);
							$db->query('UPDATE '.DB_PREFIX.'item_answers SET answer = ? WHERE id = ?', array($replaceAnswer, $row4['id']));
						}
					}
				}
				
				if ($count2 > 0 and (! in_array($row2['id'], $_SESSION['blockId']))) {
					$db->query('UPDATE '.DB_PREFIX.'tmp_cleanMedia SET block_id = ?, block_type = ?',array($row2['id'], "info_blocks"));
 			        $res3 = $db->query('SELECT content, id FROM '.DB_PREFIX.'info_pages WHERE block_id = ?', array($row2['id']));	
					while ($res3->fetchInto($row3)) {
						$replaceContent = str_replace("upload/media", "upload/media/".$modulo."/".$row['media_connect_id'], $row3['content']);
						$db->query('UPDATE '.DB_PREFIX.'info_pages SET content = ? WHERE id = ?', array($replaceContent, $row3['id']));
					}
				}
				
				if ($count3 > 0 and (! in_array($row2['id'], $_SESSION['blockId']))) {
					$db->query('UPDATE '.DB_PREFIX.'tmp_cleanMedia SET block_id = ?, block_type = ?',array($row2['id'], "feedback_blocks"));
					$res3 = $db->query('SELECT id FROM '.DB_PREFIX.'feedback_pages WHERE block_id = ?', array($row2['id']));
					while ($res3->fetchInto($row3)) {
						$res4 = $db->query('SELECT content, id FROM '.DB_PREFIX.'feedback_paragraphs WHERE page_id = ?', array($row3['id']));						
						while ($res4->fetchInto($row4)) {
							$replaceContent= str_replace("upload/media", "upload/media/".$modulo."/".$row['media_connect_id'], $row4['content']);
							$db->query('UPDATE '.DB_PREFIX.'feedback_paragraphs SET content = ? WHERE id = ?', array($replaceContent, $row4['id']));						
						}		
					}
				}
				//Store the blocks which items are already processed. 
				if (! in_array($row2['id'], $_SESSION['blockId']))
					$_SESSION['blockId'][] = $row2['id'];
			}
		$_SESSION['lastIdMedia'] = $row['id'];
		}
	
	$_SESSION['cnt'] = $cnt - $numSteps;

	}
	
	
	
}

/*
	Find the Block type for a media connect id
	@pram integer media connect id
*/
	
function getBlockTypeTable($mediaConnectId)
{
	global $db;
   
	$res = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'item_blocks WHERE media_connect_id = ?', array($mediaConnectId));
	if ($res > 0) {
		@$res = $db->query('SELECT * FROM '.DB_PREFIX.'item_blocks WHERE media_connect_id = ?', array($mediaConnectId));
		return $res;
	}
	
	else {
		$res = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'info_blocks WHERE media_connect_id = ?', array($mediaConnectId));
	}
	if ($res > 0) {
		@$res = $db->query('SELECT * FROM '.DB_PREFIX.'info_blocks WHERE media_connect_id = ?', array($mediaConnectId));
		return $res;
	}
	
	else {
		$res = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'feedback_blocks WHERE media_connect_id = ?', array($mediaConnectId));
	}
	if ($res > 0) {
		@$res = $db->query('SELECT * FROM '.DB_PREFIX.'feedback_blocks WHERE media_connect_id = ?', array($mediaConnectId));
		return $res;
	}
	
	else
		$res = $db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'item_style WHERE media_connect_id = ?', array($mediaConnectId));
	if ($res > 0) {
		@$res = $db->query('SELECT * FROM '.DB_PREFIX.'item_style WHERE logo = ?', array($mediaConnectId));
		return $res;
	}
}

step();
fancyForm();
