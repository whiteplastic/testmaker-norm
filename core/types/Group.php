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
 * @package Core
 */

define('GROUP_VIRTUAL_TAN', -1);
define('GROUP_VIRTUAL_PASSWORD', -2);

/**
 * Group class
 *
 * @package Core
 */

class Group extends DataObject
{
	/**#@+
	 *
	 * @access private
	 */
	static protected $table = 'groups';
	
	static protected $prototype = array (
		'groupname'  => array(),
		'description' => NULL,
		'autoadd' => 0,
	);
	
	static protected $retrievalQueries = array(
		'userId' => 'SELECT g.* FROM @T AS g LEFT JOIN @P_groups_connect AS gc ON (gc.group_id = g.id) WHERE gc.user_id = @{*}',
		'listAutoaddOnlyVirtual' => 'SELECT * FROM @T WHERE autoadd=1',
		'list' => 'SELECT * FROM @T WHERE id>0',
		'listAutoaddOnly' => 'SELECT * FROM @T WHERE autoadd=1 AND id>0',
		'listVirtual' => 'SELECT * FROM @T',
		);
		
	
	/**
	 * Alias for getGroupname.
	 */
	function getTitle()
	{
		return $this->get('groupname');
	}


	/**
	 * Returns whether this is actually a virtual group.
	 */
	function isVirtual()
	{
		return (is_numeric($this->get('id')) && $this->get('id') < 0);
	}

	/**
	 * Checks if this group has a certain kind of access to a certain target.
	 *
	 * @param string Permission type. Currently supported types include
	 *   'portal', 'run', 'tan', 'review', 'view', 'publish', 'create',
	 *   'export', 'edit', 'delete', 'copy', 'link', 'admin'.
	 *   Can be an array of type names to check if any of the given privs is
	 *   set.
	 * @param mixed Target; a Block object, NULL to check for global
	 *   permissions, or FALSE to check if this permission is given on
	 *   anything at all.
	 * @param boolean Whether to use the GID 1 safety belt.
	 * @return boolean The value of the permission or NULL if permission is
	 *   denied.
	 */
	function checkPermission($type, $target = NULL, $safetyBelt = true)
	{
		// Safety belt
		if ($safetyBelt && $this->get('id') == 1) return true;

		if ($target) $id = $target->getId();
		if ($target === NULL) $id = 0;
		$query = "SELECT permission_value FROM ".DB_PREFIX."group_permissions WHERE group_id=?";
		$params = array($this->get('id'));

		if (is_array($type)) {
			for ($i = 0; $i < count($type); $i++) {
				$type[$i] = "'". addslashes($type[$i]) ."'";
			}
			$query .= ' AND permission_name IN ('. implode(',', $type) .')';
		} else {
			$query .= ' AND permission_name=?';
			$params[] = $type;
		}

		if ($target !== FALSE) {
			$query .= " AND block_id IN (?, ?)";
			$params[] = $id;
			$params[] = 0;
		}
		$query .= ' ORDER BY permission_value DESC';
		$res = $this->db->getOne($query, $params);
		if (PEAR::isError($res)) return NULL;
		return $res;
	}

	/**
	 * Checks whether this group is "special" (has editing-related permissions
	 * on anything).
	 *
	 * @param boolean Whether to use the GID 1 safety belt.
	 * @return boolean Whether this group is "special".
	 */
	function isSpecial($safetyBelt = true)
	{
		return $this->checkPermission(Group::getPermissionNames(true), false, $safetyBelt);
	}

	/**
	 * Gets the names of all valid permissions.
	 *
	 * @static
	 * @return string[]
	 */
	function getPermissionNames($specialOnly = false, $excludeAdmin = false)
	{
		$res = array('publish', 'view', 'edit', 'create', 'delete', 'copy', 'link', 'cert');
		if (!$excludeAdmin) {
			$res[] = 'admin';
			$res[] = 'export';
			$res[] = 'special';
		}
		if (!$specialOnly) {
			$res[] = 'portal';
			$res[] = 'preview';
			$res[] = 'run';
			$res[] = 'review';
		}
		return $res;
	}

	/**
	 * Returns all permissions on a certain Block object (pass NULL to check for
	 * global permissions).
	 *
	 * @return array Associative array mapping permission names to values.
	 */
	function getPermissions($target = NULL)
	{
		$res = array();

		if ($target) {
			$id = $target->getId();
		} else {
			$id = 0;
		}
		$dbh = $this->db->getAll("SELECT permission_name, permission_value FROM ".DB_PREFIX."group_permissions WHERE group_id=? AND block_id=?",
			array($this->get('id'), $id));
		foreach ($dbh as $row) {
			$res[$row['permission_name']] = $row['permission_value'];
		}
		return $res;
	}

	/**
	 * Sets the group's permissions on a certain Block object (pass NULL to
	 * set global permissions).
	 *
	 * @param array An associative array mapping permission names to values.
	 * @param mixed A Block object or NULL.
	 */
	function setPermissions($perms, $target = NULL)
	{
		if ($target) {
			$id = $target->getId();
		} else {
			$id = 0;
		}

		$this->db->query("DELETE FROM ".DB_PREFIX."group_permissions WHERE group_id=? AND block_id=?", array($this->get('id'), $id));
		foreach ($perms as $pname => $pvalue) {
			$this->db->autoExecute(DB_PREFIX.'group_permissions', array(
					'group_id' => $this->get('id'),
					'block_id' => $id,
					'permission_name' => $pname,
					'permission_value' => $pvalue,
				), DB_AUTOQUERY_INSERT);
		}
	}

	/**
	 * Set group name
	 *
	 * @param string New group name
	 * @param integer Id of the user how change the group
	 */
	function setGroupname($groupname, $userid)
	{
		$this->groupname = $groupname;
		$query = "UPDATE ".DB_PREFIX."groups SET groupname=?, t_modified=?, u_modified=? WHERE id=?";
		$res = $this->db->query($query, array($this->groupname, NOW, $userid, $this->get('id')));

		if ($this->db->isError($res))
		{
			return T("types.group.error.name_change");
		}

		return T("types.change.successful");
	}

	/**
	 * Set group description
	 *
	 * @param string New group description
	 * @param integer Id of the user how change the group
	 */
	function setDescription($description, $userid)
	{
		$this->description = $description;
		$query = "UPDATE ".DB_PREFIX."groups SET description=?, t_modified=?, u_modified=? WHERE id=?";
		$res = $this->db->query($query, array($this->description, NOW, $userid, $this->get('id')));

		if (PEAR::isError($res))
		{
			return T("types.group.error.description_change");
		}

		return T("types.change.successful");
	}

	/**
	 * Configure whether this is an auto-add group.
	 *
	 * @param boolean Whether new users should be added to this group
	 * @param integer ID of the user who is performing this change.
	 */
	function setAutoAdd($autoadd, $userid)
	{
		$this->autoAdd = $autoadd;
		$query = "UPDATE ".DB_PREFIX."groups SET autoadd=?, t_modified=?, u_modified=? WHERE id=?";
		$res = $this->db->query($query, array($this->autoAdd, NOW, $userid, $this->get('id')));

		if (PEAR::isError($res))
		{
			return T("types.group.error.auto_add");
		}

		return T("types.change.successful");
	}
}

?>
