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
 * Creates and sends mails as text and/or html, etc.
 *
 * This class allows for simple text mails, HTML mails, HTML mails with a text alternative,
 * attachments and also embedded HTML objects.
 *
 * An example using nearly all features:
 * <code>
 * libLoad("email::Composer");
 * $mail = new EmailComposer();
 * $mail->setSubject("My daughter!!!");
 * $mail->setFrom("john.doe@example.com", "John Doe");
 * $mail->addRecipient("alice.duh@example.org", "Alice Duh");
 * $mail->setTextMessage("Look at my daughter. Ain't she just *cute*?\n");
 * $contentId = $mail->addHtmlAttachment("c:\\pics\\signature.gif", "image/jpeg");
 * $mail->setHtmlMessage("<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n<html>\n<head>\n  <meta content=\"text/html;charset=ISO-8859-1\" http-equiv=\"Content-Type\">\n  <title></title>\n</head>\n<body bgcolor=\"#336699\" text=\"#FFFFFF\">\n<font size=\"+3\">Look at my daughter. Ain't she just <b>cute</b>?</font><br><br><center><img alt=\"Alice\" src=\"cid:$contentId\" style=\"border: 3px solid black\"></center>\n</body>\n</html>");
 * $mail->addAttachment("c:\\pics\\jane\\baby2.jpg", "image/jpeg");
 * $success = $mail->sendMail();
 * if ($success) {
 * 	echo "<p>The mail was successfully sent.</p>";
 * } else {
 * 	echo "<p>The mail could not be sent.</p>";
 * }
 * </code>
 *
 * @package Library
 */

libLoad("utilities::validateEmail");

class EmailComposer
{
	# META

	/**#@+
	 * @access private
	 */
	var $subject = "(no subject)";
	var $to = array();
	var $from;
	var $validTypes = array("To", "CC", "BCC");
	/**#@-*/

	/**
	 * Sets the subject
	 * @param string The subject to set
	 */
	function setSubject($subject) {
		if ($subject != "") {
			$this->subject = mb_encode_mimeheader($subject, "ISO-8859-1");// $this->_encodeQuotedPrintable($subject); //b/
		}
	}

	function _inflateAddress(&$address, &$name)
	{
		if (is_array($address)) {
			if (! isset($name) && count($address) >= 2) {
				$name = $address[1];
			}
			$address = $address[0];
		}
	}

	/**
	 * Sets the sender
	 * @param string E-Mail address of the sender
	 * @param string Name of the sender
	 */
	function setFrom($address, $name = NULL) {
		$this->_inflateAddress($address, $name);
		if (! $this->_validateEmailAddress($address)) {
			trigger_error("Invalid email address \"$address\" was rejected as sender.", E_USER_WARNING);
			return FALSE;
		}
		$this->from = array("address" => $address, "name" => $name, "type" => "From");
		return TRUE;
	}

	/**
	 * Adds a recipient
	 * @param string E-Mail address of the sender
	 * @param string Name of the sender
	 * @param string Address type (To, CC or BCC)
	 */
	function addRecipient($address, $name = NULL, $type = "to")
	{
		$this->_inflateAddress($address, $name);
		if (! $this->_validateEmailAddress($address)) {
			trigger_error("Invalid email address \"$address\" was rejected as recipient.", E_USER_WARNING);
			return FALSE;
		}
		$found = FALSE;
		foreach ($this->validTypes as $validType) {
			if (strtolower($validType) == strtolower($type)) {
				$found = TRUE;
				$type = $validType;
				break;
			}
		}
		if (! $found) {
			trigger_error("Recipient $address was rejected, since the address type \"$type\" is invalid", E_USER_WARNING);
			return FALSE;
		}
		$this->to[] = array("address" => $address, "name" => $name, "type" => $type);
		return TRUE;
	}

	# META - HELPER FUNCTIONS

	/**
	 * @access private
	 */
	function _validateEmailAddress($address)
	{
		$errors = array();
		return validateEmail($address, $errors);
	}

	# CONTENT

	/**#@+
	 * @access private
	 */
	var $textBody = "";
	var $htmlBody = "";
	var $htmlAttachments = array();
	var $attachments = array();
	/**#@-*/

	/**
	 * Sets the text message
	 *
	 * Either this or the HTML message must be set
	 *
	 * @param string The text message to set
	 */
	function setTextMessage($text) {
		$this->textBody = $text;
	}

	/**
	 * Sets the HTML message
	 *
	 * Either this or the text message must be set
	 *
	 * @param string The HTML message to set
	 */
	function setHtmlMessage($text) {
		$this->htmlBody = $text;
	}

	/**
	 * Adds a file as an inline attachment to use in the HTML part
	 * @param string Path to the file to add
	 * @param string The Mime Type of the file
	 * @param string The new name of the file
	 * @return string The Content ID of this attachment (for use in the HTML part)
	 */
	function addHtmlAttachment($file, $mimeType, $fileName = NULL) {
		$contentId = $this->_getContentId();
		if (! isset($fileName)) {
			$fileName = basename($file);
		}
		$this->htmlAttachments[] = array("file" => $file, "mimeType" => $mimeType, "contentId" => $contentId, "fileName" => $fileName);
		return $contentId;
	}

	/**
	 * Adds any data as an inline attachment to use in the HTML part
	 * @param string The contents of the virtual file to add
	 * @param string The Mime Type of the data
	 * @param string The name of the virtual file
	 * @return string The Content ID of this attachment (for use in the HTML part)
	 */
	function addHtmlAttachmentFromMemory($data, $mimeType, $fileName)
	{
		$contentId = $this->_getContentId();		
		$this->htmlAttachments[] = array("data" => $data, "mimeType" => $mimeType, "contentId" => $contentId, "fileName" => $fileName);
		return $contentId;
	}

	/**
	 * Adds a file as an attachment
	 * @param string Path to the file to add
	 * @param string The Mime Type of the file
	 * @param string The new name of the file
	 */
	function addAttachment($file, $mimeType, $fileName = NULL) {
		if (! isset($fileName)) {
			$fileName = basename($file);
		}
		$this->attachments[] = array("file" => $file, "mimeType" => $mimeType, "fileName" => $fileName);
	}

	/**
	 * Adds any data as an attachment
	 * @param string The contents of the virtual file to add
	 * @param string The Mime Type of the data
	 * @param string The name of the virtual file
	 */
	function addAttachmentFromMemory($data, $mimeType, $fileName) {

		$this->attachments[] = array("data" => $data, "mimeType" => $mimeType, "fileName" => $fileName);
	}

	# CONTENT - HELPER FUNCTIONS

	/**
	 * @access private
	 */
	function _getContentId()
	{
		static $part = 0;
		$part++;

		if (! isset($GLOBALS["ContentId"])) {
			$GLOBALS["ContentId"] = 0;
		}
		$GLOBALS["ContentId"]++;

		libLoad("utilities::randomString");
		$token = md5(uniqid(randomString(32), true));

		if (isset($_SERVER["HTTP_HOST"])) {
			$computerName = $_SERVER["HTTP_HOST"];
		}
		elseif (isset($_ENV["COMPUTERNAME"])) {
			$computerName =  $_ENV["COMPUTERNAME"];
		}
		else {
			$computerName = "random[".randomString(20)."]";
		}

		$contentId = $part.".".$GLOBALS["ContentId"].".".$token.".".time()."@".$computerName;

		return $contentId;
	}

	# COMPOSITION

	/**#@+
	 * @access private
	 */
	var $mimeMessage = "This is a multi-part message in MIME format.";
	/**#@-*/

	/**
	 * @access private
	 */
	function _compose()
	{
		$header = "";

		$header .= "Date: ".date("r").PHP_EOL;
		if (isset($this->from)) {
			$header .= $this->_formatAddressHeader(array($this->from));
		}
		$header .= "Return-Path: <".$this->from["address"].">".PHP_EOL;
		$header .= "User-Agent: EmailComposer/0.1".PHP_EOL;
		$header .= "MIME-Version: 1.0".PHP_EOL;
		//$header .= $this->_formatAddressHeader($this->to);

		$part = $this->_composeAttachments();
		$header .= $part[0];
		$body = $part[1];

		return array($header, $body);
	}

	/**
	 * @access private
	 */
	function _composeAttachments()
	{
		if (! $this->attachments) {
			return $this->_composeCore();
		}

		$boundary = $this->_newBoundary();
		$header = "Content-Type: multipart/mixed;".PHP_EOL." boundary=\"$boundary\"".PHP_EOL;
		$body = $this->_getMimeMessage();

		$body .= "--".$boundary.PHP_EOL.implode(PHP_EOL, $this->_composeCore()).PHP_EOL.PHP_EOL;
		foreach ($this->attachments as $data)
		{
			if ($attachment = $this->_composeAttachment($data)) {
				$body .= "--".$boundary.PHP_EOL.implode(PHP_EOL, $attachment).PHP_EOL;
			}
		}
		$body .= "--".$boundary."--".PHP_EOL;
		return array($header, $body);
	}

	/**
	 * @access private
	 */
	function _composeAttachment($attachment)
	{
		if (isset($attachment["file"])) {
			$data = $this->_getFile($attachment["file"]);
		}
		else {
			$data = $attachment["data"];
		}

		$base64 = chunk_split(base64_encode($data));
		if (! isset($base64)) {
			return NULL;
		}

		$fileName = $this->_encodeHeaderValue($attachment["fileName"]);

		$header = "";
		$header .= "Content-Type: ".$attachment["mimeType"].";".PHP_EOL." name=\"$fileName\"".PHP_EOL;
		$header .= "Content-Transfer-Encoding: base64".PHP_EOL;
		if (isset($attachment["contentId"])) {
			$header .= "Content-ID: <".$attachment["contentId"].">".PHP_EOL;
		}
		$header .= "Content-Disposition: attachment;".PHP_EOL." filename=\"$fileName\"".PHP_EOL;

		$body = $base64;

		return array($header, $body);
	}

	/**
	 * @access private
	 */
	function _composeCore()
	{
		if ($this->textBody != "" && $this->htmlBody != "") {
			$body = $this->_getMimeMessage();
		}
		$text = $this->_composeText();
		$html = $this->_composeHtml();

		if ($text && $html)
		{
			$boundary = $this->_newBoundary();
			$header = "";
			$header .= "Content-Type: multipart/alternative;".PHP_EOL." boundary=\"$boundary\"".PHP_EOL;
			$body .= "--".$boundary."".PHP_EOL;
			$body .= implode(PHP_EOL, $text)."".PHP_EOL;
			$body .= "--".$boundary."".PHP_EOL;
			$body .= implode(PHP_EOL, $html)."".PHP_EOL;
			$body .= "--".$boundary."--".PHP_EOL;
			return (array($header, $body));
		}
		elseif ($text) {
			return $text;
		}
		elseif ($html) {
			return $html;
		}
		return array("", "");
	}

	/**
	 * @access private
	 */
	function _composeText()
	{
		if ($this->textBody == "") {
			return FALSE;
		}
		$header = "";
		$header .= "Content-Type: text/plain; charset=\"ISO-8859-1\"".PHP_EOL;
		$header .= "Content-Transfer-Encoding: quoted-printable".PHP_EOL;

		$body = $this->_encodeQuotedPrintable($this->textBody); //b/

		return array($header, $body);
	}

	/**
	 * @access private
	 */
	function _composeHtml()
	{
		if (! $this->htmlAttachments) {
			return $this->_composeHtmlCore();
		}

		$boundary = $this->_newBoundary();
		$header = "";
		$header .= "Content-Type: multipart/related;".PHP_EOL." boundary=\"$boundary\"".PHP_EOL;

		$body = $this->_getMimeMessage();
		$body .= "--".$boundary.PHP_EOL.implode(PHP_EOL, $this->_composeHtmlCore()).PHP_EOL.PHP_EOL;

		foreach ($this->htmlAttachments as $attachment)
		{
			if ($attachment = $this->_composeAttachment($attachment)) {
				$body .= "--".$boundary.PHP_EOL.implode(PHP_EOL, $attachment).PHP_EOL;
			}
		}
		$body .= "--".$boundary."--".PHP_EOL;
		return (array($header, $body));

	}

	/**
	 * @access private
	 */
	function _composeHtmlCore() {
		if ($this->htmlBody == "") {
			return FALSE;
		}
		$header = "";
		$header .= "Content-Type: text/html; charset=\"ISO-8859-1\"".PHP_EOL;
		$header .= "Content-Transfer-Encoding: quoted-printable".PHP_EOL;

		$body = $this->_encodeQuotedPrintable($this->htmlBody);	//b/

		return array($header, $body);
	}

	# COMPOSITION - HELPER FUNCTIONS

	/**
	 * @access private
	 */
	function _getMimeMessage()
	{
		static $used = FALSE;
		$message = "";
		if (! $used) {
			$message .= $this->mimeMessage;
			$used = TRUE;
		}
		$message .= PHP_EOL;
		return $message;
	}

	/**
	 * @access private
	 */
	function _newBoundary()
	{
		static $part = 0;
		$part++;
		libLoad("utilities::randomString");
		return "------------=_Boundary_".$part."_".randomString(16);
	}

	/**
	 * @access private
	 */
	function _getFile($file)
	{
		if (! $handle = fopen($file, "rb")) {
			return NULL;
		}
		$data = fread($handle, filesize($file));
		fclose($handle);
		return $data;
	}

	/**
	 * @access private
	 */
	function _formatAddressHeader($addresses)
	{
		$groups = array();
		foreach ($addresses as $address)
		{
			if (! isset($groups[$address["type"]])) {
				$groups[$address["type"]] = array();
			}
			$groups[$address["type"]][] = array("address" => $address["address"], "name" => $address["name"]);
		}

		$header = "";

		foreach ($groups as $type => $addresses)
		{
			$header .= $type.": ";
			foreach ($addresses as $i => $address) {
				if ($i) {
					$header .= ",".PHP_EOL." ";
				}
				if (isset($address["name"])) {
					$header .= $this->_encodeHeaderValue('"'.$address["name"].'"')." <".$address["address"].">";
				} else {
					$header .= $address["address"];
				}
			}
			$header .= PHP_EOL;
		}

		return $header;
	}

	/**
	 * @access private
	 */
	function _encodeHeaderValue($value)
	{
		$encodedValue = $this->_encodeQuotedPrintable($value);
		if ($value != $encodedValue) {
			$value = "=?ISO-8859-1?Q?".$encodedValue."?=";
		}
		//this encoding produces errors. plaintext works as well =p
		return $value;
	}

	/**
	 * @access private
	 */
	function _encodeQuotedPrintable($text)
	{
		$lines = explode(PHP_EOL, str_replace("\r", "", $text));
		$text = "";

		foreach ($lines as $i => $buffer)
		{
			$buffer = preg_replace("/[\\x00-\\x08\\x0A-\\x1F\\x21\\x23-\\x24\\x3D\\x40\\x5B-\\x5E\\x60\\x7B-\\xFF]/e", "'='.strtoupper(dechex(ord('\\0')))", $buffer);
			while (strlen($buffer) > 76) {
				$wrap = 75;
				if (preg_match("/^(.*)=[0-9A-F]{2}/", substr($buffer, 73, 4), $match)) {
					$wrap += strlen($match[1]) - 2;
				}
				$text .= substr($buffer, 0, $wrap)."=".PHP_EOL;
				$buffer = substr($buffer, $wrap);
			}

			preg_match("/^(.*?)([\\x09\\x20]*)$/", $buffer, $match);
			$text .= $match[1];
			$text .= preg_replace("/./e", "'='.strtoupper(dechex(ord('\\0')))", $match[2]);

			if ($i + 1 < count($lines)) {
				$text .= PHP_EOL;
			}
		}

		return $text;
	}

	/**
	 * Encodes data in Base 64
	 *
	 * This method is really slow, use chunk_preg_split(base64_encode($data)) instead.
	 *
	 * @access private
	 */
	function _encodeBase64($data)
	{
		static $alphabet = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9","+","/");

		$result = "";

		$offset = 0;
		$written = 0;
		do
		{
			$substr = substr($data, $offset, 3);
			$offset += 3;

			$bits = "";
			for ($i = 0; $i < strlen($substr); $i++) {
				$bits .= sprintf("%08b", ord($substr[$i]));
			}

			for ($i = 0; $i < strlen($bits); $i += 6) {
				$part = substr($bits, $i, 6);
				while (strlen($part) != 6) {
					$part .= "0";
				}
				$part = bindec($part);
				$result .= $alphabet[$part];
				$written++;
			}

			if ($written % 72 == 0) {
				$result .= PHP_EOL;
			}
		} while (strlen($substr) == 3);

		while ($written % 4) {
			$result .= "=";
			$written++;
		}

		return $result;
	}

	# DELIVERY

	/**
	 * Composes and sends the message
	 * @return boolean false on error, true otherwise
	 */
	function sendMail()
	{
		$recipients = $this->_getRecipients();
		if ($recipients == "") {
			trigger_error("No recipients found, cannot send the mail", E_USER_WARNING);
			return FALSE;
		}
		list($header, $body) = $this->_compose();
		return mail($recipients, $this->subject, $body, $header);
	}

	# DELIVERY - HELPER FUNCTIONS

	/**
	 * @access private
	 */
	function _getRecipients($includeBcc = FALSE)
	{
		$recipients = array();
		foreach ($this->to as $to) {
			if ($to["type"] != "BCC" || $includeBcc) {
				$recipients[] = $to["address"];
			}
		}
		return implode(", ", $recipients);
	}
}

?>