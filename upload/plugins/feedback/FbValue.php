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

class FbValue extends DynamicDataPlugin
{
	var $title = array(
		'de' => 'Einzelne Basiswerte',
		'en' => 'Single basic values',
	);
	var $desc = array(
		'de' => 'Gibt Punktzahlen, Summen, Prozentwerte etc. aus.',
		'en' => 'Shows score values, sums, procentual value etc.',
	);

	var $types = array(
		'denom'		=> 'dimensions',
		'ids'		=> 'dimensions',
		'num'		=> 'dimensions',
	);

	function init($generator)
	{
		$this->generator = $generator;
	}

	function calculate()
	{
		$res = 0;
		switch ($this->params['mode']) {
		case 'sum':
			$ids = explode(',', $this->params['ids']);
			foreach ($ids as $code) {
				$res += $this->generator->getValueByCode($code);
			}

			return $res;
		case 'ratio':
			// {value:mode="ratio" num="ID" denom="ID:max" percent="1"}
			// or
			// {value:mode="ratio" num="ID" denom="Maxim:literal" percent="1"}
			//
			// returns a ratio
			$num = (double) $this->generator->getValueByCode($this->params['num']);
			$denom = (double) $this->generator->getValueByCode($this->params['denom']);
			$min = (double) $this->generator->getMinScores($this->params['num']);
			if (($denom == 0) || ($denom == $min)) { 
				// $denom = 0.01; NO
				// if denom calculates to 0 the resulting value will be set to NAN
				return NAN;
			} else {
				$res = ($num - $min)/ ($denom - $min);
			}
			if ($this->params['percent']) {
				// return a value as a percent value, no decimals
				return (int) round(($res * 100));
			}
			return floatToString($res);
		}
	}

	function getOutput($params, $args)
	{
		if (!isset($params['percent'])) {
			// don't return a percent value
			$params['percent'] = 0;
		} 
		$this->params = $params;

		$perc = ($this->params['percent']) ? '%' : '';
		return $this->calculate() . $perc;
	}
}

