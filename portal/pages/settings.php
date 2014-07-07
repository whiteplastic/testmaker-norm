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
 * Shows a list of general configuration tasks
 *
 * Default action: {@link doList()}
 *
 * @package Portal
 */
class SettingsPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "list";

	function doList()
	{
		$this->checkAllowed('admin', true);

		$body = $this->renderTemplate("Settings.html", array(
			'setting' => array(
				array('link' => linkTo('intro_page', array(), true), 'title' => T('pages.intro_page.title')),
			),
		), true);

		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);

		$this->tpl->setVariable('page_title', T('menu.user.settings'));

		$this->tpl->show();
	}
}

