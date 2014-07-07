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
 * A group of dimensions that can be used in conjunction (e.g. for displaying
 * top X results out of a selected set of dimensions).
 *
 * @package Core
 */
class DimensionGroup extends DataObject
{
	
	static protected $table = 'dimension_groups';
	
	static protected $useSequence = true;
	
	static protected $prototype = array (
		'block_id' => NULL,
		'dimension_ids' => array(),
		'title' => NULL,
	);	
	
	static protected $retrievalQueries = array(
		'getAllByBlockId' => 'SELECT * FROM @T WHERE block_id = @{*} ORDER BY title',
	);
	
	static protected $manipulationQueries = array(
		'deleteById' => 'DELETE FROM @T WHERE id = @{*}',
	);

	/**
	 * Returns all dimension groups for a given block.
	 *
	 * @param int ID of the feedback block to look at.
	 * @return DimensionGroup[]
	 * @static
	 */
	function getAllByBlockId($blockId)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$query = $db->getAll("SELECT * FROM ".DB_PREFIX."dimension_groups WHERE block_id=?", array($blockId));
		if ($db->isError($query)) return array();
		if (!$query) return array();

		$res = array();
		foreach ($query as $row) {
			$res[$row['id']] = new DimensionGroup($row);
		}
		return $res;
	}
	
	static function validate($data)
	{
		if (is_array($data['dimension_ids'])) {
			$data['dimension_ids'] = implode(",",$data['dimension_ids']);
		}
		return $data;
	}
	
	static function autoConvert($key,$value)
	{
		if ($key == 'dimension_ids') {
			return array_filter(explode(",",$value));
		}
		return $value;
	}
	
	function delete()
	{
		return DataObject::query('DimensionGroup', 'deleteById', $this->get('id'));
	}

	function getId()
	{
		return $this->fields['id'];
	}
	
	function getTitle()
	{
		return $this->get('title');
	}

	function setTitle($title)
	{
		$this->title = $title;
		$this->db->query("UPDATE ".DB_PREFIX."dimension_groups SET title=? WHERE id=?", array($title, $this->id));
	}

	function usedInFeedback()
	{
		$counter = 0;
		$data = array('dimension_groups' => array($this->get('id') => 1));
		$block = new FeedbackBlock($this->get('block_id'));

		// Get all conditions for this block. Yuck. :(
		$pages = $block->getTreeChildren();
		foreach ($pages as $page) {
			if ($page->getDisabled()) continue;
			$paras = $page->getChildren();
			foreach ($paras as $para) {
				// Check conditions
				$conds = $para->getConditions();
				foreach ($conds as $cond) {
					$plugin = Plugin::load('extconds', $cond['type']);
					if (!$plugin->checkIds($cond, $data)) continue;
					$counter++;
				}

				// Check paragraph text using evil black magic
				// i.e. the text expanding routine can use callbacks. we use the unusual one below.
				if (true === FeedbackGenerator::expandText($para->getContents(), array($this, '_usedInFeedbackText'), array($data))) $counter++;
			}
		}
		return $counter;
	}

	function _usedInFeedbackText($type, $params, $ids)
	{
		$plugin = Plugin::load('feedback', $type, array(NULL));
		if (!$plugin) return '';
		if (!$plugin->checkIds($params, $ids)) return '';
		return true;
	}



}

