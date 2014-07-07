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

libLoad("PEAR");
/**
 * PEAR's database abstraction
 */
require_once("DB.php");

/**
 * This class handles the access to the data source. It provides all necessary information methods for the different data types to access the data source.
 * There is no special rule what this class should provide, because it depends on the type of source.
 *
 * @package Core
 */
class DataAccess
{
	/**#@+
	 * @access private
	 */
	var $db;
	var $type;
	var $host;
	var $name;
	var $user;
	var $password;
	var $connected = false;
	/**#@-*/

	/**
	 * Creates a DataAccess object
	 */
	function DataAccess($type = '', $host = '', $name = '', $user = '', $password = '')
	{
		$this->type = $type;
		$this->host = $host;
		$this->name = $name;
		$this->user = $user;
		$this->password = $password;
	}

	/**
	 * Connects to the database an delete password
	 * @return mixed PEAR database instance or PEAR error object or FALSE
	 */
	function &connect()
	{
		$this->db =& DB::connect($this->type."://".$this->user.":".$this->password."@".$this->host."/".$this->name);
		if (! DB::isError($this->db)) {
			$this->connected = true;
			$this->db->setErrorHandling(PEAR_ERROR_CALLBACK, array(&$this, "handlePearError"));
			$this->db->setFetchMode(DB_FETCHMODE_ASSOC);
		}
		return $this->db;
	}

	/**
	 * Error handler for PEAR; used to automatically produce error messages
	 * when database calls fail.
	 */
	function handlePearError($error)
	{
		if (!error_reporting()) return;
		if (is_object($error)) {
			// Special handling for ominous "lost connection" error :(
			if (is_a($error, 'DB_Error') && FALSE !== strpos($error->userinfo, '[nativecode=2013 ** ')) {
				libLoad('html::preInitErrorPage');
				preInitErrorPage('init.error.db_lost_connection');
			}

			$error = $error->toString();
		}

		if (!isset($GLOBALS['ERROR_HANDLER'])) {
			libLoad('html::preInitErrorPage');
			preInitErrorPage('init.error.db_error', array('error' => $error));
		}

		trigger_error("Database error: ".$error, E_USER_ERROR);
	}

	/**
	 * Disconnects from the database
	 * @return bool
	 */
	function disconnectDb()
	{
		return $this->db->disconnect();
	}

	/**
	 * Sets the database type and reconnects the database (if it's already connected)
	 * @param string Type of database that is used (detailed description in PEAR documentation)
	 * @return mixed New database handle or TRUE if the database wasn't connected before
	 */
	function setType($type)
	{
		$this->type = $type;
		if($this->connected == true) {
			$this->db->disconnect();
			return $this->db->connect();
		} else {
			return true;
		}
	}

	/**
	 * Sets the hostname and reconnects the database (if it's already connected)
	 * @param string Hostname of the database server
	 * @return mixed New database handle or TRUE if the database wasn't connected before
	 */
	function setHost($host)
	{
		$this->host = $host;
		if($this->connected == true) {
			$this->db->disconnect();
			return $this->db->connect();
		} else {
			return true;
		}
	}

	/**
	 * Sets the database name and reconnects the database (if it's already connected)
	 * @param string $name Database name
	 * @return mixed New database handle or TRUE if the database wasn't connected before
	 */
	function setName($name)
	{
		$this->name = $name;
		if($this->connected == true) {
			$this->db->disconnect();
			return $this->db->connect();
		} else {
			return true;
		}
	}

	/**
	 * Sets the username and reconnects the database (if it's already connected)
	 * @param string $user Username for database access
	 * @return mixed New database handle or TRUE if the database wasn't connected before
	 */
	function setUser($user)
	{
		$this->user = $user;
		if($this->connected == true) {
			$this->db->disconnect();
			return $this->db->connect();
		} else {
			return true;
		}
	}

	/**
	 * This function sets the password and reconnects the databse, if it's already connected
	 * @param string $password password for database access
	 * @return mixed New database handle or TRUE if the database wasn't connected before
	 */
	function setPassword($password)
	{
		$this->password = $password;
		if($this->connected == true) {
			$this->db->disconnect();
			return $this->db->connect();
		} else {
			return true;
		}
	}

	/**
	 * This function returns the database connection
	 * @return DB
	 */
	function &getConnection()
	{
		return $this->db;
	}

	/**
	 * Generates database-dependent SQL for string concatenation.
	 */
	function getConcat($list)
	{
		switch ($this->type) {
		case 'mysql':
			return 'CONCAT('. implode(', ', $list) .')';
		case 'mssql':
			return implode(' + ', $list);
		default:
			return implode(' || ', $list);
		}
	}
}

?>
