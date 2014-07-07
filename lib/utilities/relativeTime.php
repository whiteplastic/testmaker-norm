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
 * Formats a time difference
 *
 * In mini mode, the output is more compact:
 * <samp>
 * in 4w 4d 6h 42m 12s
 * </samp>
 *
 * You can limit the accuracy to a certain number of parts.
 * This number <var>$maxParts</var> limits the output to the first actually
 * used unit and the <var>$maxParts-1</var> <i>possible</i> units thereafter.
 * Let <var>$maxParts</var>=3, then these outputs are plausible:
 * <ul>
 * <li>5 days ago (as in 5 days, 0 hours and 0 minutes ago)
 * <li>12 days and 5 hours ago (as in 12 days, 5 hours and 0 minutes ago)
 * <li>2 days, 5 hours and 33 minutes ago
 * </ul>
 *
 * If the time difference exceeds <var>$longDistance</var>, the absolute time is returned.
 *
 * To tune the units, etc. you have to modify the utilities translation file of Library.
 *
 * @param int The time of the event
 * @param int Current time
 * @param boolean Whether to render the string compact
 * @param int Accuracy, may be NULL
 * @param int The time difference that is considered a long distance (default: 7 days)
 * @return string The time difference rendered human-readable
 */

function relativeTime($time, $now = NOW, $miniMode = FALSE, $maxParts = 3, $longDistance = 604800, $omitSuffix = FALSE)
{
	$diff = $now - $time;

	if($longDistance != -1 && $diff > $longDistance) {
		return strftime(T("utilities.relative_time.strftime"), $time);
	}
	elseif ($diff == 0) {
		return T("utilities.relative_time.format.now");
	}

	if ($diff < 0) {
		$format = "future";
		$diff *= -1;
	}
	elseif ($diff > 0) {
		$format = "past";
	}

	$units = array(
		"second" => 1,
		"minute" => 60,
		"hour" => 3600,
		"day" => 86400,
		"week" => 604800,
	);

	$parts = array();
	$partCount = 0;
	foreach (array_reverse($units) as $unit => $seconds)
	{
		$amount = 0;
		while ($diff >= $seconds) {
			$amount++;
			$diff -= $seconds;
		}
		if ($amount) {
			$parts[] = array($unit, $amount);
		}

		if (isset($maxParts))
		{
			if ($partCount != 0 || $amount) {
				$partCount++;
			}
			if ($partCount == $maxParts) {
				break;
			}
		}
	}

	$diff = "";
	foreach ($parts as $i => $part)
	{
		list($unit, $amount) = $part;
		if ($miniMode) {
			$unit = "mini_".$unit;
		}
		$unit = T("utilities.relative_time.unit.".$unit.($amount > 1 ? "s" : ""));

		if ($i)
		{
			if ($i+1 == count($parts)) {
				$diff .= T("utilities.relative_time.".($miniMode ? "mini_" : "")."last_join");
			} else {
				$diff .= T("utilities.relative_time.".($miniMode ? "mini_" : "")."inner_join");
			}
		}
		$diff .= $amount.($miniMode ? "" : " ").$unit;
	}

	if ($omitSuffix) {
		return $diff;
	}
	return T("utilities.relative_time.format.".$format, array("time_diff" => $diff));
}

?>
