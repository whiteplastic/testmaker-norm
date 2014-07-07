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

libLoad('environment::updateTimestamp');

/**
 * Displays the about page
 *
 * Default action: {@link doShowAbout()}
 *
 * @package Portal
 */
class AboutPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "show_about";

	function doShowAbout()
	{
		$this->tpl->loadTemplateFile("About.html");
		$this->tpl->setVariable("tm_version", TM_VERSION . TM_VERSION_SUFFIX);
		$this->tpl->setVariable("year", date("Y"));

		if ($this->checkAllowed('admin')) {
			$this->tpl->setVariable("tm_stamp", getUpdateTimestamp());
			$this->tpl->touchBlock('update');
		} else {
			$this->tpl->hideBlock('update');
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("menu.testmaker.about"));

		$this->tpl->show();
	}
}

?>
