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
 * Checks whether a certain error report should be sent.
 * The table error_log_verbose is used to keep track of past reports.
 * Upon connection, entries older then <var>$ageLimit</var> seconds are deleted.
 *
 * @package Library
 */
class ErrorInterestChecker
{
	var $db;
	var $ageLimit;

	function ErrorInterestChecker($ageLimit)
	{
		$this->ageLimit = (int)$ageLimit;
	}

	function openDatabase()
	{
		if (isset($this->db)) {
			return TRUE;
		}
		if (isset($GLOBALS['dao'])) {
			$this->db = &$GLOBALS['dao']->getConnection();

			// Clean up the database
			$table = DB_PREFIX."error_log_verbose";
			$this->db->query("DELETE FROM $table WHERE last_sent<(".time()."-".$this->ageLimit.")");
			return TRUE;
		}

		return FALSE;
	}

	function isInterestingReport($fingerprint)
	{
		// No database connection? Then just send the report.
		if (! $this->openDatabase()) {
			return TRUE;
		}

		// Search for the fingerprint in the database
		$table = DB_PREFIX."error_log_verbose";
		$query = $this->db->query("SELECT * FROM $table WHERE fingerprint=?", array($fingerprint));

		$newCount = 1;
		// The error has occured recently -> don't spam us
		if ($query->fetchInto($result)) {
			$newCount = $result['countError'] + 1;
			$query = $this->db->query("UPDATE $table SET countError=? WHERE fingerprint=?", array($newCount, $fingerprint));
			if (time()-$result["last_sent"]<5*86400) {
				return FALSE;
			}
			else {
			     $query = $this->db->query("UPDATE $table SET last_sent=? WHERE fingerprint=?", array(time(), $fingerprint));
			     return TRUE;
			}
		}

		// The error is currently unknown (old enough to have been deleted or new) -> send it
		$this->db->query("INSERT INTO $table (last_sent, fingerprint, countError) VALUES (?, ?, ?)", array(time(), $fingerprint, $newCount));
		return TRUE;
	}
}