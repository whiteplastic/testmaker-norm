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
 * Loads the base class
 */
require_once(PORTAL.'BlockPage.php');

/**
 * Loads the Dimension type
 */
require_once(CORE.'types/Dimension.php');

/**
 * Loads the ItemBlock type
 */
require_once(CORE.'types/ItemBlock.php');

libLoad('oop::isSubclassOf');
libLoad('utilities::cutBlockFromString');
libLoad('utilities::getCorrectionMessage');


/**
 * Displays the relevant pages for feedback blocks
 *
 * Default action: {@link doEdit()}
 *
 * @package Portal
 */
class DimensionPage extends BlockPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "edit";
	var $dimId;

	function init($workingPath)
	{

		if (! $this->block) {
			$page = &$GLOBALS["PORTAL"]->loadPage('admin_start');
			$page->run();
			return FALSE;
		}

		if (!$this->block->isFeedbackBlock()) {
			$page = &$GLOBALS["PORTAL"]->loadPage('block_edit');
			$page->run();
			return FALSE;
		}

		$this->tpl->setGlobalVariable('working_path', $workingPath);

		$this->targetPage = 'dimension';
		return TRUE;
	}

	function initTemplate($activeTab)
	{
		$id = getpost('item_id');
		if ($id==-1)
			$id = $this->dimId;
		$tabs = array(
			"edit" => array("title" => "tabs.test.general", "link" => linkTo("dimension", array("action" => "edit",
				"item_id" => $id, "working_path" => $this->workingPath))
			),
			"settings" => array("title" => "tabs.test.delete.dimension.ref_value", "link" => linkTo("dimension", array("action" => "settings",
				"item_id" => $id, "working_path" => $this->workingPath))
			),
			"delete" => array("title" => "tabs.test.delete.dimension", "link" => linkTo("dimension", array("action" => "confirm_delete",
				"item_id" => $id, "working_path" => $this->workingPath))
			)
		);

		$disabledTabs = array();
		if (!$this->mayEdit) {
			$disabledTabs[] = 'settings';
			$disabledTabs[] = 'delete';
		}

		$this->initTabs($tabs, $activeTab, $disabledTabs);

		if($this->block->hasMultipleParents() && $activeTab != 'edit_perms' && $activeTab != 'copy_move') {
			$this->tpl->touchBlock("block_multi_linked");
			$parents = $this->getAllParents($this->block, array(), array());
			for($i = 0;$i < count($parents); $i++)
			{
				$this->tpl->setVariable('path', '_'.implode('_',$parents[$i]['path']).'_');
				$this->tpl->setVariable('title', implode('&gt;',$parents[$i]['titles']));
				$this->tpl->parse('linked_parent');
			}
		}
	}

	function setupAdminMenu()
	{
		if (!$this->mayCreate) return;

		// Add new dimension
		$this->tpl->setVariable('page', 'dimension');
		$this->tpl->setVariable('name', 'action');
		$this->tpl->setVariable('value', 'edit');
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('name', 'working_path');
		$this->tpl->setVariable('value', $this->workingPath);
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('name', 'item_id');
		$this->tpl->setVariable('value', '-1');
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('title', T('pages.feedback_block.add_dimension'));
		$this->tpl->parse('menu_admin_item');

		// Add new page
		$this->tpl->setVariable('page', 'feedback_block');
		$this->tpl->setVariable('name', 'action');
		$this->tpl->setVariable('value', 'create_feedback_page');
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('name', 'working_path');
		$this->tpl->setVariable('value', $this->workingPath);
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('title', T('pages.feedback_block.new_feedback_page'));
		$this->tpl->parse('menu_admin_item');
	}

	function doEdit()
	{
	
		$dimId = getpost('item_id');
		$workingPath = getpost('working_path');
		$editType = post('edit_type', false);

		if ($editType && $editType == 'mc') $type = 1;
		elseif ($editType && $editType == 'scale') $type = 2;
		elseif ($editType && $editType == 'custom') $type = 3;
		$db = &$GLOBALS['dao']->getConnection();
		$countDim = $db->getOne("SELECT COUNT(*) FROM ".DB_PREFIX."dimensions WHERE block_id = ?", array($this->block->getId()));
		$countDim = $countDim + 1;

		if ($dimId == -1) {
			$dimName = post('title', T('pages.feedback_block.unnamed_dimension'))." ".$countDim;
			$dimDesc = post('description', '');
			$dimId = Dimension::createNew($this->block->getId(), $dimName, $dimDesc);
			//$dimId = DataObject::create('Dimension',array('block_id'=>$this->block->getId(), 'name'=>$dimName, 'description'=>$dimDesc));
			$this->dimId = $dimId;
			$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_created', MSG_RESULT_POS,
			array('dim_name' => htmlentities($dimName)));
			redirectTo('dimension', array('action' => 'edit', 'working_path' => $workingPath, 'item_id' => $dimId ,'resume_messages' => 'true'));
		} 
		
		// Check if we're already past the first step
		if (post('submit_dim', false)) {
			// Don't allow if permission is missing
			$this->checkAllowed('edit', true);
			$checkValues = array(
				array('title', post('title'), array(CORRECTION_CRITERION_LENGTH_MIN => 1)),
			);
			$dimName = post('title', false);
			$dimDesc = post('description', false);

			$GLOBALS["MSG_HANDLER"]->flushMessages();
			for($i = 0; $i < count($checkValues); $i++) {
				$tmp = getCorrectionMessage(T('forms.'.$checkValues[$i][0]), $checkValues[$i][1], $checkValues[$i][2]);
				if(count($tmp) > 0) {
					$this->correctionMessages[$checkValues[$i][0]] = $tmp;
				}
			}

			if (count($this->correctionMessages) > 0) {
				$dimName = post('title');
				$dimDesc = post('description');
			} 
			else {
				// Create/modify dimension
				$postedItems = post('items');
				if ($postedItems == NULL) {
					$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.no_item', MSG_RESULT_NEG);
					redirectTo('dimension', array('action' => 'edit', 'working_path' => $workingPath, 'item_id' => $dimId ,'resume_messages' => 'true'));
					
				}	
				$dim = DataObject::getById('Dimension',$dimId);		
				$dim->set('score_type', $type);
				$dim->commit();
				$dim->updateInfo($dimName, $dimDesc);
				
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_saved', MSG_RESULT_POS,
				array('dim_name' => htmlentities($dimName)));

				$_GET['item_id'] = $dimId;
				$scores = $dim->getAnswerScores($postedItems);

				return $this->run('scores');
			}
		}

		if ($dimId == -1) {
			$dimName = post('title', T('pages.feedback_block.unnamed_dimension'));
			$dimDesc = post('description', '');
		} else {
			$dim = DataObject::getById('Dimension', $dimId);
			if (!post('submit_dim', false)) {
				$dim_data = $dim->getAnswerScores();
				$dimName = $dim->get('name');
				$dimDesc = $dim->get('description');
			}
		}

		// Construct list of items
		$qs_data = array();
		foreach ($this->block->getSourceIds() as $id) {
			$qb = $GLOBALS["BLOCK_LIST"]->getBlockById($id, BLOCK_TYPE_ITEM);

			// Filter out adaptive blocks
			if ($qb->isAdaptiveItemBlock()) continue;

			$qs_data = array_merge($qs_data, $qb->getTreeChildren());
		}
		$items = false;
		$postedItems = post('items');

		if (count($qs_data) > 0) {
			$items = array();
			$itemIds = array();
			
			foreach ($qs_data as $q) {
			
				$itemDisabled = '';
				
				if ($q->getDisabled())
					$itemDisabled = 'style="display: none;';
						
				// Filter out items that have no answers
				$answers = $q->getChildren();
				if (count($answers) == 0 && !$q->hasCustomScoring()) 
					continue;
					
				$isChecked = (isset($dim_data) && isset($dim_data[$q->getId()])) || (isset($postedItems[$q->getId()])) ? ' checked="checked"' : '';
				$title = $q->getTitle();
				
				if ($q->hasCustomScoring()) 
					$title .= ' '. T('pages.feedback_block.dimension.score_ignored');
					
				$items[] = array(
					array('item_id', $q->getId()),
					array('item_checked', $isChecked),
					array('item_title', $q->getTitle()),
					array('item_disabled', $itemDisabled),
				);
				$itemIds[] = $q->getId();
			}
		}

		// By default, select none of the editors
		$checkMc = '';
		$checkScale = '';
		$checkCustom = '';
		$curMin = '';
		$curMax = '';
		$curRef = '';
		
		// For existing dimensions, try to detect which editor to use
		if (isset($dim) && isset($itemIds)) {
			$scores = $dim->getAnswerScores($itemIds);
			$curMin = $scores['min'];	
			$curMax = $scores['max'];		
			$curStdDev = intval($dim->get('std_dev'));
			$curRefValue = intval($dim->get('reference_value'));		
			$curRef = $curRefValue;		
						
			// add % to the reference value if needed
			$referenceValueType = $dim->get('reference_value_type');
			$curRef .= ($referenceValueType == 0) ? '' : ' %';
			
			
			// don't show ref value = 0, tell user that there is no ref value
			if ($curRefValue == 0) {
				$curRef = T('pages.feedback_block.dimension.no_ref_value_given');
			} else {
				$curRef = T('pages.feedback_block.dimension.ref_value').$curRef;
			}			
			
			$mcCount = 1;
			$scaleCount = 1;
			$totalCount = count($scores);
			if (isset($scores['max'])) $totalCount--;

			foreach ($scores as $q) {
				$answerCount = count($q['answers']);
				if ($answerCount == 0) continue;

				// Check for scale
				$dir = 0;
				$index = 1;
				foreach ($q['answers'] as $a) {
					if ($dir == 0) {
						if ($a['score'] == 1) {
							$dir = 1;
						} elseif ($a['score'] == $answerCount) {
							$dir = -1;
						} else {
							break;
						}
						$index++;
						continue;
					}

					if ($dir == 1) {
						$expected = $index;
					} else {
						$expected = $answerCount - $index + 1;
					}

					if ($expected == $a['score']) {
						$index++;
						continue;
					}

					break;
				}
				if ($index > $answerCount) $scaleCount++;

				// Check for MC item
				$zeroCount = 0;
				$oneCount = 0;
				foreach ($q['answers'] as $a) {
					if ($a['score'] == 0) {
						$zeroCount++;
					} elseif ($a['score'] == 1) {
						$oneCount++;
					} else {
						break;
					}
				}
				if ($zeroCount + $oneCount == $answerCount) {
					$mcCount++;
				}
				// Since there may be new items in there, we allow for all zero scale items.
				if ($zeroCount == $answerCount) {
					$scaleCount++;
				}
			}

			$scoreType = $dim->get('score_type');

			// Check the right radio buttons, use information from either the form of the database
			if ((!$editType && $mcCount == $totalCount) || $editType == 'mc' || $scoreType == SCORE_TYPE_MC) {
				$checkMc = 'checked="checked" ';
			} elseif ((!$editType && $scaleCount == $totalCount) || $editType == 'scale' || $scoreType == SCORE_TYPE_SCALE) {
				$checkScale = 'checked="checked" ';
			} else {
				$checkCustom = 'checked="checked" ';
			}
			if ($curMin == $curMax && !empty($curMin))
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.max_is_min', MSG_RESULT_NEG); 
		}
		
		$this->tpl->loadTemplateFile('EditDimension.html');
		foreach ($this->correctionMessages as $key => $value) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}
		$this->initTemplate('edit');
		$body = $this->renderTemplateCore(
			array(
				'id' => $dimId,
				'title' => $dimName,
				'description' => $dimDesc,
				'working_path' => $workingPath,
				'items' => $items,
				'q_options' => is_array($items),
				'no_items' => !is_array($items),
				'check_mc' => $checkMc,
				'check_scale' => $checkScale,
				'check_custom' => $checkCustom,
				'submit_button' => $this->mayEdit,
				'no_submit_button' => !$this->mayEdit,
				'curMin' => $curMin,
				'curMax' => $curMax,
				'curRef' => $curRef,
				'curStdDev' => $curStdDev,
			), true);
			
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.feedback_block.dimension').": '".$dimName."'");

		$this->tpl->show();
	}

	function doSettings()
	{
		
		$dimId = getpost('item_id');
		$workingPath = getpost('working_path');

		$dim = DataObject::getById('Dimension',$dimId);

		// Class sizes have been set / altered
		if (post('submit_class_sizes', false)) {
			// Don't allow if permission is missing
			$this->checkAllowed('edit', true);

			$sizes = post('class_sizes', '');

			// Parse
			$err = 0;
			$class_sizes = array();
			foreach (explode("\n", $sizes) as $class_size) {
				$str = trim($class_size);
				if (empty($str)) continue;
				if (!preg_match('/^(\d+):\s*(\d+)$/', $str, $matches)) {
					$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.dimension.msg.invalid_class_size', MSG_RESULT_NEG, array('line' => htmlentities($str)));
					$err = true;
				} else {
					$class_sizes[intval($matches[1])] = intval($matches[2]);
				}
			}
			if (!$err) {
				$dim->setClassSizes($class_sizes);
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.dimension.msg.sizes_updated', MSG_RESULT_POS);
			}
		} else {
			$class_sizes = $dim->getClassSizes();
		}

		// Reference value has been set / altered
		if (post('submit_reference_value', false)) {
			// Don't allow if permission is missing
			$this->checkAllowed('edit', true);
			$ref_value_type = post('reference_value_type', '');
			$ref_value_type = ($ref_value_type == 'percent') ? 1 : 0;
			$ref_value = post('reference_value', '');
			$std_dev = post('std_dev', '');
	
			$ref_value = intval($ref_value);	
			$std_dev = intval($std_dev);

			if ($ref_value < 0 || ($ref_value_type == 1 && $ref_value > 100) || !is_int($ref_value)) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.dimension.msg.reference_value_error', MSG_RESULT_NEG);
			} elseif ($dim->setReferenceValue($ref_value) && $dim->setReferenceValueType($ref_value_type) && $dim->setStdDev($std_dev)) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.dimension.msg.reference_value_updated', MSG_RESULT_POS);
			}
		} else {
			$ref_value = intval($dim->get('reference_value'));
			$std_dev = intval($dim->get('std_dev'));
			$ref_value_type = $dim->get('reference_value_type');
		}

		$ref_value_type_points = ($ref_value_type == 0) ? 'selected' : '';
		$ref_value_type_percent = ($ref_value_type == 1) ? 'selected' : '';

		$sizes = '';
		foreach ($class_sizes as $score => $size) {
			$sizes .= "$score: $size\n";
		}

		// beginning of added 05.01.11
		$qs_data = array();
		foreach ($this->block->getSourceIds() as $id) {
			$qb = $GLOBALS["BLOCK_LIST"]->getBlockById($id, BLOCK_TYPE_ITEM);

			// Filter out adaptive blocks
			if ($qb->isAdaptiveItemBlock()) continue;

			$qs_data = array_merge($qs_data, $qb->getTreeChildren());
		}
		if (count($qs_data) > 0) {
			$items = array();
			$itemIds = array();
			
			foreach ($qs_data as $q) {
			
				$itemDisabled = '';
				
				if ($q->getDisabled())
					$itemDisabled = 'style="display: none;';
						
				// Filter out items that have no answers
				$answers = $q->getChildren();
				if (count($answers) == 0 && !$q->hasCustomScoring()) 
					continue;
					
				$isChecked = (isset($dim_data) && isset($dim_data[$q->getId()])) || (isset($postedItems[$q->getId()])) ? ' checked="checked"' : '';
				$title = $q->getTitle();
				
				if ($q->hasCustomScoring()) 
					$title .= ' '. T('pages.feedback_block.dimension.score_ignored');
					
				$items[] = array(
					array('item_id', $q->getId()),
					array('item_checked', $isChecked),
					array('item_title', $q->getTitle()),
					array('item_disabled', $itemDisabled),
				);
				$itemIds[] = $q->getId();
			}
		}
		// end of added 05.01.11
			
		$scores = $dim->getAnswerScores($itemIds);		
		$curMin = $scores['min'];	// RD			
		$curMax = $scores['max'];	// RD			

		$this->tpl->setVariable('curMin', $curMin);
		$this->tpl->setVariable('curMax', $curMax);

		
		$this->tpl->loadTemplateFile('EditDimensionSettings.html');
		$this->initTemplate('settings');
		$body = $this->renderTemplateCore(
			array(
				'id' => $dimId,
				'title' => $dim->get('name'),
				'working_path' => $workingPath,
				'class_sizes' => $sizes,
				'reference_value' => $ref_value,
				'std_dev' => $std_dev,
				'reference_value_type_points' => $ref_value_type_points,
				'reference_value_type_percent' => $ref_value_type_percent,
				'submit_button' => $this->mayEdit,
				'no_submit_button' => !$this->mayEdit,
				'curMin' => $curMin,
				'curMax' => $curMax
			), true);
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.feedback_block.dimension').": '".$dim->get('name')."'");

		$this->tpl->show();
	}

	function doScores()
	{
		$dimId = getpost('item_id');
		$workingPath = getpost('working_path');
		$dim = DataObject::getById('Dimension',$dimId);
		
		// Construct contents for editor
		$type = post('edit_type');
		if ($type != 'mc' && $type != 'scale') {
			$type = 'custom';
		}
		
		$items = array_keys(post('items', array()));
		$answers = post('dim_answer', array());

		$scores = $dim->getAnswerScores($items);
		
		
		if (post('submit_dim_scores', false)) {
			// page 2 of 2 (scores)
			// check, if there are any points, because 0 points/no checks leads to incorrect feedback information
			if (min($answers) == 0 && max($answers) == 0) {
				// no valid information on points
				$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.scores_invalid', MSG_RESULT_NEG, array('dim_name' => htmlentities($dim->get('name'))));
			} else {
				if ($dim->setScores($answers)) {
					$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.scores_saved', MSG_RESULT_POS, array('dim_name' => htmlentities($dim->get('name'))));
					// store new valid points	
					$scores = $dim->getAnswerScores();
				}
			}
		} else {
			// page 1 of 2 (dimensions)
			// Permissions check
			$this->checkAllowed('edit', true);
		}			
		
		
		$curMin = $scores['min'];	// RD			
		$curMax = $scores['max'];	// RD			
		$curRefValue = intval($dim->get('reference_value'));		
		$curStdDev = intval($dim->get('std_dev'));
		$curRef = $curRefValue;	

		if ($curMin == $curMax)
			$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.max_is_min', MSG_RESULT_NEG);	
		
		// add % to the reference value if needed
		$referenceValueType = $dim->get('reference_value_type');
		$curRef .= ($referenceValueType == 0) ? '' : ' %';
		
/*		enable this, if you need 
 * 
 * 
		// don't show ref value = 0, tell user that there is no ref value
		if ($curRefValue == 0) {
			$curRef = T('pages.feedback_block.dimension.no_ref_value_given');
		} else {
			$curRef = T('pages.feedback_block.dimension.ref_value').$curRef;
		}
*/	
		
		// Output
		$this->tpl->loadTemplateFile('EditDimensionScores.html');
		$this->initTemplate('edit');
		
		foreach ($this->correctionMessages as $key => $value) {
			$GLOBALS["MSG_HANDLER"]->addMultipleFinishedMessages($value, MSG_RESULT_NEG);
			$this->tpl->touchBlock('correction_'.$key);
		}

		foreach ($items as $qid) {
			$this->tpl->setVariable('ql_id', $qid);
			$this->tpl->parse('item_list');
		}
		$this->tpl->setVariable('title', $dim->get('name'));
		$this->tpl->setVariable('working_path', $workingPath);
		$this->tpl->setVariable('edit_type', $type);
		$this->tpl->setVariable('id', $dimId);
		
		if (count($scores) == 0) {
			$this->tpl->touchBlock('no_items');
			return $this->tpl->get();
		}

		$tmpBlock = 0;
		$answers = post('dim_answer', array());
		foreach ($scores as $qid => $qinfo) {
			if (!is_numeric($qid)) continue;
			if ($tmpBlock != $qinfo['block_id'])
			{
				$itemBlock = $GLOBALS['BLOCK_LIST']->getBlockById($qinfo['block_id']);
				
				if ($tmpBlock)
				{
					$this->tpl->parse("dim_{$type}_block");
				}
				$this->tpl->setVariable('block_title', $itemBlock->getTitle());
				$tmpBlock = $qinfo['block_id'];
			}
			foreach ($qinfo['answers'] as $aid => $ainfo) {
				
				$this->tpl->setVariable('a_id', $aid);
				if (!$ainfo['text']) $ainfo['text'] = '<em>'. T('pages.feedback_block.dimension.no_answers') .'</em>';
				if (Item::getItem($qid)->hasCustomScoring() == TRUE) {
					$this->tpl->setVariable('disabled', 'disabled = "disabled"');
					$ainfo['score'] = 0;
				}

				$linebreaker = array("<p>", "<div>", "</p>", "</div>");
				$ainfo['text'] = str_replace($linebreaker, "", $ainfo['text']);
				
				$this->tpl->setVariable('a_title', $ainfo['text']);
				$this->tpl->setVariable('a_value', $ainfo['score']); 
				$this->tpl->setVariable('a_checked', ($ainfo['score'] > 0 ? ' checked="checked"' : ''));
				$this->tpl->parse("dim_{$type}_answer");
				
			}
			$this->tpl->setVariable('q_id', $qid);
			$qtitle = $qinfo['title'];
			if ($qinfo['item_type'] && preg_match('/^[a-z0-9_]+$/i', $qinfo['item_type'])) {
				require_once(ROOT.'upload/items/'. $qinfo['item_type'] .'.php');
				if (eval("return $qinfo[item_type]::hasCustomScoring();")) $qtitle .= ' <em>'. T('pages.feedback_block.dimension.score_ignored') .'</em>';
			}
			$this->tpl->setVariable('q_title', $qtitle);
			$this->tpl->setVariable('q_body', $qinfo['text']);
			$this->tpl->parse("dim_{$type}_item");
		}
		$this->tpl->parse("dim_{$type}_block");
		$this->tpl->touchBlock("dim_{$type}");
		if ($type != 'mc') $this->tpl->hideBlock("dim_mc");
		if ($type != 'scale') $this->tpl->hideBlock("dim_scale");
		if ($type != 'custom') $this->tpl->hideBlock("dim_custom");

		$edit1 = ($this->checkAllowed('edit') ? '' : 'no_');
		$this->tpl->touchBlock("{$edit1}submit_button");

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.block.feedback').": '".$this->block->getTitle()."'");

		$this->tpl->show();
	}

	function doConfirmDelete()
	{
		$workingPath = getpost('working_path');
		$id = getpost('item_id');
		
		if (! $this->init($workingPath, $id)) {
			return;
		}

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("DeleteDimension.html");
		$this->initTemplate("delete", $id);
		$this->tpl->setVariable('id', $id);

		$dim = DataObject::getById('Dimension',$id);
		$dimName = $dim->get('name');
		if (!isset($dimName)) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.id_gone', MSG_RESULT_NEG);
			redirectTo('feedback_block', array('working_path' => $workingPath));
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.feedback_block.dimension') .': '. $dim->get('name'));

		$this->tpl->show();
	}

	function _dimUsedInFeedback($dimId)
	{
		$counter = 0;
		$groupCounter = 0;
		$data = array('dimensions' => array($dimId => 1));

		// Get all conditions for this dimension's block. Yuck. :(
		$pages = $this->block->getTreeChildren();
		foreach ($pages as $page) {
			if ($page->getDisabled())
				continue;
			$paras = $page->getChildren();
			foreach ($paras as $para) {
				// Check conditions
				$conds = $para->getConditions();
				foreach ($conds as $cond) {
					$plugin = Plugin::load('extconds', $cond['type']);
					if (!$plugin->checkIds($cond, $data)) continue;
					$counter++;
				}

				// Check paragraph text using evil black magic
				if (true === FeedbackGenerator::expandText($para->getContents(), array($this, '_dimUsedInFeedbackText'), array($data))) $counter++;
			}
		}

		// Check if it shows up in a group.
		$groups = DataObject::getBy('DimensionGroup', 'getAllByBlockId', $this->block->getId());
		foreach ($groups as $group) {
			if (in_array($dimId, $group->get('dimension_ids'))) $groupCounter++;
		}
		return array($counter, $groupCounter);
	}

	function _dimUsedInFeedbackText($type, $params, $ids)
	{

		$plugin = Plugin::load('feedback', $type, array(NULL));
		if (!$plugin) 
			return '';
		if (!$plugin->checkIds($params, $ids)) 
			return '';
		return true;
	}

	function doDelete()
	{
		$dimId = getpost('item_id');
		$workingPath = getpost('working_path');

		$this->checkAllowed('edit', true);

		if (getpost('cancel_delete', false)) {
			redirectTo('dimension', array('action' => 'edit', 'item_id' => $dimId,'working_path' => $workingPath, 'resume_messages' => 'true'));
		}

		$dim = DataObject::getById('Dimension',$dimId);
		$dimName = htmlentities($dim->get('name'));

		// Before we proceed, check if this dimension is used anywhere
		list($counter, $groupCounter) = $this->_dimUsedInFeedback($dimId);
		if ($counter || $groupCounter) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_del_inuse', MSG_RESULT_NEG,
				array('dim_name' => $dimName, 'dim_count' => $counter, 'dimgrp_count' => $groupCounter));
		// delete
		} elseif ($dim->delete()) {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_deleted', MSG_RESULT_POS,
				array('dim_name' => $dimName));
		} else {
			$GLOBALS['MSG_HANDLER']->addMsg('pages.feedback_block.msg.dim_del_error', MSG_RESULT_NEG,
				array('dim_name' => $dimName));

		}

		redirectTo('feedback_block', array('action' => 'edit', 'working_path' => $workingPath, 'resume_messages' => 'true'));
	}

}

?>
