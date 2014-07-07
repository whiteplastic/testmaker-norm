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
 * Base class for all pages dealing with genereal management
 *
 * @package Portal
 */
class ManagementPage extends Page
{
	function run($actionName = NULL)
	{
		$this->checkAllowed('admin', true, NULL);
		parent::run($actionName);
	}

	function initTemplate($activeTab)
	{
		$tabs = array(
			"edit_intro_page" => array("title" => "pages.admin.tabs.management_greeting", "link" => linkTo("intro_management", array("action" => "edit_intro_page"))),
			"logo_management" => array("title" => "pages.admin.tabs.management_logo", "link" => linkTo("logo_management")),
			"edit_privacy_policy" => array("title" => "pages.admin.tabs.management_privacy_policy", "link" => linkTo("edit_privacy_policy")),
			"view_edit_logs" => array("title" => "pages.admin.tabs.management_editlogs", "link" => linkTo("view_edit_logs")),
			"maintenance_mode" => array("title" => "pages.admin.tabs.maintenance_mode", "link" => linkTo("maintenance_mode")),
		);
		
		$this->initTabs($tabs, $activeTab);
	}

}

?>
