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
 * Merges parts of an array into another.
 *
 * @param mixed[] Target array.
 * @param mixed[] Array to merge into target.
 * @param array Array mapping keys to merge to target keys. If no array key is
 *   given for a specific target key, it's assumed to be the source key as
 *   well. For that reason, numeric source keys are not supported.
 * @return mixed[]
 */
function selectiveMerge($target, $source, $keys)
{
	foreach ($keys as $sKey => $tKey ) {
		if (is_int($sKey)) $sKey = $tKey;
		if (!array_key_exists($sKey, $source)) continue;
		$target[$tKey] = $source[$sKey];
	}
	return $target;
}

