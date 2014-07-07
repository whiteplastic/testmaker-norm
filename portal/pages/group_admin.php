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
 * Include UserList
 */
require_once(CORE.'types/UserList.php');

/**
 * Loads the base class
 */
require_once(PORTAL.'AdminPage.php');

/**
 * Allows an adminstrator to manage user groups
 *
 * Default action: {@link doListGroup()}
 *
 * @package Portal
 */
class GroupAdminPage extends AdminPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "list_group";

	/**
	 * Lists all groups
	 */
	function doListGroup()
	{
		$userlist = new UserList();

		$body = "";

		$this->tpl->loadTemplateFile("GroupAdmin.html");
		$this->initTemplate("list_group");
		$this->tpl->touchBlock("groups_block");

		$userId = $GLOBALS['PORTAL']->getUserId();

		//delete group from list
		if (post("delete") == "yes")
		{
			$grp = DataObject::getById('Group', get('id'));
			if ($grp->get('id')) {
				EditLog::addEntry(LOG_OP_GROUP_DELETE, $grp, NULL);
			}
			$userlist->deleteGroup(get("id"));
		}
		// create new group
		if (post("handle") == "create")
		{
			$res = $userlist->createGroup(post("groupname"), post("description"), post('autoadd', false), 0);

			if ($res) {
				$perms = post('perms', NULL);
				if (is_array($perms)) {
					$res->setPermissions($perms);
				}

				EditLog::addEntry(LOG_OP_GROUP_CREATE, $res, array(
					'perms' => $perms,
					'guests' => post('guests', false),
				));

				$guest = DataObject::getById('User', 0);
				$gGroups = $guest->getGroupIds();
				if (post('guests', false) && !in_array($res->getId(), $gGroups)) {
					$gGroups[] = $res->getId();
				} elseif (!post('guests', false)) {
					$gGroups = array_diff($gGroups, array($res->getId()));
				}
				$guest->setGroups($gGroups, $userId);

				$this->tpl->touchBlock("status_block");
				$this->tpl->setVariable("status", T("pages.group.create.successful"));
				$this->tpl->touchBlock("status_block");
			} else {
				$this->tpl->touchBlock("status_block");
				$this->tpl->setVariable("status", T("pages.group.create.failed"));
				$this->tpl->touchBlock("status_block");
			}
		}
		// edit an existing group
		elseif (post("handle") == "edit")
		{
			$errors = array();

			$editGroup = DataObject::getById('Group', get('id'));
			$details = array();
			if ($editGroup->isVirtual()) $this->checkAllowed('deny', true);

			if ($editGroup->get('groupname') != post("groupname"))
			{
				$errors = array_merge((array) $editGroup->setGroupname(post("groupname"), $userId), $errors);
				$details['groupname'] = post('groupname');
			}
			if ($editGroup->get('description') != post("description"))
			{
				$errors = array_merge((array) $editGroup->setDescription(post("description"), $userId), $errors);
				$details['description'] = post('description');
			}

			$guest = DataObject::getById('User', 0);
			$gGroups = $guest->getGroupIds();
			if (post('guests', false) && !in_array($editGroup->get('id'), $gGroups)) {
				$gGroups[] = $editGroup->get('id');
			} elseif (!post('guests', false)) {
				$gGroups = array_diff($gGroups, array($editGroup->get('id')));
			}
			$guest->setGroups($gGroups, $userId);
			$details['groups'] = $gGroups;

			if ($editGroup->get('autoadd') != post('autoadd', false)) {
				$editGroup->setAutoAdd(post('autoadd', false), $userId);
				$details['autoadd'] = post('autoadd', false);
			}

			$perms = post('perms', array());
			if (!is_array($perms)) $perms = array();
			if ($editGroup->getId() != 1) $editGroup->setPermissions($perms);
			$details['perms'] = $perms;

			if ($errors)
			{
				$this->tpl->touchBlock("status_block");
				foreach ($errors AS $error)
				{
					$this->tpl->setVariable("status", $error);
					$this->tpl->parse("status_list");
				}
			}
			else
			{
				EditLog::addEntry(LOG_OP_GROUP_EDIT, $editGroup, $details);

				$this->tpl->touchBlock("status_block");
				$this->tpl->setVariable("status", T("pages.group.edit.successful"));
			}
		}

		// create groupslist
		$groups = $userlist->getGroupList();

		foreach ($groups AS $group)
		{
			$this->tpl->setVariable("groupid", $group->get('id'));
			$this->tpl->setVariable("groupname", $group->get('groupname'));
			$this->tpl->setVariable("description", $group->get('description'));
			if($group->get('autoadd')) {
				$this->tpl->setVariable("isdefault", "(".T("generic.default").")");
				$this->tpl->setVariable("groupname", "* ".$group->get('groupname'));
			}
			$this->tpl->parse("group_list");
		}

		$body .= $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		$this->tpl->show();
	}

	/**
	 * Allows to create a group
	 */
	function doCreateGroup()
	{
		$body = "";

		$this->tpl->loadTemplateFile("CreateGroup.html");
		$this->initTemplate("create_group");
		$this->tpl->touchBlock("create_group");

		$this->tpl->setVariable("button", t('buttons.create'));
		$this->tpl->setVariable("handle", "create");

		$body .= $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		$this->tpl->show();
	}

	/**
	 * Allows to edit a group
	 */
	function doEditGroup()
	{
		$body = "";

		$editGroup = DataObject::getById('Group', get('id'));
		if ($editGroup->isVirtual()) $this->checkAllowed('deny', true);

		$this->tpl->loadTemplateFile("GroupAdmin.html");
		$this->initTemplate("edit_group");
		$this->tpl->setVariable('groupid', $editGroup->get('id'));

		// Preselect permissions
		if ($editGroup->get('id') == 1) {
			foreach (Group::getPermissionNames() as $pname) {
				$this->tpl->setVariable("perms_{$pname}_checked",' checked="checked" disabled="disabled"');
			}
		} else {
			$perms = $editGroup->getPermissions();
			foreach ($perms as $pname => $_pvalue) {
				$this->tpl->setVariable("perms_{$pname}_checked", ' checked="checked"');
			}
		}

		$this->tpl->touchBlock("create_group");
		$this->tpl->hideBlock("group_list");

		$this->tpl->setVariable("button", t('buttons.edit'));
		$this->tpl->setVariable("handle", "edit");

		$this->tpl->setVariable("groupname", $editGroup->get('groupname'));
		$this->tpl->setVariable("description", $editGroup->get('description'));

		$guest = DataObject::getById('User', 0);
		$gGroups = $guest->getGroupIds();
		$this->tpl->setVariable("autoadd_checked", $editGroup->get('autoadd') ? ' checked="checked"' : '');
		$this->tpl->setVariable("guests_checked", in_array($editGroup->getId(), $gGroups) ? ' checked="checked"' : '');

		$body .= $this->tpl->get();

		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T('menu.user.admin'));
		$this->tpl->show();
	}
}

?>
