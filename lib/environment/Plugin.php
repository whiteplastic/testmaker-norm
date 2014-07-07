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
 * Provides commonly used functions for enumerating and calling plugins.
 *
 * @package Library
 */

define('PLUGINS', ROOT.'upload/plugins/');

libLoad('utilities::snakeToCamel');

$PLUGIN_TYPES = array();

/**
 * A set of functions that are used for plugin management, and the superclass
 * for plugins.
 */
class Plugin
{
	/**
	 * @access private
	 * @static
	 */
	function _checkSanity($type)
	{
		return !preg_match('/[^\w]/', $type);
	}

	/**
	 * Registers a new type of plugin.
	 * @static
	 */
	function register($type, $prefix)
	{
		global $PLUGIN_TYPES;

		$PLUGIN_TYPES[$type] = array(
			'type'		=> $type,
			'prefix'	=> $prefix,
		);
	}

	/**
	 * Enumerates all plugins of a given type.
	 * @return string[]
	 * @static
	 */
	function getAllByType($type)
	{
		global $PLUGIN_TYPES;

		$path = PLUGINS.$type;
		if (!is_dir($path)) {
			trigger_error("Failed to enumerate `$type' plugins; directory missing");
		}

		$dir = opendir($path);
		$result = array();
		while (FALSE !== ($file = readdir($dir))) {
			if (!preg_match('/^'. $PLUGIN_TYPES[$type]['prefix'] .'(\w+)\.php$/', $file, $matches)) continue;
			$result[] = camelToSnake($matches[1]);
		}
		return $result;
	}

	/**
	 * Check whether a given plugin exists.
	 * @param string The type of plugin.
	 * @param string The name of the plugin.
	 * @return boolean
	 * @static
	 */
	function exists($type, $name)
	{
		global $PLUGIN_TYPES;
		if (!Plugin::_checkSanity($name)) return false;

		$className = $PLUGIN_TYPES[$type]['prefix'] . snakeToCamel($name);
		$incFile = PLUGINS . $type .'/'. $className .'.php';
		return file_exists($incFile);
	}

	/**
	 * Loads and initializes a given plugin.
	 * @param string The type of plugin.
	 * @param string The name of the plugin.
	 * @param mixed[] Parameters to pass to the plugin's init() method.
	 * @static
	 */
	function load($type, $name, $params = array())
	{
		global $PLUGIN_TYPES;
		if (!Plugin::exists($type, $name)) {
			trigger_error("Attempted to load `$type' plugin `$name' which does not exist");
			return NULL;
		}

		$className = $PLUGIN_TYPES[$type]['prefix'] . snakeToCamel($name);
		$incFile = PLUGINS . $type .'/'. $className .'.php';

		require_once($incFile);
		$obj = new $className();
		$obj->setPath(PLUGINS . $type .'/'. $className .'/');
		$obj->_initInternal($name);
		if (count($params) > 0) call_user_func_array(array(&$obj, 'init'), $params);

		return $obj;
	}

	/**
	 * @access private
	 */
	function _initInternal($name)
	{
		if (is_dir($this->path)) {
			$this->tpl = new Sigma($this->path);
		}
		if (isset($this->lang)) {
			foreach ($this->lang as $key => $value) {
				addT($key, $value);
			}
		}
		$this->name = $name;
	}

	/**
	 * @access private
	 */
	function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Returns the path containing plugin-specific files.
	 */
	function getPath()
	{
		return $this->path;
	}
}

