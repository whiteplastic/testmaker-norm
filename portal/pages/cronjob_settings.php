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

class CronjobSettingsPage extends DataAnalysisPage
{
	var $defaultAction = "show_settings";
	
	function doShowSettings()
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
		
		
		$this->tpl->loadTemplateFile("CronjobSettings.html");
		$pageTitle = T("menu.test.survey");
		
		$this->initTemplateCronjobs("cronjob_settings");

		
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);			
		$this->tpl->setVariable("page_title", $pageTitle);
		$this->tpl->show();
	}

}
?>