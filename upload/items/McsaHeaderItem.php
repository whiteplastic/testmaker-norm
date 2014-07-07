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


require_once('McsaItem.php');

/**
 * @package Upload
 */

/**
 * The class for mcsa_header item objects
 *
 * @package Upload
 */
class McsaHeaderItem extends McsaItem
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'McsaHeaderItem';

	public static $description = array(
					'de' => 'Eine Antwortalternative wählbar, als Matrix mit Kopfzeile',
					'en' => 'Single answer allowed, as matrix with headline');
	
  	var $templateFile = 'mcsa_header.html';

	/**
	 * Parse the appropriated templatefile
	 */
	function parseTemplate($key, $items_count, $oldAnswers, $missingAnswers, $beginHeader = false, $closeTable = false)
	{
		$this->_initTemplate();

		$block = $this->getParent();

		$cols = $this->getTemplateCols() ? $this->getTemplateCols() : $block->getDefaultTemplateCols();

		if ($beginHeader == true) {
			$items = $this->getChildren();
			$itemcount = count($items);
			foreach ($items as $answer)
			{
				if(!$answer->getDisabled())
				{
					$this->qtpl->setVariable("answer_header_width", floor(60 / $itemcount));
					$this->qtpl->setVariable("answer_header", $answer->getAnswer());
					$this->qtpl->parse("answers");
				}
			}
		}

		$this->qtpl->setVariable('odd_or_even', $key%2 ? 'odd' : 'even');

		$this->qtpl->setVariable("question", $this->getQuestion());

		if (in_array($this->getId(), $missingAnswers))
		{
			$this->qtpl->setVariable('is_forced', 'isForced');
		}

		$colLength = $cols ? $cols : 2;
		foreach ($this->getChildren() as $i => $answer)
		{
			if(!$answer->getDisabled())
			{
					$this->qtpl->setVariable("width", floor(100/$colLength));
					$this->qtpl->setVariable("aid", $answer->getId());
					$this->qtpl->setVariable("answer", $answer->getAnswer());
					if (isset($oldAnswers[$this->getId()]) && $oldAnswers[$this->getId()] == $answer->getId())
					{
						$this->qtpl->setVariable('aold', ' checked="checked"');
					}
					$this->qtpl->parse("answer");

					if (($i+1) % $colLength == 0)
					{
						$this->qtpl->parse("answer_block");
					}
			}
		}
		
		if ($this->qtpl->blockExists("send_bar") && $key == $items_count-1)
		{			
			$this->qtpl->touchBlock("footer_block");
			$this->qtpl->touchBlock("send_bar");
		}
	
		if ($closeTable == true) {
			$this->qtpl->touchBlock("footer_block");
		}
		
		
		return $this->qtpl->get();
	}

}

?>
