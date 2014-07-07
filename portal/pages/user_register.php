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
 * Include the UserList class
 */
require_once(CORE.'types/UserList.php');

/**
 * Allows a user to register an account
 *
 * Default action: {@link doShowForm()}
 *
 * @package Portal
 */
class UserRegisterPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "show_form";

	function doShowForm()
	{
		!Setting::get('curr_privacy_policy') ? $no_privacy=1 : $no_privacy=0;
				
		$this->tpl->loadTemplateFile("UserRegisterForm.html");

		foreach($GLOBALS["TRANSLATION"]->getAvailableLanguages() as $language)
		{
			$this->tpl->setVariable("valueLang", $language);
			$this->tpl->setVariable("longLang", T('language.'.$language));
			$this->tpl->parse("languages");
		}

		$this->tpl->setVariable("ppversion", PrivacyPolicy::getCurrentVersion());
		if ($no_privacy) $this->tpl->hideBlock("pp_check");
		else $this->tpl->touchBlock("pp_check");
				
		
		$body = $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.user_register.register.title"));
		$this->tpl->show();
	}
	function doCheckName()
	{
		$requested_username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
		$userList = new UserList();
		User::validateUsername($requested_username, $errors);
		if(!empty($errors))
			echo implode(",", $errors);
	}
	
	function doAddAccount()
	{
		$requested_username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
		$firstUser = DataObject::getById('User', SUPERUSER_ID);
		
		if ($firstUser === null)
			$hasAdmin = false;
		else
			$hasAdmin = ($firstUser->get('id') == SUPERUSER_ID);
			
		$userList = new UserList();

		
		!Setting::get('curr_privacy_policy') ? $no_privacy=1 : $no_privacy=0;
		
		if (post('accepted') || $no_privacy){
			
			if (! $hasAdmin) {
				$errors = $userList->registerUser($requested_username, post('password'), post('passwordControl'), post('fullname', NULL), htmlentities(post('email')), post('language', 'en'), array());
			} else {
				$key = md5(uniqid(mt_rand()));
				$errors = $userList->registerUser($requested_username, post('password'), post('passwordControl'), post('fullname', NULL), htmlentities(post('email')), post('language', 'en'), array(), $key, 0);
			}
		} else {
			$errors = array();
			$errors['privacy'] = T("pages.privacy_policy.msg.not_accepted");
		}
			
		
		if (! $errors)
		{
			if (!$hasAdmin) {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.register.first_done', MSG_RESULT_POS);
				redirectTo("start");
			}
			else
			{
				$name = post('fullname') != "" ? post('fullname') : $requested_username;

				$bodies = array(
					"html" => "UserRegisterMail.html",
					"text" => "UserRegisterMail.txt",
				);
				foreach ($bodies as $type => $templateFile) {
					$this->tpl->loadTemplateFile($templateFile);
					$this->tpl->setVariable("email_address", post('email'));
					$this->tpl->setVariable("name", htmlentities($name));
					$this->tpl->setVariable("user_name", htmlentities($requested_username));
					//$this->tpl->setVariable("link", linkTo("user_register", array("action" => "confirm_register", "key" => $key), FALSE, TRUE));
					$this->tpl->setVariable("link", linkToFile("register.php", array("key" => $key), FALSE, TRUE));
					$bodies[$type] = $this->tpl->get();
				}

				libLoad('email::Composer');
				$mail = new EmailComposer();
				$mail->setSubject(T('pages.user.register.subject'));
				$mail->setFrom(SYSTEM_MAIL, "testMaker");
				$mail->addRecipient(post('email'), $name);
				//print_r($bodies['html']);
//printvar($bodies);
				$mail->setHtmlMessage($bodies["html"]);
				$mail->setTextMessage($bodies["text"]);

				if (@$mail->sendMail()) {
					$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.register.seeMail', MSG_RESULT_POS);
					$user = DataObject::getOneBy('User', 'username', $requested_username);
					if (!$no_privacy) $user->setPrivacyPolicyAcc(PrivacyPolicy::getCurrentVersion());
				} else {
					$user = DataObject::getOneBy('User', 'username', $requested_username);
					list($msgId, $msgType) = $userList->deleteUser($user->getId(), -1, FALSE);
					$GLOBALS["MSG_HANDLER"]->addMsg("types.user_list.create_user_failed", MSG_RESULT_NEG);
					$errors[] = T('pages.user.register.mailerror');
				}
			}
		}

		if (!$errors) {
			redirectTo("user_login", array("resume_messages" => "true"));
		}
		else
		{
			$this->tpl->loadTemplateFile("UserRegisterForm.html");
			$this->tpl->setVariable('username', post('username', ''));
			$this->tpl->setVariable('fullname', post('fullname', ''));
			$this->tpl->setVariable('email', post('email', ''));
			$this->tpl->setVariable("ppversion", PrivacyPolicy::getCurrentVersion());
			
			if ($no_privacy) $this->tpl->hideBlock("pp_check");
			else $this->tpl->touchBlock("pp_check");
			
			if(array_key_exists('username', $errors)) {
				$this->tpl->touchBlock('correction_username');
			}
			if(array_key_exists('password', $errors)) {
				$this->tpl->touchBlock('correction_password');
				$this->tpl->touchBlock('correction_password_control');
			}
			if(array_key_exists('password_control', $errors)) {
				$this->tpl->touchBlock('correction_password_control');
			}
			if(array_key_exists('fullname', $errors)) {
				$this->tpl->touchBlock('correction_fullname');
			}
			if(array_key_exists('email', $errors)) {
				$this->tpl->touchBlock('correction_email');
			}
			if(!post('accepted') && !$no_privacy) {
				$this->tpl->touchBlock('correction_pp_accept');
			}

			foreach($GLOBALS["TRANSLATION"]->getAvailableLanguages() as $language)
			{
				$this->tpl->setVariable("valueLang", $language);
				$this->tpl->setVariable("longLang", T('language.'.$language));
				if($language == post('language', '')) {
					$this->tpl->setVariable('language_selected', 'selected="selected"');
				}
				$this->tpl->parse("languages");
			}

			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages(array_values($errors), MSG_RESULT_NEG);

			$body = $this->tpl->get();

			$this->loadDocumentFrame();
			$this->tpl->setVariable("body", $body);
			$this->tpl->setVariable("page_title", T("pages.user_register.register.title"));
			$this->tpl->show();
		}
	}

	function doConfirmRegister()
	{
		$userList = new UserList();
		$key = substr(get('key'), 0, 32);
		$error = $userList->confirmUser($key);

		if (! $error)
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.confirm', MSG_RESULT_POS);
			redirectTo("user_login", array("resume_messages" => "true"));
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addFinishedMsg($error, MSG_RESULT_NEG);
			redirectTo("user_register", array("resume_messages" => "true"));
		}
	}
}

?>
