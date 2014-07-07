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

// Include classes for email handling
libLoad('utilities::validateEmail');

// This might be the one place we need to include DataObject :(
require_once(CORE.'types/DataObject.php');
require_once(CORE.'types/Group.php');
require_once(CORE.'types/Test.php');

/**
 * This class handles user information. It includes methods for registering and
 * modifying user accounts.
 *
 * @package Core
 */
class User extends DataObject
{
	static protected $table = 'users';
	static protected $useSequence = true;

	static protected $prototype = array(
		'username' => array(),
		'full_name' => '',
		'password_hash' => array(),
		'email' => array(),
		'groups' => NULL,
		'lang' => NULL,
		'activation_key' => NULL,
		'confirmation_key' => NULL,
		'new_email' => NULL,
		'email_key' => NULL,
		'deleted' => 0,
		'blocked' => 0,
		'last_login' => NULL,
		'delete_time' => NULL,
		'privacy_policy_ok' => 0,
	);

	static protected $retrievalQueries = array(
		'login' => 'SELECT * FROM @T WHERE username = @{s:username} AND password_hash = @{s:password_hash}',
		'username' => 'SELECT * FROM @T WHERE username = @{*}',
		'activation_key' => 'SELECT * FROM @T WHERE activation_key = @{*}',
		'confirmation_key' => 'SELECT * FROM @T WHERE confirmation_key = @{*}',
		'email_key' => 'SELECT * FROM @T WHERE email_key = @{*}',
		'email' => 'SELECT * FROM @T WHERE email = @{*}',
	);

	static protected $manipulationQueries = array(
		'changeAges' => 'UPDATE @T SET age = @{i:newAge} WHERE fullname IN (@{oldNames})',
	);

	/**
	 * Creates a new user object.
	 */
	function __construct($data)
	{
		parent::__construct($data);
		if (isset($data['id'])) {
			$this->groups = DataObject::getBy('Group', 'userId', $data['id']);
		}
	}

	/**
	 * Return wheter or not the user has a activation key in database or not
	 *
	 * @return boolean
	 */
	function hasKey()
	{
		return (strlen($this->get('activation_key')) > 0);
	}

	/**
	 * Returns a list of names of groups the user is a member of.
	 *
	 * @return array List of group names.
	 */
	function getGroupnames()
	{
		$res = array();
		if (is_array($this->groups)) {
			foreach ($this->groups as $grp) {
				$res[] = $grp->get('groupname');
			}
		}
		return $res;
	}

	/**
	 * Returns a list of names of groups the user is a member of and group have access to the test ('run','view').
	 * @param test
	 * @return array List of group names.
	 */

	function getGroupnamesByPermissonToTest($testId)
	{
		$res = array();

		$Block = new test($testId);
		if (is_array($this->groups)) {
			foreach ($this->groups as $grp) {
				if (($grp->checkPermission(array("run", "portal"),$Block))) {
					$res[] = $grp->get('groupname');
				}
			}
		}
		return $res;
	}

	/**
	 * Returns a list of id of groups the user is a member of and group have access to the test ('run','view').
	 * @param test
	 * @return array List of group names.
	 */

	function getGroupIdsByPermissonToTest($testId)
	{
		$res = array();

		$Block = new test($testId);
		if (is_array($this->groups)) {
			foreach ($this->groups as $grp) {
				if (($grp->checkPermission(array("run", "portal"),$Block))) {
					$res[] = $grp->get('id');
				}
			}
		}
		return $res;
	}

	/**
	 * Returns the user's full name if set and the username otherwise
	 *
	 * @return string
	 */
	function getDisplayFullname()
	{
		if (trim($this->get('full_name')) == "") {
			return $this->get('username');
		}
		return $this->get('full_name');
	}
	/**
	 * Alias for getDisplayFullname.
	 */
	function getTitle()
	{
		return $this->getDisplayFullname();
	}

	/**
	 * Returns the groups the user is a member of.
	 *
	 * @return Array
	 */
	function getGroups()
	{
		return $this->groups;
	}

	/**
	 * Returns a list of the IDs of groups the user is member of.
	 *
	 * @return Array
	 */
	function getGroupIds()
	{
		$res = array();
		foreach ($this->groups as $grp) {
			$res[] = $grp->get('id');
		}
		return $res;
	}

	/**
	 * Changes the username
	 *
	 * @param string New username
	 * @param integer The user who performed the change
	 * @return array A list of errors; an empty array if successful
	 */
	function setUsername($username, $userid = NULL)
	{
		// default $uid value
		if (! $userid) $userid = $this->get('id');

		$errors = array();

		$this->validateUsername($username, $errors);

		if (! $errors)
		{
			$sql = "UPDATE ".DB_PREFIX."users SET username=?, t_modified=?, u_modified=? WHERE id=?";
			$input = array($username, NOW, $userid, $this->get('id'));
			$this->db->query($sql, $input);
			$this->invalidate();
		}

		return $errors;
	}
	/**
	 * Sets the email address
	 *
	 * @param string New e-mail address
	 * @param integer Activation key
	 * @param integer The user who performed the change
	 * @return array A list of errors; an empty array if successful
	 */
	function setEmail($email, $userid = NULL)
	{
		// default $uid value
		if (! $userid) $userid = $this->get('id');

		$errors = array();

		validateEmail($email, $errors);

		if (! $errors)
		{
			$sql = "UPDATE ".DB_PREFIX."users SET email=?, t_modified=?, u_modified=? WHERE id=?";
			$input = array($email, NOW, $userid, $this->get('id'));
			$this->db->query($sql, $input);
			$this->invalidate();
		}

		return $errors;
	}

	/**
	 * Sets the email address to use when the email_key is submitted
	 *
	 * @param string New e-mail address
	 * @param integer The user who performed the change
	 * @return array A list of errors; an empty array if successful
	 */
	function setNewEmail($email, $userid = NULL)
	{
		// default $uid value
		if (! $userid) $userid = $this->get('id');

		$errors = array();

		if ($email !== NULL) {
			validateEmail($email, $errors);
		}

		if (! $errors)
		{
			$sql = "UPDATE ".DB_PREFIX."users SET new_email=?, t_modified=?, u_modified=? WHERE id=?";
			$input = array($email, NOW, $userid, $this->get('id'));
			$res = $this->db->query($sql, $input);
			$this->invalidate();
			if (PEAR::isError($res)) {
				return $errors;
			}
			$this->new_email = $email;
		}

		return $errors;
	}

	/**
	 * Sets the email key to determine the validity of a new email address
	 *
	 * @param string The key
	 * @param integer The user who performed the change
	 */
	function generateEmailKey($userid = NULL)
	{
		// default $uid value
		if (! $userid) $userid = $this->get('id');

		libLoad("utilities::randomString");
		do {
			$key = randomString(16);
			$query = "SELECT COUNT(*) FROM ".DB_PREFIX."users WHERE email_key=?";
			$res = $this->db->query($query, array($key));
			list($count) = array_values($res->fetchRow());
		} while ($count > 0);

		$this->email_key = $key;
		$sql = "UPDATE ".DB_PREFIX."users SET email_key=?, t_modified=?, u_modified=? WHERE id=?";
		$input = array($key, NOW, $userid, $this->get('id'));
		$res = $this->db->query($sql, $input);
		$this->invalidate();
		return $key;
	}


	/**
	 * Resets the email key to NULL
	 *
	 * @param integer The user who performend this change (the target by default)
	 * @return array A list of errors; an empty array is successful
	 */
	function resetEmailKey($userid = NULL)
	{
		// default $uid value
		if (!$userid) $userid = $this->get('id');

		$errors = array();

		$query = "UPDATE ".DB_PREFIX."users SET email_key=?, t_modified=?, u_modified=? WHERE id=?";
		$this->db->query($query, array(NULL, NOW, $userid, $this->get('id')));

		return $errors;
	}

	/**
	 * Checks if this user has a certain kind of access to a certain target.
	 *
	 * @param string Permission type; see Group::checkPermission.
	 * @param mixed Target; see Group::checkPermission.
	 * @param boolean Whether to consider virtual permissions (i.e. UID 1
	 *   safety belt and owner check).
	 * @return mixed The value of the permission or NULL if permission is
	 *   denied.
	 */
	function checkPermission($type, $target = NULL, $useVirtual = true)
	{
		// Safety belt
		if ($useVirtual && $this->get('id') == SUPERUSER_ID) return true;

		// Check if we own the block
		if ($useVirtual && $target && ($owner = $target->getOwner()) && $owner->getId() == $this->getId()) return true;

		if ($this->get('deleted')!=0)
		return NULL;

		// Ask all groups
		if (!isset($this->groups)) return NULL;
		foreach ($this->groups as $grp) {
			$res = $grp->checkPermission($type, $target, $useVirtual);

			if ($res !== NULL) return $res;
		}
		return NULL;
	}

	/**
	 * Checks if this user is part of some special group.
	 * @return boolean
	 */
	function isSpecial($useVirtual = true)
	{
		if ($useVirtual && $this->get('id') == 1) return true;

		if (!isset($this->groups)) return NULL;
		foreach ($this->groups as $grp) {
			$res = $grp->isSpecial();
			if ($res) return true;
		}
		return false;
	}

	/**
	 * Checks if this user can edit at least one block.
	 * @return boolean
	 */
	function canEditSomething()
	{
		if ($this->checkPermission('edit', false)) return true;

		// See if there is a block we own
		$res = $this->db->getOne("SELECT id FROM ".DB_PREFIX."container_blocks
			WHERE owner = ? LIMIT 1", $this->getId());
		return ($res !== NULL);
	}

	function getUsername() { return $this->get('username'); }
	function getBday() { return $this->get('u_bday'); }
	function getFullname() { return $this->get('full_name'); }
	function getLanguage() { return $this->get('lang'); }
	function getConfirmationKey() { return $this->get('confirmation_key'); }
	function getEMail() {
		if ($this->get('new_email') == NULL)
		return $this->get('email');
		else
		return $this->get('new_email');
	}
	function getEmailKey() { return $this->get('email_key'); }

	/**
	 * Sets a new password and suspends the user, pending e-mail re-activation
	 *
	 * @param string New password
	 * @param string Second entry of password to match against
	 * @param string Old password
	 * @param integer User who performed the change (the target by default)
	 * @return array A list of errors; an empty array if successful
	 */
	function setPasswd($newPassword, $passwordControl, $oldPassword = NULL, $userid = NULL)
	{
		// default $uid value
		if (!$userid) $userid = $this->get('id');

		$errors = array();
		if ($oldPassword)
		{
			$oldPassword = md5('~~tmaker~'.$oldPassword);
			if ($oldPassword != $this->get('password_hash')) {
				$temp = $this->get('password_hash');
				$errors['password_old'] = T("types.user.error.password_old_false");
			}
		}
		elseif ($oldPassword == NULL)
		$errors['password_old'] = T("types.user.error.password_old_false");

		$this->validatePassword($newPassword, $passwordControl, $errors);

		if (!$errors)
		{
			$newPassword = md5('~~tmaker~'.$newPassword);
			$output = array($newPassword, NOW, $userid, $this->get('id'));
			$query = "UPDATE ".DB_PREFIX."users SET password_hash=?, t_modified=?, u_modified=? WHERE id=?";

			$res = $this->db->query($query, $output);
			$this->invalidate();
		}
		return $errors;
	}

	/**
	 * Sets a new password and suspends the user, pending e-mail re-activation
	 *
	 * @param string New password
	 * @param string Second entry of password to match against
	 * @return array A list of errors; an empty array if successful
	 * @param integer User who performed the change (the target by default)
	 */
	function setPasswdWithoutOld($newPassword, $passwordControl,  $userid = NULL)
	{
		// default $uid value
		if (!$userid) $userid = $this->get('id');

		$errors = array();

		$this->validatePassword($newPassword, $passwordControl, $errors);

		if (!$errors)
		{
			$newPassword = md5('~~tmaker~'.$newPassword);
			$output = array($newPassword, NOW, $userid, $this->get('id'));
			$query = "UPDATE ".DB_PREFIX."users SET password_hash=?, t_modified=?, u_modified=? WHERE id=?";

			$this->db->query($query, $output);
			$this->invalidate();
		}
		return $errors;
	}

	/**
	 * Change a user's group memberships
	 *
	 * @param array List of group IDs
	 * @param integer The user who performed the change
	 * @return boolean True if successful.
	 */
	function setGroups($gids, $userid)
	{
		$res = $this->db->query("DELETE FROM ".DB_PREFIX."groups_connect WHERE user_id=?", array($this->get('id')));
		if (PEAR::isError($res)) return false;

		foreach ($gids as $gid) {
			$res = $this->db->query("INSERT INTO ".DB_PREFIX."groups_connect VALUES (?, ?)", array($this->get('id'), $gid));
			if (PEAR::isError($res)) return false;
		}
		$query = "UPDATE ".DB_PREFIX."users SET t_modified=?, u_modified=? WHERE id=?";
		$res = $this->db->query($query, array(NOW, $userid, $this->get('id')));
		$this->invalidate();

		return !(PEAR::isError($res));
	}

	/**
	 * Change the user's fullname
	 *
	 * @param string The new Fullname
	 * @param integer The user who performend this change (the target by default)
	 * @return array A list of errors; an empty array is successful
	 */
	function setFullname($fullname, $userid = NULL)
	{
		// default $userid value
		if (!$userid) $userid = $this->get('id');

		$errors = array();
		$this->fullname = $fullname;
		$query = "UPDATE ".DB_PREFIX."users SET full_name=?, t_modified=?, u_modified=? WHERE id=?";

		$this->db->query($query, array($this->fullname, NOW, $userid, $this->get('id')));

		return $errors;
	}

	/**
	 * Generates a new confirmation key
	 *
	 * @param integer The user who performend this change (the target by default)
	 * @return array A list of errors; an empty array is successful
	 */
	function generateConfirmationKey($userid = NULL)
	{
		// default $uid value
		if (!$userid) $userid = $this->get('id');

		$errors = array();

		libLoad("utilities::randomString");
		$key = randomString(16);

		//define('DBO_CURRENT_USER', $userid);
		$this->set('confirmation_key', $key);
		$this->commit();

		return $errors;
	}

	/**
	 * Resets the confirmation key to NULL
	 *
	 * @param integer The user who performend this change (the target by default)
	 * @return array A list of errors; an empty array is successful
	 */
	function resetConfirmationKey($userid = NULL)
	{
		// default $uid value
		if (!$userid) $userid = $this->get('id');

		$errors = array();

		$query = "UPDATE ".DB_PREFIX."users SET confirmation_key=?, t_modified=?, u_modified=? WHERE id=?";
		$this->db->query($query, array(NULL, NOW, $userid, $this->get('id')));
		$this->invalidate();

		return $errors;
	}

	/**
	 * Validates a username
	 *
	 * @param string Username to validate
	 * @param array Reference to $errors array
	 */
	function validateUsername(&$username, &$errors)
	{
		$username = trim($username);
		if (strlen($username) < NAME_MIN_CHARS)
		{
			$errors['username'] = T("types.user.error.username_too_short");
		}
		if (strlen($username) > NAME_MAX_CHARS)
		{
			$errors['username'] = T("types.user.error.username_too_long");
		}
		if (User::nickInDB($username))
		{
			$errors['username'] = T("types.user.error.username_exists");
		}
	}

	/**
	 * Validates a user's full name against our limitations
	 *
	 * @param string Full username to validate
	 * @param string[] Reference to $errors array
	 */
	function validateFullname(&$fullname, &$errors)
	{
		$fullname = trim($fullname);
		if (strlen($fullname) < FULL_MIN_CHARS)
		{
			$errors['fullname'] = T("types.user.error.fullname_too_short");
		}
		if (strlen($fullname) > FULL_MAX_CHARS)
		{
			$errors['fullname'] = T("types.user.error.fullname_too_long");
		}
	}

	/**
	 * Makes sure that a given password conforms to our restrictions
	 *
	 * @param string Password to validate
	 * @param string Password to match against
	 * @param string[] Reference to $errors array
	 */
	function validatePassword($password, $passwordControl, &$errors)
	{
		if (strlen($password) < PASS_MIN_CHARS)
		{
			$errors['password'] = T("types.user.error.password_too_short");
		}
		if ($password != $passwordControl)
		{
			$errors['password_control'] = T("types.user.error.password_mismatch");
		}
	}

	/**
	 * Checks if a username already exists in the database
	 *
	 * @param string Username
	 * @return boolean TRUE if username exists
	 */
	function nickInDB($username)
	{
		$db = $GLOBALS['dao']->getConnection();

		$query = "SELECT username FROM ".DB_PREFIX."users WHERE username=?";
		$res = $db->query($query, array($username));

		if (!PEAR::isError($res) && $res->numRows() > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Expires this object in the object cache.
	 */
	function invalidate()
	{
		unstore('User', $this->get('id'));
	}
	
	function setLastLogin()
	{
		$db = $GLOBALS['dao']->getConnection();

		$query = "UPDATE ".DB_PREFIX."users SET last_login=? WHERE id=?";
		$db->query($query, array(time(), $this->getId()));
	}
	
	function setDeleteTime($deleteTime)
	{
		$db = $GLOBALS['dao']->getConnection();

		$query = "UPDATE ".DB_PREFIX."users SET delete_time = ? WHERE id = ?";
		$db->query($query, array($deleteTime, $this->getId()));
	}

	/**
	 * Returns the version of latest Privacy Policy the user has accepted
	 *
	 * @return int (timestamp)
	 */
	function getPrivacyPolicyAcc()
	{
		return $this->get('privacy_policy_ok');
	}
	
	/**
	 * Set the version of the Privacy Policy wich user has accepted
	 *
	 * @return bool
	 */
	function setPrivacyPolicyAcc($pp_acc_version, $userid=NULL)
	{
		// default $uid value
		if (!$userid) $userid = $this->get('id');
		
		$query = "UPDATE ".DB_PREFIX."users SET privacy_policy_ok = ".$pp_acc_version." WHERE id=".$userid;
		$res = $this->db->query($query);
		
		return $res;
	}
	
}

