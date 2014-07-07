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
 * The class for mcsa item objects
 *
 * @package Upload
 */
class McsaItem extends Item
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'McsaItem';

	public static $description = array(
					'de' => 'Eine Antwortalternative wählbar',
					'en' => 'Single answer allowed'); 
	
	var $templateFile = 'mcsa.html';

	function interpretateAnswers($answers_array)
	{
		$answers_array = post("answer", array());
		$answer = array_key_exists($this->getId(), $answers_array) ? $answers_array[$this->getId()] : array();

		$result = array();
		$wasSkipped = TRUE;
		// Iterate through the child list to ensure that the order of the results in the answer set is correct
		$number = 0;
		foreach ($this->getChildren() as $child)
		{
			$number++;
			if ($answer == $child->getId())
			{
				$wasSkipped = FALSE;
				$result[$child->getId()] = $number;
				break;
			}
		}

		return array($wasSkipped, $result);
	}

	/**
	 * Compare a given answer and the correct answer for this item
	 */
	function evaluateAnswer($givenAnswerSet)
	{
		$correctAnswersIds = array();
		foreach ($this->getChildren() as $answer) {
			if ($answer->isCorrect()) {
				$correctAnswersIds[] = $answer->getId();
			}
		}

		if (! $correctAnswersIds) {
			return NULL;
		}

		$givenAnswers = $givenAnswerSet->getAnswers();
		
		if (! $givenAnswers) return  NULL;
		
		$answerNumber = current($givenAnswers);
		foreach ($this->getChildren() as $i => $answer) {
	
			if ($i+1 == $answerNumber) {
				return in_array($answer->getId(), $correctAnswersIds);
			}
		}
		
		return NULL;
	}

	function wasSkipped($givenAnswers)
	{
		if ($givenAnswers) {
				return FALSE;
		}
	}

		/**
		 * Parse the appropriated templatefile
		 */
		function parseTemplate($key, $items_count, $oldAnswers, $missingAnswers)
		{
			$this->_initTemplate();

			$block = $this->getParent();

			$cols = $this->getTemplateCols() ? $this->getTemplateCols() : $block->getDefaultTemplateCols();

			$this->qtpl->setVariable('odd_or_even', $key%2 ? 'odd' : 'even');

			$this->qtpl->setVariable("question", $this->getQuestion());

			if (in_array($this->getId(), $missingAnswers))
			{
				$this->qtpl->setVariable('is_forced', 'isForced');
			}

			$this->qtpl->touchBlock("answer_block");
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
				$this->qtpl->touchBlock("send_bar");
			}

			return $this->qtpl->get();
		}
}

?>