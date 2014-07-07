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
 * Provides an interface for a subset of the PEAR Graph package.
 * Parses a control array from session variable and can return an image.
 */
// temp fix, to show image, please debug!
// ini_set('display_errors', '0');
error_reporting(~E_STRICT);

session_name('TMID');
@session_start();
set_include_path('external/PEAR'. PATH_SEPARATOR. get_include_path());
require_once('Image/Graph.php');
require_once('Image/Canvas.php');

// Load the control array...
if(!isset($steps)) $steps = $_SESSION['fbgraphses' . $_GET['sesskey']];

// ...and process it
foreach ($steps as $step) {

	switch ($step[0]) {

	case 'init':
		$width = $step[1]['x'];
		$height = $step[1]['y'];
		$params = array('width' => $width, 'height' => $height, 'antialias' => 'native', 'transparent' => true);
		//if (!isset($step[1]['antialias']) || !$step[1]['antialias']) unset($params['antialias']);
		$canvas = Image_Canvas::factory('svg', $params);
		$graph = Image_Graph::factory('graph', $canvas);
		$graph->setBackgroundColor('white');
		$graph->setBorderColor('black');
		break;

	case 'addPlotArea':
		if ($step[1]['legend']) {
			$graph->add(Image_Graph::vertical(
				$pa = Image_Graph::factory('plotarea', array('category', 'axis', 'horizontal')),
				$legend = Image_Graph::factory('legend'),
				floor((1 - $step[1]['legend'] / (double)$height)*100)
			));
			$legend->setPlotarea($pa);
		} else {
			$pa = $graph->addNew('plotarea', array('category', 'axis', 'horizontal'));
		}
		$AxisY = $pa->getAxis(IMAGE_GRAPH_AXIS_Y);
		$AxisY->forceMinimum($step[1]['ymin']);
		$AxisY->forceMaximum($step[1]['ymax']);
		$ticks = $step[1]['ticks'];
		$ticks1 = array(); $ticks2 = array();
		for ($i = $ticks[0]; $i <= $ticks[3]; $i += $ticks[1]) {
			$ticks1[] = $i;
		}
		for ($i = $ticks[0]; $i <= $ticks[3]; $i += $ticks[2]) {
			$ticks2[] = $i;
		}
		$AxisY->setLabelInterval($ticks1, 1);
		$AxisY->setLabelOption('showtext', false, 1);
		$AxisY->setTickOptions(0, 1, 1);
		$AxisY->setLabelInterval($ticks2, 2);
		$AxisY->setLabelOptions(array('showtext'=> true), 2);
		$AxisY->showLabel(IMAGE_GRAPH_LABEL_MINIMUM | IMAGE_GRAPH_LABEL_ZERO | IMAGE_GRAPH_LABEL_MAXIMUM);
		$AxisY->setTickOptions(0, 3, 2);

		if (isset($step[1]['pad_x']) && $step[1]['pad_x']) {
			$AxisX = $pa->getAxis(IMAGE_GRAPH_AXIS_X);
			// Say goodbye, "encapsulation" :(
			$AxisX->_setAxisPadding('low', 10);
			$AxisX->_setAxisPadding('high', 12);
		}

		$pa->setPadding(array('left' => 20, 'top' => 20, 'right' => 20, 'bottom' => 20));
		$pa->setBorderColor($step[1]['bordercolor']);
		
		$plotarea[] = $pa;
		break;
	
	case 'setFont':
		$font = $graph->addNew('font', $step[1]['type']);
		$font->setSize($step[1]['size']+3);
		$graph->setFont($font);
		break;
		
	case 'setTitle':
		$AXISY = $plotarea[$step[1]['area']]->getAxis(IMAGE_GRAPH_AXIS_Y);
		$AxisY->setTitle($step[1]['title'], 'horizontal'); 
		break;

	case 'setFillColor':
		$plotarea[$step[1]['area']]->setFillColor('white');
		break;

	case 'addDatasets':
		for ($i = 0; $i < $step[1]['sets']; $i++)
			$dataset[] = Image_Graph::factory('dataset');
		break;

	case 'addPoint':
		$dataset[$step[1]['set']]->addPoint($step[1]['key'],  $step[1]['value']);
		break;

	case 'addPlot':
		$tmp = array();
		foreach ($step[1]['datasets'] as $set) $tmp[] = $dataset[$set];
		$myPlot = $plotarea[$step[1]['area']]->addNew($step[1]['type'], array($tmp, $step[1]['display']));
		
		if ($step[1]['type'] == 'line' OR $step[1]['type'] == 'bar') {
			$myPlot->setLineColor($step[1]['line_color']);
			switch ($step[1]['line_style']) {
			case 'dashed':
				$myPlot->setLineStyle(Image_Graph::factory('Image_Graph_Line_Dotted', array($step[1]['line_color'], 'transparent')));
				break;
			case 'normal':
			default:
				$myPlot->setLineStyle(Image_Graph::factory('Image_Graph_Line_Solid', array($step[1]['line_color'], 'transparent')));
			}
			
		}
		if (isset($step[1]['title'])) {
			$myPlot->setTitle($step[1]['title']);
		}
		$plot[] = $myPlot;

		break;
		
	case 'setMarker':
		switch ($step[1]['style']) {
			case 'cross':
				$CircleMarker = Image_Graph::factory('Image_Graph_Marker_Cross');
				$plot[$step[1]['plot']]->setMarker($CircleMarker);
				$CircleMarker->setFillColor($step[1]['color']);
				break;
			case 'value':
				$ValueMarker = Image_Graph::factory('Image_Graph_Marker_Value', array(IMAGE_GRAPH_VALUE_Y));
				$plot[$step[1]['plot']]->setMarker($ValueMarker);
				$PointingMarker_Rainfall = $plot[$step[1]['plot']]->addNew('Image_Graph_Marker_Value', array(26, $ValueMarker));
				$plot[$step[1]['plot']]->setMarker($PointingMarker_Rainfall); 
				
				break;
			case 'value2':
				$ValueMarker = Image_Graph::factory('Image_Graph_Marker_Value', array(IMAGE_GRAPH_VALUE_Y));
				$plot[$step[1]['plot']]->setMarker($ValueMarker);
			
				$ValueMarker->setBorderColor($step[1]['bordercolor']);
				$ValueMarker->setBackgroundColor($step[1]['bgcolor']);
				$ValueMarker->setFontColor($step[1]['color']);
				$ValueMarker->setPadding(2);
				break;
		}
		break;

	case 'addFillArray':
		$fillarray = Image_Graph::factory('Image_Graph_Fill_Array');
		foreach($step[1]['colors'] as $color) $fillarray->addColor($color);
		$plot[$step[1]['plot']]->setFillStyle($fillarray);
		break;

	case 'output':
		$graph->done();
		break;

	case 'output_file':
		$graph->done(
		    array('filename' => $step[1]['filename'])
		);
		break;
	}
}

?>
