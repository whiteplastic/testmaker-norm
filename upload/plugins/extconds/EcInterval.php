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

class EcInterval extends ExtCondPlugin
{

	var $types = array(
		'dim_id'		=> 'dimensions',
	);

	function _renderCondition($dims, $dimgroups, $page, $cond, $type)
	{
		switch ($type) {
		case 'view':
			if (isset($cond['dim_id']))
				if (isset($dims[$cond['dim_id']]['dim_name']))
					$this->tpl->setVariable('dim_name', $dims[$cond['dim_id']]['dim_name']);
			break;

		case 'edit':
			foreach ($dims as $dimId => $dim) {
				$this->tpl->setVariable('dim_id', $dimId);
				$this->tpl->setVariable('dim_name', $dim['dim_name']);
				$this->tpl->setVariable('dim_selected', ($cond['dim_id'] == $dimId ? ' selected="selected"' : ''));
				$this->tpl->parse('dimensions');
			}
			break;
		}

		$this->tpl->setVariable('min', $cond['min_value']);
		$this->tpl->setVariable('max', $cond['max_value']);
	}

	function _getConditionPrototype()
	{
		return array(
			'min_value'	=> '',
			'max_value'	=> '',
			'dim_id'	=> '',
		);
	}

	function checkCondition($params, $generator)
	{
		$dimScores = $generator->getScores();
		if (!isset($dimScores[$params['dim_id']])) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.test.feedback.invalid_id', MSG_RESULT_NEG, array('id' => $id));
			return false;
		}
		else return ($dimScores[$params['dim_id']] >= $params['min_value'] && $dimScores[$params['dim_id']] <= $params['max_value']);
	}

}

