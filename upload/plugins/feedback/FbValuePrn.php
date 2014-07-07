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

libLoad('utilities::FloatToString');

class FbValuePrn extends DynamicDataPlugin
{
	var $title = array(
		'de' => 'Prozentrangnormbasierte Werte',
		'en' => 'Values based on percent range norm',
	);
	var $desc = array(
		'de' => 'Gibt Vergleiche des Probandenergebnisses zu einer vorgegebenen Ergebnisverteilung aus.',
		'en' => 'Shows the participant\'s result in relation to a pre-defined distribution of result values.',
	);

	var $types = array(
		'id'		=> 'dimensions',
	);

	function init($generator)
	{
		$this->generator = $generator;
	}

	function calculate()
	{
		$res = 0;

		$score = $this->generator->getScores($this->params['id']);
		$cls = $this->generator->getDimClasses($this->params['id']);
		$ltCount = 0;
		$eqCount = 0;
		$gtCount = 0;
		$count = 0;

		foreach ($cls as $cscore => $size) {
			if ($cscore <  $score) $ltCount += $size;
			elseif ($cscore == $score) $eqCount += $size;
			else $gtCount += $size;
		}
		$count = $ltCount + $eqCount + $gtCount;
		if (!$count) return 100;

		$ltRatio = (double) $ltCount / (double) $count;
		$eqRatio = (double) $eqCount / (double) $count;
		$gtRatio = (double) $gtCount / (double) $count;

		switch ($this->params['mode']) {
			case 'lt':  return (int) round($ltRatio * 100);
			case 'lte': return (int) round(($ltRatio + $eqRatio) * 100);
			case 'eq':  return (int) round($eqRatio * 100);
			case 'gte': return (int) round(($gtRatio + $eqRatio) * 100);
			case 'gt':  return (int) round($gtRatio * 100);
			default:    return 0;
		}
	}

	function getOutput($params, $args)
	{
		$this->params = $params;
		return $this->calculate() .'%';
	}

}

