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
 * store given variable in session
 */
function storeRequestDataInSession($var, $list)
{
	if($var != '_POST' && $var != '_GET') {
		trigger_error("<b>storeRequestDataInSession</b>: unsupported request variable '$var'");
		return -1;
	}
	
	$storedContent = array();
	
	$storedContent['name'] = $var;
	$storedContent['time'] = time();
	
	$storedContent['var'] = array();
	for($i = 0; $i < count($list); $i++) {
		$storedContent['var'][$key] = $$var[$list[$i]];
	}
	
	if(!array_key_exists('requestData', $_SESSION)) {
		$_SESSION['requestData'] = array();
	} else {
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
	}
	
	return (push($_SESSION['requestData'], storedContent) - 1);
	
}

?>
