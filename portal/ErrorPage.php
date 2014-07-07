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
 * Displays an error message
 * @package Portal
 */
class ErrorPage extends Page
{
	/**
	 * @access private
	 */
	var $errorMessage;

	function setErrorMessage($errorMessage) {
		$this->errorMessage = $errorMessage;
	}

	/**
	 * @access private
	 */
	var $httpStatus;

	function setHttpStatus($httpStatus) {
		$this->httpStatus = $httpStatus;
	}

	/**
	 * @access private
	 */
	var $errors;

	function setErrors($errors) {
		$this->errors = $errors;
	}

	/**
	 * @access private
	 */
	var $remarks = array();

	function addRemark($remark) {
		$this->remarks[] = $remark;
	}

	function run()
	{
		$body = "";

		$this->tpl->loadTemplateFile("Error.html");
		$this->tpl->setVariable("message", $this->errorMessage);
		if (isset($this->errors)) {
			$this->tpl->setVariable("errors", $this->errors);
			$this->tpl->touchBlock("show_errors");
		}
		foreach ($this->remarks as $remark)
		{
			$this->tpl->setVariable("remark", $remark);
			$this->tpl->parse("remark");
		}
		$body .= $this->tpl->get();

		if (isset($this->httpStatus)) {
			header("HTTP/1.1 ".$this->httpStatus);
		}


		if (empty($result)) 
			$logo = "portal/images/tm-logo-sm.png";
		else 
			$logo = "upload/media/".$result;


		$this->tpl->loadTemplateFile("DocumentFrame.html");
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", "Error");
		$this->tpl->setVariable("logo", $logo);
		$this->tpl->show();
	}
}

?>