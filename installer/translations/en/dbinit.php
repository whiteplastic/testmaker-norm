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
 * Translation file
 * @package Installer
 */

$TRANSLATIONS = array(
	'dbinit.db_not_found'			=> 'Could not find database. You should consider creating it first.',
	'dbinit.db_cant_connect'		=> 'Could not connect to database',
	'dbinit.db_connect'				=> 'Successfully connected to database',

	'dbinit.cant_init_table'		=> 'Could not initialize table [table]: [error]',
	'dbinit.init_table'				=> 'Successfully initialized table [table]',
	'dbinit.migrate_table'			=> 'Successfully updated structure of table [table]',
	'dbinit.migrate.dberr'			=> 'The following error was returned from the database server during migration of table [table]: [message]',
	'dbinit.cant_init_sequence'		=> 'Could not initialize sequence [sequence]',
	'dbinit.init_sequence'			=> 'Successfully initialized sequence [sequence]',
	'dbinit.cant_query_groups'		=> 'Could not get the list of groups',
	'dbinit.cant_create_guest_user'	=> 'Could not create guest user',
	'dbinit.create_guest_user'		=> 'Successfully created guest user',
	'dbinit.cant_create_group'		=> 'Could not create group [group]',
	'dbinit.create_group'			=> 'Successfully created group [group]',
	'dbinit.insert_templates'		=> 'Successfully inserted template [template]',
	'dbinit.cant_insert_templates'	=> 'Could not insert template [template]',
	'dbinit.cant_query_templates'	=> 'Could not get the list of templates',

	'dbinit.cant_query_users'		=> 'Could not get the list of users',
	'dbinit.cant_create_user'		=> 'Could not create administrator account: [error]',

	'dbinit.init_versions'			=> 'Could not initialize table versioning information',
	'dbinit.update_version'			=> 'Could not update versioning information for [table]',
	'dbinit.init_version'			=> 'Could not initialize versioning information for [table]',

	'dbinit.create'					=> 'Database successfully initialized.',
);

?>
