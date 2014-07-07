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
 * Include TestImporter
 */
require_once(CORE.'types/TestImporter.php');

/**
 * Import/Export of tests
 *
 * Default action: {@link doListTests()}
 *
 * @package Portal
 */
class ImportExportPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = 'list_tests';


	function doListTests()
	{
		libLoad("utilities::shortenString");
		$canExport = $this->checkAllowed('copy', false, false);
		$canImport = $this->checkAllowed('create', false);
		if (!$canExport && !$canImport) $this->checkAllowed('deny', true);

		$this->tpl->loadTemplateFile("ImportExport.html");
		$pageTitle = T("pages.import_export.export");

		$this->tpl->touchBlock("test_list");

		$root = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		$children = $root->getChildren();

		foreach ($children as $index => $child) {
			if (! $this->checkAllowed('copy', false, $child)) {
				unset($children[$index]);
			}
		}
		$children = array_values($children);
		usort($children, Array("Test", "compareTestTitle"));

		if (! $children) {
			$this->tpl->touchBlock("no_tests");
		}
		else
		{
			$this->tpl->touchBlock("has_tests");
			foreach ($children as $child)
			{
				$tmpTitle = shortenString($child->getTitle(),64);
				$this->tpl->setVariable("test_title", $tmpTitle);
				$this->tpl->setVariable("test_id", $child->getId());
				$this->tpl->parse("test");
			}

			$immId = get('ignoremissing', 0);
			if ($immId) {
				$this->tpl->setVariable('exp_option_name', 'ignoremissing');
				$this->tpl->setVariable('exp_option_value', $immId);
				$this->tpl->touchBlock('export_options');
			}
		}

		if ($canImport) {
			$this->tpl->touchBlock('test_import');
		} else {
			$this->tpl->hideBlock('test_import');
		}


		// Output
		$body = $this->tpl->get();
		$this->loadDocumentFrame();

		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", $pageTitle);
		$this->tpl->show();
	}

	function doImportTest()
	{
		$this->checkAllowed('create', true);

		if (!isset($_FILES['test_data']) || $_FILES['test_data']['size'] == 0) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.import_export.error.file_not_given', MSG_RESULT_NEG);
			return $this->doListTests();
		}

		$imp = new TestImporter();
		$res = $imp->processUpload($_FILES['test_data']['tmp_name'], $_FILES['test_data']['name']);

		if (!$res) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.import_export.error.partial_import', MSG_RESULT_NEUTRAL);
			return $this->doListTests();
		}
		redirectTo('container_block', array('working_path' => "_0_{$res}_", 'resume_messages' => 'true'));
	}

	function doExportTest()
	{
		if (post("overview")) {
			return $this->doTestOverview();
		}

		$testId = post("test_id");
		$imm = ($testId == post('ignoremissing', 0));
		$error = NULL;
		$errorParams = array();
		if (! $GLOBALS["BLOCK_LIST"]->existsBlock($testId)) {
			$error = "test_not_found";
		}
		else {
			$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testId, BLOCK_TYPE_CONTAINER);
			$rootLevel = FALSE;
			foreach ($test->getParents() as $parent) {
				if ($parent->getId() == 0) {
					$rootLevel = TRUE;
					break;
				}
			}
			if (! $rootLevel) {
				$error = "not_root_level";
			}
			elseif (! $this->checkAllowed('copy', false, $test)) {
				$error = "forbidden";
			}
		}

		if (!isset($error)) {
			require_once(CORE."types/TestExporter.php");
			$export = new TestExporter($testId);
			$arch = $export->getArchive($imm); 

			if ($arch == TEST_EXPORT_NO_WRITE) {
				$error = "tempdir_not_writable";
			} elseif ($arch == TEST_EXPORT_MISSING_MEDIA) {
				$error = "missing_media_files";

				$errorParams['files'] = implode(', ', $export->getMissingMediaFiles());
			}
		}

		if (isset($error)) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.import_export.error.".$error, MSG_RESULT_NEG, $errorParams);
			redirectTo("import_export", array("resume_messages" => "true", "ignoremissing" => $testId));
		}

		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=" . $export->getName());
		header("Content-Length: " . strlen($arch));
		print $arch;
	}

	function doTestOverview()
	{
		$testId = post("test_id");
		if (!isset($testId))
		$testId = getpost('test_id');
		
		if (! $GLOBALS["BLOCK_LIST"]->existsBlock($testId)) {
			$error = "test_not_found";
		}
		else {
			$test = $GLOBALS["BLOCK_LIST"]->getBlockById($testId, BLOCK_TYPE_CONTAINER);
			$rootLevel = FALSE;
			foreach ($test->getParents() as $parent) {
				if ($parent->getId() == 0) {
					$rootLevel = TRUE;
					break;
				}
			}
			if (! $rootLevel) {
				$error = "not_root_level";
			}
			elseif (! $this->checkAllowed('copy', false, $test)) {
				$error = "forbidden";
			}
		}

		if (isset($error)) {
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.import_export.error.".$error, MSG_RESULT_NEG);
			redirectTo("import_export", array("resume_messages" => "true"));
		}

		require_once(CORE."types/FileHandling.php");
		$this->mediaRepository = new FileHandling();

		$this->testMake = &$GLOBALS["PORTAL"]->loadPage("test_make");

		$pageTitle = $test->getTitle();
		$body = "";

		$logoId = $test->getLogo();
		if ($logoId)
		{
			$this->tpl->loadTemplateFile("ImportExport.html");
			$this->tpl->touchBlock("test_dump");
			$mediaList = $this->mediaRepository->listMedia($logoId);
			if ($mediaList) {
				$logoFile = $mediaList[0]->getFilename();
				$this->tpl->setVariable("logo_file", $logoFile);
				$this->tpl->touchBlock("test_logo");
				$body .= $this->tpl->get();
			}
		}
		if (getpost('layout') != 'print') {
			$this->tpl->loadTemplateFile("ImportExport.html");
			$this->tpl->touchBlock("test_dump");
			$this->tpl->touchBlock("print_link2");
			$this->tpl->setVariable("print_test_id", $testId);
			$body .= $this->tpl->get();
		}

		$body .= $this->dumpBlock($test);
		$this->loadDocumentFrame();

		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", $pageTitle);
		if (getpost('layout') == 'print') {
			$this->tpl->setVariable('css_file', 'portal/css/main_print.css');
			$this->tpl->parse('css_file');
			$this->tpl->hideBlock('menu_language_item');
			$this->tpl->hideBlock('menu_unswitch');
			$this->tpl->hideBlock('menu_user_area');
			$this->tpl->hideBlock('container_left');
		}
		$this->tpl->show();
	}

	function dumpBlockHeader($block)
	{
		$this->tpl->setVariable("title", $block->getTitle());
		$this->tpl->setVariable("description", $block->getDescription ());

		$this->tpl->hideBlock("container_block_header");
		$this->tpl->hideBlock("item_block_header");
		$this->tpl->hideBlock("info_block_header");

		foreach (array("container", "item", "info", "feedback") as $type) {
			if ($block->isBlockType(constant("BLOCK_TYPE_".strtoupper($type)))) {
				$this->tpl->touchBlock($type."_block_header");
			}
		}

		$this->tpl->parse("block_header");
	}

	function dumpBlock($block, $depth = 0)
	{
		@set_time_limit(5);
		$output = "";

		if ($block->isContainerBlock())
		{
			$children = $block->getChildren();
			foreach ($children as $child)
			{
				$this->tpl->loadTemplateFile("ImportExport.html");
				$this->dumpBlockHeader($child);
				$this->tpl->parse("block");
				
				if(!empty($child->data["introduction"]))
					$this->tpl->setVariable("introduction", $child->data["introduction"]);
				
				$body = $this->tpl->get();

				$body .= $this->dumpBlock($child, $depth+1);

				if ($depth > 0) {
					$this->tpl->loadTemplateFile("ImportExport.html");
					$this->tpl->setVariable("body", $body);
					$this->tpl->touchBlock("indent");
					$body = $this->tpl->get();
				}

				$output .= $body;
			}

			return $output;
		}

		$children = $block->getTreeChildren();
		$this->tpl->loadTemplateFile("ImportExport.html");
		foreach ($children as $child)
		{
			$this->dumpChild($child);
		}
		return $this->tpl->get();
	}

	function dumpChild($item)
	{
		@set_time_limit(5);
		$this->tpl->hideBlock("item_child");
		$this->tpl->hideBlock("info_child");
		$this->tpl->hideBlock("feedback_child");
		$this->tpl->setVariable("itemTitle", $item->getTitle());
		
		$itmDisabled = $item->getDisabled() ? "[".T("generic.disabled")."]" : "";
		$this->tpl->setVariable("itemDisabled", $itmDisabled);
		
		
		if (is_a($item, "InfoPage"))
		{
			$this->tpl->touchBlock("info_child");
			$this->tpl->setVariable("content", $item->getContent());
		}
		elseif (is_a($item, "FeedbackPage"))
		{
			$this->tpl->touchBlock("feedback_child");

			$firstParagraph = TRUE;
			foreach ($item->getParagraphs() as $paragraph)
			{
				if ($firstParagraph) {
					$firstParagraph = FALSE;
				} else {
					$this->tpl->touchBlock("paragraph_separator");
				}

				$conditions = $paragraph->getConditions();
				if ($conditions)
				{
					$this->tpl->touchBlock("conditions");
					$firstCondition = TRUE;
					foreach ($paragraph->getConditions() as $condition)
					{
						if ($firstCondition) {
							$firstCondition = FALSE;
						} else {
							$this->tpl->touchBlock("condition_separator");
						}

						$plugin = Plugin::load('extconds', $condition['type']);
						// Eek
						require_once(PORTAL.'pages/feedback_page.php');
						list($dims, $dimgroups) = FeedbackPagePage::_buildDimList($item->getParent());
						$condInfo = $plugin->renderCondition($dims, $dimgroups, $item, $condition, 'view');

						$this->tpl->setVariable("condition_info", $condInfo);
						$this->tpl->parse("condition");
					}
				}
				$this->tpl->setVariable("paragraph", $paragraph->getContents());
				$this->tpl->parse("paragraph");
			}
		}
		elseif (is_a($item, "Item"))
		{
			$this->tpl->touchBlock("item_child");
			$this->tpl->setVariable("question", $item->getQuestion());

			$parent = $item->getParent();
			$template = $item->getTemplate() ? $item->getTemplate() : $parent->getDefaultItemTemplate();
			list($templateFile, $templateClass, $templateVariant) = $this->testMake->inflateTemplateName($template);

			$this->tpl->hideBlock("item_class_text_line");
			$this->tpl->hideBlock("item_class_text_memo");
			$this->tpl->hideBlock("item_class_mcma");
			$this->tpl->hideBlock("item_class_mcsa");
			$this->tpl->hideBlock("item_class_mcsa_quick");

			foreach (array($templateClass."_".$templateVariant, $templateClass) as $suffix) {
				$blockName = "item_class_".$suffix;
				if ($this->tpl->blockExists($blockName)) {
					$this->tpl->touchBlock($blockName);
					break;
				}
			}

			$blockName .= "_answer";

			if ($this->tpl->blockExists($blockName))
			{
				foreach ($item->getChildren() as $answer)
				{
					$answercorrect = $answer->isCorrect() ? "  (".T("generic.correct").")" : "";
					
					$this->tpl->setVariable("answer", ($answer->getAnswer()).$answercorrect);
										
					$this->tpl->parse($blockName);
				}
			}
		}

		$this->tpl->parse("child");
	}
}

?>
