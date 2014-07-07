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
 * revert given data set from session to request variable
 */
function revertRequestDataFromSession($dataSet, $newVarName = NULL)
{
	if(!array_key_exists('requestData', $_SESSION) || !array_key_exists($dataSet, $_SESSION['requestData'])) {
		trigger_error("<b>revertRequestDataFromSession</b>: invalid dataSet given");
		return false;
	}
	
	if(defined(REQUESTVAR_STORE_TIME)) {
		$timeDiff = REQUESTVAR_STORE_TIME;
	} else {
		$timeDiff = 300;
	}
	for(reset($_SESSION['requestData']); list($key, $val) = each($_SESSION['requestData']);) {
		if($key['time'] < time() - $timeDiff) {
			unset($_SESSION['requestData'][$key]);
		}
	}

	if($newVarName = NULL) {
		$name = $_SESSION['requestData'][$dataSet]['name'];
	} else {
		$name = $newVarName;
	}
	
	for(reset($_SESSION['requestData'][$dataSet]['var']); list($key, $value) = each($_SESSION['requestData'][$dataSet]['var']);) {
		$$name[$key] = $value;
	}
	
	return true;
	
}

?>
