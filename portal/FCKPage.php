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
 * Require base page class
 */
require_once(PORTAL.'Page.php');

/**
 * Base class for fckeditor filebrowser pages
 *
 * @package Portal
 * @abstract
 */
class FCKPage extends Page
{

	/**
	 * Constructor
	 *
	 * @param string Page name
	 */
	function FCKPage($pageName)
	{
		parent::Page($pageName);
	}

	/**
	 * Loads the test document frame template and initializes it
	 *
	 * Basically, this does the following:
	 * <code>
	 * $this->tpl->loadTemplateFile("FCKFrame.html");
	 * </code>
	 */
	function loadDocumentFrame()
	{
		$this->tpl->loadTemplateFile("FCKFrame.html");
	}
}

?>
