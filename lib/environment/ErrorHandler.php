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

libLoad("utilities::indentBlock");

/**
 * This class provides the interface to handle program internal errors.
 * It should give a nice response to the user and an informative error description to the programmer.
 *
 * @package Library
 */
class ErrorHandler
{

	/**#@+
	 * @access private
	 */
	var $programmer;
	var $errors = array();
	var $warnings = array();
	var $systemErrors = array();
	var $userMessageSent = FALSE;
	var $systemErrorLevels = array(
		1 => "E_ERROR",
		2 => "E_WARNING",
		4 => "E_PARSE",
		8 => "E_NOTICE",
		16 => "E_CORE_ERROR",
		32 => "E_CORE_WARNING",
		64 => "E_COMPILE_ERROR",
		128 => "E_COMPILE_WARNING",
		256 => "E_USER_ERROR",
		512 => "E_USER_WARNING",
		1024 => "E_USER_NOTICE",
		2047 => "E_ALL",
		2048 => "E_STRICT",
	);
	var $db;
	/**#@-*/

	function ErrorHandler($programmer = NULL)
	{
		$this->setProgrammer($programmer);

		// get Database connection, but only if we've already got a connection
		if (isset($GLOBALS['dao'])) {
			$this->db = &$GLOBALS['dao']->getConnection();
		} else {
			$this->db = NULL;
		}
	}

	/**
	 * Installs the ErrorHandler
	 */
	function install($programmer = NULL)
	{
		if (isset($GLOBALS["ERROR_HANDLER"])) {
			trigger_error("ErrorHandler is already installed", E_USER_ERROR);
		}

		// Install the error handler in a global variable
		$GLOBALS["ERROR_HANDLER"] = new ErrorHandler($programmer);

		// We want the execute method to be called even if the user aborts the script execution
		ignore_user_abort(TRUE);

		// Set reportSystemError as the new system error handler
	//	set_error_handler(array(&$GLOBALS["ERROR_HANDLER"], "reportSystemError"));

		// Register execute to be called on shutdown (even if exit() ist called before the end of the script is reached)
		register_shutdown_function(array(&$GLOBALS["ERROR_HANDLER"], "execute"));
	}

	/**
	 * Sets the programmer's e-mail address
	 * @param string e-mail address of the programmer
	 */
	function setProgrammer($programmer)
	{
		$this->programmer = $programmer;
	}

	/**
	 * Returns whether a programmer email address has been set
	 * @return whether a programmer email address has been set
	 */
	function hasProgrammer()
	{
		return $this->programmer != NULL;
	}

	/**
	 * Stores an error
	 * @param string The error message
	 * @param string The file in which the error was produced (__FILE__)
	 * @param string The name of the function that produced the error (__FUNCTION__)
	 * @param int Number of the line in which the error was produced (__LINE__)
	 */
	function reportError($message, $file, $function, $line)
	{
		$error['message'] = $message;
		$error['file'] = $file;
		$error['function'] = $function;
		$error['line'] = $line;
		$this->errors[] = $error;
		$this->showUserMessage();
	}

	/**
	 * Stores a warning
	 * @param string The error message
	 * @param string The file in which the error was produced (__FILE__)
	 * @param string The name of the function that produced the error (__FUNCTION__)
	 * @param int Number of the line in which the error was produced (__LINE__)
	 */
	function reportWarning($message, $file, $function, $line)
	{
		$warning['message'] = $message;
		$warning['file'] = $file;
		$warning['function'] = $function;
		$warning['line'] = $line;
		$this->warnings[] = $warning;
		$this->showUserMessage();
	}

	/**
	 * Error handler for PHP's default error mechanism
	 * @param int The error level
	 * @param int The error message
	 * @param string The file in which the error was produced
	 * @param int Number of the line in which the error was produced
	 */
	function reportSystemError($level, $message, $file = '', $line = 0)
	{
		// Ignore strict errors (they are unavoidable if we want PHP 4 compatibility)
		if (PHP_VERSION >= "5.0.0" && ($level & E_STRICT)) {
			return;
		}

		// Ignore suppressed errors (@ operator)
		if (! error_reporting()) {
			return;
		}

		$systemError['level'] = $level;
		$systemError['message'] = $message;
		$systemError['file'] = $file;
		$systemError['line'] = $line;
		$this->systemErrors[] = $systemError;
		$this->showUserMessage();

		// Stop execution in case of fatal errors
		if ($level & (E_USER_ERROR | E_ERROR)) {
			exit();
		}
	}

	/**
	 * Forwards the occurance of errors to the message handler if not already done
	 */
	function showUserMessage()
	{
		if (! $this->userMessageSent) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.core.system_error', -1);
			$this->userMessageSent = TRUE;
		}
	}

	/**
	 * Sends all errors to the programmer
	 */
	function execute()
	{
		// Restore the default error handler, in case this function produces further errors
		restore_error_handler();

		if($this->errors || $this->warnings || $this->systemErrors)
		{
			$mailContent = 'There were errors when executing "'.$_SERVER['SCRIPT_NAME'].'" on '.date('d.m.Y H:i:s')."!\n\n";

			$containsNewErrors = FALSE;
			foreach (array(
				1 => $this->systemErrors,
				2 => $this->errors,
				3 => $this->warnings,
			) as $type => $errors)
			{
				foreach ($errors as $error) {
					if ($this->checkIfNewMessage($error['file'], $error['line'], $type)) {
						$containsNewErrors = TRUE;
						// DO NOT abort querying the database here.
						// If 3 new errors occur, then the first, second and third occurence would
						// cause sending an email, since each time only one of them will be registered.
					}
				}
			}

			$this->_filterPasswords($this->systemErrors);
			$this->_filterPasswords($this->errors);
			$this->_filterPasswords($this->warnings);

			if($this->systemErrors)
			{
				$mailContent .= "PHP errors:\n";
				foreach($this->systemErrors as $systemError) {
					if (isset($this->systemErrorLevels[$systemError['level']])) {
						$systemError['level'] = $this->systemErrorLevels[$systemError['level']];
					}
					$mailContent .= ' - "'.$systemError['message'].'" ('.$systemError['level'].') in file "'.$systemError['file'].'" on line '.$systemError['line']."\n";
				}
				$mailContent .= "\n";
			}
			if($this->errors)
			{
				$mailContent .= "Errors:\n";
				foreach ($this->errors as $error) {
					$mailContent .= ' - In file "'.$error['file'].'" in function "'.$error['function'].'" on line '.$error['line']." the following error appeared:\n".indentBlock(print_r($error['message'], TRUE), 3)."\n";
				}
				$mailContent .= "\n";
			}
			if($this->warnings)
			{
				$mailContent .= "Warning(s):\n";
				foreach($this->warnings as $warning) {
					$mailContent .= ' - In file "'.$warning['file'].'" in function "'.$warning['function'].'" on line '.$warning['line']." the following warning appeared:\n".indentBlock(print_r($warning['message'], TRUE), 3)."\n";
				}
				$mailContent .= "\n";
			}

			$mailContent .= $this->_dumpVariable($_GET, "\$_GET")."\n";
			$mailContent .= $this->_dumpVariable($_POST, "\$_POST")."\n";
			$mailContent .= $this->_dumpVariable($_COOKIE, "\$_COOKIE")."\n";
			$mailContent .= $this->_dumpVariable($_SERVER, "\$_SERVER")."\n";
			$mailContent .= $this->_dumpVariable($_ENV, "\$_ENV");
			if (isset($_SESSION)) {
				$mailContent .= "\n".$this->_dumpVariable($_SESSION, "\$_SESSION");
			}

			if($containsNewErrors && isset($this->programmer))
			{
				libLoad("email::Composer");
				$mail = new EmailComposer();
				$mail->setFrom(SYSTEM_MAIL);
				$mail->addRecipient($this->programmer);
				$mail->setSubject("Error Report for ".$_SERVER["HTTP_HOST"]);
				$mail->setTextMessage($mailContent);
				if (!$mail->sendMail()) {
					if(!$GLOBALS["is_dev_machine"])
					{
						echo "<p class=\"Error\">Failed to send the error report via e-mail.</p>";
					}
				}
			}

			if (!isset($this->programmer)) {
				echo "<p class=\"Error\">The ErrorHandler was not told the address of a programmer, so errors appear right here.</p>";
				echo "<pre>",htmlspecialchars($mailContent),"</pre>";
			}
		}
	}

	/**
	 * Returns if there are new messages (or old ones which are older then
	 * 5 days) in this report and indicates these as send now
	 * @return boolean true if there are new messages, false otherwise
	 */
	function checkIfNewMessage($file, $line, $type)
	{
		// Need database to check
		if (!$this->db) return true;

		// If we're not sending any e-mails, don't bother suppressing this message
		if (!isset($this->programmer)) return true;

		$newCount = 1;
		//check if message already registered
		if($row = $this->db->getRow('SELECT *, last_mail FROM '.DB_PREFIX.'error_log WHERE filemd5 = ? AND line = ? AND  type = ?', array(md5($file), $line, $type))) {

			$newCount = $row['countError'] + 1;
			$this->db->query('UPDATE '.DB_PREFIX.'error_log SET countError = ? WHERE id = ?', array($newCount, $row['id']));
			//check if message is old enough to be sent
			if($row['last_mail'] < (time() - (3600 * 24 * 5)))
			{
				$this->db->query('UPDATE '.DB_PREFIX.'error_log SET last_mail = ? WHERE id = ?', array(time(), $row['id']));
				return true;
			}
		} else {
			//create message log
			$id = $this->db->nextId(DB_PREFIX.'error_log');
			if($this->db->isError($id)) {
				return true;
			}
			$this->db->query('INSERT INTO '.DB_PREFIX.'error_log (id, filemd5, line, type, last_mail, countError) VALUES (?, ?, ?, ?, ?, ?)', array($id, md5($file), $line, $type, time(), $newCount));
			return true;
		}

		return false;
	}

	/**
	 * Returns whether there have been errors of any kind
	 * @return boolean true if there were errors, false otherwise
	 */
	function hasErrors()
	{
		return ($this->errors || $this->warnings || $this->systemErrors);
	}

	/**
	 * Dumps a variable as valid PHP code
	 * @access private
	 */
	function _dumpVariable($variable, $name)
	{
		$this->_filterPasswords($variable);
		return $name." = ".var_export($variable, TRUE).";\n";
	}

	function _filterPasswords(&$parentVariable, $parentKey = NULL)
	{
		if (is_array($parentVariable) && ! @$parentVariable["IS_FILTERING_PASSWORD"]) {
			$parentVariable["IS_FILTERING_PASSWORD"] = TRUE;
			foreach (array_keys($parentVariable) as $key) {
				if ($key !== "IS_FILTERING_PASSWORD") {
					$this->_filterPasswords($parentVariable[$key], $key);
				}
			}
			unset($parentVariable["IS_FILTERING_PASSWORD"]);
		}
		elseif (is_object($parentVariable) && ! @$parentVariable->IS_FILTERING_PASSWORD) {
			$parentVariable->IS_FILTERING_PASSWORD = TRUE;
			foreach (get_object_vars($parentVariable) as $key => $value) {
				if ($key !== "IS_FILTERING_PASSWORD") {
					$this->_filterPasswords($parentVariable->$key, $key);
				}
			}
			unset($parentVariable->IS_FILTERING_PASSWORD);
		}
		elseif (is_scalar($parentVariable)) {
			if (preg_match("/(^pass|^pw$|password)/i", $parentKey)) {
				$parentVariable = "[censored]";
			}
		}
	}
}

?>
