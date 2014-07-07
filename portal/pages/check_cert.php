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

class CheckCertPage extends Page
{
	var $defaultAction = "check_certificate";
	
	function doCheckCertificate()
	{
		$this->checkAllowed('cert', true);
		$body = "";
		$this->tpl->loadTemplateFile("CheckCert.html");
		$this->tpl->touchBlock("cert_test");
		
		$name = get('name', '');
		$date = get('date', '');
		$testTitle = get('testTitle', '');
		
		if ($name != 'false' && $date != 'false') {
			$this->tpl->setVariable('name', $name);
			$this->tpl->setVariable('date', $date);
			$this->tpl->setVariable('testTitle', $testTitle);
			$this->tpl->hideBlock('resultBarCodeNeg');
		}

		if ($name == 'false' && $date == 'false') {
			$this->tpl->hideBlock('resultBarCodePos');
			$this->tpl->setVariable('message_color', 'red');
			$this->tpl->setVariable('result_message_barcode', T('pages.check_certificate.result_neg'));
		}
		if ($name == '' && $date =='') {
			$this->tpl->hideBlock('resultBarCodePos');
			$this->tpl->hideBlock('resultBarCodeNeg');
		}
		
		$body .= $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.checkCert'));
		$this->tpl->show();
	}
	
	function doDefault()
	{
		$this->tpl->loadTemplateFile("CheckCert.html");
		$this->loadDocumentFrame();
		$this->tpl->show();
	}

}
?>