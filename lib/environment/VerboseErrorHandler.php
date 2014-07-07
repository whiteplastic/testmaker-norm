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
 * Handles errors by collecting them and sending a detailed report by email.
 * The reports need to be read by a special error tracker.
 * Optionally, this error handler can use an interest checker that determines whether
 * a given report is interesting enough to be sent via email. Also, the error handler
 * can use a notifier that gets called if an error occures which is handy to inform the
 * user.
 *
 * Example interest checker:
 *
 * <code>
 * ErrorHandler::useInterestChecker(array(new InterestChecker(), "isInterestingReport"));
 * class InterestChecker {
 *   function isInterestingReport($fingerprint) {
 *     if ($this->db->isNewReport($fingerPrint)
 *         || time() - $this->db->getLastSent($fingerPrint) >= 60*60*24) {
 *       $this->db->setLastSent($fingerPrint, time());
 *       return true;
 *     }
 *  }
 * </code>
 *
 * Example notifier:
 *
 * <code>
 * ErrorHandler::useNotifier(array(new Notifier(), "notify"));
 * class Notifier {
 *   var $notificationSent = FALSE;
 *   function notify($level, $message, $file, $line, $context) {
 *     if (! $this->notificationSent) {
 *       echo "An error has occured!";
 *       $this->notificationSent = TRUE;
 *     }
 *   }
 * }
 * </code>
 *
 * @see ErrorHandler::install()
 * @see ErrorHandler::useInterestChecker()
 * @see ErrorHandler::useNotifier()
 * @package Library
 */
class ErrorHandler
{
	var $projectName;
	var $projectVersion;
	var $projectLink;

	var $trackerUrl;
	var $receivers;
	var $sender;

	var $uploadFileContingent;
	var $heedErrorLevel;

	var $interestChecker;
	var $notifier;

	var $errors = array();

	function ErrorHandler()
	{
		static $installed = FALSE;

		if ($installed) {
			trigger_error("Use ErrorHandler::install() to install and configure the Error Handler", E_USER_ERROR);
			return;
		}

		set_error_handler(array(&$this, "_handlePhpError"));
		register_shutdown_function(array(&$this, "_sendReport"));

		$installed = TRUE;
	}

	function &getInstance()
	{
		if (! isset($GLOBALS["ERROR_HANDLER"])) {
			$GLOBALS["ERROR_HANDLER"] = new ErrorHandler();
		}

		return $GLOBALS["ERROR_HANDLER"];
	}

	function install($projectName, $projectVersion, $projectLink, $trackerUrl, $receivers, $sender = NULL, $uploadFileContingent = 512000, $heedErrorLevel = TRUE)
	{
		$instance = &ErrorHandler::getInstance();

		if (is_string($receivers)) {
			$receivers = array($receivers);
		}

		$instance->projectName = $projectName;
		$instance->projectVersion = $projectVersion;
		$instance->projectLink = $projectLink;

		$instance->trackerUrl = $trackerUrl;
		$instance->receivers = $receivers;
		$instance->sender = $sender;

		$instance->uploadFileContingent = $uploadFileContingent;
		$instance->heedErrorLevel = $heedErrorLevel;
	}

	function useInterestChecker($checker)
	{
		if (! is_callable($checker)) {
			trigger_error("Invalid callback provided", E_USER_WARNING);
			return;
		}
		$instance = &ErrorHandler::getInstance();
		$instance->interestChecker = $checker;
	}

	function useNotifier($notifier)
	{
		if (! is_callable($notifier)) {
			trigger_error("Invalid callback provided", E_USER_WARNING);
			return;
		}
		$instance = &ErrorHandler::getInstance();
		$instance->notifier = $notifier;
	}

	function hasErrors()
	{
		$instance = &ErrorHandler::getInstance();
		return $instance->errors ? TRUE : FALSE;
	}

	function _handlePhpError($level, $message, $file = "", $line = 0, $context = array())
	{
		// Ignore errors below the current error level
		if ($this->heedErrorLevel && ! (error_reporting() & $level)) {
			return;
		}

		if ($this->notifier) {
			call_user_func($this->notifier, $level, $message, $file, $line, $context);
		}

		// Remove default variables from the context
		// We create a new array here because somehow <code>unset($context["_GET"])</code> deletes $_GET as well.
		$filteredContext = array();
		foreach ($context as $name => $value) {
			if (! isset($GLOBALS[$name])) {
				$filteredContext[$name] = $value;
			}
		}

		// Store the function call history
		$backtrace = debug_backtrace();

		// Strip off the call to this function
		array_shift($backtrace);

		$this->errors[] = array(
			"level" => $level,
			"message" => $message,
			"file" => $file,
			"line" => $line,
			"context" => $filteredContext,
			"backtrace" => $backtrace,
		);

		if ($level == E_USER_ERROR) {
			exit(1);
		}
	}

	function isInterestingReport($fingerprint)
	{
		if ($this->interestChecker) {
			return call_user_func($this->interestChecker, $fingerprint);
		}
		return TRUE;
	}

	function getLoadedExtensions()
	{
		$list = get_loaded_extensions();
		sort($list);

		return $list;
	}

	function getUploadedFiles($files)
	{
		$uploadedFiles = array();

		// Sort the files by their size, but prefer complete files over partially uploaded files
		$completeFiles = array();
		$partialFiles = array();

		foreach ($files as $fileIndex => $file) {
			if ($file["error"] == UPLOAD_ERR_OK) {
				$completeFiles[$fileIndex] = filesize($file["tmp_name"]);
			}
			elseif ($file["error"] == UPLOAD_ERR_PARTIAL) {
				$partialFiles[$fileIndex] = filesize($file["tmp_name"]);
			}
		}

		asort($completeFiles);
		asort($partialFiles);


		// Insert as many files as possible into the report
		// If the contingent is exceeded, the last file is truncated to fit
		$candidates = array_merge(array_keys($completeFiles), array_keys($partialFiles));
		$available = $this->uploadFileContingent;

		while ($available > 0 && $candidates)
		{
			$fileIndex = array_shift($candidates);
			$file = $files[$fileIndex];
			$size = filesize($file["tmp_name"]);
			$uploadedFiles[$fileIndex] = array(
				"contents" => substr(file_get_contents($file["tmp_name"]), 0, $available),
				"complete" => ($file["error"] == UPLOAD_ERR_OK && $available >= $size),
			);
			$available -= $size;
		}

		return $uploadedFiles;
	}

	function getPhpInfo($subjects)
	{
		ob_start();
		phpinfo($subjects);
		$phpInfo = ob_get_contents();
		ob_end_clean();

		return $phpInfo;
	}

	function getClassHierarchy($report)
	{
		$classHierarchy = array();

		libLoad("oop::findClasses");
		libLoad("oop::getParentClasses");
		foreach (findClasses($report) as $className) {
			$parent = &$classHierarchy;
			foreach (array_reverse(getParentClasses($className)) as $parentClassName)
			{
				if (! isset($parent[$parentClassName])) {
					$parent[$parentClassName] = array();
				}
				$parent = &$parent[$parentClassName];
			}
		}

		return $classHierarchy;
	}

	function _sendReport()
	{
		// No errors? Nothing to do then.
		if (! $this->errors) { return; }

		// If errors occur beyond this point, show them right away
		restore_error_handler();

		libLoad("utilities::cleanCopy");

		// Create just a basic report for the duplication check
		$report = array(
			"errors" => $this->errors,
			"code_files" => array(),
		);

		// Also include the participating files. They might be changed to fix the error,
		// but if it appears again, the fix obviously didn't work and we need to know.
		foreach ($report["errors"] as $errorIndex => $error)
		{
			foreach ($error["backtrace"] as $step)
			{
				if (isset($step["file"])) {
					if (! isset($files[$step["file"]])) {
						$report["code_files"][$step["file"]] = file_get_contents($step["file"]);
					}
				}
			}
			preg_match_all('<\S*?(?:[\\\\/][^\s:\\|\\<\\>]*)+>', $error["message"], $matches);
			foreach ($matches[0] as $file) {
				if (! isset($files[$file]) && @is_file($file)) {
					$report["code_files"][$file] = file_get_contents($file);
				}
			}
		}

		ksort($report["code_files"]);

		// Copy the report because we will modify it for the duplication check
		$signature = cleanCopy($report);

		// Remove objects from the signature because their state is more likely to be irrelevant.
		// E.g. PEAR::DB objects store timestamps that would result in a different fingerprint for the same error.
		foreach ($signature["errors"] as $errorIndex => $error)
		{
			foreach ($error["backtrace"] as $stepIndex => $step)
			{
				if (isset($step["args"])) {
					foreach ($step["args"] as $argIndex => $arg) {
						if (is_object($arg)) {
							$signature["errors"][$errorIndex]["backtrace"][$stepIndex]["args"][$argIndex] = get_class($arg);
						}
					}
				}

			}
			unset($signature["errors"][$errorIndex]["context"]);
			unset($signature["errors"][$errorIndex]["backtrace"]);
			unset($signature["errors"][$errorIndex]["message"]);
			unset($signature["code_files"]);
		}
		// Generate a fingerprint based on the basic report and free some memory
		$fingerprint = sha1(serialize($signature['errors'][0]));
		unset($signature);

		// Allow better tracking of development information
		if (isset($_SESSION['show_error_fingerprints']) && isset($GLOBALS['MSG_HANDLER'])) {
			$GLOBALS['MSG_HANDLER']->addMsg('environment.error_fingerprint', MSG_RESULT_NEUTRAL, array('code' => $fingerprint));
		}

		// If an equivalent report exists and has been sent recently, abort
		if (! $this->isInterestingReport($fingerprint)) return;


		// Extend the report with as much useful information as possible
		$report = array(
			"is_error_report" => TRUE,
			"version" => 1,
			"meta" => array(
				"Project Name" => $this->projectName,
				"Project Version" => $this->projectVersion,
				"Project Link" => $this->projectLink,
				"Tracker Link" => $this->trackerUrl,
				"Date" => date("Y-m-d H:i:s (O)"),
				"Fingerprint" => $fingerprint,
			),
			"errors" => $this->errors,
			"environment" => array(
				"get" => $_GET,
				"post" => $_POST,
				"cookie" => $_COOKIE,
				"request" => $_REQUEST,
				"files" => $_FILES,
				"session" => @$_SESSION,
				"server" => $_SERVER,
				"env" => $_ENV,
				"extra" => array(
					"PHP Version" => PHP_VERSION,
					"PHP Operating System" => PHP_OS,
					"PHP SAPI" => php_sapi_name(),
				),
			),
			"class_hierarchy" => array(),
			"included_files" => get_included_files(),
			"code_files" => $report["code_files"],
			"uploaded_files" => $this->getUploadedFiles($_FILES),
			"loaded_extensions" => $this->getLoadedExtensions(),
			// $_ENV is sometimes empty under Windows, yet phpinfo() has this information
			"phpinfo" => $this->getPhpInfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT),
		);

		$report["class_hierarchy"] = $this->getClassHierarchy($report);

		// Filter passwords (this has to be done before var_export is applied to them)
		libLoad("utilities::censorKeys");
		censorKeys($report, "/(^pass|^pw$|password)/i", "[censored]");

		// Objects in arguments lead to incomplete class objects in the tracker, so we prepare processing here
		// Some objects might remain uncovered if they reside in unusual places like $_GET
		foreach (array_keys($report["errors"]) as $errorIndex) {
			foreach ($report["errors"][$errorIndex]["backtrace"] as $stepNumber => $step) {
				if (isset($step["args"])) {
					$report["errors"][$errorIndex]["backtrace"][$stepNumber]["exported_args"] = array();
					foreach ($step["args"] as $argNumber => $arg) {
						$report["errors"][$errorIndex]["backtrace"][$stepNumber]["exported_args"][$argNumber] = var_export(cleanCopy($arg), TRUE);
					}
				}
			}
		}

		$report["environment"]["exported_session"] = var_export(cleanCopy($report["environment"]["session"]), TRUE);

		// Pack the report into a string
		$packedReport = serialize($report);

		// Create a hash for identification
		$hash = sha1($packedReport);

		// Compress the report to save space
		$packedReport = gzencode($packedReport, 9);
		$mimeType = "application/x-gzip";
		// Make sure the uncompressed file has an extension that Windows regards as binary
		$fileName = "Error Report $hash.dat.gzip";

		$subject = "Error Report";

		$reportLink = preg_replace("</+$>", "", $this->trackerUrl)."/show_report.php?hash=".$hash;

		$lb = "\n";

		// Create the mail text
		$html = "";
		$body = "";

		$html .= '<?xml version="1.0" encoding="ISO-8859-1"?>'.$lb;
		$html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.$lb;
		$html .= '<html><head><title>'.htmlspecialchars($subject).'</title>'.$lb;
		$html .= '<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />'.$lb;
		$html .= '<style type="text/css">'.$lb;
		$html .= 'body,p,div,h1,h2,h3,h4,h5,h6,button,input,select,textarea { font-family: Verdana,Tahoma,Trebuchet MS,sans-serif; }'.$lb;
		$html .= 'body,div.Document,table { font-size:0.95em; }'.$lb;
		$html .= 'p,ul,ol { line-height: 130%; }'.$lb;
		$html .= '.First { margin-top: 0; }'.$lb;
		$html .= '.Last { margin-bottom: 0; }'.$lb;
		$html .= 'table { border-collapse: collapse }'.$lb;
		$html .= 'td,th { padding:3px 0 }'.$lb;
		$html .= 'th { text-align:left; padding-right: 1em }'.$lb;
		$html .= 'ul { list-style-type:square; }'.$lb;
		$html .= 'body,form { margin:0; padding:0; }'.$lb;
		$html .= 'body { background-color: #EEE; }'.$lb;
		$html .= 'div.Document { border: 1px solid #999; background-color: white; margin: 1em; padding: 0.8em; }'.$lb;
		$html .= 'a:active, a:visited, a:link, input.Link {	color:#044294; text-decoration:none; }'.$lb;
		$html .= 'a:hover, input.Link:hover { text-decoration:underline; }'.$lb;
		$html .= '</style>'.$lb;
		$html .= '</head><body>'.$lb;

		$body .= '<div class="Document"><h1 class="First">Error Report<span style="display:none">'.$lb.'------------</span></h1>'.$lb.$lb;
		$body .= '<p><a href="'.htmlspecialchars($reportLink).'">Open this report<span style="display:none">:'.$lb.htmlspecialchars($reportLink).'</span></a></p>'.$lb;
		$body .= '<table>'.$lb;
		foreach ($report["meta"] as $name => $value) {
			if (substr($name, -4) == "Link") {
				$value = '<a href="'.htmlspecialchars($value).'">'.htmlspecialchars($value).'</a>';
			} else {
				$value = htmlspecialchars($value);
			}
			$body .= '<tr><th>'.htmlspecialchars($name).': </th><td>'.$value.'</td></tr>'.$lb;
		}
		$body .= '</table>'.$lb;

		$body .= '<h2>Error Summary<span style="display:none">'.$lb.'-------------</span></h2>'.$lb;
		$body .= '<ul>'.$lb;
		foreach ($report["errors"] as $error) {
			$message = strip_tags($error["message"], "<b><i><u><code><p><br><a>");
			$body .= '<li>'.$message.'</li>'.$lb.$lb;
		}
		$body .= '</ul>';
		$body .= '</div>';

		$html .= $body;
		$html .= '</body></html>';

		$text = html_entity_decode(strip_tags($body));

		// Compose the mail
		libLoad("email::Composer");
		$mail = new EmailComposer();
		if (isset($this->sender)) {
			$mail->setFrom($this->sender);
		}
		foreach ($this->receivers as $receiver) {
			$mail->addRecipient($receiver);
		}
		$mail->setSubject($subject);
		$mail->setHtmlMessage($html);
		$mail->setTextMessage($text);
		$mail->addAttachmentFromMemory($packedReport, $mimeType, $fileName);

		// Send the mail
		if ($mail->sendMail())
		{
			//echo '<div style="clear:both;background-color:#FFF;color:#000;font-size:14px;border:3px solid #900;padding:2px;line-height:140%;text-align:center;width:200px;font-weight:bold;font-variant:none;font-family:sans-serif;letter-spacing:0">An error occured.<br />A report has been sent.</div>';
		}
	}
}

?>
