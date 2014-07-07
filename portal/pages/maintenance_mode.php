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
require_once(PORTAL.'ManagementPage.php');
require_once(CORE.'types/Setting.php');
require_once(PORTAL.'IntroEditor.php');

/**
 * Allows putting testMaker in maintenance mode, thus preventing any non-administrative usage.
 *
 * Default action: {@link doSetMode()}
 *
 * @package Portal
 */
class MaintenanceModePage extends ManagementPage
{
	/**
	 * @access private
	 */
    var $defaultAction = "edit_maintenance_mode";
	var $setting;
	
	
	function doEditMaintenanceMode()
	{
		$this->checkAllowed('admin', true);

		
		// Load template
		$this->tpl->loadTemplateFile("MaintenanceMode.html");
		$this->initTemplate("maintenance_mode");

		// Page setup
		$editor = new IntroEditor(Setting::get('maintenance_message'));
		$this->tpl->setVariable('maintenance_message', $editor->CreateHtml());
		
		if (isset($_COOKIE['mcook']) && file_exists(dirname(__FILE__)."/../../index_".$_COOKIE['mcook'].".php"))
			$this->tpl->setVariable('extended', 'checked=\"checked\"');
		else 
			$this->tpl->setVariable('extended', '');
		
		if (Setting::get('maintenance_mode_on')){
			$this->tpl->setVariable('checked', 'checked=\"checked\"');
			$this->tpl->setVariable('display', 'block');
		}
		else {
			$this->tpl->setVariable('checked', '');
			$this->tpl->setVariable('display', 'none');
		}	
		
		// Display page
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.general_management.title'));
		$this->tpl->show();
		

	}

	
	function enableExtendedMaintenance()
	{
		//first, disable old extended maintenance mode and remove dummy file	
		if (isset($_COOKIE['mcook']))
			$this->disableExtendedMaintenance();
				
		$m_key = NOW;
			
		$this->tpl->loadTemplateFile("ExtendedMaintenance.txt");
		$this->tpl->setVariable("m_key", $m_key);
		$this->tpl->setVariable("content", Setting::get('maintenance_message'));
		$fcontent = $this->tpl->get();	
			
		$destfilename = dirname(__FILE__)."/../../indexMtmp.php";	//create new temporary dummy file with content
		if($fhandle = fopen($destfilename, "x+")){
			fwrite($fhandle, $fcontent);
			fclose($fhandle);
		
			rename(dirname(__FILE__)."/../../index.php", dirname(__FILE__)."/../../index_".$m_key.".php");	//rename original index.php to index_<key>.php
			rename(dirname(__FILE__)."/../../indexMtmp.php", dirname(__FILE__)."/../../index.php");			//rename temporary dummy file to index.php
		
			setcookie("mcook", $m_key);
		}
		else 
			return 0;				


		$currentUser = $GLOBALS["PORTAL"]->getUser();
		$name = $currentUser->getFullname();
		$email = $currentUser->getEMail();
				
		$this->tpl->loadTemplateFile("extended_maintenance_mail.txt");
		$this->tpl->setVariable("host", gethostbyaddr($_SERVER['REMOTE_ADDR']));
		$this->tpl->setVariable("name", $name);
		$this->tpl->setVariable("link", linkToFile("index.php", array("maintenance_key" => $m_key), FALSE, TRUE));
		$body = $this->tpl->get();	
			
		libLoad('email::Composer');
		$mail = new EmailComposer();
		$mail->setSubject("testMaker: Extended Maintenance-Mode access");
		$mail->setFrom(SYSTEM_MAIL, "testMaker");
		$mail->addRecipient($email, $name);
		$mail->setTextMessage($body);
		if (! @$mail->sendMail())
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.admin.email.send_failure', MSG_RESULT_NEG);	
			
		return 1;		
	}
	
	function disableExtendedMaintenance()
	{
		if (isset($_COOKIE['mcook']))
			$m_key = $_COOKIE['mcook'];
		else 
			return 2;
		
		if(file_exists(dirname(__FILE__)."/../../index_".$m_key.".php")){
			$e1 = rename(dirname(__FILE__)."/../../index.php", dirname(__FILE__)."/../../index_2del.php");
			$e2 = rename(dirname(__FILE__)."/../../index_".$m_key.".php", dirname(__FILE__)."/../../index.php");	//rename index_<key>.php (original index file) back to index.php
			$e3 = unlink(dirname(__FILE__)."/../../index_2del.php");	// delete maintenance dummy page
			
			if(!$e1 || !$e2 || !$e3)
				return 0;
			
			setcookie("mcook", "", NOW-3600);	//delete cookie
			return 1;
		}
		else 
			return 2;	//ext was not enabled
			
	}
	
	
	function doSaveMaintenanceMode()
	{
		
		$this->checkAllowed('admin', true);

		$logString = '';
		
		// Save changes
		if (post('enabled', false)) {
			Setting::set('maintenance_mode_on', 1);
			Setting::set('maintenance_message', $_POST['fckcontent']);
			if (post('extended', false)){
				if($this->enableExtendedMaintenance())
					$GLOBALS["MSG_HANDLER"]->addMsg('portal.ext_maintenance_mode.activated', MSG_RESULT_POS);
				else 
					$GLOBALS["MSG_HANDLER"]->addMsg('portal.ext_maintenance_mode.failed', MSG_RESULT_NEG);					
				$logString = 'Extended activated';
			} else {
				$logString = 'Extended deactivated';
				switch($this->disableExtendedMaintenance()){
					case 0:	$GLOBALS["MSG_HANDLER"]->addMsg('portal.ext_maintenance_mode.failed', MSG_RESULT_NEG); break;					
					case 1:	$GLOBALS["MSG_HANDLER"]->addMsg('portal.ext_maintenance_mode.deactivated', MSG_RESULT_POS); break;
					case 2: $logString = ''; break; //was not activated
				}
			}
			$GLOBALS["MSG_HANDLER"]->addMsg('portal.maintenance_mode.activated', MSG_RESULT_POS);
			EditLog::addEntry(LOG_OP_MAINTENANCE_MODE, 1, $logString);
// log maintenance enabled (mit oder ohne extended)	extra log oder index: E
				
		} else {
			Setting::set('maintenance_mode_on', 0);
			//Setting::delete('maintenance_message');	//preserve msg for later use
			$logString = 'Extended deactivated';		
			switch($this->disableExtendedMaintenance()){
				case 0:	$GLOBALS["MSG_HANDLER"]->addMsg('portal.ext_maintenance_mode.failed', MSG_RESULT_NEG); break;					
				case 1:	$GLOBALS["MSG_HANDLER"]->addMsg('portal.ext_maintenance_mode.deactivated', MSG_RESULT_POS); break;
				case 2: $logString = ''; break; //was not activated
			}	
			$GLOBALS["MSG_HANDLER"]->addMsg('portal.maintenance_mode.deactivated', MSG_RESULT_POS);
			EditLog::addEntry(LOG_OP_MAINTENANCE_MODE, 0, $logString);		
		}
		
		redirectTo('maintenance_mode', array('resume_messages' => 'true'));
	}
	
	
	
}
