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
class UserDeletePage extends AdminPage
{
 /**
	 * @access private
	 */
	var $defaultAction = "filter_user";
	var $correctionMessages = array();


	/**
	 * Deletes a user
	 */
	function doDeleteUser($id)
	{
		$userlist = new UserList();

		$user = $userlist->getUserById($id);
		if ($user) {
			EditLog::addEntry(LOG_OP_USER_DELETE, $user, array(
				'username' => $user->getUsername(),
				'fullname' => $user->getFullname(),
				'email' => $user->getEmail(),
			));
		}
		list($msgId, $msgType) = $userlist->deleteUser($id, $GLOBALS['PORTAL']->getUserId());
		$GLOBALS["MSG_HANDLER"]->addMsg($msgId, $msgType);
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

	
	
	function doFilterUser()
	{
		$userlist = new Userlist();
		
		$_SESSION['countEmail'] = 0;
		
		if (!isset($_SESSION["DATE"]) || $_SESSION["DATE"] == '' || post("date") == '') 
			$_SESSION["DATE"] = "DD.MM.YYYY";
			
		if (post("date")) {
			$_SESSION["DATE"] = post("date");
		}
		$body = "";

		$this->tpl->loadTemplateFile("UserDelete.html");
		$this->initTemplate("delete_user");
		$this->tpl->touchBlock("search_user");
		$this->tpl->touchBlock("group_list");
		
		$this->tpl->setVariable("date", $_SESSION["DATE"]);

		// create groupslist
		$groups = $userlist->getGroupList();

		// make currently searched group appear first in list
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
		
		if ((!post("date")) && (!$_SESSION["DATE"])) {
			$date = 0;
		} 
		elseif (post("date"))
			$date = post("date");
		else 
			$date = $_SESSION["DATE"];
		

		// create userlist by search pattern
		$unixTime = strtotime($date);
		$args = array($groupid,  "", "", "", "", $unixTime, "");

		$userCount = call_user_func_array(array($userlist, 'countUsers'), $args);

		// Page Selector Setup
		if (post("entries_per_page") > 0) {
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

		$pageSelector = new PageSelector($pageCount, $_SESSION['USER_ADMIN_PAGE'], $pageLinkDistance, 'user_delete');

		// Fetch users
		if ($pageCount > 0) {
			$offset = intval(($_SESSION['USER_ADMIN_PAGE']-1) * $entriesPerPage);
		} else {
			$offset = 0;
		}
		$args = array_merge($args, array($offset, intval($entriesPerPage)));
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
				
				if ($user->fields["last_login"] != NULL)
					$this->tpl->setVariable("lastlogin", date("d.m.Y", $user->fields["last_login"]));
				else
					$this->tpl->setVariable("lastlogin", T('generic.lastLogin.unknown'));
				
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
		
		$unixTime = time();
		$deleteTime = $unixTime + 1209600;
		
		// Display number of users in queue#
		$this->tpl->touchBlock("users_in_queue");
		$this->db = $GLOBALS['dao']->getConnection();
		
		$res = $this->db->query("SELECT COUNT(*) FROM ".DB_PREFIX."users WHERE delete_time IS NOT NULL AND (deleted <> 1 OR deleted is NULL) AND delete_time < ?", 
								array($deleteTime));
								
		$usersInQueue = $res->fetchRow();

		$this->tpl->setVariable("usersInQueue", $usersInQueue['COUNT(*)']);
		
		// Display number of users to delete
		$this->tpl->touchBlock("user_trash");
		$args = array("",  "", "", "", "", "", $unixTime);
		$userCountDel = call_user_func_array(array($userlist, 'countUsers'), $args);
		$this->tpl->setVariable("usersToDelete", $userCountDel);

		// Display page selector
		$pageSelector->renderDefault($this->tpl);

		$body.= $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		$this->tpl->show();
		echo getpost("display_entries");
	}
	
	/**
	 *Confirm mail to user
	*/
	
	function doConfirmUserEmail()
	{
		$userlist = new Userlist();

		if (isset($_SESSION["DATE"]))
			$date = $_SESSION["DATE"];
		else
			$date = 0;
		if (isset($_SESSION["GROUP_ID"]))	
			$groupid = $_SESSION["GROUP_ID"];
		else
			$groupid = 0;
		$this->tpl->loadTemplateFile("confirmDeleteMail.html");
		$this->initTemplate("delete_user");
		$this->tpl->touchBlock("confirm_mail");
		
		$unixTime = strtotime($date);
		$args = array($groupid,  "", "", "", "", $unixTime, "");
		$userCount = call_user_func_array(array($userlist, 'countUsers'), $args);
		
		$this->tpl->setVariable("usersToMail", $userCount);
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		$this->tpl->show();
	}
	
	/**
	 *Send mail to user
	*/
	  
	function doUserEmail()
	{
		$userlist = new Userlist();

		if (isset($_SESSION["DATE"]))
			$date = $_SESSION["DATE"];
		else
			$date = 0;
		if (isset($_SESSION["GROUP_ID"]))	
			$groupid = $_SESSION["GROUP_ID"];
		else
			$groupid = 0;
			
		if (isset($_SESSION['countEmail']))
			$countEmail = $_SESSION['countEmail'];
		else
			$countEmail = 0;
		
		$unixTime = strtotime($date);
		$args = array($groupid,  "", "", "", "", $unixTime, "");
		$userCount = call_user_func_array(array($userlist, 'countUsers'), $args);
	
		$entriesPerPage = 100;
		
		if ($countEmail >= $userCount)
			$countEmail = 0;
			
		$offset = $countEmail;
		
		
		
		$args = array_merge($args, array($offset, intval($entriesPerPage)));
		$users = call_user_func_array(array($userlist, 'getUserList'), $args);
		$this->tpl->loadTemplateFile("EmailForDelAccount.html");
		$textOrginal = $this->tpl->get();
		$link = $_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF'];
		$cut = strrchr($link, "/"); 
		$link = str_replace($cut, "", $link);
		$deleteTime = time() + 1209600;
		
		$time1 = time();
		
		/*$this->tpl->loadTemplateFile("countMails.html");
		$this->tpl->parse("count_mail");
		$this->tpl->setVariable("countMails", $countEmail);
		$this->tpl->setVariable("toSendMails", $userCount);
		$this->tpl->show();*/
		
		$newPackage = false;
		
		$count = 0;
		
		foreach ($users as $user) {
			if ($user->fields["delete_time"] == NULL) {
				$emailAddress = $user->fields["email"];
				$subject = "Testmaker account";
				$text = $textOrginal;
				$text = str_replace("-12xy21-", htmlentities($user->fields["full_name"]), $text);
				$linkUser = "http://".$link."/index.php?page=confirm_account&action=confirm&id=".$user->getId()."&passwh=".$user->fields["password_hash"];
		
				$text = str_replace("www...", $linkUser, $text);
				$text = str_replace("www---...", $link, $text);
				require_once('lib/email/Composer.php');
				$mail = new EmailComposer();
				$mail->setTextMessage($text);
				$mail->setSubject($subject);
				$mail->setFrom(SYSTEM_MAIL);
				$mail->addRecipient($emailAddress);
				$mail->sendMail();
				$user->setDeleteTime($deleteTime);
				
				$countEmail++;
			}
					
			$count++;
		
			//if near server timeout store number of sent mails and make redirect
			if ((time() - $time1 > 45) or ($count >= $entriesPerPage)) {
				$_SESSION['countEmail'] = $countEmail;
				$newPackage = true;
				break;
			}
		}

		if ($newPackage) {
			$this->tpl->loadTemplateFile("countMails.html");
			$this->tpl->hideBlock("count_mail");
			$this->tpl->touchBlock("click");
			$link = "index.php?page=user_delete&action=user_email";
			$this->tpl->setVariable("link", $link);
			$this->tpl->show();
			/*echo'<script type="text/javascript"> 
						document.getElementsByTagName("a")[0].click();
				</script>
				</html>';*/
		}
		else {
			$this->tpl->loadTemplateFile("countMails.html");
			$this->tpl->hideBlock("click");
			$this->tpl->touchBlock("count_mail");
			$this->tpl->setVariable("countMails", $countEmail);
			$this->tpl->setVariable("toSendMails", $userCount);
			$this->tpl->touchBlock("finished");
			$this->tpl->show();	
		}
	}
	
	function doUsersDeleteFinal()
	{
		$userlist = new Userlist();
		$unixTime = time();

		$args = array("", "", "", "", "", "", $unixTime);
		$offset = 0;
		$entriesPerPage = 9999999;
	
		$args = array_merge($args, array($offset, intval($entriesPerPage)));
		$users = call_user_func_array(array($userlist, 'getUserList'), $args);
	
		$userDeleteListId = array();
		foreach ($users as $user) {
					$userDeleteListId[] = $user->getId();
		}
		foreach ($userDeleteListId as $userDelId) {
			$this->doDeleteUser($userDelId);
		}
		
		redirectTo('user_delete', array('action' => 'filter_user'));
	
	}
}

?>
