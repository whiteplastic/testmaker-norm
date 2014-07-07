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
 * Prepares a global StopWatch for profiling
 *
 * The {@link StopWatch} instance is stored in <kbd>$GLOBALS["STOP_WATCH"]</kbd> and available by calling {@link getStopWatch()}
 *
 * @package Library
 */

/*
 * Check whether stop watch has been enabled or not
 */
function enabled()
{
	return (array_key_exists("enable_stopwatch", $GLOBALS) && $GLOBALS["enable_stopwatch"]);
}

/**
 * Wrapper for {@link StopWatch::startInterval()} of <kbd>$GLOBALS["STOP_WATCH"]</kbd>
 */
function start($label)
{
	if(enabled() && $GLOBALS["STOP_WATCH"])
	{
		return $GLOBALS["STOP_WATCH"]->startInterval($label);
	}
}

/**
 * Wrapper for {@link StopWatch::endInterval()} of <kbd>$GLOBALS["STOP_WATCH"]</kbd>
 */
function stop()
{
	if(enabled() && $GLOBALS["STOP_WATCH"])
	{
		return $GLOBALS["STOP_WATCH"]->endInterval();
	}
}

/**
 * Wrapper for {@link StopWatch::clear()} of <kbd>$GLOBALS["STOP_WATCH"]</kbd>
 */
function clearStopWatch()
{
	if(enabled() && $GLOBALS["STOP_WATCH"])
	{
		return $GLOBALS["STOP_WATCH"]->clear();
	}
}

/**
 * Returns the global <kbd>StopWatch</kbd> instance <kbd>$GLOBALS["STOP_WATCH"]</kbd>
 * @return StopWatch <kbd>$GLOBALS["STOP_WATCH"]</kbd>
 */
function &getStopWatch()
{
	if(enabled() && $GLOBALS["STOP_WATCH"])
	{
		return $GLOBALS["STOP_WATCH"];
	} else
	{
		return null;
	}
}

if(enabled())
{
	$GLOBALS["STOP_WATCH"] = new StopWatch();
}

/**
 * Measures the duration of actions (incl. nested actions) and displays them on demand
 * @package Library
 */
class StopWatch
{
	/**#@+
	 * @access private
	 */
	var $current = array();
	var $interval;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * Initializes a new StopWatch instance
	 */
	function StopWatch()
	{
		$this->clear();
	}

	/**
	 * Starts the timer for an action
	 *
	 * @param string A short description of the action
	 * @return boolean TRUE
	 */
	function startInterval($label)
	{
		$interval = array(
			"label" => $label,
			"intervals" => array(),
			"start" => $this->_getMicroTime(),
		);

		if ($this->current) {
			$this->current[count($this->current)-1]["intervals"][] = &$interval;
		}
		else {
			$this->interval["intervals"][] = &$interval;
		}

		$this->current[] = &$interval;

		return TRUE;
	}

	/**
	 * Stops the timer for the last started action
	 * @return boolean TRUE on success, FALSE on error
	 */
	function endInterval()
	{
		if ($this->current) {
			$this->current[count($this->current)-1]["end"] = $this->_getMicroTime();
			array_pop($this->current);
		}
		else {
			trigger_error("More StopWatch intervals were ended than started", E_USER_ERROR);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Deletes everything that has been recorded so far
	 */
	function clear()
	{
		$this->interval = array(
			"label" => "Recording interval",
			"intervals" => array(),
			"start" => $this->_getMicroTime(),
		);
	}

	/**
	 * Prints a report
	 * @return boolean TRUE on success, FALSE on error
	 */
	function printReport()
	{
		if ($this->current) {
			trigger_error("Unfinished StopWatch interval (\"".$this->current[count($this->current)-1]["label"]."\") found", E_USER_ERROR);
			return FALSE;
		}

		$interval = $this->interval;
		$interval["end"] = $this->_getMicroTime();

		$this->_printIntervalReport($interval);

		return TRUE;
	}

	/**
	 * @access private
	 */
	function _printIntervalReport(&$interval, $recordingDuration = NULL)
	{
		$startColor = array(51, 51, 51);
		$endColor = array(255, 0, 0);

		$duration = $interval["end"]-$interval["start"];

		if (isset($recordingDuration))
		{
			$quotient = $duration / $recordingDuration;
			libLoad("utilities::getGradientColor");
			$midColor = getGradientColor($startColor, $endColor, $quotient);
			$sColor = ";color:".sprintf("#%02X%02X%02X", $midColor[0], $midColor[1], $midColor[2]);
			$sSize = round(13 + ($quotient * 12), 0)."px";
		}
		else {
			$sColor = "";
			$sSize = "13px";
		}

		$sDuration = sprintf("%0.4fs", $duration);
		$sHeader = "<span style=\"font-size:".$sSize.$sColor."\">".$sDuration.": ".htmlentities($interval["label"])."</span>";

		if (isset($recordingDuration)) {
			$sHeader = "<li>".$sHeader."</li>\n";
		}
		else {
			$sHeader = "<p style=\"margin-bottom:0\">".$sHeader."</p>\n";
		}

		echo $sHeader;

		if (! isset($recordingDuration)) {
			$recordingDuration = $duration;
		}

		if ($interval["intervals"])
		{
			echo "<ul style=\"margin-top:0\">\n";
			for ($i = 0; $i < count($interval["intervals"]); $i++) {
				$this->_printIntervalReport($interval["intervals"][$i], $recordingDuration);
			}
			echo "</ul>\n";
		}
	}

	/**
	 * @access private
	 */
	function _getMicroTime()
	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}
}

?>