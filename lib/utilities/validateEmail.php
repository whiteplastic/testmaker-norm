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
 * @package Library
 */

/**
 * Validates an email address
 * @param string $email the email adress to verify
 * @return boolean wether or not the email adresse is valid
 */
function validateEmail($email, &$errors)
{
	$email = trim($email);
	if ($email == "") {
		$errors[] = T("utilities.validate_email.error", array("email" => $email));
		return FALSE;
	}

	libLoad("PEAR");
	require_once("Mail/RFC822.php");
	$result = Mail_RFC822::parseAddressList($email);
	if (PEAR::isError($result)) {
		$errors[] = T("utilities.validate_email.error", array("email" => $email));
		return FALSE;
	}
	return TRUE;
}

?>
