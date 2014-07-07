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
 * Set a privacy policy that every user has to accept befor performing a test
 *
 * Default action: {@link doEditPrivacyPolicy()}
 *
 * @package Portal
 */
class EditPrivacyPolicyPage extends ManagementPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "edit_privacy_policy";


	function doEditPrivacyPolicy()
	{
		$this->checkAllowed('admin', true);	

		$this->tpl->loadTemplateFile("EditPrivacyPolicy.html");
		$this->initTemplate("edit_privacy_policy");
		
		$current_pp = PrivacyPolicy::getCurrentVersion(); 	//current version
		$latest_pp = PrivacyPolicy::getVersionBeforeDeactivation();	//latest valid version (not 0)
		
		if(get('revert') == 'true') {
			$editor = new IntroEditor(defuseScripts(deUtf8(post('fckcontent', ''))));
								
			if (post('enabled', false)){
				$this->tpl->setVariable('checked', 'checked=\"checked\"');
				$this->tpl->setVariable('display', 'block');
				$this->tpl->setVariable('cversion', "-");	//$this->tpl->setVariable('cversion', "latest: version ".$current_pp." (".date('d.n.Y', PrivacyPolicy::getCreationDate($current_pp)).")");
				$this->tpl->setVariable('exp_range', post('exp_range'));
				if (!is_numeric(post('exp_range')) || !post('exp_range'))
					$this->tpl->setVariable('exp_error', 'style="color:red;font-weight:bold;"');
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.edit_privacy_policy.msg.modified_failure', MSG_RESULT_NEG);
			}
			else {
				$this->tpl->setVariable('checked', '');
				$this->tpl->setVariable('display', 'none');
				$this->tpl->setVariable('cversion', "-");	
			}
			
		} else {
			$editor = new IntroEditor(PrivacyPolicy::getPolicyContent($latest_pp));
				
			if ($current_pp != 0){
				$this->tpl->setVariable('checked', 'checked=\"checked\"');
				$this->tpl->setVariable('display', 'block');
				$this->tpl->setVariable('cversion', "latest: version ".$current_pp." (".date('d.n.Y', PrivacyPolicy::getCreationDate($current_pp)).")");
				$this->tpl->setVariable('exp_range', PrivacyPolicy::getExpirationRange($current_pp));
			}
			else {
				$this->tpl->setVariable('checked', '');
				$this->tpl->setVariable('display', 'none');
				$this->tpl->setVariable('exp_range', PrivacyPolicy::getExpirationRange($latest_pp));
				$this->tpl->setVariable('cversion', "-");	
			}					
		}
		
		$this->tpl->setVariable('content', $editor->CreateHtml());
				
				
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);

		$this->tpl->setVariable('page_title', T('pages.general_management.title'));

		$this->tpl->show();
	}

	function doSavePrivacyPolicy()
	{
		$this->checkAllowed('admin', true);

		libLoad("html::defuseScripts");
		libLoad("utilities::deUtf8");
		$content = defuseScripts(deUtf8(post('fckcontent', '')));
		
		$exp_range = post('exp_range');
		

		if (!post('enabled', false)) {
		
			if (PrivacyPolicy::setCurrentVersion(0)) {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.edit_privacy_policy.msg.deleted_success', MSG_RESULT_POS);
			} else {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.edit_privacy_policy.msg.deleted_failure', MSG_RESULT_NEG);
			}
			
			redirectTo('edit_privacy_policy', array('resume_messages' => 'true'));
		
		} else {
			
			if ((!$exp_range || !is_numeric($exp_range)) && $exp_range !== '0')		// allow entry '0' 
			{
				$this->doEditPrivacyPolicy();
			} else {
		
			// store new policy in database and set its timestamp to current version
			
				if (PrivacyPolicy::addPrivacyPolicy($content, $exp_range)) {
	
					$GLOBALS["MSG_HANDLER"]->addMsg('pages.edit_privacy_policy.msg.modified_success', MSG_RESULT_POS);
										
				} else {
					$GLOBALS["MSG_HANDLER"]->addMsg('pages.edit_privacy_policy.msg.modified_failure', MSG_RESULT_NEG);
				}
				
				redirectTo('edit_privacy_policy', array('resume_messages' => 'true'));
			}
		}

	}

}

?>
