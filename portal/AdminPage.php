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
 * Base class for all pages dealing with user administration
 *
 * @package Portal
 */
class AdminPage extends Page
{
	function run($actionName = NULL)
	{
		//because, by unswitching identity the admin user is not login as admin.
		if ($actionName == "unswitch")
			parent::run($actionName);
			
		$this->checkAllowed('admin', true, NULL);
		parent::run($actionName);
	}

	function initTemplate($activeTab)
	{
		$tabs = array(
			"list_user" => array("title" => "pages.admin.tabs.list_user", "link" => linkTo("user_admin", array("action" => "list_user"))),
			"create_user" => array("title" => "pages.admin.tabs.create_user", "link" => linkTo("user_admin", array("action" => "user_creation"))),
			"list_group" => array("title" => "pages.admin.tabs.list_group", "link" => linkTo("group_admin", array("action" => "list_group"))),
			"create_group" => array("title" => "pages.admin.tabs.create_group", "link" => linkTo("group_admin", array("action" => "create_group"))),
			"switch_user" => array("title" => "pages.admin.tabs.change_identity", "link" => linkTo("switch_user", array("action" => "show_switch"))),
			"edit_email" => array("title" => "pages.admin.tabs.email", "link" => linkTo("email_admin", array("action" => "edit_email"))),
			"delete_user" => array("title" => "pages.admin.tabs.delete_user", "link" => linkTo("user_delete", array("action" => "filter_user")))
		);

		$this->initTabs($tabs, $activeTab);
	}

}

?>
