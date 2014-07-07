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
 * Default action: {@link doPrintDetails()}
 *
 * @package Portal
 */
class UserDetailsPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "print_details";

	/**
	 * Print table with user details and to input fields for new password and email
	 */
	function doPrintDetails()
	{
		$body = "";

		$this->tpl->loadTemplateFile("UserDetails.html");
		$this->tpl->touchBlock("user_block");
		$this->tpl->touchBlock("passwd_block");
		$this->tpl->touchBlock("email_block");
		$this->tpl->touchBlock("language_block");
		$this->tpl->touchBlock("fullname_block");
		$this->tpl->touchBlock("delete_block");
		
		$user = $GLOBALS["PORTAL"]->getUser();
		if ($user->getId() == 0) {
			$this->checkAllowed('invalid', true);
		}

		$this->tpl->setVariable("username", $user->getUsername());
		$group = implode(', ', $user->getGroupnames());
		if ($user->getId() == 1)
		{
			$group .= ", Superuser";
		}
		$this->tpl->setVariable("group", $group);
		$this->tpl->setVariable("fullname", $user->getFullname());
		$bday = $user->getBday();
		if(isset($bday))
			$this->tpl->setVariable("bday", date('d.m.Y',$user->getBday()));
		$this->tpl->setVariable("email", $user->getEmail());
		$this->tpl->setVariable("language", ($user->getLanguage() ? T('language.'.$user->getLanguage()) : ''));
		$this->tpl->setVariable("target_email", linkTo("user_details", array("action" => "change_email"), TRUE));
		$this->tpl->setVariable("target_passwd", linkTo("user_details", array("action" => "change_passwd"), TRUE));
		$this->tpl->setVariable("target_fullname", linkTo("user_details", array("action" => "change_fullname"), TRUE));
		$this->tpl->setVariable("target_language", linkTo("user_details", array("action" => "change_language"), TRUE));
		$this->tpl->setVariable("target_delete", linkTo("user_details", array("action" => "delete_user"), TRUE));

		$privacy_accepted = $user->getPrivacyPolicyAcc();
		if($privacy_accepted > 0)
			$this->tpl->setVariable("privacy", '<a href="index.php?page=user_details" onclick="window.open(\'index.php?page=show_privacy_policy&amp;action=show_privacy_popup&amp;info=none&amp;version='.$privacy_accepted.'\', \'page_help_\'+((new Date()).getTime()), \'width=600,height=540,left=\'+Math.round((screen.width-600)/2)+\',top=\'+Math.round((screen.height-540)/2)+\',scrollbars=yes,status=yes,toolbar=yes,resizable=yes\'); return false"> version ' . $privacy_accepted .'</a>');
		else 
			$this->tpl->setVariable("privacy", '---');

		
		foreach($GLOBALS["TRANSLATION"]->getAvailableLanguages() as $language)
		{
			$this->tpl->setVariable("valueLang", $language);
			if ($user->getLanguage() == $language)
			{
				$this->tpl->setVariable("select", " selected=\"selected\"");
			}
			$this->tpl->setVariable("longLang", T('language.'.$language));
			$this->tpl->parse("languages");
		}

		$this->tpl->parse("user_block");

		$body .= $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_details.print_details.title"));
		$this->tpl->show();
	}

	/**
	 * Set new email
	 */
	function doChangeEmail()
	{
		$email = post("email");
		if($_SERVER["REQUEST_METHOD"] == "GET")
		{
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_email.wrong_request_method", MSG_RESULT_NEG);
			redirectTo("user_details");
		}

		$user = $GLOBALS["PORTAL"]->getUser();

		$errors = $user->setNewEmail($email);

		if (! $errors)
		{
			$key = $user->generateEmailKey();

			$name = $user->getDisplayFullname();

			$subject = T("pages.user_details.change_email.mail.subject");

			$bodies = array(
				"html" => "UserEmailChange.html",
				"text" => "UserEmailChange.txt",
			);
			foreach ($bodies as $type => $templateFile) {
				$this->tpl->loadTemplateFile($templateFile);
				$this->tpl->setVariable("name", htmlentities($name));
				$this->tpl->setVariable("link", linkToFile("email.php", array("key" => $key), FALSE, TRUE));
				$bodies[$type] = $this->tpl->get();
			}

			libLoad("email::Composer");
			$mail = new EmailComposer();
			$mail->setSubject($subject);
			$mail->setFrom(SYSTEM_MAIL, "testMaker");
			$mail->addRecipient($email, $name);
			$mail->setHtmlMessage($bodies["html"]);
			$mail->setTextMessage($bodies["text"]);
			$success = $mail->sendMail();

			if ($success) {
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_email.mail.success", MSG_RESULT_POS);
			} else {
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_email.mail.error", MSG_RESULT_NEG);
			}
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addFinishedMsg($errors[0], MSG_RESULT_NEG);
		}

		redirectTo("user_details", array("resume_messages" => "true"));
	}

	/**
	 * Accept a new email address
	 */
	function doConfirmEmailChange()
	{
		$key = get("key");
		$user = '';

		$userlist = new UserList();
		if (! $user = $userlist->getUserByEmailKey($key))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_email.invalid_key", MSG_RESULT_NEG);
		}
		else
		{	
			$user->setEmail($user->get('new_email'));
			$user->resetEmailKey();
			$user->setNewEmail(NULL);

			$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_email.success", MSG_RESULT_POS);
		}

		$page = $GLOBALS["PORTAL"]->getUserId() ? "user_details" : "user_login";
		redirectTo($page, array("resume_messages" => "true"));
	}

	/**
	 * Set new password
	 */
	function doChangePasswd()
	{
		$user = $GLOBALS["PORTAL"]->getUser();
		$error = $user->setPasswd(post('password'), post('confirmPassword'), post('oldPassword'));

		if (! $error) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_password.success", MSG_RESULT_POS);
		} else {
			$GLOBALS["MSG_HANDLER"]->addFinishedMsg(reset($error), MSG_RESULT_NEG);
		}

		redirectTo("user_details", array("resume_messages" => "true"));
	}

	/**
	 * Set new Full username
	 */
	function doChangeFullname()
	{
		$user = $GLOBALS["PORTAL"]->getUser();
		$error = $user->setFullname(post('fullname'));

		if (! $error) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_fullname.success", MSG_RESULT_POS);
		} else {
			$GLOBALS["MSG_HANDLER"]->addFinishedMsg($error[0], MSG_RESULT_NEG);
		}

		redirectTo("user_details", array("resume_messages" => "true"));
	}

	/**
	 * Set new default language
	 */
	function doChangeLanguage()
	{
		$user = $GLOBALS["PORTAL"]->getUser();
		if (! ($user->get('lang') == post('language'))) {
			$user->set('lang', post('language'));
			$user->commit();

			// No error-handling for now, because of DataObject.
			// Update the system language as well
			$GLOBALS["PORTAL"]->setLanguage($user->getLanguage());
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.user_details.change_language.success", MSG_RESULT_POS);
		}
		redirectTo("user_details", array("resume_messages" => "true"));
	}
	
	/**
	 * Deletes the User
	 */
	function doDeleteUser()
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		
		$proceed = post("sure", 0);
		$password = post("password", 0);
		
		$user = $GLOBALS["PORTAL"]->getUser();
		$password = md5('~~tmaker~'.$password);
		if ($password != $user->get('password_hash'))
			$proceed = 0;
		
		if(empty($proceed)) {
			$GLOBALS["MSG_HANDLER"]->addMsg("types.user_list.delete_user.error", MSG_RESULT_NEG);
			redirectTo("user_details", array("resume_messages" => "true"));
		}
		else {
			$user = $user->getId();
			$id = -1;
			$simulate = TRUE;
			
			if ($user < 1 || $user == SUPERUSER_ID) {
				$GLOBALS["MSG_HANDLER"]->addMsg("types.user_list.delete_user.special_account", MSG_RESULT_NEG);
				redirectTo("user_details", array("resume_messages" => "true"));
			}

			if ($user == $id) {
				$GLOBALS["MSG_HANDLER"]->addMsg("types.user_list.delete_user.error", MSG_RESULT_NEG);
				redirectTo("user_details", array("resume_messages" => "true"));
			}

			if ($simulate)
			{
				$query = "UPDATE ".DB_PREFIX."users SET deleted=1, username=?, full_name='DEL', email='DEL', t_modified=?, u_modified=? WHERE id=?";
				$this->db->query($query, array('DEL'.$user, NOW, $id, $user));
			}
			else
			{
				$query = "DELETE FROM ".DB_PREFIX."groups_connect WHERE user_id=?";
				$this->db->query($query, array($user));
				$query = "DELETE FROM ".DB_PREFIX."users WHERE id=?";
				$this->db->query($query, array($user));
			}
			
			$GLOBALS["MSG_HANDLER"]->addMsg("types.user_list.delete_user.success", MSG_RESULT_POS);
			redirectTo("user_login", array("action" => "logout","resume_messages" => "true", "user_delete" => "true"));
		}
		
	}
	
}

?>
