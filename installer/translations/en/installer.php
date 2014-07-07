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
	'ui.title'     => 'testMaker installer',
	'ui.t_welcome' => 'Welcome to the testMaker installer',
	'ui.t_finish'  => 'testMaker installation finished!',

	'ui.phpversion' => 'testMaker requires PHP version 5 or higher. The installed version is PHP ' . PHP_VERSION . '.',
	'ui.step.config_edit'         => 'Set up configuration file',
	'ui.step.config_edit.no_db'   => 'The database configuration you specified
		doesn\'t seem to be working. Please double-check and correct it.',
	'ui.step.config_edit.title'   => 'Setting up configuration file',
	'ui.step.config_edit.success' => 'Configuration file is all set up and in place.',
	'ui.step.upload_perms'        => 'Check upload permissions',
	'ui.step.upload_perms.title'  => 'Checking upload permissions',
	'ui.step.upload_perms.success'=> 'Upload permissions are set up correctly.',

	'ui.step.config_edit.no_db_host'   => 'Textfiled Database hostname is empty.',
	'ui.step.config_edit.no_db_user'   => 'Textfiled Database username is empty.',
	'ui.step.config_edit.no_db_name'   => 'Textfiled Database name is empty.',
	'ui.step.config_edit.no_system_mail'   => 'Textfiled System e-mail address is empty.',
	'ui.step.config_edit.no_db_prefix'   => 'Textfiled Prefix for all tables is empty.',

	'ui.step.db_init'         => 'Initialize the database',
	'ui.step.db_init.title'   => 'Initializing the database',
	'ui.step.db_init.success' => 'The database was successfully set up.',

	'ui.step.guest_init'         => 'Create guest user',
	'ui.step.guest_init.title'   => 'Creating guest user',
	'ui.step.guest_init.success' => 'The guest user was successfully created.',

	'ui.step.groups_init'         => 'Create default groups',
	'ui.step.groups_init.title'   => 'Creating default groups',
	'ui.step.groups_init.success' => 'The default groups were successfully created.',

	'ui.step.adv_groups_init'         => 'Create virtual groups',
	'ui.step.adv_groups_init.title'   => 'Creating virtual groups',
	'ui.step.adv_groups_init.success' => 'The virtual groups were successfully created.',

	'ui.step.item_templates_init'			=> 'Initialize standard templates',
	'ui.step.item_templates_init.title'		=> 'Initialize standard templates',
	'ui.step.item_templates_init.success'	=> 'The standard templates were successfully initialized.',

	'ui.step.create_admin'         => 'Create administrator account',
	'ui.step.create_admin.title'   => 'Creating administrator account',
	'ui.step.create_admin.success' => 'An administrator account was created.',

	'ui.step.blob_test_runs'         => 'Convert test run data',
	'ui.step.blob_test_runs.title'   => 'Converting test run data',
	'ui.step.blob_test_runs.success' => 'The test run data was converted to the format of the current version.',
	
	'ui.step.test_run_duration' 		=> 'Convert test run data',
	'ui.step.test_run_duration2' 		=> 'Convert test run data',
	'ui.step.test_run_duration.title'   => 'Converting test run data',
	'ui.step.test_run_duration.success' 	=> 'The test run data was converted to the format of the current version.',
	
	'ui.step.test_run_gn2id' 			=> 'Update test run data',
	'ui.step.test_run_gn2id.title'   	=> 'Updating test run data',
	'ui.step.test_run_gn2id.success' 	=> 'The test run data was updated to the format of the current version.',
	
	'ui.step.enter_feedback' 			=> 'Testmaker Feedback',
	'ui.step.enter_feedback.title'   	=> 'Testmaker Feedback',
	'ui.step.enter_feedback.success' 	=> 'Testmaker Feedback has been send.',
	
	'ui.step.kill_test_structure_redu' => 'Convert test run data',

	'ui.step.finish' => 'Finish the installation',
	'ui.step.cleanMedia' => 'Median Cleaner',
);

?>
