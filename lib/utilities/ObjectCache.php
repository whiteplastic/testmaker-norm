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
 * Offers functions to cache objects in various categories, by some sort of unique ID (or tuple of IDs).
 * In the case of a tuple that acts as an ID, collections of objects can be fetched by using a prefix of
 * the tuple as the retrieval ID.
 * @package Library
 */

$_objectCache = array();

/**
 * Stores an object that can be retrieved by a category/id tuple. The id parameter can be an array, in which case it is interpreted as a tuple of IDs.
 */
function store($category, $id, $obj)
{
	global $_objectCache;
	if (!isset($_objectCache[$category])) $_objectCache[$category] = array();

	// Deal with tuple of IDs
	if (!is_array($id)) $id = array($id);
	$lastId = array_pop($id);
	$arr = &$_objectCache[$category];

	foreach ($id as $idLevel) {
		if (!isset($arr[$idLevel])) $arr[$idLevel] = array();
		$arr = &$arr[$idLevel];
	}
	$arr[$lastId] = $obj;
}

/**
 * Removes an object from the cache referenced by category/id.
 */
function unstore($category, $id)
{
	global $_objectCache;
	if (!isset($_objectCache[$category])) return;

	// Deal with tuple
	if (!is_array($id)) $id = array($id);
	$lastId = array_pop($id);
	$arr = &$_objectCache[$category];

	foreach ($id as $idLevel) {
		if (!isset($arr[$idLevel])) return;
		$arr = &$arr[$idLevel];
	}
	unset($arr[$lastId]);
}

/**
 * Removes all objects from the cache.
 */
function unstore_all()
{
    unset($GLOBALS['_objectCache']);
}

/**
 * Retrieves an object from the cache referenced by category/id.
 */
function retrieve($category, $id)
{
	global $_objectCache;
	$null = NULL;
	if (!isset($_objectCache[$category])) return $null;

	// Deal with tuple
	if (!is_array($id)) $id = array($id);
	$lastId = array_pop($id);
	$arr = &$_objectCache[$category];

	foreach ($id as $idLevel) {
		if (!isset($arr[$idLevel])) return $null;
		$arr = &$arr[$idLevel];
	}
	if (!isset($arr[$lastId])) return $null;

	return $arr[$lastId];
}

