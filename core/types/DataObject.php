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

define('DBO_BYPASS_CACHE', 'bpc');
define('DBO_BYPASS_ERROR', 'bpe');
define('DBO_CACHE_LIST', 'cl');
define('DBO_FETCH_LEVEL', 'fl');
define('DBO_LIMIT', 'l');
define('DBO_OFFSET', 'ofs');
define('DBO_ORDER', 'ord');
define('DBO_SINGLE', 's');

/**
 * Provides common behavior for objects that are stored in the database.
 * @package Core
 */
class DataObject
{
	static $db;

	// Constructor {{{1
	function __construct($data = array())
	{
		$this->db = self::$db;
		$this->fields = array();
		$this->pending = array();

		$class = get_class($this);
		$this->prototype = self::getPrototype($class);
if (!is_array($data)) {
	echo "<pre>"; print_r(debug_backtrace());
	die();
}
		foreach ($data as $key => $val) {
			if ($key != 'id' && !array_key_exists($key, $this->prototype))
				die("Failed creating instance of '$class' with invalid ".
					"field: '$key' (not in prototype)");
			$this->fields[$key] = call_user_func(array($class, 'autoConvert'), $key, $val);
		}
	}

	// Type information {{{1
	static protected $table = NULL;
	static protected $useSequence = TRUE;
	static protected $useAuditing = TRUE;

	static protected $retrievalQueries = array(
		'all' => 'SELECT * FROM @T',
		'fields' => 'SELECT * FROM @T WHERE @{&:*}',
		'ids' => 'SELECT * FROM @T WHERE id IN (@{l:*})',
	);

	static protected $manipulationQueries = array(
		'insert' => 'INSERT INTO @T (@{v:key_string}) VALUES(@{l:values})',
		'deleteBy' => 'DELETE FROM @T WHERE @{&:*}',
		'updateById' => 'UPDATE @T SET @{h:1} WHERE id = @{i:0}',
	);

	static protected $prototype = array(
		'u_created' => NULL,
		'u_modified' => NULL,
		't_created' => NULL,
		't_modified' => NULL,
		'u_bday' => NULL,
		'form_filled' => NULL,
		);

	static protected function validate($data)
	{
		if (!isset($data['@audit']) || !$data['@audit']) return $data;

		if (isset($data['@create']) && $data['@create']) {
			$data['t_created'] = NOW;
			if (defined('DBO_CURRENT_USER')) $data['u_created'] = DBO_CURRENT_USER;
		} elseif (isset($data['@update_audit']) && $data['@update_audit']) {
			$data['t_modified'] = NOW;
			if (defined('DBO_CURRENT_USER')) $data['u_modified'] = DBO_CURRENT_USER;
		}
		return $data;
	}

	static protected function autoConvert($key, $value)
	{
		return $value;
	}

	// Internal tools {{{1
	static private function getMergedArray($class, $type, $name = NULL)
	{
		$arr = array();
		while ($class) {
			$list = eval("return $class::\$$type;");

			if ($name !== NULL) {
				if (isset($list[$name])) return $list[$name];
				if (isset($list[$name.'/'.DB_TYPE])) return $list[$name.'/'.DB_TYPE];
			} else {
				$arr = array_merge($arr, $list);
			} 
			$class = get_parent_class($class);
		}
		if ($name === NULL) return $arr;
		die("Unknown query $class::$name");
	}
	static protected function getRetrievalQuery($class, $name)
	{
		return self::getMergedArray($class, 'retrievalQueries', $name);
	}
	static protected function getManipulationQuery($class, $name)
	{
		return self::getMergedArray($class, 'manipulationQueries', $name);
	}
	static protected function getPrototype($class)
	{
		return self::getMergedArray($class, 'prototype');
	}

	static private function processGlobalQueryOptions(&$query, $options)
	{
		$limit = isset($options[DBO_LIMIT]) ? $options[DBO_LIMIT] : 0;
		$offset = isset($options[DBO_OFFSET]) ? $options[DBO_OFFSET] : 0;
		if ($offset || $limit) {
			$query .= ' LIMIT ';
			if ($offset) {
				$query .= $offset .', ';
				if (!$limit) $limit = 999999999999;
			}
			$query .= $limit;
		}
	}

	// Helper functions for parseQuery {{{
	static private function parseQueryEscape($val, $type = 'auto')
	{
		if ($type == 'auto') {
			if (is_int($val) || is_bool($val)) $type = 'int';
			elseif (is_float($val)) $type = 'float';
			elseif (is_string($val)) $type = 'string';
			elseif (is_null($val)) {
				$type = 'verbatim';
				$val = 'NULL';
			}
			else die("Bad query: can't auto-guess how to deal with type ". gettype($val));
		}
		switch ($type) {
		case 'verbatim':
			return $val;
		case 'int':
			return intval($val);
		case 'float':
			return floatval($val);
		case 'string':
			return "'". addslashes($val) ."'";
		case 'list':
			return implode(', ', array_map(array('self', 'parseQueryEscape'), $val));
		}

		if ($type != 'hash' && $type != 'and') die ("Invalid manual query parameter type: $type");

		$res = array();
		foreach ($val as $key => $value) {
			$res[] = $key ." = ". self::parseQueryEscape($value);
		}
		if ($type == 'hash') return implode(', ', $res);
		if ($type == 'and') return implode(' AND ', $res);
		die("Invalid query list type: $type");
	}
	// }}}

	static private function parseQuery($class, $query, $params)
	{
		$res = '';
		// I suppose this will get a little ugly.
		while (strpos($query, '@') !== FALSE) {
			list($bit, $query) = explode('@', $query, 2);
			$res .= $bit;
			switch ($query[0]) {
			case '@':
				$res .= '@';
				$query = substr($query, 1);
				break;
			case 'T':
				$res .= DB_PREFIX. eval("return $class::\$table;");
				$query = substr($query, 1);
				break;
			case 'P':
				if ($query[1] == '_') {
					$res .= DB_PREFIX;
				} else {
					$res .= '@P';
				}
				$query = substr($query, 2);
				break;
			case '{':
				if (preg_match('/^\{((?:[sifvlh&]:)?(?:\w+|\*))\}/', $query, $matches)) {
					$var = $matches[1];
					$type = 'auto';
					$typeList = array(
						's' => 'string',
						'i' => 'int',
						'f' => 'float',
						'v' => 'verbatim',
						'l' => 'list',
						'h' => 'hash',
						'&' => 'and',
					);
					if (isset($typeList[$var[0]]) && $var[1] == ':') {
						$type = $typeList[$var[0]];
						$var = substr($var, 2);
					}
					if ($var == '*') {
						$val = self::parseQueryEscape($params, $type);
					} else {
						if (!isset($params[$var]))
							die("Missing parameter '$var' when using query: $class::$query");
						$val = self::parseQueryEscape($params[$var], $type);
					}

					$res .= $val;
					$query = substr($query, strpos($query, '}')+1);
				}
				break;
			default:
				$res .= '@';
			}
		}
		return $res . $query;
	}

	/* Filters @keys from data array */
	static private function _filterData($data)
	{
		$res = array();
		foreach ($data as $key => $value) {
			if ($key[0] == '@') continue;
			$res[$key] = $value;
		}
		return $res;
	}


	// Static operations {{{1
	static function create($class, $data)
	{
		$virtData = call_user_func(array($class, 'validate'), array_merge($data, array('@create' => true, '@audit' => eval("return $class::\$useAuditing;"))));
		if (!$virtData) return NULL;

		if (eval("return $class::\$useSequence;")) {
			$virtData['id'] = self::$db->nextId(DB_PREFIX. eval("return $class::\$table;"));
		}
		$virtData = self::_filterData($virtData);

		$keyString = implode(', ', array_keys($virtData));
		$res = self::query($class, 'insert', array('key_string' => $keyString, 'values' => array_values($virtData)));
		if (!$res) return NULL;
		return new $class($virtData);
	}

	static function getBy($class, $query, $params = array(), $options = array())
	{
		$query = self::getRetrievalQuery($class, $query);

		if (isset($options[DBO_SINGLE])) $options[DBO_LIMIT] = 1;
		self::processGlobalQueryOptions($query, $options);

		$query = self::parseQuery($class, $query, $params);
		if (isset($options[DBO_SINGLE])) {
			$res = self::$db->getRow($query);
		} else {
			$res = self::$db->getAll($query);
		}
		if (PEAR::isError($res) || (!$res && !is_array($res))) {
			// TODO: handle DB error
			return NULL;
		}

		if (isset($options[DBO_SINGLE])) {
			return new $class($res);
		}
		$ret = array();
		foreach ($res as $row) {
			if (eval("return $class::\$useSequence;")) {
				$ret[$row['id']] = new $class($row);
			} else {
				$ret[] = new $class($row);
			}
		}
		return $ret;
	}

	static function getOneBy($class, $query, $params = array(), $options = array())
	{
		return self::getBy($class, $query, $params, array_merge($options, array(DBO_SINGLE => true)));
	}

	static function getById($class, $id)
	{
		return self::getBy($class, 'fields', array('id' => $id), array(DBO_SINGLE => true));
	}

	static function getByIds($class, $ids)
	{
		return self::getBy($class, 'ids', $ids);
	}

	static function query($class, $name, $params)
	{
		$query = self::getManipulationQuery($class, $name);

		$res = self::$db->query(self::parseQuery($class, $query, $params));
		if ($res == DB_OK) return self::$db->affectedRows();
		// TODO: handle DB error
		return NULL;
	}

	// Instance methods {{{1
	public function get($name)
	{
		return $this->fields[$name];
	}

	public function getId()
	{
		$c = get_class($this);
		if (!eval("return $c::\$useSequence;")) die("Trying to get an ID for class '$c' which doesn't have automatic IDs");
		return $this->get('id');
	}

	public function set($name, $value)
	{
		if (!array_key_exists($name, $this->prototype))
			die("Trying to set invalid field of '". get_class($this) .
				"': '$name' (not in prototype)");
		$this->pending[$name] = $value;
	}

	public function commit()
	{
		$class = get_class($this);
		$newData = call_user_func(array($class, 'validate'),
			array_merge($this->fields, $this->pending, array('@audit' => eval("return $class::\$useAuditing;"))));
		if (!$newData) return false;
		$newData = self::_filterData($newData);
		$res = self::query($class, 'updateById', array($this->get('id'), $newData));
		if (!isset($res)) return false;
		$this->fields = $newData;
		return $res;
	}

	// }}}
}

DataObject::$db = $GLOBALS['dao']->getConnection();

