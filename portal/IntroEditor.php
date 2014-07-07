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
 * Include FCKeditor
 */
require_once('external/FCKeditor/fckeditor.php');

/**
 * Outsourcing FCKeditor config
 *
 * @package Portal
 */
class IntroEditor
{
	/**
	 * @access private
	 */
	var $editor;

	/**
	 * Create FCKeditor
	 *
	 * @param mixed Content for Editor
	 */
	function IntroEditor($content = NULL)
	{
		$this->editor = new FCKeditor('fckcontent');
		$self = trim($_SERVER['SCRIPT_NAME'], 'index.php');
		$this->editor->BasePath = $self.'portal/external/FCKeditor/';
		$this->editor->Config['PluginsPath'] = $self.'portal/js/fckplugins/';
		$this->editor->Config['CustomConfigurationsPath'] = $self.'portal/js/fckeditor.js';
		$this->editor->Config['BaseHref'] = server('HTTPS', 'off') == 'on' ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'].$self;
		$this->editor->Config['DefaultLanguage'] = $_SESSION['language'];
		$this->editor->Config['ImageBrowserURL'] = '../../../../../index.php?page=browser&type=image&settings=true';
		$this->editor->ToolbarSet = 'testmaker';
		$this->editor->Width = '640px';
		$this->editor->Height = '400px';
		if ($content)
		{
			$this->editor->Value = $content;
		}
	}

	/**
	 * Return Editor HTML Code
	 *
	 * @return string FCKeditor
	 */
	function CreateHtml()
	{
		return $this->editor->CreateHtml();
	}
}

?>
