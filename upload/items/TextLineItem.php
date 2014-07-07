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


require_once(CORE.'types/Item.php');

/**
 * @package Upload
 */

/**
 * The class for text_line item objects
 *
 * @package Upload
 */
class TextLineItem extends Item
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'TextLineItem';

	public static $description = array(
					'de' => 'Freier Text, einzeilig',
					'en' => 'Free text, one row');

	var $templateFile = 'text_line.html';

	function interpretateAnswers($answers_array)
	{
		$result = array(array_key_exists($this->getId(), $answers_array) ? $answers_array[$this->getId()] : "");

		$wasSkipped = TRUE;
		if (trim($result[0]) != "")
		{
			$wasSkipped = FALSE;
		}
		else
			$result = array();
		return array($wasSkipped, $result);
	}

	/**
	 * Compare a given answer and the correct answer for this item
	 */
	function evaluateAnswer($givenAnswerSet)
	{
		$correctAnswersIds = array();
		$answers = $this->getChildren();
		foreach ($answers as $answer) {
			if ($answer->isCorrect()) {
				$correctAnswersIds[] = $answer->getId();
			}
		}

		if (! $correctAnswersIds) {
			return NULL;
		}

		$givenAnswers = $givenAnswerSet->getAnswers();
		
		if (!function_exists('formatAnswer')) {
			function formatAnswer ($string) {
				$umlautArray = Array('/Ä/','/Ö/','/Ü/','/ä/','/ö/','/ü/','/ß/','/&nbsp;/');
				$replaceArray = Array('a','o','u','a','o','u','ss','');
				return preg_replace($umlautArray, $replaceArray, strtolower(trim(html_entity_decode(strip_tags($string)))));
			}
		}
		
        if (current($givenAnswers)) {
			$answerText = current($givenAnswers);
			foreach ($answers as $i => $answer) {
				if (formatAnswer($answer->getAnswer()) == formatAnswer($answerText)) {
					return true;
				}
			}
        }
		return false;
	}

	function wasSkipped($givenAnswers)
	{
		if ($givenAnswers && trim($givenAnswers[0]->getValue() != '')) {
			return false;
		}
	}

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
		
		$this->qtpl->setVariable("question", $this->getQuestion());

		if (isset($oldAnswers[$this->getId()])) {
				$this->qtpl->setVariable('aold', $oldAnswers[$this->getId()]);
		}

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