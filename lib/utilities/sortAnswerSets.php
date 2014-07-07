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
 * Helper function for sorting AnswerSets by step number
 * @param AnswerSet First AnswerSet
 * @param AnswerSet Second AnswerSet
 * @return int Either 0 if both step numbers are equal, -1 if the first step number is less, 1 otherwise
 */
function cmpAnswerSets($a, $b)
{
	if(get_class($a) == "GivenAnswerSet" && get_class($b) == "GivenAnswerSet")
	{
		$aStep = $a->getStepNumber();
		$bStep = $b->getStepNumber();
		if($aStep == $bStep) return 0;
		elseif($aStep < $bStep) return -1;
		else return 1;
	}
	else return 0;
}
