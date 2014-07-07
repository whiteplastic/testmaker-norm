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
 * Handles privacy policy.
 *
 * @package Core
 */
class PrivacyPolicy
{

	
	/**
	 * Gets the current Version of Privacy Policy
	 * @return int
	 */
	function getCurrentVersion()
	{
		$db = &$GLOBALS['dao']->getConnection();

		$res = $db->getOne("SELECT content FROM ".DB_PREFIX."settings WHERE name = 'curr_privacy_policy'");
		if (PEAR::isError($res)) return 0;
		
		return $res;
	}
	
	
	/**
	 * Sets version number of current Privacy Policy
	 * @param int new version
	 * @return int
	 */
	function setCurrentVersion($new_version)
	{
		$db = &$GLOBALS['dao']->getConnection();
		
		$res = $db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."settings WHERE name = 'curr_privacy_policy'");
		if (PEAR::isError($res)) return false;

		$exp_range = $db->getOne("SELECT exp_range FROM ".DB_PREFIX."privacy_policys WHERE version = ".$new_version);

		//close current policy
		$timestamp = NOW;
		$currentVersion = PrivacyPolicy::getCurrentVersion();
		if ($currentVersion && $new_version == 0)	
			$res = $db->query("UPDATE ".DB_PREFIX."privacy_policys SET closed = ? WHERE version = ?", array($timestamp, $currentVersion));
		
		
		if ($res == 1) {
			$res = $db->query("UPDATE ".DB_PREFIX."settings SET content = ? WHERE name = 'curr_privacy_policy'", array($new_version));
			EditLog::addEntry(LOG_OP_PRIVACY_POLICY, $new_version, $exp_range);
			return !PEAR::isError($res);
		} else {
			$res = $db->query("INSERT INTO ".DB_PREFIX."settings VALUES(?, ?)", array('curr_privacy_policy', $new_version));
			EditLog::addEntry(LOG_OP_PRIVACY_POLICY, $new_version, $exp_range);
			return !PEAR::isError($res);
		}	
	}
	
	
	/**
	 * Gets number of users for wich the specific Privacy Policy is valid
	 * @return int
	 */
	function getUserCount($version)
	{
		$db = &$GLOBALS['dao']->getConnection();

		if ($res = $db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."users WHERE privacy_policy_ok = ".$version))
			return $res;
		else 
			return 0;
	}
	
	
	/**
	 * Gets the creation date of a specific Privacy Policy
	 * @return timestamp
	 */
	function getCreationDate($version)
	{
		$db = &$GLOBALS['dao']->getConnection();

		if ($res = $db->getOne("SELECT t_stamp FROM ".DB_PREFIX."privacy_policys WHERE version = ".$version))
			return $res;
		else 
			return 0;
	}
	
	/**
	 * Gets the date when specific Privacy Policy became invalid
	 * @return timestamp
	 */
	function getClosingDate($version)
	{
		$db = &$GLOBALS['dao']->getConnection();

		if ($res = $db->getOne("SELECT closed FROM ".DB_PREFIX."privacy_policys WHERE version = ".$version))
			return $res;
		else 
			return 0;
	}
		
	/**
	 * Gets the expiration range in month of a specific Privacy Policy
	 * @return data validity time in month
	 */
	function getExpirationRange($version)
	{
		$db = &$GLOBALS['dao']->getConnection();

		if (!$version) 
			$version = 0;
		
		if ($res = $db->getOne("SELECT exp_range FROM ".DB_PREFIX."privacy_policys WHERE version = ".$version))
			return $res;
		else 
			return 0;
	
	}
	
	
	/**
	 * Returns the last available and valid privacy policy version
	 * even when the current version is 0 (Policy Deactivated)
	 *  
	 * @return version integer
	 */
	function getVersionBeforeDeactivation()
	{
		$db = &$GLOBALS['dao']->getConnection();
		
		$latest_entry = $db->query("SELECT * FROM ".DB_PREFIX."privacy_policys ORDER BY t_stamp DESC LIMIT 0 , 1 ")->fetchRow();
		if (PEAR::isError($latest_entry)) return 0;
		
		return $latest_entry['version'];
	}
		
	/**
	 * Saves a new Privacy Policy version
	 * @param string content of policy (html)
	 * @param int: expiration_range
	 * @return string
	 */
	function addPrivacyPolicy($pp_content, $expiration_range)
	{
		$db = &$GLOBALS['dao']->getConnection();
		
		$timestamp = NOW;
		$getlatest = $db->query("SELECT * FROM ".DB_PREFIX."privacy_policys ORDER BY t_stamp DESC LIMIT 0 , 1 ")->fetchRow();
		$new_vers = $getlatest['version'] + 1; 	
	
		$currentVersion = PrivacyPolicy::getCurrentVersion();
		
		//if current version exists (enabled), close it
		if ($currentVersion != 0)		
			$res = $db->query("UPDATE ".DB_PREFIX."privacy_policys SET closed = ? WHERE version = ?", array($timestamp, $currentVersion));

			
		$res = $db->query("INSERT INTO ".DB_PREFIX."privacy_policys VALUES(?, ?, ?, ?, ?)", array($new_vers, $pp_content, $timestamp, $expiration_range, 0));	
		if (PEAR::isError($res)) return 0;	
		
		return PrivacyPolicy::setCurrentVersion($new_vers);
	}	
	
	/**
	 * Gets the content of the current Privacy Policy
	 * @return string (html)
	 */
	function getPolicyContent($version = FALSE)
	{
		$db = &$GLOBALS['dao']->getConnection();
		
		if(!$version)
			$curr_vers = PrivacyPolicy::getCurrentVersion();
		else
			$curr_vers = $version;
		
		if ($curr_vers != 0){			
			$res = $db->getOne("SELECT content FROM ".DB_PREFIX."privacy_policys WHERE version = ".$curr_vers);
			if (PEAR::isError($res)) return 0;
		} else {
			return 0;	
		} 
		
		return $res;
	}
	
}

?>
