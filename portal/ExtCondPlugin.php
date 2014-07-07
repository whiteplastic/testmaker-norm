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

/**
 * Abstract base class for plugins that implement types of feedback
 * conditions. Children are expected to implement the method
 * <kbd>_renderCondition</kbd> with the same parameters as
 * <kbd>renderCondition</kbd> and no return value, and the method
 * <kbd>_getConditionPrototype</kbd> with the same interface as
 * <kbd>getConditionPrototype</kbd>. Optionally,
 * <kbd>_processJavaScriptHook</kbd> may be implemented as well.
 */
class ExtCondPlugin extends DynamicDataPlugin
{

	function renderCondition($dims, $dimgroups, $page, $cond, $type)
	{
		switch ($type) {
		case 'view':
			$this->tpl->loadTemplateFile('ConditionView.html');
			break;
		case 'edit':
			$this->tpl->loadTemplateFile('ConditionEdit.html');
		}
		$this->tpl->setVariable('type', $this->name);

		$this->_renderCondition($dims, $dimgroups, $page, $cond, $type);
		return $this->tpl->get();
	}

	/**
	 * Checks whether the current plugin should be displayed in the current context (true) or not (false).
	 * May be overwritten by implementations of ExtCondPlugin.
	 */
	function checkApplicability($page)
	{
		return true;
	}

	function getConditionPrototype()
	{
		return array_merge(array('type' => $this->name), $this->_getConditionPrototype());
	}

}
