<?php 
session_start();

/***********************
 How it works:

In DB:
Table cron_jobs

id	| tstart	  | type	| slave | destination	| content			| done

2	  timestmp	  mail	  1			xy@z.de		  NULL				  NULL
2	  timestmp	  mail	  NULL		"Betreff"	  searializ.content	  NULL
0	  NULL		  NULL	  88		NULL		  serializ.status	  88


Every Job has master entry (slave=NULL) with general Job-Info and 
several slaves representing single tasks (e.g. e-mails to send)

ID 0 contains general status information like time of last start of execution cycle 
and processed tasks so far (to make sure limits are not exceeded)

Create new Job:
$slaves = array(
		array("destination"=>"abc"),
		array("destination"=>"def", "content"=>"ghi"),
);
$maincontent = array("subject"=>"Title", "body"=>"This is the mailtext");
$job = new CronJob();
$job->createJob("mail", "Test des Tests", $maincontent, &$slaves);


Successfully completed jobs are removed after a defined period of time. (default: 1 year) 

Execution cycles work like this:
Jobs are being executed until execution limit $maxTasksInCycle is reached.
It will be continued when enough time has past since last cycle-start 
=> start new cycle

Mail-Server Limitations (max mails per minute) have to be adjusted.
Default is 300 Mails per 10 Minutes

//ATTENTION: limit might be exceeded when executing multiple instances of this script (although it should not be, since counter ist stored in db)

Please make sure (via .htaccess in Portal folder) that this script can be executed. 

************************/


require("../../lib/utilities/StopWatch.php");
require_once("../../init.php");
//require_once(ROOT.'portal/init.php');

require_once('../../lib/utilities/printVar.php');

//max script execution time to prevent server-timeout
$TimeoutSafe = 30; //reload after 30 sec

//Mail-Server Limitations (example)
$minCycleTime = 540000;	//9min
$maxTasksInCycle = 300;	//300mails per 9min	

$time1 = time();
$tasksDone = 0;
$deleteTime = $time1 - 7776000; // delete done jobs after 3 Month

$db = $dao->getConnection();



function sendEmail($text, $subject, $recipient){
	
	require_once('../../lib/email/Composer.php');
	$mail = new EmailComposer();
	$mail->setHtmlMessage($text);
	$mail->setSubject($subject);
	$mail->setFrom(defined('SYSTEM_MAIL_B') ? SYSTEM_MAIL_B : SYSTEM_MAIL);
	$mail->addRecipient($recipient);

	return  $mail->sendMail();
}


	
	//get time of last execution cycle to make sure limits are not exceeded (if not there, create entry)
	$status = unserialize($db->getOne("SELECT content FROM ".DB_PREFIX."cron_jobs WHERE id=0 AND slave=88 AND done=88", array()));
	if(!$status) {
		unset($status);
		$status["lastCycleStart"] = 0; 
		$status["counter"] = 0;
		$db->query("INSERT INTO ".DB_PREFIX."cron_jobs (id, slave, content, done) VALUES (?,?,?,?) ", array(0, 88, serialize($status), 88));
	}
	

	if( $time1 - $status["lastCycleStart"] > $minCycleTime ) {
		//last execution cycle was long ago enough. => start new cycle
		$status["lastCycleStart"] = $time1;
		$status["counter"] = 0;
	}

	if ( $status["counter"] < $maxTasksInCycle )
		$goAhead = true;

	while($goAhead && ( $status["counter"] < $maxTasksInCycle ) ) 
	{
		//select oldest master that is not done
		$master = $db->query("SELECT * FROM ".DB_PREFIX."cron_jobs WHERE slave IS NULL AND done IS NULL ORDER BY tstart ASC", array())->fetchRow();
		
		if($master) {
			echo "selected master: ".$master["id"].". \n";
			
			//count all slaves for this master
			$slaveCount = $db->getOne("SELECT count(slave) FROM ".DB_PREFIX."cron_jobs WHERE id=? AND slave IS NOT NULL", array($master["id"]));
			
			if($slaveCount > 0) {
				//get one slave from selected master
				$slave = $db->query("SELECT * FROM ".DB_PREFIX."cron_jobs WHERE id=? AND slave IS NOT NULL AND done IS NULL", array($master["id"]))->fetchRow();	
				if(!$slave) {
					//all slaves are done => set master also
					$now = time();
					$ret = $db->query("UPDATE ".DB_PREFIX."cron_jobs SET done=? WHERE id=? AND slave IS NULL", array($now, $master["id"]));
					echo "master ".$master["id"]." done. ";
				}
				else {
					
					//block selected slave during process
					$ret = $db->query("UPDATE ".DB_PREFIX."cron_jobs SET done=? WHERE id=? AND slave=?", array(5500550055, $slave["id"], $slave["slave"]));
					
					//process slave
					$taskdone = false;
					switch($slave["type"]) 
					{
						case "mail_custom": {
							echo "Slave ".$slave["slave"].":\n Sending mail to ".$slave["destination"]." with content: ";
							$content = unserialize($slave["content"]) ? unserialize($slave["content"]) : $slave["content"];
	
							//printvar($content);					
							echo sendEmail($content["body"], $content["subject"], $slave["destination"]);		
										
							$taskdone = true;
							$tasksDone++;
							
							break;
						}
						case "mail_general": {
							echo "Slave ".$slave["slave"].":\n Sending mail to ".$slave["destination"]." with content: ";
							$content = unserialize($master["content"]);
							if($content)
								printvar($content);
							else
								printvar($master["content"]);
						
							$taskdone = true;
							$tasksDone++;
							break;
						}
						default: {
							$ret = $db->query("UPDATE ".DB_PREFIX."cron_jobs SET done=? WHERE id=? AND slave=?", array(2000000000, $slave["id"], $slave["slave"]));
							echo "error processing slave ".$slave["slave"].". \n\n";
							break;
						}
					}
					
					if($taskdone) 
					{
						//set slave done
						$now = time();
						$ret = $db->query("UPDATE ".DB_PREFIX."cron_jobs SET done=? WHERE id=? AND slave=?", array($now, $slave["id"], $slave["slave"]));
						echo "Slave ".$slave["slave"]." done. \n\n";
						
						$status["counter"] ++;
						$db->query("UPDATE ".DB_PREFIX."cron_jobs SET content=? WHERE id=0 AND slave=88", array(serialize($status)));
						
					}
				}
			}
			else {
					//master without slaves
					//currently not implemented
					//output error
					$db->query("UPDATE ".DB_PREFIX."cron_jobs SET done=? WHERE id=? AND slave IS NULL", array(2000000000, $master["id"]));
			}
		
		} else {
			echo "Nothing to do. End cronjob execution. \n";
			break;
		}	
		
		if(time() - $time1 > $TimeoutSafe){
			echo " timeout. ";
			$goAhead = false;
		}
	}


	if ( $status["counter"] >= $maxTasksInCycle )
		echo "reached limit of job executions for current cycle.  \n";	
	
	//delete old jobs
	$oldjobs = $db->getOne("SELECT count(*) FROM ".DB_PREFIX."cron_jobs WHERE id <> 0 AND done<?", array($deleteTime));
	if($oldjobs) {
		$db->query("DELETE FROM ".DB_PREFIX."cron_jobs WHERE id <> 0 AND done<?", array($deleteTime));
		echo $oldjobs." old entrys deleted. \n";
	}
	
echo "\n ".$tasksDone." tasks done. => End.\n";

?>