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
 * Unescapes <kbd>$_GET</kbd>, <kbd>$_POST</kbd>, <kbd>$_COOKIE</kbd> and <kbd>$_SERVER</kbd> if
 * {@link http://www.php.net/manual/en/ref.info.php#ini.magic-quotes-gpc magic_quotes_gpc} is enabled.
 *
 * @package Library
 */

if (get_magic_quotes_gpc())
{
	$queue = array(&$_GET, &$_POST, &$_COOKIE, &$_SERVER);
	$head = 0;

	while ($queue)
	{
		$array = array();
		foreach (array_keys($queue[$head]) as $key)
		{
			$strippedKey = stripslashes($key);
			if (! is_array($queue[$head][$key])) {
				$array[$strippedKey] = stripslashes($queue[$head][$key]);
			}
			else {
				$array[$strippedKey] = $queue[$head][$key];
				$queue[] = &$array[$strippedKey];
			}
		}
		$queue[$head] = $array;
		unset($queue[$head]);
		$head++;
	}

	unset($array);
	unset($strippedKey);
	unset($head);
	unset($queue);
}

?>