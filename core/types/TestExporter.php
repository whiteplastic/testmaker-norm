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


/**_dumpInfo
 * @package Core
 */

libLoad("utilities::selectiveMerge");
libLoad("utilities::tempdir");

/**
 * Include PCLZip
 */
require_once(ROOT.'external/pclzip/pclzip.lib.php');
/**
 * Include FileHandling class
 */
require_once(CORE."types/FileHandling.php");

libLoad('utilities::serializeToPlainText');

define('TEST_EXPORT_NO_WRITE', -1);
define('TEST_EXPORT_MISSING_MEDIA', -2);

/**
 * TestExporter class
 * @package Core
 */
class TestExporter
{
	/**#@+
	 * @access private
	 */
	var $db;
	var $test;
	var $mediaRepository = NULL;
	var $mediaFiles = array();
	var $mediaIds = array();
	var $missingMedia = array();
	var $mediaPath = array();
	/**#@- */

	/**
	 * Constructor
	 *
	 * @param integer Test id
	 */
	function TestExporter($id)
	{
		$this->test = $GLOBALS["BLOCK_LIST"]->getBlockById($id, BLOCK_TYPE_CONTAINER);
		$this->mediaRepository = new FileHandling();
		$this->db = $GLOBALS["dao"]->getConnection();
	}

	/**
	 * Build test archive or data file
	 *
	 * @return string Exported Test
	 */
	function getArchive($ignoreMissingFiles = false)
	{
		$data = serializeToPlainText($this->_dumpTest($this->test)); 
		if (count($this->mediaFiles) > 0)
		{
			$delstack = array();
			$zipfiles = array();

			// Fail if media files are missing
			if (!$ignoreMissingFiles && $this->missingMedia)
			{
				return TEST_EXPORT_MISSING_MEDIA;
			}

			// Build ZIP
			$tempdir = tempdir(TM_TMP_DIR, "ziptmp");
			if (!$tempdir) return TEST_EXPORT_NO_WRITE;
			$delstack[] = $tempdir;

			$zipdir = $tempdir . "/content/";
			mkdir($zipdir);
			$delstack[] = $zipdir;

			$dataname = $zipdir . "test" . $this->test->getId() . ".txt";
			$datafile = fopen($dataname, "w");
			fwrite($datafile, $data);
			fclose($datafile);
			$zipfiles[] = $dataname;
			foreach ($this->mediaIds as $id => $newName)
			{
				$fh = new FileHandling();
				$path = $fh->getMediaPath($id);
				copy($path, $zipdir . $newName);
				$zipfiles[] = $zipdir . $newName;
			}

			$zipname = $tempdir . "/temp.zip";
			$zip = new PclZip($zipname);
			$zip->create($zipfiles, PCLZIP_OPT_REMOVE_ALL_PATH);

			$zipfile = fopen($zipname, "rb");
			$zipdata = fread($zipfile, filesize($zipname));
			fclose($zipfile);
			$delstack[] = $zipname;

			// Clean Up
			foreach(array_reverse(array_merge($delstack, $zipfiles)) as $file)
			{
				if (is_dir($file))
				{
					rmdir($file);
				}
				else
				{
					unlink($file);
				}
			}

			return $zipdata;
		}
		else
		{
			// No media, no ZIP...
			return $data;
		}
	}

	/**
	 * Build a suitable file name for the exported Test
	 *
	 * Should only be called after getArchive.
	 *
	 * @return string Test file name
	 */
	function getName()
	{
		return "test" . $this->test->getId() . "." .
			((count($this->mediaFiles) > 0) ? "zip" : "txt");
	}

	/**
	 * Get a list of missing media files.
	 *
	 * Should only be called after getArchive.
	 *
	 * @return string[]
	 */
	function getMissingMediaFiles()
	{
		return $this->missingMedia;
	}


	function _recurse($block)
	{
		$data = array();

		$children = $block->getChildren();
		foreach ($children as $child)
		{
			switch ($child->getBlockType())
			{
				case BLOCK_TYPE_CONTAINER:
					$data[] = array("type" => "sub") +
						$this->_dumpTest($child);
					break;
				case BLOCK_TYPE_INFO:
					$data[] = $this->_dumpInfo($child);
					break;
				case BLOCK_TYPE_FEEDBACK:
					$data[] = $this->_dumpFeedback($child);
					break;
				case BLOCK_TYPE_ITEM:
					$data[] = $this->_dumpItem($child);
					break;
			}
		}
		return $data;
	}

	function _dumpTest($block)
	{
		$data = array();

		$data["title"] = $block->getTitle();
		$data["description"] = $block->getDescription();
		$data["show subtests"] = $block->getShowSubtests();
		$data["language"] = $block->getLanguage();
		$data["progress bar"] = $block->getShowProgressbar();
		$data["pause_button"] = $block->getShowPausebutton();
		$data["open date"] = $block->getOpenDate();
		$data["close date"] = $block->getCloseDate();

		$style = array();
		$logoId = $block->getLogo();
		if ($logoId)
		{
			$mediaList = $this->mediaRepository->listMedia($logoId);

			if ($mediaList) {
				$id = $this->_getMediaId($mediaList[0]);
				if ($id) {
					$style["logo file"] = $id;
				}
			}
		}
		$blockStyle = $block->getStyle();

		if (array_key_exists("Top", $blockStyle))
			$style = selectiveMerge($style, $blockStyle["Top"], array(
				"text-align" => "logo align"));
		if (array_key_exists("body", $blockStyle))
			$style = selectiveMerge($style, $blockStyle["body"], array(
				"background-color" => "background"));
		if (array_key_exists("Question", $blockStyle))
			$style = selectiveMerge($style, $blockStyle["Question"], array(
				"background-color" => "item stem background",
				"font-family" => "font",
				"font-size" => "font size",
				"font-style" => "font style",
				"font-weight" => "font weight",
				"color" => "font color",
				"border" => "question border",
			));
		if (array_key_exists("Answers", $blockStyle))
			$style = selectiveMerge($style, $blockStyle["Answers"], array(
				"background-color" => "answers background"));
		if (array_key_exists("wrapper", $blockStyle))
			$style = selectiveMerge($style, $blockStyle["wrapper"], array(
				"width" => "wrapper width"));
		if (array_key_exists("Answers table.Border", $blockStyle))
			$style = selectiveMerge($style, $blockStyle["Answers table.Border"], array(
				"border" => "answers border"));
		if (array_key_exists("Answers td.Border", $blockStyle))
			$style = selectiveMerge($style, $blockStyle["Answers td.Border"], array(
				"border" => "answer border",));		
		if (count($style) > 0) {
			$data["style"] = $style;
		}

		$sub = $this->_recurse($block);
		if (count($sub) > 0)
			$data["contents"] = $sub;

		return $data;
	}

	function _dumpInfo($block)
	{
		if($block->getDisabled())
			return array();
		
		$data = array();
		$data["type"] = "info";
		$data["title"] = $block->getTitle();
		$data["description"] = $block->getDescription();

		$this->_dumpMedia($block, $data);

		$data["contents"] = array();

		$children = $block->getTreeChildren();
		foreach ($children as $child)
		{
			$data["contents"][] = array(
				"title" => $child->getTitle(),
				"text" => $this->_replaceMedia($child->getContent())
			);
		}

		return $data;
	}

	var $itemExcludeFields = array(
				'disabled',
				'media_connect_id',
				'original',
				'owner',
				'permissions_recursive',
				't_created',
				't_modified',
				'u_created',
				'u_modified');

	function _dumpItem($block)
	{
		if($block->getDisabled())
			return array();
		
		$data = array();

		// Get item block structure
		$res = $this->db->query("DESCRIBE ".DB_PREFIX."item_blocks");
		$fields = array();
		while($column = $res->fetchRow()) $fields[] = $column["Field"];
		$fieldList = "";
		$i = 0;
		foreach($fields as $field)
		{
			if(!in_array($field, $this->itemExcludeFields)) 
			{
				$fieldList .= $field;
				if($i < count($fields) - 1) $fieldList .= ",";
			}
		}
		$fieldList = rtrim($fieldList, ",");
		$res = $this->db->query("SELECT ".$fieldList." FROM ".DB_PREFIX."item_blocks WHERE id=?", array($block->getId()));
		$blockData = $res->fetchRow();
		foreach($blockData as $field => $value)
		{
			$data[$field] = $value;
		}

		$data["type"] = "items";
		$data["title"] = $block->getTitle();
		$data["id"] = $block->getId();
		$data["description"] = $block->getDescription();
		$data["max time"] = $block->getMaxTime();
		$data["introduction"] = $this->_replaceMedia($block->getIntroduction());
		$data["intro label"] = $block->getIntroLabel();
		$data["hidden intro"] = $block->isHiddenIntro() ? 1 : 0;
		$data["intro firstonly"] = $block->isIntroFirstOnly() ? 1 : 0;
		$data["intro pos"] = $block->getIntroPos() ? 0 : 1;
		
		if ($block->isIRTBlock()) $data["irt"] = $block->isIRTBlock();
		if ($block->isAdaptiveItemBlock())
		{
			$data["adaptive"] = $block->isAdaptiveItemBlock();
			$data["max items"] = $block->getMaxItems();
			$data["max sem"] = $block->getMaxSem();
		}

		$itemFields = array(
			'getDefaultMinItemTime' => 'default min time',
			'getDefaultMaxItemTime' => 'default max time',
			'isDefaultItemForced' => 'answer required',
			'getDefaultTemplateCols' => 'default columns',
			'getDefaultTemplateAlign' => 'default align',
			'getItemsPerPage' => 'items per page',
		);
		foreach ($itemFields as $meth => $fieldName) {
			$val = $block->$meth();
			if (!$val) continue;
			$data[$fieldName] = $val;
		}
		/*if (isset($data['default align'])) {
			if ($data['default align'] == 'h') {
				$data['default align'] = 'horizontal';
			} else {
				$data['default align'] = 'vertical';
			}
		}*/

		$tmpl = $block->getDefaultItemType();
		if ($tmpl) {
			$data['default type'] = $tmpl;
		}

		$answer_func = create_function('$x', 'return $x->getAnswer();');
		$answers = array_map($answer_func, $block->getDefaultAnswers());
		if (count($answers) > 0) $data["default answers"] = $answers;

		$this->_dumpMedia($block, $data);

		$data["items"] = array();
		$children = $block->getTreeChildren();
		foreach ($children as $child)
		{
			$quest = array(
				"id" => "item" . $child->getId(),
				"title" => $child->getTitle(),
				"text" => $this->_replaceMedia($child->getQuestion()),
				"answer required" => $child->isForced()
			);

			$itemFields = array(
				'getMinTime' => 'min time',
				'getMaxTime' => 'max time',
				'getTemplateCols' => 'columns',
				'getTemplateAlign' => 'align',
				'getDifficulty' => 'difficulty',
				'getDiscrimination' => 'discrimination',
				'getGuessing' => 'guessing',
				'getNeedsAllConditions'	=> 'conditions need all',
			);
			foreach ($itemFields as $meth => $fieldName) {
				$val = $child->$meth();
				if (!$val) continue;
				$quest[$fieldName] = $val;
			}
			if (isset($data['align'])) {
				if ($data['align'] == 'h') {
					$data['align'] = 'horizontal';
				} else {
					$data['align'] = 'vertical';
				}
			}
			
			$quest["conditions"] = array();

			foreach ($child->getConditions() as $cond) {
				$cond_item = Item::getItem($cond['item_id']); //base item enabled?
				if(!$cond_item->getDisabled())
					$quest["conditions"][] = array(
						'block' => $cond['item_block_id'],
						'item' => "item" . $cond['item_id'],
						'answer' => "answer" . $cond['answer_id'],
						'chosen' => $cond['chosen'],
					);
			}

			$type = $child->getType();
			if ($type) {
				$quest['type'] = $type;
			}
			
			if ($type == "MapItem") {
				$quest['locations'] = array();
				$locations = $child->getLocations();
				foreach ($locations as $location) {
					$location = get_object_vars($location);
					$quest['locations'][] = $location;
				}
			}

			$quest["answers"] = array();
			$answers = $child->getChildren();
			foreach ($answers as $answer)
			{
				$quest["answers"][] = array(
					"correct" => $answer->isCorrect(),
					"text" => $this->_replaceMedia($answer->getAnswer()),
					"id" => "answer" . $answer->getId(),
				);
			}

			$data["items"][] = $quest;
		}

		return $data;
	}

	function _dumpFeedback($block)
	{
		if($block->getDisabled())
			return array();
		
		$data = array();
		$data["type"] = "feedback";
		$data["title"] = $block->getTitle();
		$data["description"] = $block->getDescription();
		$data["source"] = $block->getSourceIds();
		$data["show in summary"] = $block->getShowInSummary() ? 1 : 0;

		$this->_dumpMedia($block, $data);

		// Dimensions
		$data["dimensions"] = array();
		$dims = DataObject::getBy('Dimension','getAllByBlockId',$block->getId());
		foreach ($dims as $dim)
		{
			$dimdat = array(
				"id" => $dim->get('id'),
				"title" => $dim->getTitle(),
				"description" => $dim->get('description'),
			);

			$dimdat["scores"] = array();
			$overview = $dim->getAnswerScores();
			unset($overview['max']);
			unset($overview['min']);
			foreach($overview as $quest)
			{
				foreach($quest["answers"] as $answId => $answDat)
				{
					//if ($answDat["score"] != 0)
						$dimdat["scores"]["answer" . $answId] = $answDat["score"];
				}
			}

			$dimdat["reference value"] = $dim->getReferenceValue();
			
			$dimdat["reference value type"] = $dim->getReferenceValueType();
			
			$dimdat["score type"] = $dim->getScoreType();
			
			
			$classes = $dim->getClassSizes();
			if (count($classes) > 0)
				$dimdat["classes"] = $classes;

			$data["dimensions"][] = $dimdat;
		}

		// Dimension groups
		$dimgroups = DataObject::getBy('DimensionGroup','getAllByBlockId',$block->getId());
		foreach ($dimgroups as $dimgroup) {
			$grpdat = array(
				"id" => $dimgroup->get('id'),
				"title" => $dimgroup->get('title'),
				"dimensions" => $dimgroup->get('dimension_ids'),
			);

			$data["dimension groups"][] = $grpdat;
		}

		$pages = array();
		$children = $block->getTreeChildren();
		foreach ($children as $child)
		{
			$page = array(
				"title" => $child->getTitle(),
				"paragraphs" => array(),
			);
			foreach ($child->getParagraphs() as $para)
			{
				$paraOut = array();
				$conditions = $para->getConditions();
				if (count($conditions) > 0)
				{
					$paraOut["conditions"] = array();
					foreach ($conditions as $dimId => $condDat)
					{
						$paraOut["conditions"][] = $condDat;
					}
				}

				$paraOut["text"] = $this->_replaceMedia($para->getContents());
				$page["paragraphs"][] = $paraOut;
			}
			$pages[] = $page;
		}
		$data["pages"] = $pages;

		return $data;
	}

	/**
	 * Get media files for a block and append them to the export data array
	 *
	 * @param mixed Current block
	 * @param mixed[] Reference to export array for current block
	 */
	function _dumpMedia($block, &$blockData)
	{
		$media = $this->mediaRepository->listMedia($block->getMediaConnectId());
		if ($media)
		{
			$data = array();
			foreach ($media as $medium)
			{
				$id = $this->_getMediaId($medium);
				if ($id) {
					$data[$id] = $id;
				}
			}
			$blockData["media files"] = $data;
		}
	}

	/**
	 * Build an unique name for a media file
	 *
	 * @param string Current name of the media file
	 *
	 * @return string New name of media file
	 */
	function _getMediaId($medium)
	{
		$fh = new FileHandling();
		$path = $fh->getMediaPath($medium->getId());

		if (!file_exists($path)) {
			$this->missingMedia[] = $medium->getFilename();
			return FALSE;
		}
		$mediaFile = $medium->getFilename();

		if (!array_key_exists($mediaFile, $this->mediaFiles))
		{
			$newname = preg_replace('/^\d+_/', '', $mediaFile);
			$newbase = explode(".", $newname);
			$ext = array_pop($newbase);
			$newbase = implode(".", $newbase);
			$i = 1;
			while (in_array($newname, $this->mediaFiles))
			{
				$newname = $newbase . "_" . $i++ . "." . $ext;
			}
			$this->mediaFiles[$mediaFile] = $newname;
			$this->mediaIds[$medium->getId()] = $newname;
			$this->mediaPath[$newname] = $medium->getFilePath()."/".$medium->getFilename();
		}
		return $this->mediaFiles[$mediaFile];
	}

	function _replaceMedia($text)
	{
		foreach($this->mediaPath as $id => $orgName)
		{

			$text = preg_replace("|((http://". preg_quote($_SERVER['HTTP_HOST']) .')?'. preg_quote(dirname($_SERVER['SCRIPT_NAME'])) .
				"/)?upload/media/$orgName|", "{medium:$id}", $text);
		}
		return $text;
	}

}
