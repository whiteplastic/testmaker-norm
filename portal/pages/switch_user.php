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
 * include userlist
 */
require_once(CORE.'types/UserList.php');

/**
 * Loads the base class
 */
require_once(PORTAL.'AdminPage.php');

/**
 * Allows a adminstrator to handle users and create new user
 *
 * Default action: {@link doListUser()}
 *
 * @package Portal
 */
class SwitchUserPage extends AdminPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "show_switch";

	/**
	 * Show form to switch user identity
	 */
	function doShowSwitch()
	{
		$this->checkAllowed('admin', true);

		$this->tpl->loadTemplateFile("SwitchUser.html");
		$this->initTemplate("switch_user");
		$body = $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.switch_user'));
		$this->tpl->show();
	}

	/**
	 * Perform identity switch
	 */
	function doSwitch()
	{
		$this->checkAllowed('admin', true);
		$userlist = new Userlist();

		$username = post('username');
		if (!$username) {
			$userId = 0;
			$username = '_guest';
		} else {
			$user = $userlist->getUserByName($username);
			if (!$user) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.switch_user.not_found', MSG_RESULT_NEG);
				redirectTo('switch_user');
			}
			if($user->checkPermission('admin') && $GLOBALS["PORTAL"]->getUserId() != SUPERUSER_ID)
			{
				$GLOBALS['MSG_HANDLER']->addMsg('types.user.error.not_allowed_to_edit_admin', MSG_RESULT_NEG);
				redirectTo('switch_user');
			}
			$userId = $user->getId();
			$username = htmlspecialchars($user->getUsername());
		}

		if ($userId != $GLOBALS['PORTAL']->getUserId()) $GLOBALS['PORTAL']->switchUserId($userId);
		$GLOBALS['MSG_HANDLER']->addMsg('pages.switch_user.successful', MSG_RESULT_POS, array('username' => $username));
		redirectTo('');
	}

	/**
	 * Perform identity unswitch
	 */
	function doUnswitch()
	{
		$GLOBALS['PORTAL']->unswitchUserId();
		$user = $GLOBALS['PORTAL']->getUser();
		$GLOBALS['MSG_HANDLER']->addMsg('pages.switch_user.successful', MSG_RESULT_POS, array('username' => htmlspecialchars($user->getUsername())));
		redirectTo('', array('resume_messages' => 'true'));
	}
}

?>
