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
require_once(CORE.'types/Setting.php');
require_once(CORE.'types/User.php');

/**
 * Show the privacy policy that every user has to accept befor performing a test.
 *
 * @package Portal
 */

class ShowPrivacyPolicyPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "show_privacy_policy";

	function doShowPrivacyPolicy()
	{
		$this->tpl->loadTemplateFile("ShowPrivacyPolicy.html");
		
		$this->tpl->setVariable('content', PrivacyPolicy::getPolicyContent());
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.admin.tabs.management_privacy_policy"));

		$this->tpl->show();
	}
	

	function doAcceptPrivacyPolicy()
	{
		if (post('accept', false)) {		
				
			$userid = $GLOBALS["PORTAL"]->getUserId();
			$userList = new UserList();
			$user = $userList->getUserById($userid);
			
			$current_pp = PrivacyPolicy::getCurrentVersion();
			
			$user->setPrivacyPolicyAcc($current_pp);			
			redirectTo('test_listing', array('resume_messages' => 'true'));	
				
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.privacy_policy.msg.not_accepted', MSG_RESULT_NEG);
			redirectTo('show_privacy_policy', array('resume_messages' => 'true'));
		}
	}

	
	
	/**
	 * Shows the privacy policy
	 */
	function doShowPrivacyPopup()
	{
		$version = get("version");
		$infobox = get("info");
		
		$content = PrivacyPolicy::getPolicyContent($version);
		
		
		$closingdate = PrivacyPolicy::getClosingDate($version);
		if($closingdate == 0)
			$closingdate = '-';
		else
			$closingdate = date(T('pages.core.date_time'), $closingdate);	
			
			
		$this->tpl->loadTemplateFile("ViewPrivacyPopup.html");
				

		if (!$content || $version == 0)	{
			$this->tpl->touchBlock('invalid_pp');
			$this->tpl->hideBlock('infobox');
		} else {
			$this->tpl->setVariable('content', $content);
			$this->tpl->hideBlock('invalid_pp');
			$this->tpl->touchBlock('infobox');
		}
		
		if (isset($infobox) && $infobox=='none')
			$this->tpl->hideBlock('infobox');
		
		$this->tpl->setVariable("pp_version", $version);
		$this->tpl->setVariable("creationdate", date(T('pages.core.date_time'), PrivacyPolicy::getCreationDate($version)));
		$this->tpl->setVariable("closingdate", $closingdate);
		$this->tpl->setVariable("expiration_range", PrivacyPolicy::getExpirationRange($version));
		$this->tpl->setVariable("usercount", PrivacyPolicy::getUserCount($version));
		
		$popupBody = $this->tpl->get();
		
		$this->tpl->setVariable("body", $popupBody);

		$this->tpl->show();
	}
	
	
}

