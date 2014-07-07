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

/**
 * Include the User class
 */
require_once(dirname(__FILE__).'/User.php');
require_once(dirname(__FILE__).'/Group.php');
require_once(dirname(__FILE__).'/EditLog.php');

/**
 * UserList class
 *
 * @package Core
 */
class UserList
{
	/**#@+
	 *
	 * @access private
	 */
	var $db;
	/**#@-*/

	/**
	 * Constructor, set database connection
	 */
	function UserList()
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		// Delete unactivated accounts
		$query = $this->db->query("SELECT id FROM ".DB_PREFIX."users WHERE (activation_key IS NOT NULL AND CHAR_LENGTH(activation_key)=32) AND t_created < ".strtotime("-7 days 00:00:00", NOW));
		while ($query->fetchInto($res)) {
			$this->deleteUser($res["id"], -1, FALSE);
		}
	}

	/**
	 * get user from database by id
	 *
	 * @param string Email key
	 * @return boolean Wether the user was found or not
	 */
	function getUserById($id)
	{
		$user = DataObject::getById('User', $id);
		if (!$user || !$user->get('id'))
		{
			return false;
		}
		return $user;
	}

	/**
	 * get user from database by name
	 *
	 * @param string Username
	 * @return boolean Wether the user was found or not
	 */
	function getUserByName($name)
	{
		$user = DataObject::getOneBy('User', 'username', $name);
		if(!$user)
		{
			return false;
		}
		return $user;
	}

	/**
	 * get user from database by username and password
	 *
	 * @param string Username or E-Mail
	 * @param string Password
	 * @return boolean Wether or not login was successful
	 */
	function getUserByLogin($name, $password)
	{
		$password = md5('~~tmaker~'. $password);
		$user = DataObject::getOneBy('User', 'login', array('username' => $name, 'password_hash' => $password));
		
		/* Feature to be able to login with e-mail/password, not yet used as e-mail is NOT unique
		if (!$user || !$user->get('id'))
		{
			$tempuser = DataObject::getOneBy('User', 'email', $name);
			if (!($tempuser==NULL))
			{
				$name = $tempuser->getUsername();
				$user = DataObject::getOneBy('User', 'login', array('username' => $name, 'password_hash' => $password));
			}
			$tempuser = NULL;
			
		}*/

		if (!$user || !$user->get('id'))
		{
			$GLOBALS["MSG_HANDLER"]->addMsg("types.user.invalid_login", MSG_RESULT_NEG);
			return false;
		}
		if ($user->hasKey())
		{
			if ($user->get('blocked') == 1)
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("types.user.account_deactivated", MSG_RESULT_NEG);
			}
			elseif ($user->get('deleted') == 1)
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("types.user.account_deleted", MSG_RESULT_NEG);
			}
			else
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("types.user.account_activation_needed", MSG_RESULT_NEG);
			}
			return false;
		}
		return $user;
	}

	/**
	 * get user from database by email key
	 *
	 * @param string Email key
	 * @return boolean Wether the user was found or not
	 */
	function getUserByEmailKey($key)
	{
		$user = DataObject::getOneBy('User', 'email_key', $key);
		if (!is_object($user))
			return false;
		if (!$user->get('id'))
			return false;
		return $user;
	}

	/**
	 * get user from database by email address
	 *
	 * @param string Email address
	 * @return boolean Wether the user was found or not
	 */
	function getUserByEmail($email)
	{
		$user = DataObject::getOneBy('User', 'email', $email);
		if (($user==NULL)||(!$user->get('id')))
		{
			return false;
		}
		return $user;
	}
	
	function getUsersByEmail($email)
	{
		$user = DataObject::getBy('User', 'email', $email);
		if (($user==NULL))
		{
			return false;
		}
		return $user;
	}
	
	function getUsernameByEmail($email)
	{
		$user = DataObject::getOneBy('User', 'email', $email);
		
		if (($user==NULL)||(!$user->get('id')))
		{
			return false;
		}
		return $user->getUsername();
	}
	
	/**
	 * get user from database by confirmation key
	 *
	 * @param string Username
	 * @return boolean Wether the user was found or not
	 */
	function getUserByConfirmationKey($key)
	{
		$user = DataObject::getOneBy('User', 'confirmation_key', $key);
		if (!$user->get('id'))
		{
			return false;
		}
		return $user;
	}

	/**
	 * Validates registry informations and writes user to the database with default usergroup
	 *
	 * @param string Username
	 * @param string Password
	 * @param string Password to control first password entry
	 * @param string Optional fullusername
	 * @param string Emailaddress
	 * @param string Preferred language
	 * @param array Group ids to connect
	 * @param string Registration key
	 * @param integer Id of the user who registered the new user, 0 if user registered by themselves
	 * @return array Return FALSE if registration was successful
	 */
	function registerUser($username, $password, $passwordControl, $fullname = NULL, $email, $language, $gids = array(), $key = NULL, $userid = 0, $birthday = NULL, $timestamp = NULL)
	{
		$errors = array();

		$tmpErrors = array();

		User::validateUsername($username, $errors);
		User::validatePassword($password, $passwordControl, $errors);
		User::validateFullname($fullname, $errors);
		validateEmail($email, $tmpErrors);
		//add eMail errors to associative errors array
		foreach($tmpErrors as $tmpError) {
			$errors['email'] = $tmpError;
		}

		if (!$errors)
		{
			$password = md5('~~tmaker~'. $password);
			$user = DataObject::create('User', array(
				'username' => $username,
				'password_hash' => $password,
				'full_name' => $fullname,
				'email' => $email,
				'lang' => $language,
				'activation_key' => $key,
				'@update_audit' => true,
				'u_bday' => $birthday,
				'form_filled' => $timestamp,
			));

			if ($gids)
			{
				$user->setGroups($gids, $userid);
			}
			else
			{
				$groups = $this->getGroupList(true);
				$gids = array();
				foreach ($groups as $grp) {
					$gids[] = $grp->getId();
				}
				$user->setGroups($gids, 0);
			}
		}

		return $errors;
	}

	/**
	 * Confirm registration
	 *
	 * @param integer Registration key
	 * @return string Errormessage
	 */
	function confirmUser($key)
	{
		$error = "";

		$query = "UPDATE ".DB_PREFIX."users SET activation_key=NULL, t_modified=?, u_modified='0' WHERE activation_key=?";
		$this->db->query($query, array(NOW, $key));

		if ($this->db->affectedRows() < 1) {
			$error = T("types.user_list.confirmation_failed");
		}

		return $error;
	}

	/**
	 * Deactivate a User
	 *
	 * @param integer Deactivated userid
	 * @param integer Executers userid
	 * @return string Errormessage
	 */
	function blockUser($user, $id)
	{
		if ($user < 2) {
			return T("types.user_list.error.block_special");
		}
		if ($user != $id)
		{
			$error = "";

			$query = "UPDATE ".DB_PREFIX."users SET blocked=1, activation_key=?, t_modified=?, u_modified=? WHERE id=?";
			$this->db->query($query, array($user, NOW, $id, $user));

			if ($this->db->affectedRows() != 1) {
				trigger_error("Blocking user ".htmlspecialchars($user)," affected more than one database row.", E_USER_ERROR);
			}
		}
		else
		{
			$error = T("types.user_list.error.self_block");
		}

		return $error;
	}

	/**
	 * Activate a User
	 *
	 * @param integer Activated userid
	 * @param integer Executers userid
	 * @return string Errormessage
	 */
	function freeUser($user, $id)
	{

		$query = "UPDATE ".DB_PREFIX."users SET activation_key=NULL, blocked=NULL, t_modified=?, u_modified=? WHERE id=?";
		$this->db->query($query, array(NOW, $id, $user));
	}

	function _processUserQuery($fields, $groupid, $user, $full, $email, $uid, $lastLogin = NULL, $deleteTime = NULL, $offset = NULL, $limit = NULL)
	{
		$quote = array();

		$query = "SELECT $fields FROM ".DB_PREFIX."users AS u "; 
		if($groupid != 0)
		{
			$query .= "LEFT JOIN ".DB_PREFIX."groups_connect AS gc ON (u.id = gc.user_id) ";
		}
		$query .= "WHERE u.id <> 0 AND (deleted <> 1 OR deleted IS NULL)";

		if ($groupid != 0 && $user)
		{
			$quote[] = $groupid;
			$quote[] = "%".$user."%";
			$query .= " AND (group_id = ? AND username like ?)";
		}
		elseif ($groupid != 0 && $full)
		{
			$quote[] = $groupid;
			$quote[] = "%".$full."%";
			$query .= " AND (group_id = ? AND full_name like ?)";
		}
		elseif ($groupid != 0 && $email)
		{
			$quote[] = $groupid;
			$quote[] = "%".$email."%";
			$query .= " AND (group_id = ? AND email like ?)";
		}
		elseif ($groupid != 0 && $uid)
		{
			$quote[] = $groupid;
			$quote[] = (int)$uid;
			$query .= " AND (group_id = ? AND id = ?)";
		}
		elseif ($groupid != 0 && $lastLogin )
		{
			$quote[] = $groupid;
			$quote[] = (int)$lastLogin;
			$quote[] = (int)$lastLogin;
			$quote[] = (int)$lastLogin;
			$query .= " AND (group_id = ? AND ((last_login < ? AND t_created < ?) OR (last_login IS NULL AND t_created < ?)))";
		}
		elseif ($groupid == 0 && $lastLogin && $deleteTime == NULL)
		{
			$quote[] = (int)$lastLogin;
			$quote[] = (int)$lastLogin;
			$quote[] = (int)$lastLogin;
			$query .= " AND ((last_login < ? AND t_created < ?) OR (last_login IS NULL AND t_created < ?)) AND (delete_time IS NULL)";
		}
		elseif ($groupid == 0 && $lastLogin)
		{
			$quote[] = (int)$lastLogin;
			$quote[] = (int)$lastLogin;
			$quote[] = (int)$lastLogin;
			$query .= " AND ((last_login < ? AND t_created < ?) OR (last_login IS NULL AND t_created < ?))";
		}
		elseif ($deleteTime != NULL)
		{
			$quote[] = (int)$deleteTime;
			$query .= " AND (delete_time < ?)";
		}
		elseif ($groupid != 0)
		{
			$quote[] = $groupid;
			$query .= " AND (group_id = ?)";
		}
		elseif ($user)
		{
			$quote[] = "%".$user."%";
			$query .= " AND (username like ?)";
		}
		elseif ($full)
		{
			$quote[] = "%".$full."%";
			$query .= " AND (full_name like ?)";
		}
		elseif ($email)
		{
			$quote[] = "%".$email."%";
			$query .= " AND (email like ?)";
		}
		elseif ($uid)
		{
			$quote[] = (int)$uid;
			$query .= " AND (id = ?)";
		}

		if ($fields == '*') $query .= " GROUP BY u.id";
		$query .= " ORDER BY username";

		if ($offset !== NULL || $limit !== NULL) {
			$query .= " LIMIT ?,?";
			$quote[] = $offset;
			$quote[] = $limit;
		}
		return $this->db->query($query, $quote);
	}

	function countUsers($groupid = 0, $user = NULL, $full = NULL, $email = NULL, $uid = NULL, $lastLogin = NULL, $deleteTime = NULL)
	{
		$dres = $this->_processUserQuery('COUNT(u.id) AS cnt', $groupid, $user, $full, $email, $uid, $lastLogin, $deleteTime);
		$res = $dres->fetchRow();
		$res = $res['cnt'];
		$dres->free();

		return $res;
	}

	/**
	 * Return a list of all users in the testMaker database
	 *
	 * @param integer Id of desired group
	 * @param string Sorting characteristic
	 * @return array User list for desired group
	 */
	function getUserList($groupid = 0, $user = NULL, $full = NULL, $email = NULL, $uid = NULL, $lastLogin = NULL, $deleteTime = NULL, $offset = NULL, $limit = NULL)
	{

		$userList = array();
		$res = $this->_processUserQuery('*', $groupid, $user, $full, $email, $uid, $lastLogin, $deleteTime, $offset, $limit);

		while($row = $res->fetchRow())
		{
			$userList[] = DataObject::getById('User', $row['id']);
		}
		return $userList;
	}

	/**
	 * Delete user from database
	 *
	 * @param integer Deleted User ID
	 * @param integer User ID
	 * @return string Result of the delete query
	 */
	function deleteUser($user, $id, $simulate = TRUE)
	{
		if ($user < 1 || $user == SUPERUSER_ID) {
			return array("types.user_list.delete_user.special_account", MSG_RESULT_NEG);
		}

		if ($user == $id) {
			return array("types.user_list.delete_user.error", MSG_RESULT_NEG);
		}

		if ($simulate)
		{
			$query = "UPDATE ".DB_PREFIX."users SET password_hash='0', deleted=1, username=?, full_name='DEL', email='DEL', t_modified=?, u_modified=? WHERE id=?";
			$this->db->query($query, array('DEL'.$user, NOW, $id, $user));
		}
		else
		{
			$query = "DELETE FROM ".DB_PREFIX."groups_connect WHERE user_id=?";
			$this->db->query($query, array($user));
			$query = "DELETE FROM ".DB_PREFIX."users WHERE id=?";
			$this->db->query($query, array($user));
		}

		return array("types.user_list.delete_user.success", MSG_RESULT_POS);
	}

	/**
	 * Create a new Group
	 *
	 * @param string Name of new group
	 * @param string Group description
	 * @param integer ID of user creating the group
	 */
	function createGroup($groupname, $description, $autoadd, $userid = 0)
	{
		$id = $this->db->nextId(DB_PREFIX.'groups');
		
		//check if groupname already exists if yes add a number after the name
		$res = true;
		$i = 1;
		$oldgroupname = $groupname;
		while ($res) {
			$query = "SELECT * FROM ".DB_PREFIX."groups WHERE `groupname` = ? LIMIT 1";
			$res = $this->db->getOne($query, array($groupname));
			if ($res) {
				$groupname = $oldgroupname." $i";
				$i++;
			}
		}
		
		$query = "INSERT INTO ".DB_PREFIX."groups (id, groupname, description, autoadd, t_created, u_created, t_modified, u_modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$res = $this->db->query($query, array($id, $groupname, $description, $autoadd, NOW, $userid, NOW, $userid));

		if (!PEAR::isError($res)) {
			return DataObject::getById('Group',$id);
		}

		return NULL;
	}

	/**
	 * Create a list of all groups in the testMaker installation
	 *
	 * @param boolean True if only auto-add groups should be returned.
	 * @return array List of all Groups
	 */
	function getGroupList($autoAddOnly = false, $includeVirtual = false)
	{
		if ($autoAddOnly && !$includeVirtual)
			$groupList = DataObject::getBy('Group','listAutoaddOnly');
		else if ($autoAddOnly && $includeVirtual)
			$groupList = DataObject::getBy('Group','listAutoaddOnlyVirtual');
		else if ($includeVirtual) 
			$groupList = DataObject::getBy('Group','listVirtual');
		else  
		   $groupList = DataObject::getBy('Group','list');
		
		return $groupList;
	}

	/**
	 * Delete Group from database
	 *
	 * @param integer Group ID
	 * @return string Result of the delete query
	 */
	function deleteGroup($gid)
	{
		$res = $this->db->query("DELETE FROM ".DB_PREFIX."groups_connect WHERE group_id=?", array($gid));
		if (PEAR::isError($res)) return false;

		$res = $this->db->query("DELETE FROM ".DB_PREFIX."group_permissions WHERE group_id=?", array($gid));
		if (PEAR::isError($res)) return false;

		$query = "DELETE FROM ".DB_PREFIX."groups WHERE id=?";
		$res = $this->db->query($query, array($gid));

		if (PEAR::isError($res)) return false;

		$GLOBALS["MSG_HANDLER"]->addMsg("types.user_list.delete_group.success", MSG_RESULT_POS);
		return true;
	}
	
	//b/ not needed anymore!!
	/**
	 * Set privacy-policy-flag privacy_policy_ok to 0 for all users
	 *
	 * @return string Result of the db query
	*/
	function resetPrivacyPolicyFlag()
	{
		$query = "UPDATE ".DB_PREFIX."users SET privacy_policy_ok = 0 WHERE 1";
		return $this->db->query($query);
	}	 
	
}

?>
