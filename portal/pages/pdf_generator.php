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
 * pdf_generator
 *
 * This class implements the functionalities for generating PDF documents within the testMaker.
 * It is possible to create a test feedback as a pdf or produce a certificate of graduation.
 *
 */
 
require_once(PORTAL.'BlockPage.php');
require_once(CORE.'types/TestRunBlock.php');

class PdfGeneratorPage extends Page
{
	/**
	 * Create a certificate for the 'Maschinenwesen' faculty; prepare the necessary data
	 * and then call the functionality for really building the PDF document.
	 */
	function doCreateMwCertificate()
	{ 
		libLoad('pdf::PdfCreator');
		$testRunId = get('test_run_id', 0);
		$blockId = get('block_id', 0);
		$userId = $GLOBALS['PORTAL']->getUserId();
		$fromForm = get('from_form', 0);
		$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		$db = $GLOBALS['dao']->getConnection();
		$email = post('email', NULL);
		$username = post('username', NULL);
		$becomeUser = post('becomeUserCheck', NULL);
		$ppcheck = post('ppcheck', NULL);
		$password = post('password', NULL);
		$confPassword = post('confPassword', NULL);
		$name = post('name', NULL);
		$emailCheck = post('emailCheck', NULL);
		$birthdayForm = post('birthday', NULL);
		$language = $GLOBALS['TRANSLATION']->getLanguage();
		$timeStamp = time();
		$tr_return_id = post('tr_return_id', NULL);
		// 0 is valid trrid 
		if (empty($tr_return_id) && $tr_return_id != "0")
			$tr_return_id = NULL;
		!Setting::get('curr_privacy_policy') ? $no_privacy=1 : $no_privacy=0;
		
		if(isset($_GET["manual"]))
			$testRunId = $_POST["testRunId"];
		
		//delete the user data (name, surename, birthday) from the testrun if request come from a formular
		if ($fromForm == 1) {
			
			//check if email-address necessary and correct
			if (!empty($email) AND (!filter_var($email, FILTER_VALIDATE_EMAIL))) {
				$errors['email'] = T('pages.testmake.invalid_feedback_email_adress');
			}
			if ($becomeUser && $no_privacy == 0 && $ppcheck != 1) {
				$errors['ppcheck'] = T("pages.privacy_policy.msg.not_accepted");
			}
			//check if name > 5 chars and consists of 2 words
			$nameparts = explode(" ", $name);
			if ((strlen($name) < 5) OR (sizeof($nameparts) < 2)) {
				$errors['fullname'] = T('pages.user.register.nameerror');
			}
			
			$birthdayForm2 = explode(".",$birthdayForm);
			@$birthdayTimestamp = mktime("0","0","0",$birthdayForm2[1], $birthdayForm2[0], $birthdayForm2[2]);

			//date format of birthday not correct
			if (($birthdayTimestamp == 0) OR ($birthdayTimestamp == FALSE))	{
				$errors['birthday'] = T("types.user.error.date_format_false");
			}
			else if (!check_date($birthdayForm,"dmY","/") && !check_date($birthdayForm,"dmY",".")){
				$errors['birthday'] = T("types.user.error.date_format_false");
			}
			
			if(!empty($errors))
				redirectTo("pdf_generator", array("action" => "formular_certificate", "errorsReg" => $errors, "block_id" => $blockId, "test_run_id" => $testRunId,
							"username" => $username, "name" => $name, "birthday" => $birthdayForm, "email" => $email, "emailCheck" => $emailCheck, "becomeUserCheck" => $becomeUser, "tr_id" => $tr_return_id));	
			
			$query = "SELECT cert_fname_item_id, cert_lname_item_id, cert_bday_item_id, cert_template_name FROM ".DB_PREFIX."feedback_blocks WHERE id = ?";
			$itemIds = $db->getRow($query, array($blockId));

			//register new user
			if (($becomeUser == "1") AND ($userId == 0)) {
				if($this->registerUser($username, $password, $confPassword, $name, $email, $language, $testRunId, $blockId, $birthdayForm)) {
					redirectTo("user_login", array("resume_messages" => "true"));
				}
					
			}
			
			$tr_data = $db->getAll('SELECT * FROM '.DB_PREFIX.'test_run_block_content trb WHERE trb.test_run_id = ?', array($testRunId));
			foreach ($tr_data as $trb) {
				$deletePrivacy = FALSE;
				$result = TestRunBlock::decodeBlockData($trb["content"]);
				foreach ($result as $key => $data) {
					if (array_key_exists('item_id', $data) && in_array($data['item_id'], $itemIds)) {
						$deletePrivacy = TRUE;
						$result[$key]['answers'][0] = "XXXXXX";
					}
				}
				if ($deletePrivacy) {
					$result = TestRunBlock::encodeBlockDataZip($result);
					$db->query("UPDATE ".DB_PREFIX."test_run_block_content SET content = ? WHERE subtest_id = ? AND test_run_id = ?", array($result, $trb['subtest_id'], $testRunId));
				}
			}
		}
		
		
		
		//Only show certificate one time and then no more
		if (($userId == 0) AND (($becomeUser == "") OR ($becomeUser == NULL))) {
			$query = "UPDATE ".DB_PREFIX."tans SET form_filled = ? WHERE test_run = ?";
			$db->query($query, array($timeStamp,  $testRunId));
		}

		// Check whether pdf was created for this user respectively testrun id before
		$query = "SELECT start_time FROM ".DB_PREFIX."test_runs WHERE id = ? LIMIT 1";
		$startTimestamp = $db->getOne($query, array($testRunId));
		$query = "SELECT id, random, checksum, name, birthday FROM ".DB_PREFIX."certificates WHERE test_run_id = ?  AND stamp = ? LIMIT 1";
		$cert = $db->getRow($query, array($testRunId, $startTimestamp));
		
		
		//New store format is used if name and birthday exist in table certificate. Exist there only when an admin created the certificate manually. Normal ist not.
		if ($cert['name'] == NULL) {
			$query = "SELECT cert_template_name FROM {$block->table} WHERE id = ? ";
			$itemIds = $db->getRow($query, array($blockId));

			//get data from the user table
			if ($fromForm != 1 && !isset($_GET["manual"])) 
			{
				$query = "SELECT  full_name, u_bday, email FROM ".DB_PREFIX."users WHERE id = ?";
				$result = $db->query($query, array($userId));
				$result = $result->fetchRow();
				$name = $result['full_name'];
				$birthdayTimestamp = $result['u_bday'];
				$email = $result['email'];
			} 
			//get data manually from form
			else if ($fromForm != 1 && isset($_GET["manual"])) 
			{
				$birthdayTimestamp = strtotime($_POST["birthday"]);
				$name = $_POST["firstname"]." ".$_POST["lastname"];		
				
				//update user details with new information
				$userId = $db->getOne("SELECT user_id FROM ".DB_PREFIX."test_runs WHERE id = ? LIMIT 1", array($testRunId));
				if($userId) {
					$query = "UPDATE ".DB_PREFIX."users SET full_name = ?, u_bday = ?, form_filled = ? WHERE id = ?";
					$db->query($query, array($name, $birthdayTimestamp, $timeStamp, $userId));
				}
			}
		}
		else {
			$name = $cert['name'];
			$birthday = $cert['birthday'];
			$birthday = date('d.m.Y', $birthday);
		}
		
		$random = 0;
		if(!PEAR::isError($cert)) {
			if(isset($cert['id'])) {
				// PDF was created before
				$checkSum = $cert['checksum'];
				if(strlen($checkSum) > 20) {
					$oldCheckSum = $this->alreadyExistingHash20($testRunId);
		
                	if (!$oldCheckSum) 
						$checkSum = $this->generateHash($name, $birthdayTimestamp, $startTimestamp, $testRunId, $cert['id']);
					else {
						$checkSum = $oldCheckSum;
						$query = "UPDATE ".DB_PREFIX."certificates SET checksum = ? WHERE id = ?";
						$db->query($query, array($checkSum, $cert['id']));
					}
				}
			}
			else {
				// PDF will be created for the first time
				// Check if the user is registerd and already has a certificate 
				
				$oldCheckSum = $this->alreadyExistingHash20($testRunId);						
				if (!$oldCheckSum)
					$checkSum = $this->generateHash($name, $birthdayTimestamp, $startTimestamp, $testRunId);
				else
					$checkSum = $oldCheckSum; 
			
				$id = $db->nextID(DB_PREFIX."certificates");
				$query = "INSERT INTO ".DB_PREFIX."certificates SET id = ? , random = ? , stamp = ? , test_run_id = ? , checksum = ? ";
				$db->query($query, array($id, $random, $startTimestamp, $testRunId, $checkSum));
			}
			
		}
		else {
		}
		
		$pdfCreator = new PdfCreator("Feedback");
		$date = date('d.m.Y', $startTimestamp);
		$birthday = date('d.m.Y', $birthdayTimestamp);
	
		//Update a registered user
		if (($fromForm == 1) && (strlen($name) >= 1) && ($becomeUser == NULL)) {
			$userList = new UserList();
			$user = $userList->getUserById($userId);
			if($user) {
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$errors[] = T('pages.testmake.invalid_feedback_email_adress');
					redirectTo("pdf_generator", array("action" => "formular_certificate", "errorsReg" => $errors, "block_id" => $blockId, "test_run_id" => $testRunId,
								"username" => $username, "name" => $name, "birthday" => $birthdayForm, "email" => $email, "emailCheck" => $emailCheck, "becomeUserCheck" => $becomeUser, "tr_id" => $tr_return_id));
				}
				
				if($user->getEmail() != $email)
					$user->setEmail($email);
				$query = "UPDATE ".DB_PREFIX."users SET full_name = ?, u_bday = ?, form_filled = ? WHERE id = ?";
				$db->query($query, array($name, $birthdayTimestamp, $timeStamp, $userId));
			}
		}

	
		
		
		$query = "SELECT cert_disable_barcode FROM ".DB_PREFIX."feedback_blocks WHERE id = ?";
		$cert_disable_barcode = $db->getOne($query, array($blockId));
		
		
		//redirect to test
		if ($fromForm == 1 && isset($tr_return_id)) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.check_certificate.created", MSG_RESULT_POS);
			if($userId == 0) {
				$_SESSION["cert_for_$tr_return_id"]["name"] = $name;
				$_SESSION["cert_for_$tr_return_id"]["birthday"] = $birthday;
				$_SESSION["cert_for_$tr_return_id"]["date"] = $date;
				$_SESSION["cert_for_$tr_return_id"]["cert_templ"] = $itemIds['cert_template_name'];
				$_SESSION["cert_for_$tr_return_id"]["checksum"] = $checkSum;
				$_SESSION["cert_for_$tr_return_id"]["dis_bc"] = $cert_disable_barcode;
				$_SESSION["cert_for_$tr_return_id"]["email"] = $email;
			}
			
			redirectTo("test_make", array("id" => $tr_return_id, "resume_messages" => "true"));
				
			//redirectTo("test_listing", array("resume_messages" => "true", "action" => "show_feedback", "test_run" => $testRunId));
		}
		elseif ($fromForm == 1) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.check_certificate.created", MSG_RESULT_POS);
			if($userId == 0) {
				$_SESSION["cert_for_$testRunId"]["name"] = $name;
				$_SESSION["cert_for_$testRunId"]["birthday"] = $birthday;
				$_SESSION["cert_for_$testRunId"]["date"] = $date;
				$_SESSION["cert_for_$testRunId"]["cert_templ"] = $itemIds['cert_template_name'];
				$_SESSION["cert_for_$testRunId"]["checksum"] = $checkSum;
				$_SESSION["cert_for_$testRunId"]["dis_bc"] = $cert_disable_barcode;
				$_SESSION["cert_for_$testRunId"]["email"] = $email;
			}
			redirectTo("test_listing", array("action" => "show_feedback", "test_run" => $testRunId));
		}
		
		//if certificate is sent by mail
		//if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
		//	$pdf = $pdfCreator->createMwCertificate($name, $birthday, $date, $itemIds['cert_template_name'], $checkSum, $cert_disable_barcode, $email);
		//else
			$pdf = $pdfCreator->createMwCertificate($name, $birthday, $date, $itemIds['cert_template_name'], $checkSum, $cert_disable_barcode, "");
		
		if(!$pdf){
			
			//If error occured: certificate is re-enabled again for TAN-users
			if (($userId == 0) AND (($becomeUser == "") OR ($becomeUser == NULL))) {
				$query = "UPDATE ".DB_PREFIX."tans SET form_filled = ? WHERE test_run = ?";
				$db->query($query, array(NULL,  $testRunId));
			}
					
			//Display Error-Message and send Email to admin: No certificate Template	
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.core.system_error", MSG_RESULT_NEG);
			$GLOBALS["MSG_HANDLER"]->addMsg("generic.certificate.error", MSG_RESULT_NEG);
					
			$subject = "testMaker Error: Missing template for certificate";
			$text = "Could not create certificate due to missing template. \n\n Template Name:".$itemIds['cert_template_name']." \n Feedbackblock Id: ".$blockId."\n\n Affected user: \n User-Id: ".$userId." \n Test-Run-Id: ".$testRunId." \n Name: ".$name." \n Birthday: ".$birthday." \n E-mail: ".$email."\n";
			require_once('lib/email/Composer.php');
			$mail = new EmailComposer();
			$mail->setTextMessage($text);
			$mail->setSubject($subject);
			$mail->setFrom(SYSTEM_MAIL);
			$mail->addRecipient(SYSTEM_MAIL);	
			$mail->sendMail();
		
			redirectTo("test_listing", array("resume_messages" => "true"));
		}
			
		
		redirectTo("test_listing", array("resume_messages" => "true"));
	}
	
	
	/*
	 register a tan user
	*/
	
	function registerUser($username, $password, $confPassword, $name, $email, $language, $testRunId, $blockId, $birthday)
	{
		$db = $GLOBALS['dao']->getConnection();
		$userList = new UserList();
		$key = md5(uniqid(mt_rand()));
		$timeStamp = time();
		$becomeUser = post('becomeUserCheck', NULL);
		$emailCheck = post('emailCheck', NULL);
		$birthdayForm2 = explode(".",$birthday);
		@$birthdayTimestamp = mktime("0","0","0",$birthdayForm2[1], $birthdayForm2[0], $birthdayForm2[2]);
		
		$errors = $userList->registerUser($username, $password, $confPassword, $name, htmlentities($email), $language, array(), $key, 0, $birthdayTimestamp, $timeStamp);
		//kill the tan and replace the tan user in test_runs with the new user
		if (!$errors) {
			$user = DataObject::getOneBy('User', 'username', $username);
				
			$user_id = $user->getId();
			
			$current_pp_version = $db->getOne("SELECT content FROM ".DB_PREFIX."settings WHERE name = 'curr_privacy_policy'");
			$user->setPrivacyPolicyAcc($current_pp_version,$user_id);
			
			$bodies = array(
					"html" => "UserRegisterMail.html",
					"text" => "UserRegisterMail.txt",
				);
			foreach ($bodies as $type => $templateFile) {
				$this->tpl->loadTemplateFile($templateFile);
				$this->tpl->setVariable("email_address", $email);
				$this->tpl->setVariable("name", $name);
				$this->tpl->setVariable("user_name", $username);
				$this->tpl->setVariable("link", linkToFile("register.php", array("key" => $key), FALSE, TRUE));
				$bodies[$type] = $this->tpl->get();
			}

			libLoad('email::Composer');
			$mail = new EmailComposer();
			$mail->setSubject(T('pages.user.register.subject'));
			$mail->setFrom(SYSTEM_MAIL, "testMaker");
			$mail->addRecipient($email, $name);
			//print_r($bodies['html']);
			$mail->setHtmlMessage($bodies["html"]);
			$mail->setTextMessage($bodies["text"]);

			if (@$mail->sendMail()) {
				$query = "UPDATE ".DB_PREFIX."test_runs SET user_id = ? WHERE id = ? AND user_id = ?";
				$db->query($query, array($user_id, $testRunId, 0));
				
				$query = "DELETE FROM ".DB_PREFIX."tans WHERE test_run = ?";
				$db->query($query, $testRunId);
				
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.user.register.seeMail', MSG_RESULT_POS);
				
			} else {
				$user = DataObject::getOneBy('User', 'username', $username);
				list($msgId, $msgType) = $userList->deleteUser($user->getId(), -1, FALSE);
				$GLOBALS["MSG_HANDLER"]->addMsg("types.user_list.create_user_failed", MSG_RESULT_NEG);
				$errors[] = T('pages.user.register.mailerror');
				redirectTo("pdf_generator", array("action" => "formular_certificate", "errorsReg" => $errors, "block_id" => $blockId, "test_run_id" => $testRunId,
							"email" => $email, "username" => $username, "register_try" => TRUE));
			}
		}
		else {
			redirectTo("pdf_generator", array("action" => "formular_certificate", "errorsReg" => $errors, "block_id" => $blockId, "test_run_id" => $testRunId,
											  "username" => $username, "name" => $name, "birthday" => $birthday, "email" => $email, "emailCheck" => $emailCheck, "becomeUserCheck" => $becomeUser, "register_try" => TRUE));
		}
		return true;
		
	}
	
	/*
		Show formular for input the personal data to create the certificate
	*/
	
	
	function doFormularCertificate()
	{
		libLoad('pdf::PdfCreator');
		$pdfCreator = new PdfCreator("Feedback");
		$tr_return_id = get('tr_id', NULL);
		$testRunId = get('test_run_id', NULL);
		
		$blockId = get('block_id', 0);
		$email = get('email', "");
		$username =  get('username', NULL);
		$errors = get('errorsReg', array());
		$registerTry = get("register_try", FALSE);
		$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		$db = $GLOBALS['dao']->getConnection();
		$userIdCheck = $GLOBALS['PORTAL']->getUserId();
		$birthday = get('birthday', "");
		$emailCheck = get('emailCheck', NULL);
		$becomeUser = get('becomeUserCheck', NULL);


		$query = "SELECT user_id FROM ".DB_PREFIX."test_runs WHERE id = ?";
		$userId = $db->getOne($query, array($testRunId));

		//check user id to avoid link hacks
		if (($userId != $userIdCheck) AND ($userId != 0)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.check_certificate.user.check', MSG_RESULT_NEG);
			redirectTo("test_listing", array());
		}
		
		//check if tan-user who has already filled form
		if($userId == 0 && (isset($tr_return_id) || isset($testRunId))){	
			$query = "SELECT form_filled FROM ".DB_PREFIX."tans WHERE test_run = ?";
			$result = $db->query($query, array($testRunId));
			$result = $result->fetchRow();
			// OR for public tests
			if ($result['form_filled'] != NULL || isset($_SESSION["cert_for_$tr_return_id"])) {
					if(isset($_SESSION["cert_for_$tr_return_id"])) {
						$email = $_SESSION["cert_for_$tr_return_id"]["email"];
						if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
							$pdf = $pdfCreator->createMwCertificate($_SESSION["cert_for_$tr_return_id"]["name"],
																	$_SESSION["cert_for_$tr_return_id"]["birthday"],
																	$_SESSION["cert_for_$tr_return_id"]["date"],
																	$_SESSION["cert_for_$tr_return_id"]["cert_templ"],
																	$_SESSION["cert_for_$tr_return_id"]["checksum"],
																	$_SESSION["cert_for_$tr_return_id"]["dis_bc"],
																	$email);
						else
							$pdf = $pdfCreator->createMwCertificate($_SESSION["cert_for_$tr_return_id"]["name"],
																	$_SESSION["cert_for_$tr_return_id"]["birthday"],
																	$_SESSION["cert_for_$tr_return_id"]["date"],
																	$_SESSION["cert_for_$tr_return_id"]["cert_templ"],
																	$_SESSION["cert_for_$tr_return_id"]["checksum"],
																	$_SESSION["cert_for_$tr_return_id"]["dis_bc"],
																	$email, "");
		
					}
					
			}
			if ($result['form_filled'] != NULL && isset($_SESSION["cert_for_$testRunId"])) {
					if(isset($_SESSION["cert_for_$testRunId"])) {
						$email = $_SESSION["cert_for_$testRunId"]["email"];
						if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
							$pdf = $pdfCreator->createMwCertificate($_SESSION["cert_for_$testRunId"]["name"],
																	$_SESSION["cert_for_$testRunId"]["birthday"],
																	$_SESSION["cert_for_$testRunId"]["date"],
																	$_SESSION["cert_for_$testRunId"]["cert_templ"],
																	$_SESSION["cert_for_$testRunId"]["checksum"],
																	$_SESSION["cert_for_$testRunId"]["dis_bc"],
																	$email);
						else
							$pdf = $pdfCreator->createMwCertificate($_SESSION["cert_for_$testRunId"]["name"],
																	$_SESSION["cert_for_$testRunId"]["birthday"],
																	$_SESSION["cert_for_$testRunId"]["date"],
																	$_SESSION["cert_for_$testRunId"]["cert_templ"],
																	$_SESSION["cert_for_$testRunId"]["checksum"],
																	$_SESSION["cert_for_$testRunId"]["dis_bc"],
																	$email, "");
		
					}
					
					redirectTo("test_listing", array());
			}
			
		} 
		
		//if user had already filled this formular, move user direct to the certificate
		
	
		
		//try to get data from a testrun
		$userData = $block->acquireCertData($testRunId, $blockId);
		
		if ($userData['firstname'] != NULL) {
			$name = $userData['firstname'].' '.$userData['lastname'];
			$birthday = $userData['birthday'];
		}
		
		if(isset($_GET["name"])) $name =  get('name', NULL);
		if(isset($_GET["birthday"])) $birthday =  get('birthday', NULL);
		
		if ($userId == 0)
			$this->tpl->loadTemplateFile("FormularCertificate.html");
		else 
			$this->tpl->loadTemplateFile("FormularCertificateUser.html");
		
		$this->tpl->touchBlock("Formular");
		$this->tpl->setVariable('tr_return_id', $tr_return_id);
		
		if ($userId == 0) {
			$query = "SELECT mail FROM ".DB_PREFIX."tans WHERE test_run = ?";
			$email2 = $db->getOne($query, array($testRunId));
			$this->tpl->touchBlock("becomeUser");
			$this->tpl->setVariable('birthday', $birthday);
			if(isset($name)) $this->tpl->setVariable('name', $name);
			
			if ($email2)
				$this->tpl->setVariable('email', $email2);
			else 
				$this->tpl->setVariable('email', $email);
						
			!Setting::get('curr_privacy_policy') ? $no_privacy=1 : $no_privacy=0;
			$this->tpl->setVariable("ppversion", PrivacyPolicy::getCurrentVersion());
			if ($no_privacy) {
				$this->tpl->hideBlock("pp_check");
				$this->tpl->hideBlock("pp_check2");
				}
			else {
				$this->tpl->touchBlock("pp_check");
				$this->tpl->touchBlock("pp_check2");
				}
			$this->tpl->setVariable('display', "none");
		}
		else {	
			$this->tpl->touchBlock("isUser");
			$query = "SELECT  full_name, email, form_filled FROM ".DB_PREFIX."users WHERE id = ?";
			$result = $db->query($query, array($userId));
			$result = $result->fetchRow();

			//if user had already filled this formular, move user direct to the certificate
			if ($result['form_filled'] != NULL)
				redirectTo("pdf_generator", array("action" => "create_mw_certificate", "test_run_id" => $testRunId, "block_id" => $blockId));

			
			if (isset($result['email']));
				$this->tpl->setVariable('email', $result['email']);
			if (isset($result['full_name']))
				$this->tpl->setVariable('name', $result['full_name']);
			$this->tpl->setVariable('birthday', $birthday);
		}
		
		//$this->tpl->setVariable('barcode', $userData["cert_disable_barcode"]);
		// display2 = none reg user decision screen
		$this->tpl->setVariable('blockId', $blockId);
		$this->tpl->setVariable('test_run_id', $testRunId);
		if($becomeUser) {$this->tpl->setVariable('become_checked', "1");
						 $this->tpl->setVariable('display', "block");
						 $this->tpl->setVariable('display2', "style='display: none'");
						 $this->tpl->setVariable('display4', "style='display: none'");}
		if(!empty($errors)) {$this->tpl->setVariable('display2', "style='display: none'");
							 $this->tpl->setVariable('display3', "style='display: block'");
							 }
							 
		//if($emailCheck)  $this->tpl->setVariable('email_checked', "checked");

		//erros by registration a tan user
		if (!empty($errors)) {
/*			if ($registerTry == TRUE) {
				$this->tpl->setVariable('display', "block");
				//$this->tpl->setVariable('checked', "checked");
			}
*/			
			if(array_key_exists('username', $errors)) 
				$this->tpl->touchBlock('correction_username');
			if(array_key_exists('password', $errors)) {
				$this->tpl->touchBlock('correction_password');
				$this->tpl->touchBlock('correction_password_control');
			}
			if(array_key_exists('password_control', $errors)) 
				$this->tpl->touchBlock('correction_password_control');
			if(array_key_exists('fullname', $errors)) 
				$this->tpl->touchBlock('correction_fullname');
			if(array_key_exists('email', $errors)) 
				$this->tpl->touchBlock('correction_email');
			if(array_key_exists('birthday', $errors)) 
				$this->tpl->touchBlock('correction_birthday');
			
			if ($username != NULL)
				$this->tpl->setVariable('username', $username);
				
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages(array_values($errors), MSG_RESULT_NEG);
		}
		
		$body = $this->tpl->get();
		$this->tpl->loadTemplateFile("TestFrame.html");
		$this->tpl->hideBlock('autotester');
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.feedback_page.certificate_page'));
		$this->tpl->show();
	}
	
	
	function doGenerateEntry()
	{
		//manually create certificate entry
		$this->checkAllowed('admin', true);
		$workingPath = get('working_path');
		$blockId = get('block_id', 0);
		$confirm = get('confirm', 1);
		libLoad('pdf::PdfCreator');
		$testRunId = $_POST["testRunId"];
		$firstname = $_POST["firstname"];
		$lastname = $_POST["lastname"];
		$birthday = $_POST["birthday"];
		$db = $GLOBALS['dao']->getConnection();
		
		if ($confirm == 1 && post('cancel'))
			redirectTo("feedback_block", array("action" => "edit_certificate", "working_path" => $workingPath));
		
		$name = $firstname.' '.$lastname;
		
		$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		$query = "SELECT cert_template_name FROM {$block->table} WHERE id = ? ";
		$itemIds = $db->getRow($query, array($blockId));
		
		$query = "SELECT * FROM ".DB_PREFIX."certificates WHERE id = ? ";
		$result = $db->getRow($query, array($testRunId));
		if(!PEAR::isError($result))
		{
			if(!isset($result['id'])) {
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.check_certificate.testrun.not_exist", MSG_RESULT_NEG);
				redirectTo('feedback_block', array('action' => 'edit_certificate', 'working_path' => $workingPath));
			}
		}

		// Check whether pdf was created for this user respectively testrun id before
		$query = "SELECT start_time FROM ".DB_PREFIX."test_runs WHERE id = ? LIMIT 1";
		$startTimestamp = $db->getOne($query, array($testRunId));
		$query = "SELECT id, random, checksum FROM ".DB_PREFIX."certificates WHERE test_run_id = ?  AND stamp = ? LIMIT 1";
		$cert = $db->getRow($query, array($testRunId, $startTimestamp));
		$random = 0;
		if(!PEAR::isError($cert))
		{
			if(isset($cert['id']) && $confirm == 0) {
				//$checkSum = $cert['checksum'];

				$this->tpl->loadTemplateFile("confirmCertificate.html");			
				$this->tpl->touchBlock("confirm");
				$this->tpl->setVariable("working_path", $workingPath);
				$this->tpl->setVariable("block_id", $blockId);
				$this->tpl->setVariable("firstname", $firstname);
				$this->tpl->setVariable("lastname", $lastname);
				$this->tpl->setVariable("testRunId", $testRunId);
				$this->tpl->setVariable("birthday", $birthday);
				$body = $this->tpl->get();
				$this->loadDocumentFrame();
				$this->tpl->setVariable('body', $body);
				$this->tpl->setVariable('page_title', T('pages.block.feedback.certificate'));
				$this->tpl->show();
			}
			else {
				$name = $firstname.' '.$lastname;
				$birthdayExp = explode(".",$birthday);

				$format = $birthdayExp[0]."/".$birthdayExp[1]."/".$birthdayExp[2];
				$birthdayStamp = strtotime($format);

				$db = $GLOBALS['dao']->getConnection();
				srand(time());
				$found = true;
				while ($found == true) {
					$random = rand(10000000, 99999999);
					//$checkSum = md5($random.$name.$birthday.$startTimestamp.$testRunId);
					$checkSum = $this->myHash($random, $name, $birthdayStamp, $startTimestamp, $testRunId);
					$query = "SELECT COUNT(*) FROM ".DB_PREFIX."certificates WHERE checksum = ?";
					$result = $db->getOne($query, array($checkSum));
					if ($result == 0)
						$found = false;
				}
				$id = $db->nextID(DB_PREFIX."certificates");
				$query = "DELETE FROM ".DB_PREFIX."certificates WHERE test_run_id = ?";
				$db->query($query, array($testRunId));
				$query = "INSERT INTO ".DB_PREFIX."certificates SET id = ? , random = ? , stamp = ? , test_run_id = ? , checksum = ?, name = ?, birthday = ?";
				$db->query($query, array($id, $random, $startTimestamp, $testRunId, $checkSum, $name, $birthdayStamp));
				
				$pdfCreator = new PdfCreator();
				$date = date('d.m.Y', $startTimestamp);
				$pdfCreator->createMwCertificate($name, $birthday, $date, $itemIds['cert_template_name'], $checkSum);
			}
		}
	}
	
	
	function generateHash($name, $birthdayTimestamp, $startTimestamp, $testRunId, $id = NULL)
	{
		$db = $GLOBALS['dao']->getConnection();
		srand(time());
		$found = true;
		while ($found == true) {
			$random = rand(10000000, 99999999);
			//$checkSum = md5($random.$name.$birthday.$startTimestamp.$testRunId);
						
			$checkSum = $this->myHash($random, $name, $birthdayTimestamp, $startTimestamp, $testRunId);
			$query = "SELECT COUNT(*) FROM ".DB_PREFIX."certificates WHERE checksum = ?";
			$result = $db->getOne($query, array($checkSum));
			if ($result == 0)
				$found = false;
		}
		if ($id != NULL) {
			$query = "UPDATE ".DB_PREFIX."certificates SET random = ? , stamp = ? , test_run_id = ? , checksum = ? WHERE id = ?";
			$db->query($query, array($random, $startTimestamp, $testRunId, $checkSum, $id));
		}
		return $checkSum;		
	}
	
	static function myHash($random, $name, $birthdayStamp, $startTimestamp, $testRunId)
	{	
		$char[0] = 'A';
		$char[1] = 'B';
		$char[2] = 'C';
		$char[3] = 'D';
		$char[4] = 'E';
		$char[5] = 'F';
		$char[6] = 'G';
		$char[7] = 'H';
		$char[8] = 'I';
		$char[9] = 'J';
		$char[10] = 'K';
		$char[11] = 'L';
		$char[12] = 'M';
		$char[13] = 'N';
		$char[14] = 'O';
		$char[15] = 'P';
		$char[16] = 'Q';
		$char[17] = 'R';
		$char[18] = 'S';
		$char[19] = 'T';
		$char[20] = 'U';
		$char[21] = 'V';
		$char[22] = 'W';
		$char[23] = 'X';
		$char[24] = 'Y';
		$char[25] = 'Z';
		$char[26] = '0';
		$char[27] = '1';
		$char[28] = '2';
		$char[29] = '3';
		$char[30] = '4';
		$char[31] = '5';
		$char[32] = '6';
		$char[33] = '7';
		$char[34] = '8';
		$char[35] = '9';
		
		$code = '';
		$praefix = '';
		$infix = '';
		$postfix = '';
		
		$birthdayStamp = abs($birthdayStamp);
		
		//Make sure that $name has min 8 characters
		$name = $name.$name.$name.$name;

	    $code1 = $name ^ strval($random);
		
		$length = strlen($code1);
		for ($i = 0; $i < $length; $i++) {
			$letter = $code1{$i};
			$number = (ord($letter) *  ($random % 1000)) % 36;
			$praefix = $praefix.$char[$number];
		}
		
		$code2 = intval(substr($startTimestamp , -7)) + intval(substr($birthdayStamp, -7)) + $random + intval(substr($birthdayStamp , 0, 7)) +
				 intval(substr($startTimestamp , 0, 7));
		$code2 = strval($code2) ^ strval($code1);
		
		$length = strlen($code2);
		for ($i = 0; $i < $length; $i++) {
			$letter = $code2{$i};
			$number = ord($letter)  % 36;
			$infix = $infix.$char[$number];
		}
		
		//Modulo prime numbers, because make sure that a least one of them are not zero
		
		$code3 =  $random * (intval($testRunId) % 9883) + $random * (intval($testRunId) % 9157);
	    $code3 = (strval($code3)) ^ (strval($code2));
		
		$length = strlen($code3);
		for ($i = 0; $i < $length; $i++) {
			$letter = $code3{$i};
			$number = ord($letter)  % 36;
			$postfix = $postfix.$char[$number];
		}
	
		$code = substr($praefix, 0, 7).substr($infix, 0, 7).substr($postfix, 0, 6);

		return $code;
	}
	
	
	/**
	 * Find already existing 20-char-Hash-Key
	 * return: 0 if no 20-char-hash is found for this user
	 * 		   else return an existing one
	 */

	function alreadyExistingHash20($testRunId) 
	{
		$db = $GLOBALS['dao']->getConnection();
		
		$query = "SELECT user_id FROM ".DB_PREFIX."test_runs WHERE id = ? LIMIT 1";
		$testrun_user_id = $db->getOne($query, array($testRunId));
		$tr_count = 0;
		if($testrun_user_id != 0) { 
			$query = "SELECT COUNT( * ) 
				FROM  `".DB_PREFIX."certificates` ,  `".DB_PREFIX."test_runs` 
				WHERE ".DB_PREFIX."test_runs.id = ".DB_PREFIX."certificates.test_run_id
				AND ".DB_PREFIX."test_runs.user_id = ?";
			$tr_count = $db->getOne($query, array($testrun_user_id));
		}			
		if ($testrun_user_id != 0 && $tr_count > 0) {
				$query = "SELECT `checksum` 
					FROM  `".DB_PREFIX."certificates` ,   `".DB_PREFIX."test_runs`
					WHERE ".DB_PREFIX."test_runs.id = ".DB_PREFIX."certificates.test_run_id
					AND ".DB_PREFIX."test_runs.user_id = ? AND CHAR_LENGTH(checksum) = 20";
				$checkSum = $db->getOne($query, array($testrun_user_id));
					
				if (!$checkSum) 
					return NULL;
				else 
					return $checkSum;
		}

	}
	
	
}

?>
