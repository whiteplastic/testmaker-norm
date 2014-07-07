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
 * @package Library
 */

libLoad("utilities::arrayCombine");

/**
 */
function prepareColumnNames(&$db, $tables)
{
	$columnNames = array();
	foreach ($tables as $table) {
		$info = $db->tableInfo($table);
		foreach ($info as $column) {
			$columnNames[$table][] = $column["name"];
		}
	}
	return $columnNames;
}

function groupResultByTable($result, $columnNames)
{
	$resultArray = array();
	$offset = 0;
	foreach ($columnNames as $table => $columns) {
		$columnCount = count($columns);
		$resultArray[$table] = array_combine($columnNames[$table], array_slice($result, $offset, $columnCount));
		$offset += $columnCount;
	}
	return $resultArray;
}

?>