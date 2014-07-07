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
 * include UserList
 */
require_once(CORE.'types/UserList.php');

/**
 * Loads the base class
 */
require_once(PORTAL.'AdminPage.php');

/**
 * Load page selector widget
 */
require_once(PORTAL.'PageSelector.php');

/**
 * Allows an adminstrator to manage user accounts
 *
 * Default action: {@link doListUser()}
 *
 * @package Portal
 */
class UserAdminPage extends AdminPage
{
 /**
	 * @access private
	 */
	var $defaultAction = "list_user";
	var $correctionMessages = array();

	/**
	 * Creates a new user without an activation key
	 */
	function doCreateUser()
	{
		$userlist = new Userlist();

		$groups = post('groups', array());
		if (is_array($groups))
		{
			$groups = array_keys($groups);
		}

		$errors = $userlist->registerUser(post("editusername"), post("editpassword"), post("passwordControl"), post("fullname"), post("email"), post("language"), $groups, NULL, 0);

		if ($errors)
		{
			$this->correctionMessages = $errors;
			$this->doUserCreation();
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.create_success', MSG_RESULT_POS, array('user' => post("editusername")));
			// Log success
			$user = $userlist->getUserByName(post('editusername'));
			EditLog::addEntry(LOG_OP_USER_CREATE, $user, array(
				'username' => post('editusername'),
				'fullname' => post('fullname'),
				'email' => post('email'),
				'groups' => $groups,
			));

			redirectTo('user_admin', array('action' => 'user_creation', 'resume_messages' => 'true'));
		}
	}

	/**
	 * Deletes a user
	 */
	function doDeleteUser()
	{
		$userlist = new UserList();

		$user = $userlist->getUserById(post('id'));
		if ($user) {
			EditLog::addEntry(LOG_OP_USER_DELETE, $user, array(
				'username' => $user->getUsername(),
				'fullname' => $user->getFullname(),
				'email' => $user->getEmail(),
			));
		}
		list($msgId, $msgType) = $userlist->deleteUser(getpost("id"), $GLOBALS['PORTAL']->getUserId());
		$GLOBALS["MSG_HANDLER"]->addMsg($msgId, $msgType);
		redirectTo('user_admin', array('action' => 'list_user', 'resume_messages' => 'true'));
	}

	/**
	 * Activates a user
	 */
	function doActivate()
	{
		$userlist = new Userlist();

		$user = $userlist->getUserById(get('id'));
		if ($user) {
			EditLog::addEntry(LOG_OP_USER_ACTIVATE, $user, NULL);
		}
		$userlist->freeUser(get("id"), $GLOBALS["PORTAL"]->getUserId());

		redirectTo('user_admin', array('action' => 'list_user', 'resume_messages' => 'true'));
	}

	/**
	 * Deactivates a user
	 */
	function doDeactivate()
	{
		$userlist = new Userlist();

		$user = $userlist->getUserById(get('id'));
		if ($user) {
			EditLog::addEntry(LOG_OP_USER_DEACTIVATE, $user, NULL);
		}
		if ($error = $userlist->blockUser(get("id"), $GLOBALS["PORTAL"]->getUserId()))
		{
			$GLOBALS["MSG_HANDLER"]->addFinishedMsg($error, MSG_RESULT_NEG);
		}
		redirectTo('user_admin', array('action' => 'list_user', 'resume_messages' => 'true'));
	}

	/**
	 * Modifies an existing user
	 */
	function doSaveUser()
	{
		$errors = array();

		$userlist = new Userlist();
		$editUser = $userlist->getUserById(get("id"));
		$details = array();
		
		//Only the Superuser can edit other admins
		if ($editUser->checkPermission('admin') && $this->portal->userId != SUPERUSER_ID)
		{
			$errors[] = T("types.user.error.not_allowed_to_edit_admin");
		}
		else {
			if ($editUser->getUsername() != post("editusername"))
			{
				$errors = array_merge($editUser->setUsername(post("editusername"), $GLOBALS['PORTAL']->getUserId()), $errors);
				$details['username'] = post('editusername');
			}
			if ($editUser->getEmail() != post("email"))
			{
				$errors = array_merge($editUser->setEmail(post("email"), $GLOBALS['PORTAL']->getUserId()), $errors);
				$details['email'] = post('email');
			}
			if ($editUser->getLanguage() != post("language"))
			{
				$editUser->set('lang', post("language"), $GLOBALS['PORTAL']->getUserId());
				$editUser->commit();
				$details['language'] = post('language');
			}
			if ($editUser->getFullname() != post("fullname"))
			{
				$errors = array_merge($editUser->setFullname(post("fullname"), $GLOBALS['PORTAL']->getUserId()), $errors);
				$details['fullname'] = post('fullname');
			}
			if (strlen(post("editpassword")) > 0)
			{
				$errors = array_merge($editUser->setPasswdWithoutOld(post("editpassword"), post("passwordControl"), $GLOBALS['PORTAL']->getUserId()), $errors);
			}
			// Update group information
			$groups = post('groups', array());
			if (is_array($groups)) {
				$groups = array_keys($groups);
				$editUser->setGroups($groups, $GLOBALS['PORTAL']->getUserId());
			}
			
			$form_filled = $editUser->get('form_filled');
			if (post('form_filled') == 1 && !isset($form_filled)) {
				$editUser->set('form_filled', time(), $GLOBALS['PORTAL']->getUserId());
				$editUser->commit();
			}
			elseif (isset($form_filled)) {
				$editUser->set('form_filled', NULL, $GLOBALS['PORTAL']->getUserId());
				$editUser->commit();
			}
		}
		
		if ($errors)
		{
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages(array_values($errors), MSG_RESULT_NEG);

		} else {
			if ($user = $userlist->getUserById(get('id'))) {
				EditLog::addEntry(LOG_OP_USER_EDIT, $user, $details);
			}
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.save_success', MSG_RESULT_POS, array('user' => post("editusername")));
		}
		redirectTo('user_admin', array('action' => 'edit_user', 'id' => get('id'), 'resume_messages' => 'true'));
	}

	/**
	 * Creates a full UserList or a selection
	 */
	function doListUser()
	{
		$userlist = new Userlist();
		
		if (!isset($_SESSION["SEARCHFIELD"]))
			$_SESSION["SEARCHFIELD"] = "";
		if (!isset($_SESSION["USERSEARCH"]))
			$_SESSION["USERSEARCH"] = "";

		$body = "";

		$this->tpl->loadTemplateFile("UserAdmin.html");
		$this->initTemplate("list_user");
		$this->tpl->touchBlock("search_user");
		$this->tpl->touchBlock("group_list");

		// create groupslist
		$groups = $userlist->getGroupList();

		// make currently searched group appear first in list
		if (isset($_POST["groupsearch"]) && ($_POST["groupsearch"] != 0)) {
		    $this->tpl->setVariable("groupid", $_POST["groupsearch"]);
		    $group = DataObject::getById('Group', $_POST["groupsearch"]);
            $this->tpl->setVariable("groupname", $group->get('groupname'));
            $this->tpl->parse("group_list");
		}
		
		if (isset($_POST["groupsearch"]))
			if ($_POST["groupsearch"] != 0) {
				$this->tpl->setVariable("groupid", $_POST["groupsearch"]);
				$group = DataObject::getById('Group', $_POST["groupsearch"]);
				$this->tpl->setVariable("groupname", $group->get('groupname'));
				$this->tpl->parse("group_list");
			}
	
		if (!isset($_POST["groupsearch"]) && !isset($_SESSION["GROUP_ID"])) {
			$groupid = 0;
		} 
		elseif (!isset($_POST["groupsearch"])  && isset($_SESSION["GROUP_ID"]) && $_SESSION["GROUP_ID"] != 0 ) {
			$groupid = $_SESSION["GROUP_ID"];
			$this->tpl->setVariable("groupid", $groupid);
		    $group = DataObject::getById('Group', $groupid);
            $this->tpl->setVariable("groupname", $group->get('groupname'));
            $this->tpl->parse("group_list");
		}
		elseif (isset($_POST["groupsearch"])) {
			$groupid = $_POST["groupsearch"];
		}
		else 
			$groupid = 0;
		
		$_SESSION["GROUP_ID"] = $groupid;
		
		// entry for "all users" selection
		$this->tpl->setVariable("groupid", 0);
		$this->tpl->setVariable("groupname", T("generic.all"));
		$this->tpl->parse("group_list");

		// remaining groups
		foreach ($groups AS $group)
		{
			if ($group->get('id') != post("groupsearch"))
			{
                $this->tpl->setVariable("groupid", $group->get('id'));
                $this->tpl->setVariable("groupname", $group->get('groupname'));
                $this->tpl->parse("group_list");
            }
		}

		// create userlist by search pattern


		if ((strlen(post("usersearch")) == 0) && (post("usersearch") !== NULL))
			$_SESSION["USERSEARCH"] = NULL;
			
		if (strlen(post("usersearch")) > 0 && post("searchfield") == "username") {
			$args = array($groupid, post("usersearch"), "", "", "");
			$this->tpl->setVariable("username_selected", "selected");
			$_SESSION["SEARCHFIELD"] = post("searchfield");
			$_SESSION["USERSEARCH"] = post("usersearch");
		} elseif (strlen(post("usersearch")) > 0 && post("searchfield") == "uid") {
			$args = array($groupid, "", "", "", post("usersearch"));
			$this->tpl->setVariable("uid_selected", "selected");
			$_SESSION["SEARCHFIELD"] = post("searchfield");
			$_SESSION["USERSEARCH"] = post("usersearch");
		} elseif (strlen(post("usersearch")) > 0 && post("searchfield") == "fullname") {
			$args = array($groupid, "", post("usersearch"), "", "");
			$this->tpl->setVariable("fullname_selected", "selected");
			$_SESSION["SEARCHFIELD"] = post("searchfield");
			$_SESSION["USERSEARCH"] = post("usersearch");
		} elseif (strlen(post("usersearch")) > 0 && post("searchfield") == "email") {
			$args = array($groupid, "", "", post("usersearch"), "");
			$this->tpl->setVariable("email_selected", "selected");
			$_SESSION["SEARCHFIELD"] = post("searchfield");
			$_SESSION["USERSEARCH"] = post("usersearch");
		} 
		elseif ((!post("usersearch")) && $_SESSION["SEARCHFIELD"] == "username" && isset($_SESSION["USERSEARCH"])) {
			$args = array($groupid, $_SESSION["USERSEARCH"], "", "", "");
			$this->tpl->setVariable("username_selected", "selected");
		}
		elseif ((!post("usersearch")) && $_SESSION["SEARCHFIELD"] == "uid" && isset($_SESSION["USERSEARCH"])) {
			$args = array($groupid, "", "", "", $_SESSION["USERSEARCH"]);
			$this->tpl->setVariable("uid_selected", "selected");
		}
		elseif ((!post("usersearch")) && $_SESSION["SEARCHFIELD"] == "fullname" && isset($_SESSION["USERSEARCH"])) {
			$args = array($groupid, "", $_SESSION["USERSEARCH"], "", "");
			$this->tpl->setVariable("fullname_selected", "selected");
		}
		elseif ((!post("usersearch")) && $_SESSION["SEARCHFIELD"] == "email" && isset($_SESSION["USERSEARCH"])) {
			$args = array($groupid, "", "", $_SESSION["USERSEARCH"], "");
			$this->tpl->setVariable("email_selected", "selected");
		}
		else {
			$args = array($groupid, "", "", "", "");
		}
		$userCount = call_user_func_array(array($userlist, 'countUsers'), $args);

		// Page Selector Setup
		if (post("entries_per_page")>0) {
			$entriesPerPage = getpost("entries_per_page");
		} else if (isset($_SESSION["USER_ADMIN_PAGE_ENTRIES"])) {
			$entriesPerPage = $_SESSION["USER_ADMIN_PAGE_ENTRIES"];
		} else {
			$entriesPerPage = 5;
		}
		$_SESSION["USER_ADMIN_PAGE_ENTRIES"] = $entriesPerPage;

		$this->tpl->setVariable($entriesPerPage . "_entries_per_page", "selected");
		$pageLinkDistance = 2;
		$pageCount = ceil($userCount/$entriesPerPage);

		if (! isset($_SESSION["USER_ADMIN_PAGE"])) {
			$_SESSION["USER_ADMIN_PAGE"] = $pageCount;
		}
		$_SESSION["USER_ADMIN_PAGE"] = getpost("page_number", $_SESSION["USER_ADMIN_PAGE"]);
		if ($_SESSION["USER_ADMIN_PAGE"] > $pageCount || $_SESSION["USER_ADMIN_PAGE"] <= 0 || (isset($_SESSION["USER_ADMIN_PAGE_COUNT"]) && $pageCount != $_SESSION["USER_ADMIN_PAGE_COUNT"])) {
			$_SESSION["USER_ADMIN_PAGE"] = $pageCount;
		}
		$_SESSION["USER_ADMIN_PAGE_COUNT"] = $pageCount;

		$pageSelector = new PageSelector($pageCount, $_SESSION['USER_ADMIN_PAGE'], $pageLinkDistance, 'user_admin');

		// Fetch users
		if ($pageCount > 0) {
			$offset = intval(($_SESSION['USER_ADMIN_PAGE']-1) * $entriesPerPage);
		} else {
			$offset = 0;
		}
		$args = array_merge($args, array(NULL, NULL, $offset, intval($entriesPerPage)));
		$users = call_user_func_array(array($userlist, 'getUserList'), $args);

		// List Users
		if (count($users) > 0)
		{
			$this->tpl->touchBlock("user_list");

			foreach ($users as $user)
			{
				if ($user->hasKey())
				{
					$this->tpl->setVariable("key", "mark");
				}
				else
				{
					$this->tpl->setVariable("key", "norm");
				}
				
				$this->tpl->setVariable("userid", $user->getId());
				$this->tpl->setVariable("username", $user->getUsername());
				$this->tpl->setVariable("fullname", $user->getFullname());
				$this->tpl->setVariable("createdate", date("d.m.Y", $user->fields["t_created"]));
				
				$group = implode(', ', $user->getGroupnames());
				if ($user->getId() == 1)
				{
					if($group != '') {
						$group .= ', ';
					}
					$group .= 'Superuser';
				}
				$this->tpl->setVariable("group", $group);
				if ($user->hasKey())
				{
					$this->tpl->setVariable("option", 'activate');
					$this->tpl->setVariable("activate", t('buttons.activate'));
				}
				else
				{
					$this->tpl->setVariable("option", 'deactivate');
					$this->tpl->setVariable("activate", t('buttons.deactivate'));
				}
				$this->tpl->parse("user_list");
			}
		}
		$this->tpl->setVariable("usersearch", $_SESSION["USERSEARCH"]);


		// Display page selector
		$pageSelector->renderDefault($this->tpl);

		$body .= $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		$this->tpl->show();
		echo getpost("display_entries");
	}

	/**
	 * Allows to create a user
	 */
	function doUserCreation()
	{
		$body = "";

		$userlist = new Userlist();

		$this->tpl->loadTemplateFile("UserEdit.html");
		$this->initTemplate("create_user");

		$this->tpl->setVariable("page_action", "create_user");

		$this->tpl->setVariable("button", t('buttons.create'));
		$this->tpl->setVariable("action", "create_user");

		$this->tpl->setVariable('username', post('editusername', ''));
		$this->tpl->setVariable('fullname', post('fullname', ''));
		$this->tpl->setVariable('email', post('email', ''));

		if(array_key_exists('username', $this->correctionMessages)) {
			$this->tpl->touchBlock('correction_username');
		}
		if(array_key_exists('password', $this->correctionMessages)) {
			$this->tpl->touchBlock('correction_password');
			$this->tpl->touchBlock('correction_password_control');
		}
		if(array_key_exists('password_control', $this->correctionMessages)) {
			$this->tpl->touchBlock('correction_password_control');
		}
		if(array_key_exists('fullname', $this->correctionMessages)) {
			$this->tpl->touchBlock('correction_fullname');
		}
		if(array_key_exists('email', $this->correctionMessages)) {
			$this->tpl->touchBlock('correction_email');
		}

		if ($this->correctionMessages)
		{
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages(array_values($this->correctionMessages), MSG_RESULT_NEG);
		}

		$groups = $userlist->getGroupList();

		// create languagelist
		foreach($GLOBALS["TRANSLATION"]->getAvailableLanguages() as $language)
		{
			$this->tpl->setVariable("valueLang", $language);
			$this->tpl->setVariable("longLang", T('language.'.$language));
			if($language == post('language', '')) {
				$this->tpl->setVariable('language_selected', 'selected="selected"');
			}
			$this->tpl->parse("languages");
		}

		$groupIds = post('groups', array());
		foreach ($groups AS $group)
		{
			if(array_key_exists($group->get('id'), $groupIds) && $groupIds[$group->get('id')] == "1") {
				$this->tpl->setVariable("selectGroup", ' checked="checked"');
			}
			$this->tpl->setVariable("groupid", $group->get('id'));
			$this->tpl->setVariable("groupname", $group->get('groupname'));
			$this->tpl->parse("group_list_create");
		}
		
		//hide privacy policy info
		$this->tpl->setVariable("hideLastAcc", "visibility:hidden;");
		$this->tpl->setVariable("hideOldAcc", "visibility:hidden;");
		$this->tpl->setVariable("hideNoAcc", "visibility:hidden;");
		$this->tpl->setVariable("acceptVersion", 0);

		$body .= $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		$this->tpl->show();
	}

	/**
	 * Allows to edit a user
	 */
	function doEditUser()
	{
		$body = "";

		$this->tpl->loadTemplateFile("UserEdit.html");
		$this->tpl->setVariable("action", "save_user");
		$this->initTemplate("list_user");
		$this->tpl->touchBlock("group_list_create");

		$this->tpl->setVariable("page_action", "edit_user");

		$userlist = new UserList();
		$editUser = $userlist->getUserById(get('id'));

		$groups = $userlist->getGroupList();

		$this->tpl->setVariable("user_id", $editUser->getId());
		$this->tpl->setVariable("button", t('buttons.edit'));
		$this->tpl->setVariable("username", $editUser->getUsername());
		$this->tpl->setVariable("fullname", $editUser->getFullname());
		$this->tpl->setVariable("email", $editUser->getEmail());
		$form_filled = $editUser->get('form_filled');
		if (isset($form_filled))	
			$this->tpl->setVariable("form_filled", 'checked="checked"');

		// create languagelist
		foreach($GLOBALS["TRANSLATION"]->getAvailableLanguages() as $language)
		{
			if ($editUser->get('lang') == $language)
				$this->tpl->setVariable("select", "selected=selected");
			$this->tpl->setVariable("valueLang", $language);
			$this->tpl->setVariable("longLang", T('language.'.$language));
			$this->tpl->parse("languages");
		}

		$uGroups = $editUser->getGroups();
		$uGhash = array();
		foreach ($uGroups as $uGroup) {
			$uGhash[$uGroup->get('id')] = $uGroup;
		}
		foreach ($groups AS $group)
		{
			if (isset($uGhash[$group->get('id')])) $this->tpl->setVariable('selectGroup', ' checked="checked"');

			$this->tpl->setVariable("groupid", $group->get('id'));
			$this->tpl->setVariable("groupname", $group->get('groupname'));
			$this->tpl->parse("group_list_create");
		}
		if ($editUser->getId() == SUPERUSER_ID)
		{
			$this->tpl->setVariable("superuser", "Superuser");
		}
		if (count($groups) == 0) {
			$this->tpl->hideBlock('group_list_create');
		}
		
		// check and display if user has accepted the latest Privacy Policy		
		$accVersion = $editUser->getPrivacyPolicyAcc();
		$this->tpl->setVariable("acceptVersion", $accVersion);
		$current_pp = PrivacyPolicy::getCurrentVersion();
		
		
		if($accVersion != 0)
		{
			$this->tpl->setVariable("hideNoAcc", "visibility:hidden;");
			if($accVersion != $current_pp){
				$this->tpl->setVariable("hideLastAcc", "visibility:hidden;");
			} else {
				$this->tpl->setVariable("hideOldAcc", "visibility:hidden;");
			}		
		} else {
			$this->tpl->setVariable("hideLastAcc", "visibility:hidden;");
			$this->tpl->setVariable("hideOldAcc", "visibility:hidden;");
		}
		if (!isset($current_pp)) {
			$this->tpl->setVariable("hideNoAcc", "visibility:hidden;");
			$this->tpl->setVariable("hideLastAcc", "visibility:hidden;");
			$this->tpl->setVariable("hideOldAcc", "visibility:hidden;");
		}
		
		
		$body .= $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		EditLog::addEntry(LOG_OP_USER_SHOW, $editUser, array(
				'username' => $editUser->getUsername(),
				'fullname' => $editUser->getFullname(),
				'email' => $editUser->getEmail()
			));
		$this->tpl->show();
	}

}

?>
