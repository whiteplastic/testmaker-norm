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
 * Handles global settings.
 *
 * @package Core
 */
class Setting
{

	/**
	 * Gets a setting from the database.
	 * @param string Name of the setting
	 * @param boolean Whether to use cached values
	 * @return string
	 * @static
	 */
	function get($key, $cached = true)
	{
		return Setting::_handle($key, false, $cached);
	}

	/**
	 * Changes or creates a setting.
	 * @param string Name of the setting
	 * @param string Value of the setting
	 * @return boolean
	 * @static
	 */
	function set($key, $value)
	{
		return Setting::_handle($key, $value);
	}

	/**
	 * Deletes a setting.
	 * @param string Name of the setting
	 * @return boolean
	 * @static
	 */
	function delete($key)
	{
		return Setting::_handle($key, NULL);
	}

	/**
	 * Deals with getting/changing/deleting settings.
	 * @access private
	 * @static
	 */
	function _handle($key, $value = false, $cached = true)
	{
		static $settings = array();
		$db = &$GLOBALS['dao']->getConnection();

		if ($value === false) {
			// Get
			if ($cached && isset($settings[$key])) return $settings[$key];
			$res = $db->getOne("SELECT content FROM ".DB_PREFIX."settings WHERE name = ?", array($key));
			if (PEAR::isError($res)) return false;
			$settings[$key] = $res;
			return $res;
		} elseif ($value === NULL) {
			// Delete
			unset($settings[$key]);
			$res = $db->query("DELETE FROM ".DB_PREFIX."settings WHERE name = ?", array($key));
			return !PEAR::isError($res);
		} else {
			$res = $db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."settings WHERE name = ?", array($key));
			if (PEAR::isError($res)) return false;
			$settings[$key] = $value;
			if ($res == 1) {
				$res = $db->query("UPDATE ".DB_PREFIX."settings SET content = ? WHERE name = ?", array($value, $key));
				return !PEAR::isError($res);
			} else {
				$res = $db->query("INSERT INTO ".DB_PREFIX."settings VALUES(?, ?)", array($key, $value));
				return !PEAR::isError($res);
			}
		}
	}
}

