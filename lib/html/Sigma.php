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
 * Loads and wraps PEAR::HTML::Template::Sigma
 *
 * @package Library
 */

libLoad("PEAR");
/**
 * Load PEAR::HTML::Template::Sigma
 */
require_once("HTML/Template/Sigma.php");

/**
 * Wrapper class for PEAR::HTML::Template::Sigma
 *
 * Replaces routines dealing with files to allow for translated templates.
 *
 * @package Library
 */
class Sigma extends HTML_Template_Sigma
{
    function loadTemplateFile($filename, $removeUnknownVariables = true, $removeEmptyBlocks = true)
    {
		return parent::loadTemplateFile($this->getFileName($filename), $removeUnknownVariables, $removeEmptyBlocks);
	}

	/**
	 * @access private
	 */
	function _makeTrigger($filename, $block)
	{
		return parent::_makeTrigger($this->getFileName($filename), $block);
	}

	function getFileName($filename)
	{
		if (! preg_match("/^(.*)(\\.[a-z]+)$/", $filename, $match)) {
			return $filename;
		}

		$languages = array($GLOBALS["TRANSLATION"]->getLanguage(), $GLOBALS["TRANSLATION"]->getFallbackLanguage());

		foreach ($languages as $language)
		{
			$testFile = $match[1].".".$language.$match[2];
			if (file_exists($this->fileRoot.$testFile)) {
				return $testFile;
			}
		}

		return $filename;
	}

	function verifyPath($filename)
	{
		return file_exists($this->fileRoot.$filename);
	}

	function show()
	{
		$numberMessages = $GLOBALS['MSG_HANDLER']->getNumberMessages();
		if($numberMessages > 0 && ($this->blockExists('msg_area') || $this->blockExists('msg')))
		{
			$msgs = $GLOBALS["MSG_HANDLER"]->getMessages();
				for($i = 0; $i < count($msgs); $i++)
				{
					$this->setVariable('msg', $msgs[$i]['msg']);
					$this->setVariable('flag', $msgs[$i]['flag']);
					$this->parse('msg');
				}
		}
		else
		{
			if($this->blockExists('msg_area'))
			{
				$this->hideBlock('msg_area');
			}
		}
		parent::show();
	}
}

?>