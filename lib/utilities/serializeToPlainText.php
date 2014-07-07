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

/**
 * Serializes a data structure to easily readable and editable plain text.
 * Supports atomic types as well as normal and associative arrays.
 */
function serializeToPlainText($data, $indent = 2, $level = 0)
{
	$res = '';
	if ($level == 0 && $indent != 2) $res .= "?INDENT $indent\n";

	$indentStr = str_repeat(' ', $level);
	$inlineStr = ' ';
	$first = true;

	if ($level > 0 && is_array($data)) {
		$res .= "\n";
		$first = false;
	}

	if (is_array($data) && _isSimpleNumberedArray($data)) {
		// Vector processing
		foreach ($data as $row) {
			if (!$first) $res .= $indentStr;
			$first = false;
			$res .= '-'. serializeToPlainText($row, $indent, $level + $indent);
		}
		return $res;
	} elseif (is_array($data)) {
		// Associative array processing
		foreach ($data as $key => $row) {
			if (!$first) $res .= $indentStr;
			$first = false;
			$res .= "$key:". serializeToPlainText($row, $indent, $level + $indent);
		}
		return $res;
	} elseif ($data === NULL) {
		return "\n";
	} elseif (!is_scalar($data)) {
		return NULL;
	}

	if (is_bool($data)) {
		return $inlineStr .($data ? "?TRUE" : "?FALSE"). "\n";
	}
	// Multi-line strings need to be indented/escaped differently
	if (is_string($data) && (false !== strpos($data, "\n") || (strlen($data) > 0 && $data[0] == '?'))) {
		$res = "...\n";
		$data = str_replace("\r", '', $data);

		foreach (explode("\n", $data) as $row) {
			$res .= $indentStr ."$row\n";
		}
		return $res;
	}
	return $inlineStr. strval($data). "\n";
}

/**
 * Unserializes a string as created by {@link serializeToPlainText} into a PHP
 * data structure.
 * Supports atomic types as well as normal and associative arrays.
 */
function unserializeFromPlainText($data, $indent = 2)
{
	if (!$data) return '';
	$data = explode("\n", str_replace("\r", '', $data));

	if (_matchStart($data[0], '?INDENT ')) {
		$indent = intval(str_replace('?INDENT ', '', array_shift($data)));
	}

	if ($data[0][0] == '-') {
		// List
		$res = array();

		while (count($data) > 0 && strlen($data[0]) > 0 && $data[0][0] == '-') {
			$data[0] = substr($data[0], 1);
			list($item, $data) = _splitFromIndent($data, $indent);
			$res[] = unserializeFromPlainText($item, $indent);
		}
		return $res;
	}
	if ($data[0][0] != ' ' && false !== strpos($data[0], ':')) {
		// Associative array
		$res = array();

		while (count($data) > 0 && strlen($data[0]) > 0 && $data[0] != ' ' && false !== strpos($data[0], ':')) {
			list($key, $val) = explode(':', $data[0], 2);
			$data[0] = $val;
			list($item, $data) = _splitFromIndent($data, $indent);
			$res[$key] = unserializeFromPlainText($item, $indent);
		}
		return $res;
	}

	if ($data[0] == '...') {
		return implode("\n", array_slice($data, 1, count($data)-2));
	}

	if ($data[0][0] != ' ') {
		return NULL;
	}
	if (count($data) > 1 && !(count($data) == 2 && $data[1] == '')) {
		return NULL;
	}
	$val = substr($data[0], 1);

	if ($val == '') return '';
	if ($val == '?TRUE') return true;
	if ($val == '?FALSE') return false;
	return $val;
}

/* Determines the indentation of the first line of a string. */
function _countSpaces($str)
{
	$cnt = 0;
	while ($cnt < strlen($str) && $str[$cnt] == ' ') $cnt++;
	return $cnt;
}

/* Matches a string against a same-length prefix of another string. */
function _matchStart($haystack, $needle)
{
	return substr($haystack, 0, strlen($needle)) == $needle;
}

/* Checks if the array has consecutive numbering. */
function _isSimpleNumberedArray($arr)
{
	$keys = array_keys($arr);
	for ($i = 0; $i < count($keys); $i++) {
		if ($i !== $keys[$i]) return false;
	}
	return true;
}

/* Pulls out an indented block from the given string. */
function _splitFromIndent($arr, $indent)
{
	$res = array_shift($arr);
	$res = ($res ? "$res\n" : '');

	// Continue as long as we have indented lines
	while (count($arr) > 0 && _countSpaces($arr[0]) >= $indent) {
		$res .= substr(array_shift($arr), $indent) ."\n";
	}

	return array($res, $arr);
}
