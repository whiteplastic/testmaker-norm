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

// Load required libraries etc.
/**
 * Include PCLZip
 */
require_once(ROOT.'external/pclzip/pclzip.lib.php');
/**
 * Include FileHandling class
 */
require_once(CORE.'types/FileHandling.php');

libLoad('utilities::selectiveMerge');
libLoad('utilities::serializeToPlainText');
libLoad('utilities::snakeToCamel');

/**
 * Imports tests from data files or data/media archives.
 *
 * @package Core
 */
class TestImporter
{
	/**#@+
	 * @access private
	 */
	var $cntFeedbackBlocks = 0;
	var $cntFeedbackPages = 0;
	var $cntInfoBlocks = 0;
	var $cntInfoPages = 0;
	var $cntItemBlocks = 0;
	var $cntItems = 0;
	var $cntSubtests = 0;

	// Decidedly evil, this one.
	var $connMedia = NULL;

	var $data = NULL;
	var $fh = NULL;
	var $ids = array('item_blocks' => array(), 'item_answers' => array(), 'dimensions' => array(), 'dimension_groups' => array(), 'items' => array());
	var $media = array();
	var $tempFiles = array();
	var $zip = NULL;
	/**#@-*/

	/**
	 * Processes an uploaded file.
	 * @param string Filename on server
	 * @param string Original filename
	 */
	function processUpload($file, $orgFile)
	{
		$this->tempFiles[] = $file;

		// We have no documented way of detecting a ZIP file so we use
		// a filename-based heuristic
		if (preg_match('/\.zip$/i', $orgFile)) {
			return $this->processZip($file);
		}
		return $this->processPlain($file);
	}

	/**
	 * @access private
	 */
	function processZip($file)
	{
		$zip = new PclZip($file);
		$this->zip = &$zip;
		$data = $zip->extract(
			PCLZIP_OPT_BY_PREG, '/\.txt/i',
			PCLZIP_OPT_EXTRACT_AS_STRING);
		if ($data == 0) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.zip.error', MSG_RESULT_NEG, array('error' => $zip->errorInfo()));
			$this->cleanup();
			return false;
		}
		$this->data = &$data[0]['content'];

		// Construct list of media files (at least we'll hope they're media files)
		$data = $zip->listContent();
		if ($data == 0) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.zip.error', MSG_RESULT_NEG, array('error' => $zip->errorInfo()));
			$this->cleanup();
			return false;
		}
		$this->media = array();
		foreach ($data as $entry) {
			$fname = $entry['filename'];
			if (!preg_match('/\.(jpe?g|png|gif|swf|pdf)$/i', $fname)) {
				$this->media[$fname] = 0;
				continue;
			}
			$this->media[$fname] = 1;
		}

		return $this->processMain();
	}

	/**
	 * @access private
	 */
	function processPlain($file)
	{
		$this->data = implode('', file($file));
		return $this->processMain();
	}

	/**
	 * Processes a string of YAML.
	 * @param string YAML data.
	 */
	function processYaml($data)
	{
		$this->data = $data;
		return $this->processMain();
	}

	/**
	 * @access private
	 */
	function processMain()
	{
		$this->data = unserializeFromPlainText(str_replace("\015", '', str_replace("\t", '        ', $this->data)));
		if ($this->data == NULL) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.error', MSG_RESULT_NEG, array());
			return false;
		}
		$this->fh = new FileHandling();
		$this->itemConditions = array();
		$res = $this->processContainer($this->data, new RootBlock(), 2);

		if ($res) {
			// Check item display conditions here
			$this->_addItemConditions();	
			
			$test = $GLOBALS['BLOCK_LIST']->getBlockById($res);
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.success', MSG_RESULT_POS, array('title' => $test->getTitle()));
			return $res;
		}
		return false;
	}

	/**
	 * @access private
	 */
	function processContainer($data, $parent, $allowSub = 0)
	{
		$newTestTitle = ($allowSub >= 2 ? 'root_' : '');
		$mydata = selectiveMerge(array(
			'title' => T("pages.block.{$newTestTitle}container_block_new") .' '. $this->cntSubtests++,
		), $data, array(
			'title',
			'description',
			'show subtests' => 'show_subtests',
			'language',
			'progress bar' => 'progress_bar',
			'pause_button' => 'pause_button',
			'open date' => 'open_date',
			'close date' => 'close_date',
		));
		if (isset($mydata['show_subtests']) && !$mydata['show_subtests']) $mydata['show_subtests'] = 0;
		if (isset($mydata['progress_bar']) && !$mydata['progress_bar']) $mydata['progress_bar'] = 0;
		if (isset($mydata['pause_button']) && !$mydata['pause_button']) $mydata['pause_button'] = 0;

		$block = $parent->createChild(BLOCK_TYPE_CONTAINER, $mydata);

		if (isset($data['style'])) {
			$mydata = selectiveMerge(array(), $data['style'], array(
				'logo file' => 'logo',
				'logo align' => 'logo_align',
				'background' => 'background_color',
				'item stem background' => 'item_background_color',
				'answers background' => 'dist_background_color',
				'font' => 'font_family',
				'font size' => 'font_size',
				'font style' => 'font_style',
				'font weight' => 'font_weight',
				'font color' => 'color',
			));

			if (isset($mydata['logo'])) {
				$mediaId = $this->processMedia($mydata['logo'], NULL, $parent->getId());
				if (!$mediaId) return false;
				$mydata['logo'] = $mediaId;
			}
			
			// process page width settings
			$mydata['page_width'] = ($data['style']['wrapper width'] > 1) ? 1 : 0;
			
			// process item border settings
			$itemBorders[0] = $data['style']['question border'];
			$itemBorders[1] = $data['style']['answers border'];
			$itemBorders[2] = $data['style']['answer border'];
			$border = '';
			for ($i = 0; $i < 3; $i++) {
				if ($itemBorders[$i] != 0) $border .= '1';
				else $border .= '0';
			}
			$mydata['item_borders'] = bindec($border);
			$mydata['use_parent_style'] = 0;

			$block->setStyle($mydata);
		}

		// Process sub elements
		if ($allowSub && isset($data['contents']) && is_array($data['contents'])) {
			foreach ($data['contents'] as $sub) {				
				if($sub=="") continue;
				switch ($sub['type']) {
				case 'sub':
					if (!$this->processContainer($sub, $block, $allowSub - 1)) return false;
					break;
				case 'info':
					if (!$this->processInfo($sub, $block)) return false;
					break;
				case 'items':
					if (!$this->processItems($sub, $block)) return false;
					break;
				case 'feedback':
					$res = $this->processFeedback($sub, $block);
					$this->_clearIds('dimensions');
					$this->_clearIds('dimension_groups');
					if (!$res) return false;
					break;
				default:
					$title = $sub['title'];
					if (!$title) $title = '(?)';
					$GLOBALS['MSG_HANDLER']->addMsg('types.import.invalid_type', MSG_RESULT_NEG, array('type' => $sub['type'], 'title' => $title));
					return false;
				}
			}
		} else if (isset($data['contents']) && (! is_array($data['contents']))) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.warning', MSG_RESULT_NEG, array());
			return false;
		}

		return $block->getId();
	}

	/**
	 * @access private
	 */
	function processInfo($data, $parent)
	{
		$mydata = selectiveMerge(array(
			'title' => T('pages.block.info_block_new') .' '. ++$this->cntInfoBlocks,
		), $data, array(
			'title',
			'description',
		));

		$block = $parent->createChild(BLOCK_TYPE_INFO, $mydata);

		// Deal with media files

		$media = $this->_processBlockMedia($data, $block->getId());
		if ($media === false) return false;
		if (count($media) > 0) $block->modify(array('media_connect_id' => $this->mediaConnectId));

		if (isset($data['contents']) && is_array($data['contents'])) foreach ($data['contents'] as $page) {
			if (is_array($page)) {
				if (isset($page['title'])) {
					$mydata = array('title' => $page['title'], 'content' => $page['text']);
				} else {
					$mydata = array('content' => $page['text']);
				}
			} else {
				$mydata = array('content' => $page);
			}
			if (!isset($mydata['title'])) {
				$mydata['title'] = T('pages.info_page.new') .' '. ++$this->cntInfoPages;
			}
			$mydata['content'] = $this->_substituteMedia($mydata['content'], $media);
			$block->createTreeChild($mydata);
		}

		return true;
	}

	/**
	 * @access private
	 */
	function processItems($data, $parent)
	{
		// Backwards compatibility
		if (isset($data['default template']) && !isset($data['default type'])) {
			$data['default type'] = snakeToCamel($data['default template']) .'Item';
		}
		$mydata = selectiveMerge(array(
			'title' => T('pages.block.item_block_new') .' '. ++$this->cntItemBlocks,
		), $data, array(
			'title',
			'description',
			'intro label' => 'intro_label',
			'irt',
			'max time' => 'max_time',
			'max items' => 'max_items',
			'max sem' => 'max_sem',
			'default min time' => 'default_min_item_time',
			'default max time' => 'default_max_item_time',
			'answer required' => 'default_item_force',
			'default type' => 'default_item_type',
			'default columns' => 'default_template_cols',
			'items per page' => 'items_per_page',
		));
		/*if (isset($data['default align'])) {
			if ($data['default align'] == 'horizontal') {
				$mydata['default_template_align'] = 'h';
			} else {
				$mydata['default_template_align'] = 'v';
			}
		}*/
		if (isset($data['default_item_force']) && !$data['default_item_force']) $mydata['default_item_force'] = 0;
		if (isset($data['adaptive']) && $data['adaptive']) $mydata['type'] = 1;

		$block = $parent->createChild(BLOCK_TYPE_ITEM, $mydata);

		// Associate with textual ID (if any)
		if (isset($data['id'])) {
			$res = $this->_addId('item_blocks', $data['id'], $block);
			if (!$res) return false;
		}

		// Deal with media files
		$media = $this->_processBlockMedia($data, $block->getId());
		if ($media === false) return false;
		if (count($media) > 0) $block->modify(array('media_connect_id' => $this->mediaConnectId));

		if (isset($data['introduction'])) {
			$block->modify(array('introduction' => $this->_substituteMedia($data['introduction'], $media)));
		}
		if (isset($data['hidden intro'])) {
			$block->modify(array('hidden_intro' => $data['hidden intro']));
		}
		if (isset($data['intro firstonly'])) {
			$block->modify(array('intro_firstonly' => $data['intro firstonly']));
		}
		if (isset($data['intro pos'])) {
			$block->modify(array('intro_pos' => $data['intro pos']));
		}

		// Handle default answers
		if (isset($data['default answers']) && is_array($data['default answers'])) foreach ($data['default answers'] as $answer) {
			$block->createDefaultAnswer(NULL, array('answer' => $this->_substituteMedia($answer, $media)));
		}

		// Now for items
		if (isset($data['items']) && is_array($data['items'])) foreach ($data['items'] as $item) {
			if (!isset($item['text'])) {
				$dispTitle = (isset($item['title']) ? $item['title'] : $block->getTitle());
				$GLOBALS['MSG_HANDLER']->addMsg('types.import.missing_obj_text', MSG_RESULT_NEG, array('title' => $dispTitle));
				$this->cleanup();
				return false;
			}

			// Backwards compatibility
			if (isset($item['template']) && !isset($item['type'])) {
				$item['type'] = snakeToCamel($item['template']) .'Item';
			}
			$myitem = selectiveMerge(array(), $item, array(
				'title',
				'type',
				'text' => 'question',
				'align' => 'template_align',
				'columns' => 'template_cols',
				'min time' => 'min_time',
				'max time' => 'max_time',
				'difficulty',
				'discrimination',
				'guessing',
				'answer required' => 'answer_force',
				'conditions need all' => 'conditions_need_all',
			));
			$myitem['question'] = $this->_substituteMedia($myitem['question'], $media);
			if (isset($myitem['answer_force']) && !$myitem['answer_force']) $myitem['answer_force'] == 0;
			$itemObj = $block->createTreeChild($myitem, false);

			// Associate with textual ID (if any)
			if (isset($item['id'])) {
				$res = $this->_addId('items', $item['id'], $itemObj);
				if (!$res) return false;
			}

			// Answers for this item
			if (isset($item['answers']) && is_array($item['answers'])) foreach ($item['answers'] as $answer) {
				if (is_array($answer)) {
					$myanswer = array('answer' => $answer['text'], 'correct' => ((isset($answer['correct']) && $answer['correct']) ? 1 : 0));
				} else {
					$myanswer = array('answer' => $answer);
				}
				if (!isset($myanswer['answer'])) {
					$GLOBALS['MSG_HANDLER']->addMsg('types.import.missing_obj_text', MSG_RESULT_NEG, array('title' => $block->getTitle()));
					$this->cleanup();
					return false;
				}
				$myanswer['answer'] = $this->_substituteMedia($myanswer['answer'], $media);
				$ansObj = $itemObj->createChild($myanswer);

				// Associate
				if (is_array($answer) && isset($answer['id'])) {
					$res = $this->_addId('item_answers', $answer['id'], $ansObj);
					if (!$res) return false;
				}
			}
			// Conditions for this item
			if (isset($item['conditions']) && is_array($item['conditions'])) {
				$pos = 0;
				foreach($item['conditions'] as $cond) {
					$id = $itemObj->getId();
					if(!array_key_exists($id, $this->itemConditions)) $this->itemConditions[$id] = array();
					$this->itemConditions[$itemObj->getId()][] = array(
								'block' => $cond['block'],
								'item' => $cond['item'],
								'answer' => $cond['answer'],
								'chosen' => $cond['chosen'],
								'pos' => $pos++);
				}
			}
			// Handle Map Item
			if (isset($item['type']) && $item["type"] == "MapItem") {
				$itemObj->saveLocations($item['locations']);
			}
		}

		return true;
	}

	/**
	 * @access private
	 */
	function processFeedback($data, $parent)
	{
		$mydata = selectiveMerge(array(
			'title' => T('pages.block.feedback_block_new') .' '. ++$this->cntFeedbackBlocks,
		), $data, array(
			'title',
			'description',
		));

		$block = $parent->createChild(BLOCK_TYPE_FEEDBACK, $mydata);

		if(isset($data['show in summary']))
		{
			$block->setShowInSummary($data['show in summary']);
		}

		// Deal with media files
		$media = $this->_processBlockMedia($data, $block->getId());
		if ($media === false) return false;
		if (count($media) > 0) $block->modify(array('media_connect_id' => $this->mediaConnectId));

		// Source blocks
		if (isset($data['source']) && $data['source']) {
			if (is_array($data['source'])) {
				$sources = array();
				foreach ($data['source'] as $source) $sources[] = $source;
			} else {
				$sources = array($data['source']);
			}
			$sourceIds = array();
			foreach ($sources as $source) {
				$newId = $this->_getId('item_blocks', $source);
				if (!$newId) return false;
				$sourceIds[] = $newId;
			}
			$block->setSourceIds($sourceIds);
		}

		// Prepare for replacing dimension IDs, starting with theta dimensions
		foreach ($this->_getIds('item_blocks') as $fileId => $blockId) {
			$this->_addId('dimensions', '-'. $fileId, -$blockId);
		}

		// Dimensions
		if (isset($data['dimensions']) && is_array($data['dimensions'])) {
			$oldDimIds = array();
			$newDimIds = array();
			foreach ($data['dimensions'] as $dim) {
				$mydim = selectiveMerge(array(
					'title' => T('pages.feedback_block.unnamed_dimension'),
					'description' => '',
				), $dim, array(
					'title',
					'description',
				));
				// $dim['id'] is old dimension id. $dimId[$dim['id']] is new Dimesion id. Store the changes in an array by inde the old id.
				$dimId[$dim['id']] = Dimension::createNew($block->getId(), $mydim['title'], $mydim['description']);
				$dimObj = DataObject::getById('Dimension',$dimId[$dim['id']]);
				// Associate
				if (isset($dim['id'])) {
					if (!$this->_addId('dimensions', $dim['id'], $dimObj)) return false;
				}
				
				if(isset($dim['id']) && isset($dimId[$dim['id']])) {
					$oldDimIds[] = $dim['id'];
					$newDimIds[] = $dimId[$dim['id']];	
				}
				
				// Class sizes first (easier)
				if (isset($dim['classes']) && is_array($dim['classes'])) {
					$dimObj->setClassSizes($dim['classes']);
				}

				// Reference value
				if (isset($dim['reference value']))
					$dimObj->setReferenceValue($dim['reference value']);

				// Reference value Type
				if (isset($dim['reference value type']))
					$dimObj->setReferenceValueType($dim['reference value type']);
				
				// Score Type
				if (isset($dim['score type']))
					$dimObj->setScoreType($dim['score type']);
				
				$scores = array();
				if (isset($dim['scores']) && is_array($dim['scores'])) foreach ($dim['scores'] as $id => $score) {
					$newId = $this->_getId('item_answers', $id);
					if (!$newId) return false;
					$scores[$newId] = $score;
				}
				$dimObj->setScores($scores);
			}
		}

		// Dimension groups
		if (isset($data['dimension groups']) && is_array($data['dimension groups'])) {
			foreach ($data['dimension groups'] as $dimgroup) {
				if (!isset($dimgroup['title'])) $dimgroup['title'] = '???';
				$dims = array();
				foreach ($dimgroup['dimensions'] as $dim) {
					//Replace the old demension id's with the new dimesion id's
					$newId = $dimId[$dim]; 
					if (!$newId) return false;
					$dims[] = $newId;
				}
				$dimgroupObj = DataObject::create('DimensionGroup',
				array('block_id' => $block->getId(), 'title' => $dimgroup['title'], 'dimension_ids' => $dims) );
				$this->_addId('dimension_groups', $dimgroup['id'], $dimgroupObj);

				$oldDimGroupIds[] = $dimgroup['id'];
				$newDimGroupIds[] = $dimgroupObj->getID();	
			}
		}

		// Pages
		if (isset($data['pages']) && is_array($data['pages'])) foreach ($data['pages'] as $page) {
			$mypage = selectiveMerge(array(
				'title' => T('pages.feedback_page.new') .' '. ++$this->cntFeedbackPages,
			), $page, array(
				'title',
			));

			$pageObj = $block->createTreeChild($mypage);

			// Paragraphs
			if (isset($page['paragraphs']) && is_array($page['paragraphs'])) foreach ($page['paragraphs'] as $para) {
				if (is_array($para)) {
					$mypara = array('content' => $para['text']);
				} else {
					$mypara = array('content' => $para);
				}
				$mypara['content'] = $this->_substituteFeedback($this->_substituteMedia($mypara['content'], $media), $this->ids);
				
				// search for ="xy to replace IDs
				if (isset($data['dimensions']) && is_array($data['dimensions'])){
					foreach ($oldDimIds as &$value) $value = "=\"".$value;
					foreach ($newDimIds as &$value) $value = "=\"".$value;
					$mypara['content'] = str_replace($oldDimIds,$newDimIds,$mypara['content']);
				}
				
				if (isset($data['dimension groups']) && is_array($data['dimension groups'])) {
					foreach ($oldDimGroupIds as &$value) $value = "=\"".$value;
					foreach ($newDimGroupIds as &$value) $value = "=\"".$value;		
					$mypara['content'] = str_replace($oldDimGroupIds,$newDimGroupIds,$mypara['content']);
				}
							
				$paraObj = $pageObj->createChild($mypara);

				// Display conditions
				$conds = array();
				if (is_array($para) && isset($para['conditions']) && is_array($para['conditions'])) foreach ($para['conditions'] as $cond) {
					// Backwards compatibility (I hope)
					if (!isset($cond['type'])) {
						if (!isset($cond['min']) || !isset($cond['max']) || !isset($cond['id'])) return false;
						$condNew = array(
							'type'		=> 'interval',
							'min_value'	=> $cond['min'],
							'max_value'	=> $cond['max'],
							'dim_id'	=> $cond['id'],
						);
						$cond = $condNew;
					}

					if (!Plugin::exists('extconds', $cond['type'])) {
						$GLOBALS['MSG_HANDLER']->addMsg('types.import.missing_plugin.extconds', MSG_RESULT_NEG, array('name' => $cond['type']));
						continue;
					}
					$plugin = Plugin::load('extconds', $cond['type']);
					$conds[] = $plugin->modifyForCopy($cond, $this->ids);
				}
				$paraObj->setConditions($conds);
			}
		}

		return true;
	}

	/**
	 * Processes media file in ZIP archive
	 * @access private
	 */
	function processMedia($file, $mediaConnectId, $blockId)
	{
		
		if (!isset($this->media[$file])) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.nomedia', MSG_RESULT_NEG, array('file' => $file));
			$this->cleanup();
			return false;
		}
		if (!$this->media[$file]) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.invalid_media', MSG_RESULT_NEG, array('file' => $file));
			if(substr(strtolower(strchr($file, '.')), 1) != 'pdf') {
				$this->cleanup();
				return false;
			}
		}
		$zip = &$this->zip;

		$dir = ROOT.'upload/media/';
		$mediaConnectId = $this->fh->getMediaConnectId($mediaConnectId);
		$newFilename = $file;

		if (file_exists($dir.$mediaConnectId .'_'. $newFilename)) {
			// Divide file name into name and extension if possible, else take it as name with empty extension
			if (preg_match("#^(.*)(\\.[^\\.]+)$#", $newFilename, $match)) {
				$firstPart = $match[1];
				$secondPart = $match[2];
			} else {
				$firstPart = $newFilename;
				$secondPart = "";
			}

			// Find a unique filename by appending _2, _3 and so forth
			$i = 2;
			do {
				$newFilename = $firstPart."_".($i++).$secondPart;
			} while (file_exists($dir.$mediaConnectId .'_'. $newFilename));
		}
		$fullName = $dir.$mediaConnectId .'_'. $newFilename;

		$data = $zip->extract(
			PCLZIP_OPT_BY_NAME, $file,
			PCLZIP_OPT_EXTRACT_AS_STRING
		);
		if ($data == 0) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.zip.error', MSG_RESULT_NEG, array('error' => $zip->errorInfo()));
			$this->cleanup();
			return false;
		}
		
		$media = $this->fh->uploadMedia($newFilename, $mediaConnectId, false);
		$newFilename = $this->fh->getMediaPath($media['id'], $blockId, $mediaConnectId);//'upload/media/'.$mediaConnectId .'_'. $newFilename;
		$this->fh->newMediaDir($media['id'], $blockId, $mediaConnectId);
		
		$target = fopen($newFilename, "w+");
		if (!$target) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.cantwrite', MSG_RESULT_NEG, array('file' => $fullName));
			$this->cleanup();
			return false;
		}
		fwrite($target, $data[0]['content']);
		fclose($target);
		
		$this->connMedia = $this->fh->getMediaPath($media['id'], $blockId, $mediaConnectId, false);
		return current($media);
	}

	/**
	 * Removes all temporary files.
	 */
	function cleanup()
	{
		foreach ($this->tempFiles as $file) {
			unlink($file);
		}
	}

	/**
	 * @access private
	 */
	function _addId($type, $fileId, $obj)
	{
		if ($this->_hasId($type, $fileId)) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.duplicate_id', MSG_RESULT_NEG, array('id' => $fileId, 'type' => $type, 'title' => $obj->getTitle()));
			$this->cleanup();
			return false;
		}
		if (is_object($obj)) {
			$this->ids[$type][$fileId] = $obj->getId();
		} else {
			$this->ids[$type][$fileId] = $obj;
		}
		return true;
	}
	/**
	 * @access private
	 */
	function _getId($type, $fileId)
	{
		if (!$this->_hasId($type, $fileId)) {
			$GLOBALS['MSG_HANDLER']->addMsg('types.import.id_missing', MSG_RESULT_NEG, array('id' => $fileId, 'type' => $type));
			$this->cleanup();
			return false;
		}
		return $this->ids[$type][$fileId];
	}

	/**
	 * @access private
	 */
	function _hasId($type, $fileId)
	{
		return array_key_exists($fileId, $this->ids[$type]);
	}

	/**
	 * @access private
	 */
	function _getIds($type)
	{
		return $this->ids[$type];
	}

	/**
	 * @access private
	 */
	function _clearIds($type)
	{
		$this->ids[$type] = array();
	}

	/**
	 * @access private
	 */
	function _processBlockMedia($data, $blockId)
	{
		$media = array();
		$mcId = NULL;
		if (!isset($data['media files']) || !is_array($data['media files'])) return array();
		foreach ($data['media files'] as $id => $name) {
			$mcId = $this->processMedia($name, $mcId, $blockId);
			if (!$mcId) return false;

			$this->mediaConnectId = $mcId;
			$media[$id] = $this->connMedia;
		}
		return $media;
	}

	/**
	 * @access private
	 */
	function _substituteFeedback($text, $changedIds)
	{
		return FeedbackGenerator::expandText($text, array('FeedbackParagraph', '_modifyTagForCopy'), array($changedIds));
	}

	/**
	 * @access private
	 */
	function _substituteMedia($text, $media)
	{
		foreach ($media as $id => $file) {
			$text = str_replace("{medium:$id}", $file, $text);
		}
		return $text;
	}

	function _addItemConditions()
	{
		foreach($this->itemConditions as $itemId => $itemCond)
		{
			$itemObj = Item::getItem($itemId);
			$pos = 0;
			foreach($itemCond as $id => $cond)
			{
				$itemObj->addCondition(array(
					'item_block_id' => $this->_getId('item_blocks', $cond['block']),
					'item_id' => $this->_getId('items', $cond['item']),
					'answer_id' => $this->_getId('item_answers', $cond['answer']),
					'chosen' => $cond['chosen'],
				), $pos++);
			}
		}
	}
}
