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
 * The class for mcma item objects
 *
 * @package Upload
 */
class McmaItem extends Item
{
	var $classname = 'McmaItem';

	public static $description = array(
					'de' => 'Mehrere Antwortalternativen wählbar',
					'en' => 'Multiple answers allowed');
	
	var $templateFile = 'mcma.html';

	function interpretateAnswers($answers_array)
	{
		$answers = array_key_exists($this->getId(), $answers_array) ? $answers_array[$this->getId()] : array();

		// Prevent glitch when the user manipulates the submitted data
		if (!is_array($answers))
		{
			$answers = array($answers);
		}

		$result = array();
		$wasSkipped = TRUE;
		// Iterate through the child list to ensure that the order of the results in the answer set is correct
		foreach ($this->getChildren() as $child)
		{
			$found = in_array($child->getId(), $answers) ? 1 : 0;
			$result[$child->getId()] = $found;
			if ($found)
			{
				$wasSkipped = FALSE;
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
		$givenAnswerIds = array();
		foreach ($givenAnswers as $key => $givenAnswer) {
			if ($givenAnswer) {
				$givenAnswerIds[] = $key;
			}
		}
		return $givenAnswerIds == $correctAnswersIds;
	}

	function wasSkipped($givenAnswers)
	{
		foreach ($givenAnswers as $answer) {
			if ($answer->getValue()) {
				return false;
			}
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
					if (isset($oldAnswers[$this->getId()]) && is_array($oldAnswers[$this->getId()]) && in_array($answer->getId(), $oldAnswers[$this->getId()]))
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
