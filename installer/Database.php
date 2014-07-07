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
 * Install or update this testMaker site.
 * @package Installer
 */

ob_implicit_flush(TRUE);

libLoad('utilities::snakeToCamel');
libLoad('environment::MsgHandler');

/**
 * Load table migrations
 */
start("installer/TableMigrations.php");
require_once(INSTALLER.'TableMigrations.php');
stop();

/**
 * Handles creating and updating table structures.
 *
 * @package Installer
 */
class Database
{
	/**#@+
	 * @access private
	 */
	var $db;
	var $prefix;
	var $installer;

	var $tableNames = array(
		'block_conditions',
		'blocks_connect',
		'block_structure',
		'blocks_type',
		'container_blocks',
		'certificates',
		'dimension_groups',
		'dimensions',
		'dimension_class_sizes',
		'dimensions_connect',
		'dimension_groups',
		'edit_logs',
		'error_log',
		'export_var_constellation',
		'error_log_verbose',
		'feedback_blocks',
		'feedback_blocks_connect',
		'feedback_conditions',
		'feedback_pages',
		'feedback_paragraphs',
		'feedback_scores',
		'groups',
		'groups_connect',
		'group_permissions',
		'info_blocks',
		'info_conditions',
		'info_pages',
		'item_answers',
		'item_block_answers',
		'item_blocks',
		'item_conditions',
		'item_style',
		'items',
		'media',
		'messages',
		'settings',
		'tans',
		'test_runs',
		'test_run_blocks',
		'test_structures',
		'users',
		'locations',
		'emails',
		'emails_connect',
		'email_conditions',
		'block_structure',
		'test_run_block_content',
		'privacy_policys',	
		'cron_jobs'
	);
	var $tablesInited = false;
	var $tables = array();
	var $versions = array();
	var $sequences = array(
		'blocks' => 0,
		'dimensions' => 0,
		'dimension_groups' => 0,
		'certificates' => 0,
		'edit_logs' => 0,
		'error_log' => 0,
		'feedback_paragraphs' => 0,
		'feedback_pages' => 0,
		'groups' => 0,
		'info_pages' => 0,
		'item_answers' => 0,
		'item_block_answers' => 0,
		'item_conditions' => 0,
		'item_style' => 0,
		'items' => 0,
		'media' => 0,
		'media_connect' => 0,
		'test_runs' => 0,
		'users' => 0,
	);
	/**#@-*/

	/**
	 * Initializes the database component and connects it to the
	 * installer frontend.
	 *
	 * @param mixed An object with a conforming status() method.
	 */
	function Database(&$frontend)
	{
		$this->front = &$frontend;
		$this->front->addStep('db_init', $this);
		$this->front->addStep('guest_init', $this);
		$this->front->addStep('groups_init', $this);
		$this->front->addStep('adv_groups_init', $this);

		$this->mig = new TableMigrations($this->front);

		$this->prefix = defined("DB_PREFIX") ? DB_PREFIX : "";
	}

	/**
	 * Checks whether a previous attempt to connect to the database succeeded.
	 *
	 * @return bool True if we're connected to the database.
	 */
	function check_connect()
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		if (PEAR::isError($this->db)) {
			switch ($this->db->getCode()) {
			case DB_ERROR_NOT_FOUND:
				$this->front->status(MSG_RESULT_NEG, "dbinit.db_not_found");
				break;
			default:
				$this->front->status(MSG_RESULT_NEG, "dbinit.db_cant_connect");
			}
			return false;
		}
		if (isset($this->db)) return true;
	}

	/**
	 * Attempts to connect to the database.
	 *
	 * @return bool True on success.
	 */
	function try_connect($type, $user, $password, $host, $database, $prefix)
	{
		if (isset($this->db)) return true;
		$this->prefix = $prefix;
		$GLOBALS['dao'] = new DataAccess($type, $host, $database, $user, $password);
		$this->db = &$GLOBALS['dao']->connect();

		if (!$this->check_connect()) return false;

		define('DB_PREFIX', $prefix);
		define('DB_TYPE', $type);
		$this->front->status(MSG_RESULT_NEUTRAL, 'dbinit.db_connect',
			array('database' => $database));
		return true;
	}

	/**
	 * Creates a sequence table for one of the tables with an incrementing row ID.
	 *
	 * @param string The name of the sequence.
	 *
	 * @return bool True on success.
	 */
	function init_sequence($sequence)
	{
		$this->sequences[$sequence] = $this->db->createSequence($this->prefix.$sequence, 'safe');
		if (!$this->sequences[$sequence]) {
			$this->front->status(MSG_RESULT_NEG, "dbinit.cant_init_sequence",
				array('sequence' => $sequence));
			return false;
		}
		$this->front->status(MSG_RESULT_NEUTRAL, "dbinit.init_sequence",
			array('sequence' => $sequence));
		return true;
	}

	/**
	 * Creates and/or updates tables if necessary.
	 *
	 * @return bool True on success.
	 */
	function init_tables()
	{
		if ($this->tablesInited) return true;
		$this->tablesInited = true;

		require_once(INSTALLER.'Tables.php');
		$this->mig->setDb($this->db);

		// Retrieve versioning information
		$table = 'table_versions';
		$this->tables[$table] = new TableVersions($this->db, $this->prefix.$table, 'safe');
		$data = $this->db->getAll("SELECT * FROM {$this->prefix}$table");
		if (PEAR::isError($data)) {
			$this->front->status(MSG_RESULT_NEG, "dbinit.init_versions");
			return false;
		}
		foreach ($data as $tabvers) {
			$this->versions[$tabvers['table_name']] = $tabvers['version'];
		}

		// Work on all the other tables
		foreach ($this->tableNames as $table) {
			$created = false;
			$version = $this->mig->getVersion($table);
			$class = snakeToCamel($table);

			// Detect if migration is required
			if (!isset($this->tables[$table]) && isset($this->versions[$table]) &&
					$this->versions[$table] < $version) {
				if ($this->migrate_table($table, $this->versions[$table]) < $version) {
					return false;
				}
				$this->front->status(MSG_RESULT_NEUTRAL, "dbinit.migrate_table",
					array('table' => $table));
			}

			$this->tables[$table] = new $class ($this->db, $this->prefix.$table, 'safe');

			if ($err = $this->tables[$table]->error) {
				$this->front->status(MSG_RESULT_NEG, "dbinit.cant_init_table",
					array('table' => $table, 'error' => $err->getMessage()));
				return false;
			}

			// Create/update versioning information
			if (isset($this->versions[$table])) {
				$res = $this->db->query("UPDATE {$this->prefix}table_versions SET version = ? WHERE table_name = ?", array($version, $table));
				if ($res != DB_OK) {
					$this->front->status(MSG_RESULT_NEG, "dbinit.update_version", array('table' => $table));
					return false;
				}
			} else {
				$res = $this->db->query("INSERT INTO {$this->prefix}table_versions VALUES(?, ?)", array($table, $version));
				if ($res != DB_OK) {
					$this->front->status(MSG_RESULT_NEUTRAL, "dbinit.init_version", array('table' => $table));
					return false;
				}
				$this->versions[$table] = $version;
				$created = true;
			}

			// Table didn't need to be created, inform user
			if ($created) {
				if (isset($this->sequences[$table])) {
					$res = $this->init_sequence($table);
					if (!$res) return false;
				}
				$this->front->status(MSG_RESULT_NEUTRAL, "dbinit.init_table", array('table' => $table));
			}
		}

		return true;
	}

	/**
	 * Updates the timestamp associated with the last testMaker update.
	 */
	function update_stamp()
	{
		if (isset($this->versions['VERSION'])) {
			$this->db->query("UPDATE {$this->prefix}table_versions SET version = ? WHERE table_name = 'VERSION'", array(NOW));
		} else {
			$this->db->query("INSERT INTO {$this->prefix}table_versions VALUES ('VERSION', ?)", array(NOW));
		}
	}

	/**
	 * Migrates a table's structure to the current version.
	 *
	 * @param string Name of the table.
	 * @param integer The present (outdated) version of the table.
	 *
	 * @return bool True on success.
	 */
	function migrate_table($table, $from)
	{
		return $this->mig->migrate($table, $from);
	}

	/**
	 * Adds a magic guest user (ID 0 = invisible in the interface).
	 *
	 * @return bool True on success.
	 */
	function create_guest_user()
	{
		$to_create = array(
			'id' => 0,
			'username' => '_guest',
			'password_hash' => '*',
			'full_name' => 'Magic guest user',
			'email' => '',
			'lang' => '',
			't_created' => NOW,
			't_modified' => NOW,
			'u_created' => 0,
			'u_modified' => 0,
			'last_login' => NOW,
			'delete_time' => null,
			'privacy_policy_ok' => 0,
			'u_bday' => 0,
			'form_filled' => 0,
		);
		$res = $this->tables['users']->insert($to_create);
		if (!$res) {
			$this->front->status(MSG_RESULT_NEG, "dbinit.cant_create_guest_user");
			return false;
		} else {
			$this->front->status(MSG_RESULT_NEUTRAL, "dbinit.create_guest_user");
		}
		return true;
	}

	/**
	 * Adds the default user groups for test creators and administrators.
	 *
	 * @return bool True on success.
	 */
	function create_default_groups()
	{
		$ids = array();
		$ids['admins'] = $this->tables['groups']->nextID();
		$ids['creators'] = $this->tables['groups']->nextID();
		$ids['members'] = $this->tables['groups']->nextID();
		$ids['guests'] = $this->tables['groups']->nextID();

		$to_create = array(
			'admins' => array(),
			'creators' => array(),
			'members' => array(
				'autoadd' => 1,
			),
			'guests' => array(),
		);
		foreach ($to_create as $key => $val) {
			$val = array_merge($val, array(
				'id' => $ids[$key],
				'groupname' => T("database.default_groups.$key.name"),
				'description' => T("database.default_groups.$key.description"),
				't_created' => NOW,
				't_modified' => NOW,
				'u_created' => 0,
				'u_modified' => 0,
			));
			$res = $this->tables['groups']->insert($val);
			if (!$res) {
				$this->front->status(MSG_RESULT_NEG, "dbinit.cant_create_group", array('group' => $val['groupname']));
				return false;
			}
			$this->front->status(MSG_RESULT_NEUTRAL, "dbinit.create_group", array('group' => $val['groupname']));
		}

		// Add permissions
		require_once(CORE. 'types/UserList.php');

		foreach ($ids as $key => $val) {
			$$key = DataObject::getById('Group', $val);
		}
		$admins->setPermissions(array('run' => 1, 'view' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1, 'copy' => 1, 'link' => 1, 'admin' => 1));
		$creators->setPermissions(array('create' => 1));

		$guest = DataObject::getById('User', 0);
		$guest->setGroups(array($ids['guests']), 0);

		return true;
	}

	/**
	 * Adds the special meaning user groups for TAN and password access.
	 *
	 * @return bool True on success.
	 */
	function create_advanced_groups()
	{
		// If you extend this function to create more virtual groups, take care to change checkAdvGroupsInit() as well.

		$data = $this->db->getOne("SELECT COUNT(*) FROM {$this->prefix}groups WHERE id IN (-2, -1)");
		if (PEAR::isError($data)) return false;

		$ids = array();
		if ($data < 2) {
			$ids['tan'] = -1;
			$ids['password'] = -2;
		}

		foreach ($ids as $key => $val) {
			$entry = array(
				'id' => $val,
				'groupname' => T("database.advanced_groups.$key.name"),
				'description' => T("database.advanced_groups.$key.description"),
				't_created' => NOW,
				't_modified' => NOW,
				'u_created' => 0,
				'u_modified' => 0,
			);
			$res = $this->tables['groups']->insert($entry);
			if (!$res) {
				$this->front->status(MSG_RESULT_NEG, "dbinit.cant_create_group", array('group' => $entry['groupname']));
				return false;
			}
			$this->front->status(MSG_RESULT_NEUTRAL, "dbinit.create_group", array('group' => $entry['groupname']));
		}
		return true;
	}

	/**
	 * Checks if the database needs to be initialized.
	 *
	 * @return bool True if initialization is necessary.
	 */
	function checkDbInit()
	{
		if (!$this->check_connect()) return true;
		$this->mig->setDb($this->db);

		$vers = @$this->db->getAll("SELECT * FROM {$this->prefix}table_versions");
		if (PEAR::isError($vers)) return true;

		$vers_hash = array();
		foreach ($vers as $ver) {
			$vers_hash[$ver['table_name']] = $ver['version'];
		}
		foreach ($this->tableNames as $key) {
			$value = $this->mig->getVersion($key);
			if (!isset($vers_hash[$key]) || $vers_hash[$key] < $value)
				return true;
		}

		return false;
	}

	/**
	 * Creates and/or updates everything database-related (if necessary).
	 *
	 * @return bool False on success.
	 */
	function doDbInit()
	{
		if (!$this->check_connect()) return true;
		if (!$this->init_tables()) return true;
		$this->update_stamp();
		return false;
	}

	function checkGuestInit()
	{
		if (!$this->check_connect()) return true;
		$data = $this->db->getOne("SELECT COUNT(*) FROM {$this->prefix}users WHERE id = 0");
		if (PEAR::isError($data)) {
			$this->front->status(MSG_RESULT_NEG, "dbinit.cant_query_groups");
			return true;
		}
		return ($data == 0);
	}

	function doGuestInit()
	{
		if (!$this->check_connect()) return true;
		if (!$this->init_tables()) return true;
		if (!$this->create_guest_user()) return true;
		return false;
	}

	function checkGroupsInit()
	{
		if (!$this->check_connect()) return true;
		$data = $this->db->getOne("SELECT COUNT(*) FROM {$this->prefix}groups");
		if (PEAR::isError($data)) {
			$this->front->status(MSG_RESULT_NEG, "dbinit.cant_query_groups");
			return true;
		}
		return ($data == 0);
	}

	function doGroupsInit()
	{
		if (!$this->check_connect()) return true;
		if (!$this->init_tables()) return true;
		if (!$this->create_default_groups()) return true;
		return false;
	}

	function checkAdvGroupsInit()
	{
		if (!$this->check_connect()) return true;

		// List of currently defined advanced groups
		$adv = array('-1','-2');

		$data = $this->db->getOne("SELECT COUNT(*) FROM {$this->prefix}groups WHERE id IN (". implode(', ', $adv) .')');
		if (PEAR::isError($data)) {
			$this->front->status(MSG_RESULT_NEG, "dbinit.cant_query_groups");
			return true;
		}
		return ($data < count($adv));
	}

	function doAdvGroupsInit()
	{
		if (!$this->check_connect()) return true;
		if (!$this->init_tables()) return true;
		if (!$this->create_advanced_groups()) return true;
		return false;
	}

	function doCertificatesInit()
	{
		if (!$this->check_connect()) return true;
		if (!$this->init_tables()) return true;
		return false;
	}

}

?>
