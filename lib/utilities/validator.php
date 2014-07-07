<?php

/**
 * Input text validation
 */

 class Validator
 {	 
	private static $regEx = array(
				// alphabetic
				"alpha" => '/^([a-zA-ZäöüÄÖÜ ]*)$/',
				// alphanumeric
				"alphanum" => '/^([a-zA-Z0-9äöüÄÖÜ ]*)$/',
				// numeric
				"num" => '/^([0-9]*)$/',
				// email
				"email" => '',
				);
				
	public static function getRestrictions()
	{
		return array_keys(Validator::$regEx);
	}

	public static function validateRestriction($restriction)
	{
		return array_key_exists($restriction, Validator::$regEx);
	}
	
	public static function validateText($text, $restriction)
	{
		$result = array();
		if($restriction == "email")
		{
			libLoad("utilities::validateEmail");
			$result = validateEmail($text, $errors);
			return array($result, $errors[0]);
		}
		if(Validator::validateRestriction($restriction))
		{
			$regEx = Validator::$regEx[$restriction];
			$result = array(preg_match($regEx, $text), T("pages.test.restriction.".$restriction));
		}

		return $result;
	}
 }
