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
 * @package Installer
 */

libLoad('utilities::snakeToCamel');


///////////////////////////////////////////////////////
/////
/////   CHANGES OF TABLE-MIGRATIONS HAVE TO BE    /////
/////
/////   ADDED TO TABLES.PHP AS WELL !!!!          /////
/////
/////   DECLARE NEW TABLES ALSO IN DATABASE.PHP   /////
/////
///////////////////////////////////////////////////////

/**
 * @package Installer
 */
class TableMigrations
{
	var $db;
	var $front;
	
	// Add your migration rules here (as new element at the end of the particular list)
	var $migs = array(
	
		'blocks_connect' => array(
			0 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'INT(11) NOT NULL DEFAULT 0'),
				),
			),
		'block_structure' => array(
			/* Automaticly done 3.0 -> 3.2 by updateTestRun.php, only uncomment for SVN use
			0 => array(
				array('trans_content2'),
				),*/
			),
		'container_blocks' => array(
			0 => array(
				array('add_column', 'name' => 'def_language', 'type' => 'VARCHAR(2) NULL'),
			),
			1 => array(
				array('drop_column', 'name' => 'allow_usage'),
				array('drop_column', 'name' => 'allow_copy'),
				array('add_column', 'name' => 'permissions_recursive', 'type' => 'DECIMAL(1,0)'),
			),
			2 => array(
				array('add_column', 'name' => 'progress_bar', 'type' => 'DECIMAL(1,0)'),
			),
			3 => array(
				array('drop_column', 'name' => 'direct_access'),
				array('drop_column', 'name' => 'portal_access'),
			),
			4 => array(
				array('add_column', 'name' => 'show_subtests', 'type' => 'DECIMAL(1,0)'),
			),
			5 => array(
				array('add_column', 'name' => 'password', 'type' => 'VARCHAR(255)'),
			),
			6 => array(
				array('add_column', 'name' => 'open_date', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'close_date', 'type' => 'INT(11)'),
			),
			7 => array(
				array('add_column', 'name' => 'pause_button', 'type' => 'DECIMAL(1,0)'),
			),
			8 => array(
				array('add_column', 'name' => 'enable_skip', 'type' => 'DECIMAL(1,0)'),
			),
			9 => array(
				array('add_column', 'name' => 'subtest_inactive', 'type' => 'DECIMAL(1,0)'),
			),
			10 => array(
				array('add_column', 'name' => 'random_order', 'type' => 'DECIMAL(1,0)'),
			),
			11 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
			12 => array(
				array('add_column', 'name' => 'tan_ask_email', 'type' => 'DECIMAL(1,0)'),
			),
			13 => array(
				array('drop_column', 'name' => 'tan_ask_email'),
				array('add_column', 'name' => 'tan_ask_email', 'type' => 'DECIMAL(1,0) NOT NULL DEFAULT 1'),
			),
		),
		'certificates' => array(
			0 => array(
				array('add_column', 'name' => 'random', 'type' => 'INT(8)'),
			),
			1 => array(
				array('add_column', 'name' => 'name', 'type' => 'VARCHAR(80)'),
				array('add_column', 'name' => 'birthday', 'type' => 'INT(11)'),
			),
		),
		'dimension_groups' => array(
			0 => array(
				array('add_column', 'name' => 'block_id', 'type' => 'INT(11)'),
				array('add_index', 'name' => 'block_id'),
				array('add_column', 'name' => 'title', 'type' => 'VARCHAR(255)'),
			),
		),
		'dimensions' => array(
			0 => array(
				array('delete_rows', 'table' => 'dimensions_connect'),
				array('delete_rows', 'table' => 'feedback_conditions'),
				array('delete_rows'),
			),
			1 => array(
				array('drop_index', 'name' => 'item_block_id'),
				array('drop_column', 'name' => 'item_block_id'),
				array('add_column', 'name' => 'block_id', 'type' => 'INT(11)'),
				array('add_index', 'name' => 'block_id'),
			),
			2=> array(
				array('add_column', 'name' => 'reference_value', 'type' => 'INT(11)'),
			),
			3 => array(
				array('add_column', 'name' => 'reference_value_type', 'type' => 'INT(11)'),
			),
			4 => array(
				array('add_column', 'name' => 'score_type', 'type' => 'INT(11) DEFAULT 0'),
			),
		),
		'dimensions_connect' => array(
			0 => array(
				array('drop_column', 'name' => 'reverse'),
				array('add_column', 'name' => 'score', 'type' => 'INT(11)'),
			),
		),
		'error_log' => array(
			0 => array(
				array('add_column', 'name' => 'countError', 'type' => 'INT(11)'),
			),
		),
		'error_log_verbose' => array(
			0 => array(
				array('add_column', 'name' => 'countError', 'type' => 'INT(11)'),
			),
		),
		'feedback_blocks' => array(
			0 => array(
				array('delete_rows', 'table' => 'feedback_pages'),
				array('delete_rows'),
				array('drop_index', 'name' => 'source_id'),
				array('drop_column', 'name' => 'source_id'),
			),
			1 => array(
				array('drop_column', 'name' => 'allow_usage'),
				array('drop_column', 'name' => 'allow_copy'),
				array('add_column', 'name' => 'permissions_recursive', 'type' => 'DECIMAL(1,0)'),
			),
			2 => array(
				array('add_column', 'name' => 'media_connect_id', 'type' => 'INT(11)'),
			),
			3 => array(
				array('add_column', 'name' => 'cert_enabled', 'type' => 'TINYINT(3)'),
				array('add_column', 'name' => 'cert_fname_item_id', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'cert_lname_item_id', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'cert_bday_item_id', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'cert_template_name', 'type' => 'VARCHAR(255)'),
			),
			4 => array(
				array('add_column', 'name' => 'show_in_summary', 'type' => 'INT(11)'),
			),
			5 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
			6 => array(
				array('add_column', 'name' => 'cert_disable_barcode', 'type' => 'INT(11) AFTER `cert_template_name`'),
			),
		),
		'feedback_blocks_connect' => array(
			0 => array(
				array('add_index', 'name' => 'item_block_id'),
			),
		),
		'feedback_conditions' => array(
			0 => array(
				array('drop_index', 'name' => 'paragraph_id'),
				array('add_index', 'name' => 'dimension_id'),
			),
		),
		'feedback_pages' => array(
			0 => array(
				array('add_column', 'name' => 'title', 'type' => 'VARCHAR(255)'),
			),
			1 => array(
				array('drop_column', 'name' => 'content'),
			),
			2 => array(
				array('add_column', 'name' => 'media_connect_id', 'type' => 'INT'),
			),
			3 => array(
				array('drop_column', 'name' => 'media_connect_id'),
			),
			4 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
		),
		'feedback_paragraphs' => array(
			0 => array(
				array('fix_positions'),
			),
			1 => array(
				array('add_column', 'name' => 't_created', 'type' => 'INT NOT NULL'),
				array('add_column', 'name' => 't_modified', 'type' => 'INT NOT NULL'),
				array('add_column', 'name' => 'u_created', 'type' => 'INT NOT NULL'),
				array('add_column', 'name' => 'u_modified', 'type' => 'INT NOT NULL'),
			),
			2 => array(
				array('add_column', 'name' => 'ext_conditions', 'type' => 'TEXT'),
			),
		),
		'group_permissions' => array(
			0 => array(
				array('drop_index', 'name' => 'group_block'),
				array('add_index', 'name' => 'group_block', 'columns' => array('group_id', 'block_id', 'permission_name')),
			),
		),
		'groups' => array(
			0 => array(
				array('drop_column', 'name' => 'permission'),
			),
			1 => array(
				array('add_column', 'name' => 'autoadd', 'type' => 'INT(1) NULL'),
			),
		),
		'info_pages' => array(
			0 => array(
				array('add_column', 'name' => 'title', 'type' => 'VARCHAR(255)'),
			),
			1 => array(
				array('drop_column', 'name' => 'media_connect_id'),
			),
			2 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
		),
		'info_blocks' => array(
			0 => array(
				array('drop_column', 'name' => 'allow_usage'),
				array('drop_column', 'name' => 'allow_copy'),
				array('add_column', 'name' => 'permissions_recursive', 'type' => 'DECIMAL(1,0)'),
			),
			1 => array(
				array('add_column', 'name' => 'media_connect_id', 'type' => 'INT(11)'),
			),
			2 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
		),
		'item_answers' => array(
			0 => array(
				array('add_column', 'name' => 'score', 'type' => 'INT(11)'),
			),
			1 => array(
				array('drop_column', 'name' => 'score'),
				array('alter_column_type', 'name' => 'correct', 'type' => 'DECIMAL(1,0) NULL'),
			),
			2 => array(
				array('drop_column', 'name' => 'media_connect_id'),
			),
			3 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
		),
		'item_block_answers' => array(
			0 => array(
				array('add_column', 'name' => 'media_connect_id', 'type' => 'INT'),
			),
			1 => array(
				array('drop_column', 'name' => 'media_connect_id'),
			),
		),
		'item_blocks' => array(
			0 => array(
				array('add_column', 'name' => 'default_item_type', 'type' => 'INT(11)'),
			),
			1 => array(
				array('add_column', 'name' => 'max_item_time', 'type' => 'INT(11)'),
			),
			2 => array(
				array('drop_column', 'name' => 'default_item_type'),
				array('add_column', 'name' => 'default_item_template', 'type' => 'VARCHAR(32)'),
			),
			3 => array(
				array('drop_column', 'name' => 'max_item_time'),
				array('add_column', 'name' => 'default_max_item_time', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'default_min_item_time', 'type' => 'INT(11)'),
			),
			4 => array(
				array('add_column', 'name' => 'default_template_cols', 'type' => 'INT(2)'),
				array('add_column', 'name' => 'default_template_align', 'type' => 'VARCHAR(1)'),
			),
			5 => array(
				array('add_column', 'name' => 'irt', 'type' => 'DECIMAL(1,0)'),
			),
			6 => array(
				array('drop_column', 'name' => 'allow_usage'),
				array('drop_column', 'name' => 'allow_copy'),
				array('add_column', 'name' => 'permissions_recursive', 'type' => 'DECIMAL(1,0)'),
			),
			7 => array(
				array('add_column', 'name' => 'default_item_force', 'type' => 'DECIMAL(1,0)'),
			),
			8 => array(
				array('add_column', 'name' => 'items_per_page', 'type' => 'INT(11)'),
			),
			9 => array(
				array('add_column', 'name' => 'media_connect_id', 'type' => 'INT(11)'),
			),
			10 => array(
				array('alter_column_type', 'name' => 'max_sem', 'type' => 'DOUBLE PRECISION'),
			),
			11 => array(
				array('add_column', 'name' => 'introduction', 'type' => 'TEXT'),
				array('add_column', 'name' => 'hidden_intro', 'type' => 'INT(1)'),
			),
			12 => array(
				array('add_column', 'name' => 'intro_pos', 'type' => 'INT(1)'),
			),
			13 => array(
				array('add_column', 'name' => 'default_item_type', 'type' => 'VARCHAR(64) NOT NULL'),
				array('migrate_item_block_type'),
			),
			14 => array(
				array('add_column', 'name' => 'intro_label', 'type' => 'VARCHAR(255) NULL'),
			),
			15 => array(
				array('add_column', 'name' => 'random_order', 'type' => 'DECIMAL(1,0)'),
			),
			16 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
			17 => array(
				array('add_column', 'name' => 'intro_firstonly', 'type' => 'INT(1)'),
			),
			18 => array(
				array('add_column', 'name' => 'default_conditions', 'type' => 'DECIMAL(1,0) AFTER `default_max_item_time` '),
			),
			19 => array(
				array('add_column', 'name' => 'conditions_need_all', 'type' => 'DECIMAL(1,0) AFTER `default_conditions` '),
			),
			
		),
		'item_style' => array(
			0 => array(
				array('add_column', 'name' => 'logo', 'type' => 'INT(11) NULL'),
			),
			1 => array(
				array('drop_column', 'name' => 'style'),
				array('add_column', 'name' => 'background_color', 'type' => 'VARCHAR(10) NOT NULL'),
				array('add_column', 'name' => 'font_family', 'type' => 'VARCHAR(255) NOT NULL'),
				array('add_column', 'name' => 'font_size', 'type' => 'VARCHAR(10) NOT NULL'),
				array('add_column', 'name' => 'font_style', 'type' => 'VARCHAR(10) NOT NULL'),
				array('add_column', 'name' => 'font_weight', 'type' => 'VARCHAR(10) NOT NULL'),
				array('add_column', 'name' => 'color', 'type' => 'VARCHAR(10) NOT NULL'),
				array('add_column', 'name' => 'item_background_color', 'type' => 'VARCHAR(10) NOT NULL'),
				array('add_column', 'name' => 'dist_background_color', 'type' => 'VARCHAR(10) NOT NULL'),
			),
			2 => array(
				array('add_column', 'name' => 'logo_align', 'type' => 'VARCHAR(10) NOT NULL')
			),
			3 => array(
				array('add_column', 'name' => 'page_width', 'type' => 'DECIMAL(1,0)')
			),
			4 => array(
				array('add_column', 'name' => 'item_borders', 'type' => 'INT(1)')
			),
			5 => array(
				array('add_column', 'name' => 'use_parent_style', 'type' => 'DECIMAL(1,0) NOT NULL DEFAULT 1')
			),
			6 => array(
				array('add_column', 'name' => 'logo_show', 'type' => 'INT(8) DEFAULT 0 AFTER `logo_align`'),
			),
		),
		'items' => array(
			0 => array(
				array('drop_column', 'name' => 'type'),
				array('add_column', 'name' => 'template', 'type' => 'VARCHAR(32)'),
			),
			1 => array(
				array('add_column', 'name' => 'min_time', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'max_time', 'type' => 'INT(11)'),
			),
			2 => array(
				array('add_column', 'name' => 'title', 'type' => 'VARCHAR(255)'),
			),
			3 => array(
				array('add_column', 'name' => 'template_cols', 'type' => 'INT(2)'),
				array('add_column', 'name' => 'template_align', 'type' => 'VARCHAR(1)'),
			),
			4 => array(
				array('add_column', 'name' => 'answer_force', 'type' => 'DECIMAL(1,0)'),
			),
			5 => array(
				array('drop_column', 'name' => 'media_connect_id'),
			),
			6 => array(
				array('add_column', 'name' => 'conditions_need_all', 'type' => 'DECIMAL(1,0)'),
			),
			7 => array(
				array('add_column', 'name' => 'type', 'type' => 'VARCHAR(64) NOT NULL'),
				array('migrate_item_type'),
			),
			8 => array(
			),
			9 => array(
				array('fix_migrated_item_types', 'bugfix' => true),
			),
			10 => array(
				array('add_column', 'name' => 'restriction', 'type' => 'VARCHAR(255)'),
			),
			11 => array(
				array('add_column', 'name' => 'required_plugins', 'type' => 'VARCHAR(255)'),
			),
			12 => array(
				array('add_column', 'name' => 'disabled', 'type' => 'DECIMAL(1,0)'),
			),
		),
		'media' => array(
			0 => array(
				array('drop_column', 'name' => 'type'),
				array('add_column', 'name' => 'media_connect_id', 'type' => 'INT(11)'),
			),
			1 => array(
				array('add_column', 'name' => 'filetype', 'type' => 'INT(1)'),
			),
		),
		'test_runs' => array(
			0 => array(
				array('add_column', 'name' => 'is_completed', 'type' => 'INT(11) NOT NULL'),
			),
			1 => array(
				array('drop_column', 'name' => 'is_completed', 'type' => 'INT(11) NOT NULL'),
				array('add_column', 'name' => 'available_items', 'type' => 'INT(11) NULL'),
				array('add_column', 'name' => 'processed_items', 'type' => 'INT(11) NOT NULL'),
			),
			2 => array(
				array('add_column', 'name' => 'shown_items', 'type' => 'INT(11) NOT NULL'),
				array('copy_column', 'source' => 'processed_items', 'target' => 'shown_items'),
				array('drop_column', 'name' => 'processed_items'),
				array('add_column', 'name' => 'answered_items', 'type' => 'INT(11) NOT NULL'),
				array('add_column', 'name' => 'available_required_items', 'type' => 'INT(11) NULL'),
				array('add_column', 'name' => 'answered_required_items', 'type' => 'INT(11) NOT NULL'),
			),
			3 => array(
				array('add_column', 'name' => 'available_pages', 'type' => 'INT(11) NULL'),
				array('add_column', 'name' => 'shown_pages', 'type' => 'INT(11) NOT NULL'),
			),
			4 => array(
				array('add_column', 'name' => 'next_item_id', 'type' => 'INT(11) NULL'),
			),
			5 => array(
				array('add_column', 'name' => 'next_block_id', 'type' => 'INT(11) NULL'),
			),
			6 => array(
				array('add_column', 'name' => 'next_item_ids', 'type' => 'BLOB NOT NULL'),
				array('copy_column', 'source' => 'next_item_id', 'target' => 'next_item_ids'),
				array('drop_column', 'name' => 'next_item_id'),
			),
			7 => array(
				array('add_column', 'name' => 'permission_groups', 'type' => 'TEXT'),
			),
			8 => array(
				array('add_column', 'name' => 'duration', 'type' => 'INT(11) NULL'),
			),
			9 => array(
				array('add_column', 'name' => 'structure_version', 'type' => 'INT NULL'),
				array('add_index', 'name' => 'structure_version'),
			),
			10 => array(
				array('drop_column', 'name' => 'duration'),
				array('add_column', 'name' => 'duration', 'type' => 'INT(11) NULL'),
			),
			11 => array(
				array('add_column', 'name' => 'step', 'type' => 'INT DEFAULT NULL'),
			),
			12 => array(
				array('alter_column', 'name' => 'permission_groups', 'new_name' => 'permission_groups', 'type' => 'VARCHAR(255) NULL'),
			),
		),
		'users' => array(
			0 => array(
				array('add_column', 'name' => 'confirmation_key', 'type' => 'VARCHAR(255) NULL'),
			),
			1 => array(),
			2 => array(
				array('add_column', 'name' => 'email_key', 'type' => 'VARCHAR(255) NULL'),
				array('add_column', 'name' => 'new_email', 'type' => 'VARCHAR(255) NULL'),
			),
			3 => array(
				array('drop_index', 'name' => 'group_id'),
				array('drop_column', 'name' => 'group_id'),
			),
			4 => array(
				array('add_index', 'name' => 'activation_key'),
				array('add_index', 'name' => 't_created'),
			),
			5 => array(
			),
			6 => array(
				array('add_column', 'name' => 'blocked', 'type' => 'DECIMAL(1,0) NULL'),
				array('add_column', 'name' => 'deleted', 'type' => 'DECIMAL(1,0) NULL'),
			),
			7 => array(
			),
			8 => array(
				array('alter_column', 'name' => 'id', 'new_name' => 'id', 'type' => 'MEDIUMINT(8) UNSIGNED NOT NULL'),
				array('alter_column', 'name' => 'username', 'new_name' => 'username', 'type' => 'VARCHAR(64) NOT NULL'),
				array('alter_column', 'name' => 'password_hash', 'new_name' => 'password_hash', 'type' => 'CHAR(32) NOT NULL'),
				array('alter_column', 'name' => 'full_name', 'new_name' => 'full_name', 'type' => 'VARCHAR(64) NOT NULL'),
				array('alter_column', 'name' => 'email', 'new_name' => 'email', 'type' => 'VARCHAR(64) NOT NULL'),
				array('alter_column', 'name' => 'lang', 'new_name' => 'lang', 'type' => 'CHAR(2) NOT NULL'),
				array('alter_column', 'name' => 'confirmation_key', 'new_name' => 'confirmation_key', 'type' => 'VARCHAR(16)'),
				array('alter_column', 'name' => 'email_key', 'new_name' => 'email_key', 'type' => 'VARCHAR(16)'),
				array('alter_column', 'name' => 'new_email', 'new_name' => 'new_email', 'type' => 'VARCHAR(64) NOT NULL'),
				array('alter_column', 'name' => 'activation_key', 'new_name' => 'activation_key', 'type' => 'VARCHAR(32)'),
				array('alter_column', 'name' => 'u_created', 'new_name' => 'u_created', 'type' => 'MEDIUMINT(8) UNSIGNED'),
				array('alter_column', 'name' => 'u_modified', 'new_name' => 'u_modified', 'type' => 'MEDIUMINT(8) UNSIGNED'),
				array('alter_column', 'name' => 'deleted', 'new_name' => 'deleted', 'type' => 'ENUM(\'1\')'),
				array('alter_column', 'name' => 'blocked', 'new_name' => 'blocked', 'type' => 'ENUM(\'1\')'),
			),
			9 => array(
				array('add_column', 'name' => 'last_login', 'type' => 'INT(11) NULL'),
				array('add_column', 'name' => 'delete_time', 'type' => 'INT(11) NULL'),
			),
			10 => array(
				array('add_column', 'name' => 'privacy_policy_ok', 'type' => 'BOOL NULL DEFAULT 0'),
			),
			11 => array(
				array('alter_column', 'name' => 'privacy_policy_ok', 'new_name' => 'privacy_policy_ok', 'type' => 'INT(11) NULL'),
				//array('alter_column', 'name' => 'privacy_policy_ok', 'new_name' => 'privacy_policy_version'),
			),
			12 => array(
				array('add_column', 'name' => 'u_bday', 'type' => 'INT(11) NULL'),
				array('add_column', 'name' => 'form_filled', 'type' => 'INT(11) NULL'),
			),
		),
		'tans' => array(
			0 => array(
				array('add_column', 'name' => 'test_path', 'type' => 'VARCHAR(255) NULL'),
			),
			1 => array(
				array('add_column', 'name' => 'privacy_policy_ok', 'type' => 'INT(11) NULL'),
			),
			2 => array(
				array('add_column', 'name' => 'form_filled', 'type' => 'INT(11) NULL'),
			),			
		),
		'test_run_blocks' => array(
			0 => array(
				array('add_column', 'name' => 'available_items', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'shown_items', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'available_required_items', 'type' => 'INT(11)'),
				array('add_column', 'name' => 'answered_required_items', 'type' => 'INT(11)'),
			),
			/* Automaticly added 3.0 -> 3.2 by updateTestRun.php, only uncomment for SVN use
			1 => array
				array('add_column', 'name' => 'step', 'type' => 'INT(11) DEFAULT NULL'),
			),*/
		),
		'test_run_block_content' => array(
			0 => array( 
				array('add_index', 'name' => 'test_run_id'),
				array('add_index', 'name' => 'subtest_id'),
			),
			/* Automaticly done 3.0 -> 3.2 by updateTestRun.php, only uncomment for SVN use
			1 => array(
				array('trans_content'),
				),
			),*/
		),
		'templates' => array(
			0 => array(
				array('add_column', 'name' => 'editable', 'type' => 'INT(11)'),
			),
		),
		'locations' => array(
			0 => array(
				array('add_column', 'name' => 'duration', 'type' => 'VARCHAR(5)'),
			),
			1 => array(
				array('add_index', 'name' => 'item_id'),
			),
			2 => array(
				array('drop_column', 'name' => 'type'),
				array('add_column', 'name' => 'used', 'type' => 'INTEGER(1)'),
			),
		),
		'privacy_policys' => array(
			0 => array(
				array('add_column', 'name' => 'exp_range', 'type' => 'INTEGER(3)'),
			),
			1 => array(
				array('add_column', 'name' => 'closed', 'type' => 'INT(11)'),
			),
		),
		'cron_jobs' => array(
			0 => array(
				array('add_column', 'name' => 'id', 'type' => 'MEDIUMINT(8) UNSIGNED NOT NULL'),
				array('add_column', 'name' => 'tstart', 'type' => 'INT(11) NOT NULL'),
				array('add_column', 'name' => 'type', 'type' => 'VARCHAR(32) NOT NULL'),
				array('add_column', 'name' => 'slave', 'type' => 'INT(8) DEFAULT NULL'),
				array('add_column', 'name' => 'destination', 'type' => 'VARCHAR(64) NOT NULL'),
				array('add_column', 'name' => 'content', 'type' => 'LONGTEXT NULL DEFAULT NULL'),
				array('add_column', 'name' => 'done', 'type' => 'INT(11) NULL DEFAULT NULL'),
				array('add_index', 'name' => 'tstart'),	
				array('add_index', 'name' => 'done'),
			),
		),
	);

	function TableMigrations(&$front)
	{
		$this->front = &$front;
	}

	function setDb(&$db)
	{
		$this->db = &$db;
	}

	/**
	 * Return the number of migrations defined for this table (a.k.a. the most
	 * recent version of the table).
	 *
	 * @param string The table to check.
	 */
	function getVersion($table)
	{
		if (!isset($this->migs[$table])) return 0;
		return count($this->migs[$table]);
	}

	/**
	 * Migrate the schema of a table to the newest version.
	 *
	 * @param string The table to migrate.
	 * @param integer The current version of that table's schema.
	 * @return integer The version we managed to update to.
	 */
	function migrate($table, $from)
	{
		if (!isset($this->migs[$table])) return $from;
		$updates = $this->migs[$table];
		$newest = $from;

		foreach ($updates as $ver1 => $ver2) {
			if ($ver1 < $from) continue;
			foreach ($ver2 as $ver3) {
				$result = $this->doStep($table, $ver3);
				if (!$result) return $ver2;
			}
			$newest = $ver1+1;
		}
		return $newest;
	}

	function doStep($table, $info)
	{
		$meth = snakeToCamel($info[0], false);
		return $this->$meth($table, $info);
	}

	function migrationError($table, $message)
	{
		$this->front->status(MSG_RESULT_NEG, 'dbinit.migrate.dberr', array('table' => $table, 'message' => $message));
	}

	/* ***** Utility functions for migrating ***** */

	function _tryQuery($table, $query, $params = array())
	{
		$result = $this->db->query($query, $params);
		if ($this->db->isError($result)) {
			$this->migrationError($table, $result->getMessage());
			return false;
		}
		return true;
	}
	function addColumn($table, $info)
	{
		return $this->_tryQuery($table, 'ALTER TABLE '.DB_PREFIX."$table ADD COLUMN $info[name] $info[type]");
	}

	function alterColumn($table, $info)
	{
		return $this->_tryQuery($table, 'ALTER TABLE '.DB_PREFIX."$table CHANGE $info[name] $info[new_name] $info[type]");
	}
	function addIndex($table, $info)
	{
		$columns = isset($info["columns"]) ? $info["columns"] : array($info["name"]);
		return $this->_tryQuery($table, "ALTER TABLE ".DB_PREFIX."$table ADD INDEX $info[name] (".implode(",", $columns).")");
	}
	function alterColumnType($table, $info)
	{
		if (substr($this->db->dbsyntax, 0, 5) == "mysql") {
			return $this->_tryQuery($table, 'ALTER TABLE '.DB_PREFIX."$table CHANGE $info[name] $info[name] $info[type]");
		}
		return $this->_tryQuery($table, 'ALTER TABLE '.DB_PREFIX."$table ALTER COLUMN $info[name] TYPE $info[type]");
	}
	function copyColumn($table, $info)
	{
		return $this->_tryQuery($table, 'UPDATE '.DB_PREFIX."$table SET $info[target]=$info[source]");
	}
	function deleteRows($table, $info)
	{
		$myTable = $table;
		if (isset($info['table'])) $myTable = $info['table'];

		$where = '';
		if (isset($info['where'])) $where = " WHERE $info[where]";

		return $this->_tryQuery($table, "DELETE FROM ".DB_PREFIX. $myTable . $where);
	}
	function dropColumn($table, $info)
	{
		return $this->_tryQuery($table, 'ALTER TABLE '.DB_PREFIX."$table DROP COLUMN $info[name]");
	}
	function dropIndex($table, $info)
	{
		return $this->_tryQuery($table, "DROP INDEX $info[name] ON ".DB_PREFIX."$table");
	}

	// EXOTIC MIGRATION FUNCTIONS
	
	function transContent () {
		$dir = dirname($_SERVER['SCRIPT_NAME']);
		$file = basename($dir);
		$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		$link = ($https ? "https://" : "http://").$_SERVER['HTTP_HOST'].$dir.'/installer/testrunconverter/index.php';
			echo"<head><meta http-equiv=\"refresh\" content=\"1;url=$link\" /></head>";
		return true;
	}
	
	function transContent2 () {
		$dir = dirname($_SERVER['SCRIPT_NAME']);
		$file = basename($dir);
		$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		$link = ($https ? "https://" : "http://").$_SERVER['HTTP_HOST'].$dir.'/installer/testrunconverter/index2.php';
			echo"<head><meta http-equiv=\"refresh\" content=\"1;url=$link\" /></head>";
		return true;
	}

	function fixPositions($table, $info)
	{
		$rows = $this->db->getAll("SELECT id, page_id FROM ".DB_PREFIX."$table ORDER BY page_id, pos, id");

		$page_id = -1;
		foreach ($rows as $row) {
			if ($page_id != $row['page_id']) {
				$page_id = $row['page_id'];
				$pos = 1;
			}
			$res = $this->_tryQuery($table, "UPDATE ".DB_PREFIX."$table SET pos = $pos WHERE id = ?", array($row['id']));
			$pos++;

			if (!$res) return false;
		}
		return true;
	}

	function migrateItemType()
	{
		$query = "SELECT i.id, template, i.type, default_item_template FROM ".DB_PREFIX."items AS i INNER JOIN ".DB_PREFIX."item_blocks AS b ON b.id = i.block_id";
		$rows = $this->db->getAll($query);

		foreach ($rows as $row) {
			if ($row['type'] && $row['type'] != 'Item') continue;

			$template = 'mcsa.html';
			if ($row['template'] && $row['template'] != '.html') {
				$template = $row['template'];
			}
			elseif ($row['default_item_template'] && $row['template'] != '.html') {
				$template = $row['default_item_template'];
			}
			$item = snakeToCamel(str_replace('.html', '', $template)).'Item';

			$table = 'items';

			$res = $this->_tryQuery($table, "UPDATE ".DB_PREFIX."$table SET type= ? WHERE id = ?", array($item, $row['id']));
			if (!$res) return false;
		}
		return true;
	}

	function migrateItemBlockType()
	{
		$rows = $this->db->getAll("SELECT id, default_item_template FROM ".DB_PREFIX."item_blocks");

		foreach ($rows as $row) {
			$template = 'mcma.html';
			if ($row['default_item_template']) {
				$template = $row['default_item_template'];
			}
			$item = snakeToCamel(str_replace('.html', '', $template)).'Item';

			$table = 'item_blocks';

			$res = $this->_tryQuery($table, "UPDATE ".DB_PREFIX."$table SET default_item_type= ? WHERE id = ?", array($item, $row['id']));
			if (!$res) return false;
		}
		return true;
	}

	function fixMigratedItemTypes()
	{
		$query = "SELECT i.id, i.type, default_item_type FROM ".DB_PREFIX."items AS i INNER JOIN ".DB_PREFIX."item_blocks AS b ON b.id = i.block_id";
		$rows = $this->db->getAll($query);

		foreach ($rows as $row) {
			if (!$row['type'] || $row['type'] != 'Item') continue;

			$item = $row['default_item_type'];
			$table = 'items';

			$res = $this->_tryQuery($table, "UPDATE ".DB_PREFIX."$table SET type= ? WHERE id = ?", array($item, $row['id']));
			if (!$res) return false;
		}
		return true;
	}

}
