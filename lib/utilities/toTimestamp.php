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


function toTimestamp($date)
{
	$format = '/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/';
	if (preg_match_all($format, $date, $array, PREG_SET_ORDER))
	{
		$array = $array[0];
		return mktime($array[4], $array[5], 0, $array[2], $array[3], $array[1]);
	}
	return 0;
}

?>
