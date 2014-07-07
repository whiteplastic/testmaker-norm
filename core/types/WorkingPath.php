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

/*
 * Class for translation of working path
 */

/**
 * @package Core
 */

class WorkingPath {

	static private function explodePath($workingPath)
	{
		$we = explode("_", $workingPath);
		$cleaned = array();
		foreach($we as $index => $value) 
		{
			if($value != "") $cleaned[$index] = $value;
		}
		return $cleaned;
	}

	static function getTestId($workingPath)
	{
		$testWorkingPath = WorkingPath::explodePath($workingPath);
		$testId = @$testWorkingPath[2];
		if($testId) return $testId;
		else return false;
	}

	static function getParentId($childId, $workingPath)
	{
		$pos = strrpos($workingPath, "_");
		$len = strlen($workingPath);
		if($len > 0 && $pos == $len - 1) $workingPath = substr($workingPath, 0, $pos);

		$pos = strpos($workingPath, "_".$childId);
		if(! $pos) return false;

		$parentWorkingPath = preg_split("/_/", substr($workingPath, 0, $pos));
		$parentId = end($parentWorkingPath);
		return $parentId;
	}

	static function getSubtestId($workingPath)
	{
		$testWorkingPath = WorkingPath::explodePath($workingPath);
		$testId = @$testWorkingPath[3];
		if($testId) return $testId;
		else return false;
	}

	static function getActiveBlock($workingPath)
	{
		$testWorkingPath = WorkingPath::explodePath($workingPath);
		$activeBlockId = @end($testWorkingPath);
		return $activeBlockId;
	}

	static function verify($workingPath)
	{
		return preg_match('/^(_\d+)+?/', $workingPath);
	}

}

