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

require_once(CORE.'types/Item.php'); //bindet eine Datei ein und wertet diese zur Laufzeit des Skripts aus

/**
 * @package Upload
 */

//Definiert eine benannte Konstante: (Name der Konstanten, Wert der Konstanten)
define('MCAA2ITEM_DE', 'Mehrere Antwortalternativen zum Ordnen, ohne direkte Weiterleitung');
define('MCAA2ITEM_EN', 'Multiple answers for arranging, without quick forwarding');

/**
 * The class for mcma item objects
 *
 * @package Upload
 */
class Mcaa2Item extends Item
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'Mcaa2Item';
	public static $description = array(
					'de' => 'Mehrere Antwortalternativen sortierbar',
					'en' => 'Multiple answers sortable');

    var $templateFile = 'mcaa.html';
    var $defaultAction = "edit";


	function parseTemplate($key, $items_count, $oldAnswers, $missingAnswers) //Zerlegung und Weiterverarbeitung des Templates
    {
        $this->_initTemplate();

        $this->qtpl->setVariable("question", $this->getQuestion());
        $this->qtpl->setGlobalVariable("item_id", $this->getId());

        if (in_array($this->getId(), $missingAnswers))
        {
            $this->qtpl->setVariable('is_forced', 'isForced');
        }

		$answers = $this->getChildren();

        foreach ($answers as $i => $answer)
		{
			if(!$answer->getDisabled())
			{
					$this->qtpl->setVariable("aid", $answer->getId());
					$this->qtpl->setVariable("answer", $answer->getAnswer());
					if (isset($oldAnswers[$this->getId()]) && $oldAnswers[$this->getId()] == $answer->getId())
					{
						$this->qtpl->setVariable('aold', ' selected="selected"');
					}
					$this->qtpl->parse("answer");
			}
        }

        if ($this->qtpl->blockExists("send_bar") && $key == $items_count-1)
        {
            $this->qtpl->touchBlock("send_bar");
        }

        return $this->qtpl->get();
    }


    function interpretateAnswers($answers_array)
	{
		
		$answers = array_key_exists($this->getId(), $answers_array) ? $answers_array[$this->getId()] : array();
		// Prevent glitch when the user manipulates the submitted data
		if (!is_array($answers))
		{
			$answers = array($answers);
		}

		$result = array();
		$wasSkipped = FALSE;
		// Iterate through the child list to ensure that the order of the results in the answer set is correct
		
		foreach ($answers as $position => $answer) {
			$result[$answer] = 1;
		}
		return array($wasSkipped, $result);
		
	}
	
	

	/**
	 * Compare a given answer and the correct answer for this item
	*/
	function evaluateAnswer($givenAnswerSet)
	{	
		foreach($givenAnswerSet->data['answers'] as $answer => $foo) {
			$given[] = $answer;
		}

		$useCorrectness = false;
		foreach ($this->getChildren() as $answer) {
			if ($answer->isCorrect()) $useCorrectness = true;
			$correct[$answer->getPosition() - 1] = $answer->getId();
		}
		
		$correctnes = true;
		
		if ((!$useCorrectness) || (!(isset($given)))) return NULL;
		for ($i=0; $i<count($given); $i++) {
			if ($given[$i] != $correct[$i])
				$correctnes = false;
		}
		return $correctnes;
	}
	
	function getCustomScore($givenAnswerSet)
	{
		return $this->evaluateAnswer($givenAnswerSet);
	}

	function getMaxScore($givenAnswerSet)
	{
		return 1;
	}
	
	function hasCustomScoring()
	{
		return true;
	}

	function hasSimpleAnswer()
	{
		return false;
	}
}

?>
