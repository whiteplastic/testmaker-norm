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
 * Figures out which page to initially show to a user
 *
 * Default action: {@link doStart()}
 *
 * @package Portal
 */
class StartPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "start";

	/**
	 * Shows the start page
	 */
	function doStart()
	{
		// Privileged users go to admin intro page
		$user = $GLOBALS['PORTAL']->getUser();
		
		if ((Setting::get('maintenance_mode_on') == 1) && (!$user->checkPermission('admin'))) {
			$this->loadDocumentFrame();
			$this->tpl->setVariable("body", Setting::get('maintenance_message'));
			$this->tpl->setVariable("page_title", T('pages.admin.tabs.maintenance_mode'));
			$this->tpl->show();
			exit;
		}
		
		if (isset($user) && $user->isSpecial())
			redirectTo('admin_start', array('resume_messages' => 'true'));
		// Alternatively, see if we've got an intro page for normal users
		$intro_on = Setting::get('intro_page_on');
				
		if (!$intro_on) 
			redirectTo('test_listing', array('resume_messages' => 'true'));
	
		$intro = Setting::get('intro_page');
		
		$body = $this->renderTemplate('IntroPageShow.html', array(
			'content' => $intro,
		), true);

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.admin_start.title"));

		$this->tpl->show();
	}
}

?>
