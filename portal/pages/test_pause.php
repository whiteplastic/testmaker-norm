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
require_once(CORE.'types/Test.php');
require_once(CORE.'types/ItemAnswer.php');
require_once(CORE.'types/TestRunList.php');
require_once(CORE."types/FileHandling.php");
require_once('portal/FrontendPage.php');
/**
 * Shows a pause screen for the current TestRun, including progress information
 *
 * Default action: {@link doDefault()}
 *
 * @package Portal
 */
class TestPausePage extends FrontendPage
{
	function doDefault() {
        $this->doPause();
	}

	function doPause() {
		$userId = $GLOBALS['PORTAL']->getUserId();
		$this->tpl->loadTemplateFile("TestPause.html");
		$testRunList = new TestRunList();
		$tr = new TestRun($testRunList, getpost("test_run_id"));
		$test = new Test($tr->getTestId());
		$progress = $tr->getProgress();
		$fileHandling = new Filehandling();
		$list = $fileHandling->listMedia($test->getLogo());
		if (count($list) && $test->getLogoShow() > 0) {
			$this->tpl->setVariable("filename", $list[0]->getFilePath()."/".$list[0]->getFileName());
			$this->tpl->parse("pause_logo");
		}
		
		$testStyle = $this->getStyle($test->getId());
		
		$this->tpl->setVariable("style", $testStyle);
		$this->tpl->setVariable("test_title", $test->getTitle());
		$this->tpl->setVariable("test_id", $test->getId());
		$this->tpl->setVariable("progress", $progress);
		$this->tpl->parse("pause_page");
		if ($userId === 0) $this->tpl->hideBlock("continue_later_button");
		$this->tpl->show();
		$this->loadDocumentFrame();
	}
}

?>
