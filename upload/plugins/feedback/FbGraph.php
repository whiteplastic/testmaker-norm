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

libLoad('PEAR');
require_once('Image/Graph.php');
require_once(CORE.'types/Dimension.php');
require_once(CORE.'types/DimensionGroup.php');

class FbGraph extends DynamicDataPlugin
{
	var $title = array(
		'de' => 'Diagramm',
		'en' => 'Graph',
	);
	var $desc = array(
		'de' => 'Zeigt Testergebnisse als Diagramm an.',
		'en' => 'Displays test result as a graph.',
	);

	var $lang = array(
		'de' => array(
			'plugin.fbgraph.legend.results' => 'Ihre Werte',
			'plugin.fbgraph.legend.reference' => 'Vergleichswerte',
		),
		'en' => array(
			'plugin.fbgraph.legend.results' => 'Your values',
			'plugin.fbgraph.legend.reference' => 'Reference values',
		),
	);

	var $types = array(
		'dimgroup'		=> 'dimension_groups',
	);

	function init($generator)
	{
		$this->generator = $generator;
	}

	function getScores()
	{	
		$_scores = array();
		if (isset($this->params['dimgroup'])) {
			$temp = DataObject::getById('DimensionGroup',(int)$this->params['dimgroup']);
			if (isset($temp) && $temp->get('block_id') == $this->generator->getBlockId()) {
			    foreach ($temp->get('dimension_ids') as $dimId) {
                    $_scores[$dimId] = $this->generator->getScores($dimId);
                    $_maxscores[$dimId] = $this->generator->getMaxScores($dimId);
                }
            } else {
            	$_scores = $this->generator->getScores();
           		$_maxscores = $this->generator->getMaxScores();
            }
		} else {
		    $_scores = $this->generator->getScores();
			$_maxscores = $this->generator->getMaxScores();
		}
		
		$scores = array();
		foreach ($_scores as $key => $value) {
			$temp = DataObject::getById('Dimension',$key);
			$dimSc = $temp->getAnswerScores();

			$min = $dimSc['min'];
			$max = $_maxscores[$key];
			$mean = $temp->get('reference_value');
			$sd = $temp->get('std_dev');
	
			if (($max == 0) || ($min == $max)) {
				// for a max value of 0 a value can't be calculated, so set to NAN
				$scores['user'][(string)$temp->get('name')] = NAN;	
			} else {
				//Standardwert
				$scores['user'][(string)$temp->get('name')] = round(50 + 10 * (($value - $mean) / $sd));
				//$scores['user'][(string)$temp->get('name')] = round(($value - $min) / ($max - $min) * 100);
			}

			$refValue = $temp->get('reference_value');
			if ($temp->get('reference_value') != NULL && $temp->get('reference_value')!= 0) {
				if ($temp->get('reference_value_type') == 0) {
					// Reference value is absolut points
					
					// check for incorrect values
						if ($refValue < $min) {
							// the given reference value is below the calculated min; should not occur 
							$refValue = $min;
						}
						if ($refValue > $max) {
							// the given reference value is above the calculated max; should not occur - but does in testing conditions
							$refValue = $max;
						}
					
						if (($max == 0) || ($min == $max)) {
						$scores['reference'][(string)$temp->get('name')] = NAN;
					} else {
						$scores['reference'][(string)$temp->get('name')] = 50; // mean
					
					//	$scores['uppernorm'][(string)$temp->get('name')] = 60; // +1StdDev
					//	$scores['lowernorm'][(string)$temp->get('name')] = 40; // -2StdDev

						//$scores['reference'][(string)$temp->get('name')] = round((($refValue - $min) / ($max - $min)) * 100);

						
				
/*						
						// 100 Percent is maximum and 0.0 is minimum... INDEED, BUT there must be a reason for values below 0 or above 100...
						if  ($scores['reference'][(string)$temp->get('name')] > 100.0)
							$scores['reference'][(string)$temp->get('name')] = 100.0;
						if  ($scores['reference'][(string)$temp->get('name')] < 0.0)
							$scores['reference'][(string)$temp->get('name')] = 0.0;
*/
					}
				} else {
					// Reference value is a percentage
					$scores['reference'][(string)$temp->get('name')] = $refValue;
				}
			}
		}
		return $scores;
	}

	function setupGraph()
	{
		// Get the values to be displayed
		$values = $this->getScores();
		// Create the control array for fbgrapher.php
		// Each entry is an array of the form array('command', array(parameters))
		$grapher = array();

		// Initialize graph, set canvas size
        $grapher[] = array('init', array('x' => 1000, 'y' => (count($values['user'])*55)+120)    );

        // Initialize plotarea
        $grapher[] = array('addPlotArea', array('ymin' => 0, 'ymax' => 110, 'ticks' => array(20, 10, 5, 80),
			'pad_x' => true, 'legend' => 0, 'bordercolor' => '#81a0c9')
		);
		
		// Make descriptions a bit prettier
		$grapher[] = array('setFont', array('size' => 12, 'type' => PORTAL.'external/verdana.ttf'));
		//$grapher[] = array('setTitle', array('area' => 0, 'title' => T('labels.graph.percentage')));
		$grapher[] = array('setTitle', array('area' => 0, 'title' => T('labels.graph.percentage')));

		if (!isset($values['reference']))
			$values['reference'] = 0;
        // Add Datasets, one for the user score and one for the reference score
       if (isset($values['reference'])) 
			$grapher[] = array('addDatasets', array('sets' => 4));
		else 
			$grapher[] = array('addDatasets', array('sets' => 1));

        // Add user score to dataset 0 and reference score to dataset 1
		if(!is_null($values['user']))
        foreach (array_reverse($values['user'], true) as $key => $value) {
        	if($value < 0) $value = 0;
        	if (isset($values['reference'][$key])) {
				if ($value >= $values['reference'][$key]) {
				error_reporting(0);
				@$grapher[] = array('addPoint', array('set' => 1, 'key' => $key, 'value' => $value));
				@$grapher[] = array('addPoint', array('set' => 0, 'key' => $key, 'value' => $values['reference'][$key]));
				@$grapher[] = array('addPoint', array('set' => 2, 'key' => $key, 'value' => $values['reference'][$key] - 10));
				@$grapher[] = array('addPoint', array('set' => 3, 'key' => $key, 'value' => $values['reference'][$key] + 10));
				error_reporting(1);
				}
				else {
				error_reporting(0);
				@$grapher[] = array('addPoint', array('set' => 0, 'key' => $key, 'value' => $values['reference'][$key]));
				@$grapher[] = array('addPoint', array('set' => 1, 'key' => $key, 'value' => $value));				
				@$grapher[] = array('addPoint', array('set' => 2, 'key' => $key, 'value' => $values['reference'][$key] - 10));
				@$grapher[] = array('addPoint', array('set' => 3, 'key' => $key, 'value' => $values['reference'][$key] + 10));
				error_reporting(1);
				}
			}
			else 
			$grapher[] = array('addPoint', array('set' => 1, 'key' => $key, 'value' => $value));
        }

		
		// Add the reference score plot to the canvas.
		if (isset($values['reference'])) 
			$grapher[] = array('addPlot', array('type' => 'line', 'line_style' => 'solid', 'line_color' => '#aaaaaa', 'display' => '', 'datasets' => array(0), 'area' => 0, 'title' => T('Mittelwert')));
			$grapher[] = array('addPlot', array('type' => 'line', 'line_style' => 'dotted', 'line_color' => '#c22515', 'display' => '', 'datasets' => array(2), 'area' => 0, 'title' => T('Unterer Normbereich')));
			$grapher[] = array('addPlot', array('type' => 'line', 'line_style' => 'dotted', 'line_color' => '#c22515', 'display' => '', 'datasets' => array(3), 'area' => 0, 'title' => T('Oberer Normbereich')));
		// Add the user score plot to the canvas.
		$grapher[] = array('addPlot', array('type' => 'line', 'line_style' => 'normal', 'line_color' => '#81a0c9', 'display' => '', 'datasets' => array(1), 'area' => 0, 'title' => T('plugin.fbgraph.legend.results')));
		
		// Set background fill colors and marker style for results plot
		$grapher[] = array('setFillColor', array('area' => 0));
		$grapher[] = array('setMarker', array('plot' => 3, 'style' => 'cross', 'bordercolor' => '#000080', 'bgcolor' => 'white', 'color' => '#000080'));
		// Set marker style for reference plot
		if (isset($values['reference'])) 
		//	$grapher[] = array('setMarker', array('plot' => 0, 'style' => 'cross2', 'bordercolor' => '#999999', 'bgcolor' => '#ffffff', 'color' => '#999999'));

		$grapher[] = array('output', array());
		
		return $grapher;
	}

	function setupGraphDummy() {


	}

	/**
	* Returns a representation of the feedback data.
	* @returns html
	*/
	function getOutput($params , $args)
	{
		$this->params = $params;
		if (isset($args['request_binary']))
			return $this->getBinaryOutput();
		else {
			$sessionKey = $this->generator->generateSessionKey();
			$steps = $this->setupGraph();
			if(isset($_SESSION['fbgraphses1']) && $_SESSION['fbgraphses1'] == $steps)
				return '<img src="fbgrapher.php?sesskey=1" />';
			
			$_SESSION['fbgraphses' . $sessionKey] = $steps;
			return '<img src="fbgrapher.php?sesskey='. $sessionKey . '" />';			
		}
	}
	
	function getBinaryOutput()
	{
		$steps = $this->setupGraph();
		$steps[] = array('output_binary', array());
		error_reporting(0);
		ob_start();
		include('fbgrapher.php');
		$image = ob_get_contents();
		ob_end_clean();
		//error_reporting(1);
		return $image;
	}

}

