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
 * DB_Table classes that define the structure of the testMaker tables.
 *
 * @package Installer
 */

/**
 * Load the PEAR::DB::Table class
 */
start("DB::Table");
require_once('DB/Table.php');
stop();

/**
 * Maintains versioning information for all the other tables. The information in
 * this table is used to update the structure of outdated tables.
 *
 * @package Installer
 */
class TableVersions extends DB_Table
{
	var $col = array(
		'table_name' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'version' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'table_name'		=> 'unique',
	);
}

/**
 * @package Installer
 */
class ItemBlocks extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'type' => array(
			'type' 		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'irt' => array(
			'type' 		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'default'	=> null,
	),
		'description' => array(
			'type'		=> 'clob',
	),
		'introduction' => array(
			'type'		=> 'text',
	),
		'hidden_intro' => array(
			'type'		=> 'integer',
			'size'		=> 1,
			'default'	=> null,
	),
		'intro_firstonly' => array(
			'type'		=> 'integer',
			'size'		=> 1,
			'default'	=> null,
	),
		'intro_pos' => array(
			'type'		=> 'integer',
			'size'		=> 1,
			'default'	=> null,
	),
		'intro_label' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'default'	=> null,
	),
		'original' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'permissions_recursive' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		'owner' => array(
			'type'		=> 'integer',
			'default'	=> null,
	),
		'default_item_template' => array(
			'type'		=> 'varchar',
			'size'		=> 64,
			'require'	=> true,
		
	),
		'default_item_type' => array(
			'type'		=> 'varchar',
			'size'		=> 64,
			'require'	=> true,
	),
		'default_template_align' => array(
			'type'		=> 'varchar',
			'size'		=> 1,
			'require'	=> true,
		
	),
		'default_template_cols' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'default_item_force' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'max_items' => array(
			'type'		=> 'integer',
			'default'	=> null,
	),
		'max_sem' => array(
			'type'		=> 'double',
			'default'	=> null,
	),
		'max_time' => array(
			'type'		=> 'integer',
			'default'	=> null,
	),
		'random_order' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		'items_per_page' => array(
			'type'		=> 'integer',
	),
		'default_min_item_time' => array(
			'type'		=> 'integer',
	),
		'default_max_item_time' => array(
			'type'		=> 'integer',
	),
		'default_conditions' => array(
			'type'		=> 'boolean',
	),
		'conditions_need_all' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		'media_connect_id' => array(
			'type'		=> 'integer',
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'original'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class DimensionGroups extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'dimension_ids' => array(
			'type'		=> 'clob',
			'require'	=> true,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'block_id'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class Dimensions extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'name' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'description' => array(
			'type'		=> 'clob',
	),
		'block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'reference_value' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'reference_value_type' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'score_type' => array(
			'type'		=> 'integer',
			'require'	=> false,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'block_id'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class DimensionClassSizes extends DB_Table
{
	var $col = array(
		'dimension_id' => array(
			'type' => 'integer',
			'require' => true,
			'default'	=> 0,
	),
		'score' => array(
			'type' => 'integer',
			'require' => true,
			'default'	=> 0,
	),
		'class_size' => array(
			'type' => 'integer',
			'require' => true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'dimension_score' => array(
			'type' => 'unique',
			'cols' => array('dimension_id', 'score'),
	),
	);
}

/**
 * @package Installer
 */
class DimensionsConnect extends DB_Table
{
	var $col = array(
		'dimension_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'item_answer_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'score' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'dim_ans_idx' => array(
			'type'	=> 'unique',
			'cols'	=> array('dimension_id', 'item_answer_id'),
	),
	);
}

class EditLogs extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'user_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'stamp' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'optype' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'target_type' => array(
			'type'		=> 'varchar',
			'size'		=> 32,
	),
		'target_id' => array(
			'type'		=> 'integer',
	),
		'target_title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'details' => array(
			'type'		=> 'clob',
	),
	);

	var $idx = array(
		'user_id' => 'normal',
		'stamp' => 'normal',
		'optype' => 'normal',
		'target_type' => 'normal',
	);
}

/**
 * @package Installer
 */
class FeedbackConditions extends DB_Table
{
	var $col = array(
		'paragraph_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'dimension_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'description' => array(
			'type'	=> 'clob',
	),
		'min_value' => array(
			'type'		=> 'integer',
	),
		'max_value' => array(
			'type'		=> 'integer',
	),
	);

	var $idx = array(
		'feedback_cond' => array(
			'type' => 'unique',
			'cols' => array('paragraph_id', 'dimension_id'),
	),
		'dimension_id' => 'normal',
	);
}

/**
 * @package Installer
 */
class FeedbackParagraphs extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'page_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'content' => array(
			'type'		=> 'clob',
			'require'	=> true,
	),
		'ext_conditions' => array(
			'type'		=> 'text',
			'require'	=> false,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'page_id'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class ContainerBlocks extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'description' => array(
			'type'		=> 'clob',
	),
		'original' => array(
			'type'		=> 'integer',
	),
		'permissions_recursive' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'owner' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'direct_access_key' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'def_language' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'progress_bar' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'pause_button' => array(
			'type'		=> 'integer',
			'default' => null,
	),
		'show_subtests' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'subtest_inactive' => array(
			'type'		=> 'boolean',
			'default' => null,
	),
		'enable_skip' => array(
			'type'		=> 'boolean',
			'default' => null,
	),
		'random_order' => array(
			'type'		=> 'boolean',
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		'password' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'default' => null,
	),
		'open_date' => array(
			'type' => 'integer',
			'default' => null,
	),
		'close_date' => array(
			'type' => 'integer',
			'default' => null,
			
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'tan_ask_email' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 1,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'original'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class InfoBlocks extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'description' => array(
			'type'		=> 'clob',
	),
		'original' => array(
			'type'		=> 'integer',
	),
		'permissions_recursive' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> null,
	),
		'owner' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'media_connect_id' => array(
			'type'		=> 'integer',
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'original'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class FeedbackBlocks extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'description' => array(
			'type'		=> 'clob',
	),
		'original' => array(
			'type'		=> 'integer',
	),
		'permissions_recursive' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'disabled'	=> null,
	),
		'owner' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'media_connect_id' => array(
			'type'		=> 'integer',
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'cert_enabled' => array(
			'type'		=> 'tinyint',
			'default'	=> null,
	),
		'cert_fname_item_id' => array(
			'type'		=> 'integer',
			'default'	=> null,
	),
		'cert_lname_item_id' => array(
			'type'		=> 'integer',
			'default'	=> null,
	),
		'cert_bday_item_id' => array(
			'type' 		=> 'integer',
			'default'	=> null,
	),
		'cert_template_name' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'default'	=> null,
	),
		'cert_disable_barcode' => array(
			'type' 		=> 'integer',
			'default'	=> null,
	),
		'show_in_summary' => array(
			'type'		=> 'integer',
			'default' => null,
	),
	);

	var $idx = array(
		'id'			=> 'unique',
		'original'		=> 'normal',
	);
}

/**
 * @package Installer
 */
class FeedbackBlocksConnect extends DB_Table
{
	var $col = array(
		'item_block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'feedback_block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
	);

	var $idx = array(
		'item_feedback' => array(
			'type' => 'unique',
			'cols' => array('feedback_block_id', 'item_block_id'),
	),
		'item_block_id' => 'normal',
	);
}

/**
 * @package Installer
 */
class FeedbackScores extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'dimension_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'user_id' => array(
			'type'		=> 'integer',
	),
		'score' => array(
			'type'		=> 'integer',
	),
	);

	var $idx = array(
		'id'			=> 'unique',
		'block_id'		=> 'normal',
		'dimension_id'	=> 'normal',
		'score'			=> 'normal',
	);
}

/**
 * @package Installer
 */
class Items extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'question' => array(
			'type'		=> 'clob',
	),
		'type' => array(
			'type'		=> 'varchar',
			'size'		=> '64',
			'require'	=> true,
	),
		'template_align' => array(
			'type'		=> 'varchar',
			'size'		=> '1',
	),
		'template_cols' => array(
			'type'		=> 'integer',
			'size'		=> '2',
	),
		'min_time' => array(
			'type'		=> 'integer',
	),
		'max_time' => array(
			'type'		=> 'integer',
	),
		'difficulty' => array(
			'type'		=> 'single',
	),
		'discrimination' => array(
			'type'		=> 'single',
	),
		'guessing' => array(
			'type'		=> 'single',
	),
		'answer_force' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 0,
	),
		'conditions_need_all' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'restriction' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'required_plugins' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'block_id'	=> 'normal',
		'pos'	=> 'normal',
	);
}

class Locations extends DB_Table
{
	var $col = array(
		'item_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'used' => array(
			'type'		=> 'integer',
			'size'		=> '1',
			'require'	=> true,
	),
		'startTime' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
	),
		'endTime' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
	),
		'duration' => array(
			'type'		=> 'varchar',
			'size'		=> 5,
	),
		'description' =>  array(
			'type'		=> 'clob',
	),
	);

	var $idx = array(
		'item_id'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class ItemConditions extends DB_Table
{
	var $col = array(
		'id'		=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'parent_item_id'	=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'pos'			=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'item_block_id'	=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'item_id'		=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'answer_id'		=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'chosen'		=> array(
			'type'		=> 'boolean',
			'require'	=> false,
	),
	);

	var $idx = array(
		'id'				=> 'unique',
		'parent_item_id'	=> 'normal',
		'pos'				=> 'normal',
	);
}

/**
 * @package Installer
 */
class InfoPages extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'content' => array(
			'type'		=> 'clob',
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> null,
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'block_id'	=> 'normal',
		'pos'		=> 'normal',
	);
}

/**
 * @package Installer
 */
class InfoConditions extends DB_Table
{
	var $col = array(
		'id'		=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'parent_item_id'	=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'pos'			=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'item_block_id'	=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'item_id'		=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'answer_id'		=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'chosen'		=> array(
			'type'		=> 'boolean',
			'require'	=> false,
	),
	);

	var $idx = array(
		'id'				=> 'unique',
		'parent_item_id'	=> 'normal',
		'pos'				=> 'normal',
	);
}

/**
 * @package Installer
 */
class FeedbackPages extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'title' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default' => 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'block_id'	=> 'normal',
		'pos'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class ItemAnswers extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'item_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'answer' => array(
			'type'		=> 'clob',
	),
		'correct' => array(
			'type'		=> 'boolean',
	),
		'disabled' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'			=> 'unique',
		'item_id'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class ItemBlockAnswers extends DB_Table
{

	var $col = array(

		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'item_block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'answer' => array(
			'type'		=> 'clob',
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),

	);

	var $idx = array(
		'id'			=> 'unique',
		'item_block_id'	=> 'normal',
	);

}

/**
 * @package Installer
 */
class Users extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'mediumint',
			'require'	=> true,
			'unsigned'	=> true,
	),
		'username' => array(
			'type'		=> 'varchar',
			'size'		=> 64,
			'require'	=> true,
	),
		'password_hash' => array(
			'type'		=> 'char',
			'size'		=> 32,
			'require'	=> true,
	),
		'full_name' => array(
			'type'		=> 'varchar',
			'size'		=> 64,
			'require'	=> true,
	),
		'email' => array(
			'type'		=> 'varchar',
			'size'		=> 64,
			'require'	=> true,
	),
		'lang' => array(
			'type'		=> 'char',
			'size'		=> 2,
			'require'	=> true,
	),
		'activation_key' => array(
			'type'		=> 'varchar',
			'size'		=> 32,
	),
		'confirmation_key' => array(
			'type'		=> 'varchar',
			'size'		=> 16,
	),
		'email_key' => array(
			'type'		=> 'varchar',
			'size'		=> 16,
	),
		'new_email' => array(
			'type'		=> 'varchar',
			'size'		=> 64,
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'mediumint',
			'size'		=> 8,
			'default'	=> NULL,
			'unsigned'	=> true,
	),
		'u_modified' => array(
			'type'		=> 'mediumint',
			'size'		=> 8,
			'default'	=> NULL,
			'unsigned'	=> true,
			
	),
		'deleted' => array(
			'type'		=> 'enum',
			'size'		=> "'1'",
			'require'	=> false,
	),
		'blocked' => array(
			'type'		=> 'enum',
			'size'		=> "'1'",
			'default'	=> NULL,
	),
		'last_login' => array(
			'type'		=> 'integer',
			'default'	=> NULL,
	),
		'delete_time' => array(
			'type'		=> 'integer',
			'default'	=> NULL,
	),
		'privacy_policy_ok' => array(
			'type'		=> 'integer',
			'default'	=> NULL,
	),
		'u_bday' => array(
			'type'		=> 'integer',
			'default'	=> NULL,
	),
		'form_filled' => array(
			'type'		=> 'integer',
			'default'	=> NULL,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'username'	=> 'unique',
		'activation_key' => 'unique',
		't_created'	=> 'unique',
	);
}

/**
 * @package Installer
 */
class Tans extends DB_Table
{
	var $col = array(
		'access_key' => array(
			'type'		=> 'varchar',
			'size'		=> 6,
			'require'	=> true,
	),
		'block' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'test_run' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'mail' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'default'	=> null,
	),
		'test_path' => array(
			'type'		=> 'varchar',
			'size'		=> '255',
			'default'	=> null,
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'privacy_policy_ok' => array(
		'type'		=> 'integer',
		'default'	=> NULL,
	),
		'form_filled' => array(
		'type'		=> 'integer',
		'default'	=> NULL,
	),
	);

	var $idx = array(
		'access_key'	=> 'unique',
		'block'			=> 'normal',
	);
}

/**
 * @package Installer
 */
class Groups extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'groupname' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'description' => array(
			'type'		=> 'clob',
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		't_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'u_modified' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'autoadd' => array(
			'type'		=> 'integer',
	),
	);

	var $idx = array(
		'id'		=> 'unique',
		'groupname'	=> 'unique',
	);
}

/**
 * @package Installer
 */
class GroupPermissions extends DB_Table
{
	var $col = array(
		'group_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'block_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'permission_name' => array(
			'type'		=> 'varchar',
			'size'		=> 32,
			'default'	=> null,
	),
		'permission_value' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'default'	=> null,
	),
	);

	var $idx = array(
		'group_block' => array (
			'type' => 'unique',
			'cols' => array('group_id', 'block_id', 'permission_name'),
	),
		'block_id' => 'normal',
	);
}

/**
 * @package Installer
 */
class GroupsConnect extends DB_Table
{
	var $col = array(
		'user_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'group_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'user_group' => array(
			'type' => 'unique',
			'cols' => array('user_id', 'group_id'),
	),
		'group_id' => 'normal',
	);
}

/**
 * @package Installer
 */
class Messages extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'content' => array(
			'type'		=> 'clob',
	),
		't_created' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
	);
}

/**
 * @package Installer
 */
class BlocksType extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'type' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
	);

	var $idx = array(
		'id'		=> 'unique',
	);
}

/**
 * @package Installer
 */
class Media extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'media_connect_id' => array(
			'type'		=> 'integer',
			'required'	=> true,
	),
		'filename' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
	),
		'filetype' => array(
			'type'		=> 'integer',
			'size'		=> 1,
	),
	);

	var $idx = array(
		'id'				=> 'unique',
		'media_connect_id'	=> 'normal',
	);
}

/**
 * @package Installer
 */
class BlocksConnect extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'pos' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'parent_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'disabled' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),

	);

	var $idx = array(
		'id_parentid' => array(
			'type' => 'unique',
			'cols' => array('id', 'parent_id'),
	),
		'pos' => array(
			'type' => 'normal',
			'cols' => 'pos',
	),
	);
}

/**
 * @package Installer
 */

class TestRuns extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'user_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'permission_groups' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> false,
			'default'	=> null,
	),
		'test_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'test_path' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> false,
	),
		'access_type' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> false,
	),
		'referer' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> false,
	),
		'client_ip' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> false,
	),
		'client_host' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> false,
	),
		'client_useragent' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> false,
	),
		'start_time' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'duration' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'available_pages' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'shown_pages' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'available_items' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'shown_items' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'answered_items' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'available_required_items' => array(
			'type'		=> 'integer',
			'require'	=> false,
			'default'	=> null,
	),
		'answered_required_items' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'next_item_ids' => array(
			'type'		=> 'clob',
			'require'	=> TRUE,
	),
		'next_block_id' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'structure_version' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'step' => array(
			'type'		=> 'integer',
			'require'	=> false,
			'default'	=> null,
	),
	);

	var $idx = array(
		'id' => 'unique',
		'user_id' => 'normal',
		'test_id' => 'normal',
		'access_type' => 'normal',
		'start_time' => 'normal',
		'structure_version' => 'normal',
	);
}

/**
 * @package Installer
 */

class TestRunBlocks extends DB_Table
{
	var $col = array(
		'test_run_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'subtest_id' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'available_items' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'shown_items' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'available_required_items' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'answered_required_items' => array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'step' => array(
			'type'		=> 'integer',
			'require'	=> false,
			'default'	=> NULL,
	),
	);

	var $idx = array(
		'testrun_subtest' => array(
			'type'	=> 'unique',
			'cols'	=> array('test_run_id', 'subtest_id'),
	),
	);
}

class TestStructures extends DB_Table
{
	var $col = array(
		'test_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'version' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'content' => array(
			'type'		=> 'clob',
			'require'	=> true,
	),
		'description' => array(
			'type'		=> 'clob',
			'require'	=> false,
	),
		'stamp' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'testmaker_version' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'user_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
	);

	var $idx = array(
		'test_id' => 'normal',
	);
}

/**
 * @package Installer
 */

class ItemStyle extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'test_id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'background_color' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'font_family' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'font_size' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'font_style' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'font_weight' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'color' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'item_background_color' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'dist_background_color' => array(
			'type'		=> 'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'logo' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'logo_align' => array(
			'type'		=>'varchar',
			'size'		=> 10,
			'require'	=> true,
	),
		'logo_show' => array(
			'type'		=> 'integer',
			'size'		=> 8,
			'require'	=> true,
			'default'	=> 0,
	),
		'page_width' => array(
			'type'		=> 'boolean',
			'default'	=> null,
	),
		'item_borders' => array(
			'type'		=> 'integer',
			'default'	=> null,
	),
		'use_parent_style' => array(
			'type'		=> 'boolean',
			'require'	=> true,
			'default'	=> 1,
	),
	);

	var $idx = array(
		'id' => 'unique',
		'test_id' => 'normal',
	);
}

/**
 * @package Installer
 */
class Settings extends DB_Table
{
	var $col = array(
		'name' => array(
			'type' => 'varchar',
			'size' => 32,
			'require' => true,
	),
		'content' => array(
			'type' => 'clob',
			'require' => true,
	),
	);

	var $idx = array(
		'name' => 'unique',
	);
}

/**
 * @package Installer
 */

class ErrorLog extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'filemd5' => array(
			'type'		=> 'varchar',
			'size'		=> 32,
			'require'	=> true,
			'default'	=> 0,
	),
		'line' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'type' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'last_mail' => array(
			'type'		=> 'integer',
			'require'	=> true,
			'default'	=> 0,
	),
		'countError' => array(
			'type'		=> 'integer',
			'default'	=> null,
	),
	);

	var $idx = array(
		'id' => 'unique',
		'filemd5' => 'normal',
		'line' => 'normal',
		'type' => 'normal',
	);
}

class ErrorLogVerbose extends DB_Table
{
	var $col = array(
		'fingerprint' => array(
			'type'		=> 'varchar',
			'size'		=> 40,
			'require'	=> true,
	),
		'last_sent' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'countError' => array(
			'type'		=> 'integer',
			'require'	=> true,
	),

	);

	var $idx = array(
		'fingerprint' => 'unique',
		'countError' => 'normal',
	);
}

class Certificates extends DB_Table
{
	var $col = array(
		'id' => array(
			'type' => 'integer',
			'require' => true,
	),
		'random' => array(
			'type' => 'integer',
			'require' => true,
	),
		'stamp' => array(
			'type' => 'integer',
			'require' => true,
	),
		'test_run_id' => array(
			'type' => 'integer',
			'require' => true,
	),
		'checksum' => array(
			'type' => 'varchar',
			'size' => 32,
			'require' => true,
	),
		'name' => array(
			'type' => 'varchar',
			'size' => 80,
	),
		'birthday' => array(
			'type' => 'integer',
	),
	);

	var $idx = array(
		'id' => 'unique',
	);
}

class ExportVarConstellation extends DB_Table
{
	var $col = array(
		'Test_id' => array(
			'type' => 'integer',
			'require' => true,
	),
		'const' => array(
			'type' => 'clob',
	),
	);

	var $idx = array(
		'Test_id' => 'unique',
	);

}

class Emails extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
		'subject' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'body' => array(
			'type'		=> 'varchar',
			'size'		=> 255,
			'require'	=> true,
	),
		'testrun_sent' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> false,
	),
		'send_start' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> false,
	),
	);

	var $idx = array(
		'id' => 'unique',
	);
}

class EmailsConnect extends DB_Table
{
	var $col = array(
		'email_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
		'test_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
		'conditions_need_all' => array(
			'type'		=> 'boolean',
			'require'	=> true,
	),
		'participants' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
		'participants_group' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
	);
	var $idx = array(
		'email_id' => 'normal',
		'test_id' => 'normal',
	);
}

class EmailConditions extends DB_Table
{
	var $col = array(
		'email_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
		'test_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
		'pos' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
	),
		'item_block_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> false,
	),
		'item_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> false,
	),
		'answer_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> false,
	),
		'chosen' => array(
			'type'		=> 'boolean',
			'require'	=> false,
	),
	);

	var $idx = array(
		'email_id' => 'normal',
		'test_id' => 'normal',
	);

}

class BlockStructure extends DB_Table
{
	var $col = array(
		'test_run_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
			'unsigned'	=> true,
	),
		'subtest_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> false,
			'unsigned'	=> true,
	),
		'test_structure' => array(
			'type'		=> 'text',
			'require'	=> true,
	),
	);
	var $idx = array(
		'test_run_id' => 'normal',
		'subtest_id' => 'normal',
	);
}

class TestRunBlockContent extends DB_Table
{
	var $col = array(
		'test_run_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> true,
			'unsigned'	=> true,
	),
		'subtest_id' => array(
			'type'		=> 'integer',
			'size'		=> 11,
			'require'	=> false,
			'default'	=> null,
			'unsigned'	=> true,
	),
		'content' => array(
			'type'		=> 'text',
			'require'	=> true,
	),
	);

	var $idx = array(
		'testrun_subtest' => array(
			'type' => 'unique',
			'cols' => array('test_run_id', 'subtest_id'),
		),
		
	);

}

class PrivacyPolicys extends DB_Table
{
	var $col = array(
		'version' => array(
			'type'		=> 'integer',
			'size'		=> 11,
		),
		'content' => array(
			'type'		=> 'clob',
			'size'		=> 11,
		),
		't_stamp' => array(
			'type'		=> 'integer',
		),
		'exp_range' => array(
			'type'	  => 'integer',
			'size'		=> 3,
		),
		'closed' => array(
			'type'	  => 'integer',
			'size'		=> 11,
		),
	);
}	


class CronJobs extends DB_Table
{
	var $col = array(
		'id' => array(
			'type'		=> 'mediumint',
			'require'	=> true,
			'unsigned'	=> true,
		),
		'tstart' => array(
			'type'		=> 'integer',
			'size'		=> 11,
		),
		'type' => array(
			'type'		=> 'varchar',
			'size'		=> 32,
		),		
		'slave' => array(
			'type'		=> 'integer',
			'size'		=> 8,
			'default'	=> null,
		),
		'destination' => array(
			'type'		=> 'varchar',
			'size'		=> 64,
		),
		'content' => array(
			'type'	  => 'clob',
			'default'	=> null,
		),
		'done' => array(
			'type'	  => 'integer',
			'size'		=> 11,
			'default'	=> null,
		),
	);
	
	var $idx = array(
		'tstart'	=> 'normal',
		'done'	=> 'normal',
	);
}	

/**
* @package Installer
*/
class BlockConditions extends DB_Table
{
	var $col = array(
		'id'		=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'parent_item_id'	=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'pos'			=> array(
			'type'		=> 'integer',
			'require'	=> true,
	),
		'item_block_id'	=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'item_id'		=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'answer_id'		=> array(
			'type'		=> 'integer',
			'require'	=> false,
	),
		'chosen'		=> array(
			'type'		=> 'boolean',
			'require'	=> false,
	),
	);

	var $idx = array(
		'id'				=> 'unique',
		'parent_item_id'	=> 'normal',
		'pos'				=> 'normal',
	);
}


?>
