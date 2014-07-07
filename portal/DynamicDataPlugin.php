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
 * Abstract base class for plugins that operate on dynamic data that is
 * associated with IDs (such as blocks, dimensions, etc.).
 * Expects its descendants to define a member called <kbd>types</kbd> which
 * maps parameter names to types of data. This member may be missing in case
 * a concrete implementation in a category of dynamic data plugins does not
 * actually deal with dynamic data.
 */
class DynamicDataPlugin extends Plugin
{

	var $types=array();
	/**
	 * In the parameter list of a given plugin, substitute a set of IDs by new
	 * ones, e.g. when copying objects that make use of plugins.
	 */
	function modifyForCopy($params, $changedIds)
	{
		$result = $params;
		if (!isset($this->types)) return $result;

		foreach ($this->types as $field => $type) {
			if (!isset($result[$field])) continue;
			$data = $result[$field];
			$allData = array();
			if (strpos($data, ',')) {
				$data = explode(',', $data);
			} else {
				$data = array($data);
			}

			foreach ($data as $value) {
				$valSep = explode(':', $value);
				if (isset($changedIds[$type][$valSep[0]])) {
					$valSep[0] = $changedIds[$type][$valSep[0]];
				}
				$allData[] = implode(':', $valSep);
			}
			$result[$field] = implode(',', $allData);
		}

		return $result;
	}

	/**
	 * In the parameter list of a given plugin, check whether a certain data
	 * object or one out of a set of data objects is referenced.
	 */
	function checkIds($params, $idCandidates)
	{	
		foreach ($this->types as $field => $type) {
			if (!isset($params[$field])) continue;
			$data = $params[$field];
			if (strpos($data, ',')) {
				$data = explode(',', $data);
			} else {
				$data = array($data);
			}

			foreach ($data as $value) {
				$valSep = explode(':', $value);
				if ($type == 'dimensions' || $type == 'dimension_groups')
					if (isset($idCandidates[0][$type][$valSep[0]])) return true;
				else 
					if (isset($idCandidates[$type][$valSep[0]])) return true;
			}
		}
		return false;
	}
}
