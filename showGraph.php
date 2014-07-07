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
 
set_include_path('external/PEAR'. PATH_SEPARATOR. get_include_path());
require_once('Image/Graph.php');
require_once('Image/Canvas.php');
$fontsize = 12;

//this script is for just printing the graph. The necessarry values and attributes for the graph are in $_SESSION["OutputGraph".$Key];

session_name('TMID');
@session_start();

		$Key = $_GET['Key'];
		$GraphData = $_SESSION["OutputGraph".$Key];
		
		$params = array('width' => $GraphData['width'], 'height' => $GraphData['height']);
		$canvas = Image_Canvas::factory('png', $params);
		$Graph = Image_Graph::factory('graph', $canvas);
		
		// add a TrueType font 
		$Font = $Graph->addNew('ttf_font', $GraphData['fontFile']); 
		
		// set the font size to 11 pixels 
		$Font->setSize($fontsize); 
		$Graph->setFont($Font); 
		
		$Graph->add( 
		    Image_Graph::vertical( 
		        Image_Graph::factory('title', array($GraphData['title'], $fontsize)),         
		        Image_Graph::vertical( 
		            $Plotarea = Image_Graph::factory('plotarea'), 
		            $Legend = Image_Graph::factory('legend'), 
		            90 
		        ), 
		        5 
		    ) 
		);    
		
		$Dataset = Image_Graph::factory('dataset'); 
		
		//workaround until the bug is resolved (Bug in PEAR graph 0.7.2 "Angled Text Intersects the graph")
		$maxDataSringLength = 0;
		
		foreach($GraphData['dataString'] as $dataString) {
			$Dataset->addPoint($dataString, $GraphData['dataValue'][$dataString]); 
			
			//workaround until the bug is resolved (Bug in PEAR graph 0.7.2 "Angled Text Intersects the graph")
			if (strlen($dataString) > $maxDataSringLength)
				$maxDataSringLength = strlen($dataString);
				
		}
		
		// create the 1st plot as smoothed area chart using the 1st dataset 
		$Plot = $Plotarea->addNew('line', array(&$Dataset)); 
		
		// set a line color 
		$Plot->setLineColor('black'); 

		// set a standard fill style 
		$Plot->setFillColor('#044294@1.0'); 
		
		$AxisX = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
	
		$AxisX->setTitle($GraphData['Axis_XTitle'],array('angle' => 0));
		$AxisX->setFontAngle($GraphData['dataStringAngle']); 
		
		//workaround until the bug is resolved (Bug in PEAR graph 0.7.2 "Angled Text Intersects the graph")
		if ($GraphData['dataStringAngle'] != 1 ) {
			$AxisX->setLabelOption('showOffset', true);
			$AxisX->setLabelOption('offset', $maxDataSringLength * 5 * sin($GraphData['dataStringAngle']));
		}
		
		$AxisY = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y); 
		$AxisY->setLabelInterval($GraphData['scaleIntervalY']);
		$AxisY->forceMaximum($GraphData['scaleRangeY']);
		$AxisY->setTitle($GraphData['Axis_YTitle'],"vertical");
		
		//add a marker to each bar
		$Marker = Image_Graph::factory('Image_Graph_Marker_Value', IMAGE_GRAPH_VALUE_Y);
		$PointMarker = Image_Graph::factory('Image_Graph_Marker_Pointing', array(0, -8, $Marker));
		$Plot->setMarker($PointMarker);  
		
		$Graph->done();
?>
