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
 * @package Portal
 */

require_once(PORTAL.'ExtCondPlugin.php');

class EcTop extends ExtCondPlugin
{
	var $types = array(
		'group_id'		=> 'dimension_groups',
		'target_id'		=> 'dimensions',
	);

	function _renderCondition($dims, $dimgroups, $page, $cond, $type)
	{
		switch ($type) {
		case 'view':
			$this->tpl->setVariable('dim_name', $dims[$cond['target_id']]['dim_name']);
			$this->tpl->setVariable('group_name', $dimgroups[$cond['group_id']]['group_name']);
			if ($cond['mode'] == 'min') {
				$this->tpl->touchBlock('qualifier_min');
				$this->tpl->hideBlock('qualifier_max');
			} else {
				$this->tpl->touchBlock('qualifier_max');
				$this->tpl->hideBlock('qualifier_min');
			}
			break;

		case 'edit':
			foreach ($dims as $dimId => $dim) {
				$this->tpl->setVariable('dim_id', $dimId);
				$this->tpl->setVariable('dim_name', $dim['dim_name']);
				$this->tpl->setVariable('dim_selected', ($cond['target_id'] == $dimId ? ' selected="selected"' : ''));
				$this->tpl->parse('dimensions');
			}
			foreach ($dimgroups as $groupId => $grp) {
				$this->tpl->setVariable('group_id', $groupId);
				$this->tpl->setVariable('group_name', $grp['group_name']);
				$this->tpl->setVariable('group_selected', ($cond['group_id'] == $groupId ? ' selected="selected"' : ''));
				$this->tpl->parse('groups');
			}
			if ($cond['mode'] == 'min') {
				$this->tpl->setVariable('min_selected', ' selected="selected"');
			} else {
				$this->tpl->setVariable('max_selected', ' selected="selected"');
			}
			break;
		}

		$this->tpl->setVariable('count', $cond['count']);
	}

	function _getConditionPrototype()
	{
		return array(
			'mode'		=> '',
			'count'		=> '',
			'group_id'	=> '',
			'target_id'	=> '',
		);
	}

	function checkCondition($params, $generator)
	{
		$groupId = $params['group_id'];
		$targetId = $params['target_id'];
		$modeIsMax = ($params['mode'] == 'max'); // otherwise it's 'min'
		$count = $params['count'];

		$dimGroup = DataObject::getById('DimensionGroup', $groupId);
		$groupIds = $dimGroup->get('dimension_ids');

		if (count($groupIds) <= $count) return true;
		if (!in_array($targetId, $groupIds)) return false;

		// Evil hack to speed up filtering :(
		$groupIds = array_flip($groupIds);

		$dimScores = $generator->getScores();
		$dimMaxScores = $generator->getMaxScores();

		$groupScores = array();
		foreach ($dimScores as $dimId => $score) {
			if (isset($groupIds[$dimId]))
				$groupScores[$dimId] = ($score / (double) $dimMaxScores[$dimId]);
		}
		$sort = ($modeIsMax ? 'rsort' : 'sort');
		$sortedScores = $groupScores;
		$sort($sortedScores, SORT_NUMERIC);

		if ($modeIsMax) {
			return ($sortedScores[$count - 1] <= $groupScores[$targetId]);
		} else {
			return ($sortedScores[$count - 1] >= $groupScores[$targetId]);
		}
	}

}

