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

/**
 * Handles redirects
 *
 * @package Portal
 */
class RedirectPage extends Page
{
	function redirectTo($link, $permanent)
	{
		$type = $permanent ? 301 : 302;
		$status = $permanent ? "Moved Permanently" : "Found";

		$this->tpl->loadTemplateFile("HttpRedirect".$type.".html");
		$this->tpl->setVariable("link", htmlentities($link));
		$output = $this->tpl->get();

		header("HTTP/1.1 ".$type." ".$status);
		header("Location: ".$link);
		header("Content-Length: ".strlen($output));
		header("Content-Type: text/html; charset=ISO-8859-15");

		echo $output;
	}
}

?>
