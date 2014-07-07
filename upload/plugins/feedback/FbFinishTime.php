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
 * @package Portal
 */

class FbFinishTime extends DynamicDataPlugin
{
	var $title = array(
		'de' => 'Time',
		'en' => 'Zeitpunkt',
	);
	var $desc = array(
		'de' => 'Gibt den Zeitpunkt, zu dem der Testlauf beendet wurde aus.',
		'en' => 'Displays the time when the testrun was finished.',
	);

	function init($generator)
	{
		$this->generator = $generator;
	}
	
	function getOutput() {
		return date(T('pages.core.date_time'), $this->generator->finishTime);
	}

}
