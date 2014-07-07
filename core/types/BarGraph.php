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
 * @package Core
 */

 
libLoad('PEAR');
require_once('Image/Graph.php');
require_once('Image/Canvas.php');

/**
* class for the BarGraph
*/
class BarGraph
{
	var $width;
	var $height;
	var $filename;
	var $Legend;
	var $dataString = array();
	var $Axis_XTitle = "";
	var $Axis_YTitle = "";
	
	// constructor
	function BarGraph($title, $width, $height)
	{
		$this->width = $width;
		$this->height = $height;
		$this->title = $title;
		$this->fontFile = PORTAL.'external/freesans.ttf';
	}
	
	//add the data to the graph ( label and value for each bar)
	function addData($dataString, $dataValue, $angle = 1) {
		// create the 1st plot as smoothed area chart using the 1st dataset 
		$this->dataString[] = $dataString;
		$this->dataStringAngle = $angle;
		$this->dataValue[$dataString] = $dataValue;
	}
	
	//set the range of the axis
	function outputFormat($scaleRangeY, $scaleIntervalY)
	{
		$this->scaleRangeY = $scaleRangeY;
		$this->scaleIntervalY = $scaleIntervalY;
	}
	
	//add the axis title 
	function setAxisTiltes($Axis_X = "", $Axis_Y = "") {
		$this->Axis_XTitle = $Axis_X;
		$this->Axis_YTitle = $Axis_Y;
	}
	
	//show the graph in the template
	function show($tpl)
	{
		if (!isset($_SESSION["countGraph"])) {
			$_SESSION["countGraph"] = 0;
		}
		else
		 $_SESSION["countGraph"]++;
		
		$key = $_SESSION["countGraph"] % 12;
		
		$data = array('title' => $this->title, 'width' => $this->width, 'height' => $this->height, 'fontFile' => $this->fontFile, 'dataString' => $this->dataString, 'dataStringAngle' => $this->dataStringAngle,
		'dataValue' => $this->dataValue, 'Axis_XTitle' => $this->Axis_XTitle, 'Axis_YTitle' => $this->Axis_YTitle, 'scaleRangeY' => $this->scaleRangeY, 'scaleIntervalY' => $this->scaleIntervalY);
		$_SESSION["OutputGraph".$key] = $data;
		
        // output the Graph 
		$tpl->setVariable("filename","showGraph.php?Key=".$key);
		$tpl->setVariable("graph_width",$this->width);
		$tpl->setVariable("graph_height",$this->height);
		$tpl->setVariable("alt",time());
		$tpl->parse("histo");	
	}
}
?>