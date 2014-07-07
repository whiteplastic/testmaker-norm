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
 * Displays the disclaimer page
 *
 * Default action: {@link doShowDisclaimer()}
 *
 * @package Portal
 */
class DisclaimerPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "show_disclaimer";

	function doShowDisclaimer()
	{
		$this->tpl->loadTemplateFile("Disclaimer.html");

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("menu.testmaker.disclaimer"));

		$this->tpl->show();
	}
}

?>