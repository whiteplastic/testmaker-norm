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
 * The class for mcsa_float_drop item objects
 *
 * @package Upload
 */
class McsaFloatDropItem extends McsaItem
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'McsaFloatDropItem';
	
	public static $description = array(
					'de' => 'Eine Antwortalternative wählbar, als Drop-Down-Feld im Fließtext',
					'en' => 'Single answer allowed, as drop down list in a continuous text'); 
	
  	var $templateFile = 'mcsa_float_drop.html';

    /**
     * Parse the appropriated templatefile
     */
    function parseTemplate($key, $items_count, $oldAnswers, $missingAnswers)
    {
        $this->_initTemplate();

				$this->qtpl->setVariable('odd_or_even', $key%2 ? 'odd' : 'even');

        $question = array();
        if (preg_match('/\{field:(?:drop|input)\}/', $this->getQuestion()))
        {
            $question = preg_split('/\{field:(?:drop|input)\}/', $this->getQuestion());
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
        $this->qtpl->setVariable("questionAfter", $question[1]);

        if (in_array($this->getId(), $missingAnswers))
        {
            $this->qtpl->setVariable('is_forced', 'isForced');
        }

        foreach ($this->getChildren() as $i => $answer)
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
}

?>
