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
 * The class for mcsa_drop item objects
 *
 * @package Upload
 */
class McsaDropItem extends McsaItem
{
	// Because capitalisation diffrenz between PHP4 and PHP5
	var $classname = 'McsaDropItem';

	public static $description = array(
					'de' => 'Eine Antwortalternative wählbar, als Drop-Down-Feld',
					'en' => 'Single answer allowed, as drop down list');

	var $templateFile = 'mcsa_drop.html';

	/**
	* Parse the appropriated templatefile
	*/
	function parseTemplate($key, $items_count, $oldAnswers, $missingAnswers)
	{
	$this->_initTemplate();

	$this->qtpl->setVariable("question", $this->getQuestion());
	$this->qtpl->setGlobalVariable("item_id", $this->getId());

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
