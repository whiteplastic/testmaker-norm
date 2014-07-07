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


require_once('TextLineItem.php');

/**
 * @package Upload
 */

/**
 * The class for text_float_line item objects
 *
 * @package Upload
 */
class TextFloatLineItem extends TextLineItem
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'TextFloatLineItem';

	public static $description = array(
					'de' => 'Freier Text, einzeilig im Fließtext (z.B. für Lückentexte)',
					'en' => 'Free text, one row in a continuous text (i.e. for c-tests)');
	
  	var $templateFile = 'text_float_line.html';

	/**
	 * Parse the appropriated templatefile
	 */
	function parseTemplate($key, $items_count, $oldAnswers, $missingAnswers)
 	{
		$this->_initTemplate();

		$this->qtpl->setVariable('odd_or_even', $key%2 ? 'odd' : 'even');

		if (in_array($this->getId(), $missingAnswers))
        {
            $this->qtpl->setVariable('is_forced', 'isForced');
        }
		
		$question = array();
		if (strpos($this->getQuestion(), '{field:input}'))
		{
			$question = explode('{field:input}', $this->getQuestion());
		}
		else
		{
			$question = array($this->getQuestion());
		}

		// find last empty space
		if (substr($question[0], -1, 1) != '>')
		{
			$lastSpace = strrpos($question[0], ' ');
		}
		else
		{
			$lastSpace = strlen($question[0]);
		}
		$question[0] = substr_replace($question[0], ' <span style="white-space:no-wrap">', $lastSpace, 0);

		// find first empty space
		if (isset($question[1]))
		{
			if (substr($question[1], 0, 1) != '<')
			{
				$firstSpace = strpos($question[1], ' ');
			}
			else
			{
				$firstSpace = 0;
			}
			$question[1] = substr_replace($question[1], '</span> ', $firstSpace, 0);
		}
		else
		{
			$question[1] = '</span>';
		}

		$this->qtpl->setVariable("question", $question[0]);
		if (isset($oldAnswers[$this->getId()])) {
				$this->qtpl->setVariable('aold', $oldAnswers[$this->getId()]);
		}
		$this->qtpl->setVariable("questionAfter", $question[1]);

		if ($this->qtpl->blockExists("send_bar") && $key == $items_count-1)
		{
			$this->qtpl->touchBlock("send_bar");
		}

		return $this->qtpl->get();
	}
	
	function hasSimpleAnswer()
	{
		return false;
	}
}

?>
