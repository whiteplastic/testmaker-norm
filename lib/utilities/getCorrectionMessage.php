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
 *
 * @package Library
 */

/**
 * ID for min. length criterion
 */
define('CORRECTION_CRITERION_LENGTH_MIN', 1);
/**
 * ID for max. length criterion
 */
define('CORRECTION_CRITERION_LENGTH_MAX', 2);
/**
 * ID for positive integer only criterion
 */
define('CORRECTION_CRITERION_NUMERIC_INTEGER_POS', 3);
/**
 * ID for integer only criterion
 */
define('CORRECTION_CRITERION_NUMERIC_INTEGER', 4);
/**
 * ID for decimal criterion
 */
define('CORRECTION_CRITERION_NUMERIC', 5);
/**
 * ID for min. num criterion
 */
define('CORRECTION_CRITERION_NUMERIC_MIN', 6);
/**
 * ID for max. num criterion
 */
define('CORRECTION_CRITERION_NUMERIC_MAX', 7);
/**
 * ID for regular expression criterion
 */
define('CORRECTION_CRITERION_REGULAR_EXPRESSION', 8);
/**
 * ID for non-empty criterion
 */
define('CORRECTION_CRITERION_NOT_EMPTY', 9);
/**
 * ID for not 0  criterion
 */
define('CORRECTION_CRITERION_NOT_ZERO', 10);
/**
 * ID for max. num and not null criterion 
 */
define('CORRECTION_CRITERION_NUMERIC_MAX_NOTNULL', 11); 

/**
 * Returns correction messages for the given value
 * @param string title of field to check (relevant for output message)
 * @param string content of field to check
 * @param mixed[] array of criteria to check for. Index defines the criteria type (see constantes) and value the needed option for this criterium
 * @return array with correction messages
 */
function getCorrectionMessage($title, $content, $criteria) {

	//prepare the criteria to avoid sensless or doubled informations
	if(array_key_exists(CORRECTION_CRITERION_NUMERIC_INTEGER, $criteria) && array_key_exists(CORRECTION_CRITERION_NUMERIC, $criteria)) {
		unset($criteria[CORRECTION_CRITERION_NUMERIC]);
	}
	if(array_key_exists(CORRECTION_CRITERION_NUMERIC_INTEGER_POS, $criteria) && array_key_exists(CORRECTION_CRITERION_NUMERIC, $criteria)) {
		unset($criteria[CORRECTION_CRITERION_NUMERIC]);
	}
	if(array_key_exists(CORRECTION_CRITERION_NUMERIC_INTEGER_POS, $criteria) && array_key_exists(CORRECTION_CRITERION_NUMERIC_INTEGER, $criteria)) {
		unset($criteria[CORRECTION_CRITERION_NUMERIC_INTEGER]);
	}
	if(array_key_exists(CORRECTION_CRITERION_NUMERIC_MIN, $criteria) && !(array_key_exists(CORRECTION_CRITERION_NUMERIC_INTEGER, $criteria) || array_key_exists(CORRECTION_CRITERION_NUMERIC, $criteria))) {
		$criteria[CORRECTION_CRITERION_NUMERIC] = true;
	}
	if(array_key_exists(CORRECTION_CRITERION_NUMERIC_MAX, $criteria) && !(array_key_exists(CORRECTION_CRITERION_NUMERIC_INTEGER, $criteria) || array_key_exists(CORRECTION_CRITERION_NUMERIC, $criteria))) {
		$criteria[CORRECTION_CRITERION_NUMERIC] = true;
	}
	if(array_key_exists(CORRECTION_CRITERION_NUMERIC_MAX_NOTNULL, $criteria) && !(array_key_exists(CORRECTION_CRITERION_NUMERIC_INTEGER, $criteria) || array_key_exists(CORRECTION_CRITERION_NUMERIC, $criteria))) {
		$criteria[CORRECTION_CRITERION_NUMERIC] = true;
	}
	
	$msgs = array();
	for(reset($criteria); list($key, $value) = each($criteria);) {
		switch($key) {
			case CORRECTION_CRITERION_LENGTH_MIN:
				if(strlen($content) < $value) {
					if($value == 1) {
						$msgs[] = T('utilities.get_correction_message.too_short_1', array('field' => $title, 'value' => $value));
					} else {
						$msgs[] = T('utilities.get_correction_message.too_short', array('field' => $title, 'value' => $value));
					}
				}
				break;
			case CORRECTION_CRITERION_LENGTH_MAX:
				if(strlen($content) > $value) {
					$msgs[] = T('utilities.get_correction_message.too_large', array('field' => $title, 'value' => $value));
				}
				break;
			case CORRECTION_CRITERION_NUMERIC_INTEGER:
				if(!(is_numeric($content) && (intval($content) == floatval($content)))) {
					$msgs[] = T('utilities.get_correction_message.not_integer', array('field' => $title));
				}
				break;
			case CORRECTION_CRITERION_NUMERIC_INTEGER_POS:
				if(!(is_numeric($content) && intval($content) >= 0)) {
					$msgs[] = T('utilities.get_correction_message.not_integer_positive', array('field' => $title));
				}
				break;
			case CORRECTION_CRITERION_NUMERIC:
				if(!is_numeric($content)) {
					$msgs[] = T('utilities.get_correction_message.not_numeric', array('field' => $title));
				}
				break;
			case CORRECTION_CRITERION_NUMERIC_MIN:
				if(floatval($content) < $value) {
					$msgs[] = T('utilities.get_correction_message.too_small', array('field' => $title, 'value' => $value));
				}
				break;
			case CORRECTION_CRITERION_NUMERIC_MAX:
				if(floatval($content) > $value) {
					$msgs[] = T('utilities.get_correction_message.too_large', array('field' => $title, 'value' => $value));
				}
				break;
			case CORRECTION_CRITERION_NUMERIC_MAX_NOTNULL:
				if(floatval($content) > $value && $value != 0) {
					$msgs[] = T('utilities.get_correction_message.too_large', array('field' => $title, 'value' => $value));
				}
				break;
			case CORRECTION_CRITERION_REGULAR_EXPRESSION:
				if(!preg_match("/$value/", $content)) {
					$msgs[] = T('utilities.get_correction_message.wrong', array('field' => $title));
				}
				break;
			case CORRECTION_CRITERION_NOT_EMPTY:
				if(strlen(trim($content)) > 0) {
					$msgs[] = T('utilities.get_correction_message.empty', array('field' => $title));
				}
				break;
			case CORRECTION_CRITERION_NOT_ZERO:
				if(is_numeric($content) && floatval($content) == 0) {
					$msgs[] = T('utilities.get_correction_message.zero', array('field' => $title));
				}
				break;
		}
	}
	
	return $msgs;

}

?>
