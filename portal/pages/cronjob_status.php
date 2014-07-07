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
 * @package Portal
 */


/**
 * Loads the base class
 */
require_once(PORTAL.'AdminPage.php');
require_once(PORTAL.'DataAnalysisPage.php');

require_once(CORE.'types/CronJob.php');
/**
 * Load page selector widget
 */
//require_once(PORTAL.'PageSelector.php');

libLoad('PEAR');

class CronjobStatusPage extends DataAnalysisPage
{
	var $defaultAction = "show_status";
	
	function doShowStatus()
	{
		
/*
		$slaves = array(
				array("destination"=>"fu"),
				array("destination"=>"fa"),
				array("destination"=>"fe"),
				);	
		$content = array("subject"=>"Hoppedihop", "body"=>"werte damen und herren");	
		$job = new CronJob();		
		$job->createJob("mail_custom", "Test des Tests", $content, &$slaves);
*/
		
		
		$this->tpl->loadTemplateFile("CronjobStatus.html");
		$pageTitle = T("menu.test.survey");
		
		$this->initTemplateCronjobs("cronjob_status");

		//search for jobs
		$foundJobs = CronJob::findJobs();

		$this->tpl->setVariable("count_jobs", sizeof($foundJobs));
		
		

		foreach($foundJobs as $job_id) {

			$job = new CronJob(array("id"=>$job_id));	
			$jobDetails = $job->getJobInfo();

			
			$jobProgress = $jobDetails["task_count"] > 0 ? intval( 100 * $jobDetails["tasks_done"] / $jobDetails["task_count"] , 2) : 0;

			
			$this->tpl->setVariable("job_id", $jobDetails["id"]);
			$this->tpl->setVariable("identifyer", $jobDetails["destination"]);		
			$this->tpl->setVariable("progress", $jobProgress);
			$this->tpl->setVariable("start_time", strftime(T("utilities.relative_time.strftime"), $jobDetails["start"]));
			$this->tpl->setVariable("tasks_done", $jobDetails["tasks_done"]);
			$this->tpl->setVariable("task_count", $jobDetails["task_count"]);

			
			switch($jobDetails["type"]) {
				case "mail": 
					$this->tpl->setVariable("type", "Nachbefragung"); break;
				default:
					$this->tpl->setVariable("type", $jobDetails["type"]); break;	
			}
			
			if($jobProgress == 0)
				$this->tpl->touchBlock("s_queue");
			else {
				if($jobProgress == 100 && $jobDetails["done"] != NULL) {
					$this->tpl->touchBlock("s_done");
				$this->tpl->setVariable("endtime", strftime(T("utilities.relative_time.strftime"), $jobDetails["done"]));
				} 
				else
					$this->tpl->touchBlock("s_pending");
			}
			if($jobDetails["done"] == "2000000000")
				$this->tpl->touchBlock("s_error");
					
			$this->tpl->parse("jobs_found");
			
			unset($job);
		}
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);			
		$this->tpl->setVariable("page_title", $pageTitle);
		$this->tpl->show();
	}
	
	function doDeleteJob()
	{
		$id = get('id');

		if (CronJob::deleteJob($id) )
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.test_survey.job_delete_success', MSG_RESULT_POS, array('id' => $id));
		else 
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.test_survey.job_delete_fail', MSG_RESULT_NEG, array('id' => $id));
		
		redirectTo('cronjob_status', array('action' => 'show_status', 'resume_messages' => 'true'));
	}
	

}
?>