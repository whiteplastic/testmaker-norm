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
 * Allows a user to login
 *
 * Default action: {@link doShowLogin()}
 *
 * @package Portal
 */
class UserLoginPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "show_login";

	function doShowLogin()
	{
		$this->tpl->loadTemplateFile("UserLogin.html");	
		$this->tpl->touchBlock("user_login_form");
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		
		$this->tpl->hideBlock("menu_login_form");
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_login.login.title"));
		$this->tpl->show();
	}

	/**
	 * Login page
	 */
	function doLogin()
	{
		$checkUser = $GLOBALS["PORTAL"]->getUser();
		if($checkUser != NULL && $checkUser->getId() != 0) {
			$page = &$GLOBALS["PORTAL"]->loadPage('start');
			$page->run();
			return;
		}

		$error = FALSE;

		$username = post("username", "");
		$password = post("password", "");
		$user = '';

		if ($username == "" || $password == "")
		{
			$error = T("pages.user_login.login.error.incomplete");
		}
		else
		{
			$userList = new UserList();
			$user = $userList->getUserByLogin($username, $password);
		}

		if ($user)
		{
			if(Setting::get('maintenance_mode_on') == '1' && !$user->checkPermission('admin')) {
				redirectTo('start', array('resume_messages' => 'true'));
			} else {
				$GLOBALS["PORTAL"]->startSession();
				$GLOBALS["PORTAL"]->setUserId($user->getId());
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.user_login.login_successful', MSG_RESULT_POS, array('name' => htmlentities($user->getUsername())));
				$user->setLastLogin();
				$user->setDeleteTime(NULL);
				session_regenerate_id();

				redirectTo('test_listing', array('resume_messages' => 'true'));
			}
		}
		else
		{
			$this->tpl->loadTemplateFile("UserLogin.html");
			$this->tpl->setVariable("error", $error);
			$this->tpl->touchBlock("user_login_forgot");
		}

		if ($error) {
			$this->tpl->setVariable("form_login_username", $username);
			$this->tpl->setVariable("username", $username);
			$this->tpl->touchBlock("user_login_form");
		}
		$body = $this->tpl->get();

		$this->loadDocumentFrame();
		if ($error) {
			$this->tpl->hideBlock("menu_login_form");
		}
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_login.login.title"));
		$this->tpl->show();
	}

	/**
	 * Logout page
	 */
	function doLogout()
	{
		$GLOBALS["PORTAL"]->setUserId(NULL);
		$this->tpl->loadTemplateFile('Logout.html');
		$body = $this->tpl->get();
		if(get('user_delete') == true)
		$body = T("types.user_list.delete_user.success").$body;
		

		// Löschen aller Session-Variablen.
		$_SESSION = array();

		// Falls die Session gelöscht werden soll, löschen Sie auch das
		// Session-Cookie.
		// Achtung: Damit wird die Session gelöscht, nicht nur die Session-Daten!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"],
				$params["domain"], $params["secure"], $params["httponly"]
			);
		}

		// Zum Schluß, löschen der Session.
		session_destroy();

		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T("pages.user_login.logout.confirm_title"));
		$this->tpl->show();
	}

	function doSendConfirmationLink()
	{

		$username = post("username");
		$userlist = new UserList();
		
		// also accept email address to restore password
				
		// If given string contains '@': assume it is an email-address		
		if (strpos($username, '@')){
			$users = $userlist->getUsersByEmail($username);
		} else {
			$users[] = $userlist->getUserByName($username);
		}
		if (!$users) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.failed', MSG_RESULT_NEG);
			redirectTo("user_login", array("action" => "forgot_password", "resume_messages" => "true"));
		}
		else
		{
			$user_l = count($users);
			for($i = 1; $i <= $user_l; $i++) {
				if ($user_l > 1) {
					$user = current($users);
					next($users);
					}
				else
					$user = current($users);
				$key = $user->getConfirmationKey();
				if (! $key) {
					$user->generateConfirmationKey();
					$key = $user->getConfirmationKey();
				}
				$link = linkToFile("password.php", array("key" => $key), FALSE, TRUE);
				$name = $user->getDisplayFullname();
				$username = $user->getUsername();

				$bodies = array(
					"html" => "UserForgotPasswordMail.html",
					"text" => "UserForgotPasswordMail.txt",
				);
				foreach ($bodies as $type => $templateFile) {
					$this->tpl->loadTemplateFile($templateFile);
					$this->tpl->setVariable("username", htmlentities($username));
					$this->tpl->setVariable("name", htmlentities($name));
					$this->tpl->setVariable("link", $link);
					$bodies[$type] = $this->tpl->get();
				}
				libLoad('email::Composer');
				$mail = new EmailComposer();
				$mail->setSubject(T("pages.user_login.forgot_password.mail.subject"));
				$mail->setFrom(SYSTEM_MAIL, "testMaker");
				$mail->addRecipient($user->getEmail(), $name);
				$mail->setHtmlMessage($bodies["html"]);
				$mail->setTextMessage($bodies["text"]);
				$this->tpl->loadTemplateFile("UserForgotPassword.html");
				if ($mail->sendMail()) {
					$this->tpl->touchBlock("confirmation_link_mail_success");
				} else {
					$this->tpl->touchBlock("confirmation_link_mail_failure");
				}
			}

		}
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_login.password_reset"));
		$this->tpl->show();
	}

	function doShowChangePasswordForm()
	{
		$key = get("key");

		$this->tpl->loadTemplateFile("UserForgotPassword.html");
		$this->tpl->setVariable("confirmation_key", $key);
		$userlist = new UserList();
		if (! $userlist->getUserByConfirmationKey($key)) {
			$this->tpl->touchBlock("invalid_confirmation_key");
		} else {
			$this->tpl->touchBlock("password_form");
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_login.password_reset"));
		$this->tpl->show();
	}

	function doChangePassword()
	{
		$key = post("key");
		$password = post("password");
		$passwordRepeat = post("password_repeat");
		$user = '';
		$this->tpl->loadTemplateFile("UserForgotPassword.html");
		$userlist = new UserList();
		if (! $user = $userlist->getUserByConfirmationKey($key)) {
			$this->tpl->touchBlock("invalid_confirmation_key");
		} else {
			$errors = $user->setPasswdWithoutOld($password, $passwordRepeat);

			if (! $errors) {
				$user->resetConfirmationKey();
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.password_changed', MSG_RESULT_POS);
				redirectTo("user_login", array('resume_messages' => 'true'));
			}
			else {
				$this->tpl->touchBlock("status_block");
				foreach ($errors as $error)
				{
					$this->tpl->setVariable("status", $error);
					$this->tpl->parse("status_list");
				}
				$this->tpl->setVariable("confirmation_key", $key);
				$this->tpl->touchBlock("password_form");
			}
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_login.password_reset"));
		$this->tpl->show();
	}

	function doForgotPassword()
	{
		$this->tpl->loadTemplateFile("UserForgotPassword.html");
		$this->tpl->touchBlock("username_form");
		
	    if(isset($_GET['user']))
	    	$this->tpl->setVariable("prefill", $_GET['user']);
	    else
	    	$this->tpl->setVariable("prefill", "");
	    	
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_login.password_reset"));
		$this->tpl->show();
	}
}

?>
