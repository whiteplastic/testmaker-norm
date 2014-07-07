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

/**
 * Load page selector widget
 */
require_once(PORTAL.'PageSelector.php');
/**
 * Loads the base class
 */
 
require_once(PORTAL.'DataAnalysisPage.php');
libLoad('PEAR');
require_once('Image/Graph.php');


/**
 * Test accomplishment
 *
 * Default action: {@link doShowOverview()}
 *
 * @package Portal
 */
 
class TestRunResultPage extends DataAnalysisPage
{
	var $defaultAction = 'result';
	var $histoAnsweredItems = array();
	var $Graph = array();
	
	function doResult() {
	
		$this->tpl->loadTemplateFile("TestRunResult.html");
		$this->initTemplate("result");
		$pageTitle = T("pages.data_analysis");
		
		// Load lib
		libLoad('utilities::getCorrectionMessage');

		$this->init();
		$this->initFilters("test_runresult");
		
		$this->keepFilterSettings();
		$testRunCount = $this->countTestRuns();
		
		if ((!$testRunCount) or ($testRunCount == 0))
		{
			$this->tpl->touchBlock("no_results");
		}
		elseif ($this->filters["testId"] == 0)
		{
			$this->tpl->touchBlock("no_filter");
		}
		elseif ($this->filters["testId"] != 0)
		{
			$this->tpl->touchBlock("has_results");
			$this->tpl->setVariable("result_count", $testRunCount);
			
			//construct the graphs
			require_once(CORE."types/BarGraph.php");
			$this->Graph[] = new BarGraph(T('pages.test_runresult.histotitle.answered'), 500, 300);
			$this->Graph[] = new BarGraph(T('pages.test_runresult.histotitle.answered_required'), 500, 300);
			$this->Graph[] = new BarGraph(T('pages.test_runresult.histotitle.items_shown'), 500, 300);
			$this->Graph[] = new BarGraph(T('pages.test_runresult.histotitle.pages_shown'), 500, 300);
			$this->Graph[] = new BarGraph(T('pages.test_runresult.histotitle.date_of_prticipation'), 500, 300);
			$this->Graph[] = new BarGraph(T('pages.test_runresult.histotitle.total_time'), 650, 400);
			
			//init the bar values for each graph
			for ($i = 0; $i<=18; $i++) {
				$this->histoAnsweredItem[$i] = 0;
				$this->histoAnsweredItemRequired[$i] = 0;
				$this->histoShownItems[$i] = 0;
				$this->histoShownPages[$i] = 0;
				$this->histoTime[$i] = 0;
			}
			
			for ($i = 1; $i<=12; $i++) {
				$this->histoDateOfParticipation[$i] = 0;
			}
			
			$monthToday = gmdate("n", time());
			$maxTime = 0;
			
			$NumTestRuns = 0;
			while ($testRunCount > $NumTestRuns) {
				if($this->filters["testRunID"] != 0) {
					$testRuns = array($this->testRunList->getTestRunById($this->filters["testRunID"]));
				} else {
					$testRuns = $this->getTestRuns(FALSE, $NumTestRuns, 1000);
				}
				$NumTestRuns = $NumTestRuns + 1000;
				//sum up the values over the testruns for the bars
				foreach ($testRuns as $testRun ) {
					//getting the values
					$value1 = floor(10 * $testRun->getAnsweredItemsRatio());
					$value2 = floor(10 * $testRun->getAnsweredRequiredItemsRatio());
					$value3 = floor(10 * $testRun->getShownItemsRatio());
					$value4 = floor(10 * $testRun->getShownPagesRatio());
					$value5 = $testRun->getTotalTime()/1000;
					
					if ($value5 > $maxTime) {
						$maxTime = $value5;
					}
					
					$testRunTime = $testRun->getStartTime();
					$month = gmdate("n", $testRunTime);
					$year = gmdate("Y", $testRunTime);
					$yearNow = gmdate("Y", time());
					$monthNow = gmdate("n", time());
					
					if ($value1 == 10)
						$value1 = 9;
					if ($value2 == 10)
						$value2 = 9;
					if ($value3 == 10)
						$value3 = 9;
					if ($value4 == 10)
						$value4 = 9;
					if ($value5 > 10800)
						$value5 = 10800;
					
					//sum up the bar values 
					$this->histoAnsweredItem[$value1]++;
					$this->histoAnsweredItemRequired[$value2]++;
					$this->histoShownItems[$value3]++;
					$this->histoShownPages[$value4]++;
					if (($month > $monthNow) || ($year == $yearNow))
						$this->histoDateOfParticipation[$month]++;
					$this->histoTime[floor($value5/600) % 25]++;
				}  
			}
			
			//add the bar values to the graph
			for ($i = 0; $i<=9; $i++) {
				$this->Graph[0]->addData($i*10 .'-'. ($i+1)*10, round(($this->histoAnsweredItem[$i]/$testRunCount)*100, 1) ); 
				$this->Graph[1]->addData($i*10 .'-'. ($i+1)*10, round(($this->histoAnsweredItemRequired[$i]/$testRunCount)*100, 1) );
				$this->Graph[2]->addData($i*10 .'-'. ($i+1)*10, round(($this->histoShownItems[$i]/$testRunCount)*100, 1) );
				$this->Graph[3]->addData($i*10 .'-'. ($i+1)*10, round(($this->histoShownPages[$i]/$testRunCount)*100, 1) );
			}
			
			//add the bar values to the graph
			for ($i = $monthToday+1; $i<=12; $i++) {
				$nameOfMonth = date("M", mktime(0, 0, 0, $i, 1,   date("Y")));
				$this->Graph[4]->addData($nameOfMonth, round(($this->histoDateOfParticipation[$i]/$testRunCount)*100, 1));
			}
			
			//add the bar values to the graph
			for ($i = 1; $i <= $monthToday+0; $i++) {
				$nameOfMonth = date("M", mktime(0, 0, 0, $i, 1,   date("Y")));
				$this->Graph[4]->addData($nameOfMonth, round(($this->histoDateOfParticipation[$i]/$testRunCount)*100, 1));
			}
			
			//add the bar values to the graph
			for ($i = 0; $i<=17; $i++) {
				$this->Graph[5]->addData($i*10 .'-'. ((($i+1)*10)-1), round(($this->histoTime[$i]/$testRunCount)*100, 1), 0);
			}
		
			$this->Graph[5]->addData('180+', round(($this->histoTime[$i]/$testRunCount)*100, 1), 0);
			
			
			//set the ouptputformat
			$this->Graph[0]->outputFormat(109,10);
			$this->Graph[1]->outputFormat(109,10);
			$this->Graph[2]->outputFormat(109,10);
			$this->Graph[3]->outputFormat(109,10);
			$this->Graph[4]->outputFormat(109,10);
			$this->Graph[5]->outputFormat(109,10);
			
			//set the axis labels
			$this->Graph[0]->setAxisTiltes(T("pages.test_runresult.histotitle.answered.axis_x"), T("pages.test_runresult.histotitle.standard.axis_y"));
			$this->Graph[1]->setAxisTiltes(T("pages.test_runresult.histotitle.answered_required.axis_x"), T("pages.test_runresult.histotitle.standard.axis_y"));
			$this->Graph[2]->setAxisTiltes(T("pages.test_runresult.histotitle.items_shown.axis_x"), T("pages.test_runresult.histotitle.standard.axis_y"));
			$this->Graph[3]->setAxisTiltes(T("pages.test_runresult.histotitle.pages_shown.axis_x"), T("pages.test_runresult.histotitle.standard.axis_y"));
			$this->Graph[3]->setAxisTiltes("", T("pages.test_runresult.histotitle.standard.axis_y"));
			$this->Graph[5]->setAxisTiltes(T("pages.test_runresult.histotitle.total_time.axis_x"), T("pages.test_runresult.histotitle.standard.axis_y"));
			
			foreach ($this->Graph as $graph) {
				$graph->show($this->tpl);
			}
		}
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", $pageTitle);
		
		$this->tpl->show();
	}
	
}
?>