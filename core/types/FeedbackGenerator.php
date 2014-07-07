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

/**#@+
 * Load all the feedback stuff
 */
require_once(CORE.'types/Dimension.php');
require_once(CORE.'types/GivenAnswerSet.php');
require_once(CORE.'types/FeedbackPage.php');
/**#@-*/

libLoad('utilities::snakeToCamel');

/**
 * A generator for feedback pages. Insert a feedback page and a handful of
 * test run objects and it will see to selecting the appropriate paragraphs.
 *
 * @package Core
 */
class FeedbackGenerator
{
	/**
	 * Creates a feedback generator.
	 *
	 * @param FeedbackBlock The block to collect feedback for
	 * @param mixed The {@link TestRun} object to initialize the generator
	 *   with, or an array of several TestRun objects.
	 */
	function FeedbackGenerator(&$block, &$testRun)
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		$this->blockId = $block->getId();
		$this->dimensions = array();
		$this->dimClasses = array();
		$this->dimScores = array();
		$this->dimMax = array();
		$this->dimMin = array();
		$this->dimResults = array();
		$this->blocks = array();
		$this->itemsExist = array();
		$this->itemsShown = array();
		$this->itemsAnswered = array();
		$this->finishTime = NULL;
		$this->givenAnswers = array();
		$this->givenAnswerSet = array();
		$this->testRuns = array();
		$this->uniqIdCounter = 0;

		// Fetch dimensions
		foreach (DataObject::getBy('Dimension','getAllByBlockId',$block->getId()) as $dim) {
			$dimId = $dim->get('id');
			$this->dimensions[$dimId] = $dim;
			$this->dimScores[$dimId] = $dim->getAnswerScores();
			$this->dimClasses[$dimId] = $dim->getClassSizes();
			$this->dimResults[$dimId] = 0;
		}
		if ($testRun !== NULL) {
			if (is_array($testRun)) {
				foreach ($testRun as $run) $this->addTestRun($run);
			} else {
				$this->addTestRun($testRun);
			}
		}
	}

	/**
	 * Adds data from another test run to the feedback generator.
	 * @param TestRun The test run to pull data from.
	 */
	function addTestRun(&$testRun)
	{
		$this->testRuns[] = &$testRun;

		// Fetch given answers in a horribly kludgy loop
		foreach ($testRun->getGivenAnswerSets() as $givenAnswerSet)
		{
			$this->finishTime = $givenAnswerSet->getFinishTime();
			if ($this->finishTime == NULL)
				$this->finishTime = time();
			$qId = $givenAnswerSet->getItemId();
			$blockId = $givenAnswerSet->getBlockId();
			if (!array_key_exists($blockId, $this->blocks)) {
				if (!$GLOBALS["BLOCK_LIST"]->existsBlock($blockId)) {
					$this->blocks[$blockId] = NULL;
				} else {
					$this->blocks[$blockId] = $GLOBALS["BLOCK_LIST"]->getBlockById($blockId);
				}
			}
			if ($this->blocks[$blockId] === NULL) continue;

			$block = $this->blocks[$blockId];
			if ($block->isItemBlock()) {
				$this->givenAnswersSets[$qId] = $givenAnswerSet;
				$this->givenAnswers[$qId] = $givenAnswerSet->getAnswers();

				if ($block->isAdaptiveItemBlock() || $block->isIRTBlock()) {
					$this->dimResults[-($blockId)] = doubleval($givenAnswerSet->getTheta());
				}
			}
		}
	}

	/**
	 * Sets the feedback page to work on. This removes all previously computed
	 * paragraphs.
	 * @param FeedbackPage
	 */
	function setPage(&$page)
	{
		$this->feedbackPage = &$page;
		unset($this->paragraphs);
	}

	/**
	 * Returns the feedback page currently being processed.
	 */
	function getPage()
	{
		return $this->feedbackPage;
	}
	
	/**
	* Returns the feedback block currently being processed.
	*/
	function getBlockId() {
		return $this->blockId;
	}

	/**
	 * Computes the set of paragraphs that are to be displayed.
	 */
	function filterParagraphs()
	{
		// Calculate answer scores
		
		foreach ($this->dimScores as $dimId => $scores) {

			$scoreTotal = 0;
			$totalResult = 0;
			$mapItem = false;
			$itemsExist = 0;
			$itemsShown = 0;
			$itemsAnswered = 0;
		
			foreach ($scores as $qId => $qInfo) {	
			
				if (is_numeric($qId)) {
					$itemsExist++;
					//Count the shown items. When answer is given and answer time is > 0
					if (isset($this->givenAnswersSets[$qId])) {
						$clientTime = $this->givenAnswersSets[$qId]->getClientDuration();
						if ($clientTime > 0)
							$itemsShown++;
					}
				}
				//Check if the item was linked and take these answers if no answers exist in the current testrun.
				if (is_numeric($qId) && (!isset($this->givenAnswers[$qId]))) {
					$item = Item::getItem($qId);
					
					$tempTestRun = $this->getTestRun();
					$testId = $tempTestRun->getTestId();
					// let the test run know that the block was skipped
					$block = $GLOBALS['BLOCK_LIST']->getBlockById($qInfo["block_id"]);
					//get all Ids of the testruns in that the user have alredy answered this block 
					$TestRundIds = $tempTestRun->testRunList->getSourceRunsForSkipping($GLOBALS["PORTAL"]->getUserId(), $block, $testId);
					
					if (!empty($TestRundIds)) {
						//get the latest test run id
						sort($TestRundIds, SORT_NUMERIC);
						$LastTestRunId = end($TestRundIds);
					
						//get the latest testrun where the item was answered and take this answer
						$LatestTestRun = $tempTestRun->testRunList->getTestRunById($LastTestRunId);
						$givenAnswerSet = $LatestTestRun->getGivenAnswerSetByItemId($item->getId());
						$this->givenAnswers[$qId] = $givenAnswerSet->getAnswers();
					}
				}
				// We don't want the 'max' entry here
				if ( is_numeric($qId) and isset($this->givenAnswers[$qId])) 
				{
					foreach ($this->givenAnswers[$qId] as $key => $answer) {			
						$itemsAnswered++;
						
						// Calculate score for this dimension
						$item = Item::getItem($qId);

						if (is_a($item, "MapItem")) $mapItem = true;
						if ($item->hasCustomScoring() && (!$mapItem)) {
							$score = $item->getCustomScore($this->givenAnswersSets[$qId]);
							$this->dimResults[$dimId] += $score ;
							$scores['max'] += $item->getMaxScore($this->givenAnswersSets[$qId]);
							continue;
						}
						if ($item->hasCustomScoring() && ($mapItem)) {
							$score = $item->getCustomScore($this->givenAnswersSets[$qId]);
							$scores['max'] = $item->getMaxScore($this->givenAnswersSets[$qId]);
							continue;
						}
						$aId = $key;
						
						// Handle items that allow multiple answers
						if ($aId != 0 && !$answer) continue;
						$score = 0;
						if (!function_exists('formatAnswer')) {
						function formatAnswer ($string) {
							$umlautArray = Array('/Ä/','/Ö/','/Ü/','/ä/','/ö/','/ü/','/ß/','/&nbsp;/');
							$replaceArray = Array('a','o','u','a','o','u','ss','');
							return preg_replace($umlautArray, $replaceArray, strtolower(trim(html_entity_decode(strip_tags($string)))));
						}
						}
						// Text-based items need a little more madness.
						if ($aId == 0) {
							foreach ($qInfo['answers'] as $answerId => $premadeAnswer) {
								if (formatAnswer($premadeAnswer['text']) == formatAnswer($answer)) {
									$score = $premadeAnswer['score'];
									break;
								}
							}
						} else {
							if (!isset($qInfo['answers'][$aId])) {
								$score = 0;
							} else {
								$score = $qInfo['answers'][$aId]['score'];
							}
						}
						$this->dimResults[$dimId] += $score;
					}
				}
				if ($mapItem) {
					$totalResult += $score;
					$scoreTotal +=  $scores['max']; 
				}
			}
			if (!$mapItem) {
				$this->dimMax[$dimId] = $scores['max'];
				$this->dimMin[$dimId] = $scores['min'];
			}
			else {
				$this->dimMax[$dimId] = $scoreTotal;
				$this->dimResults[$dimId] = $totalResult;
			}
			$this->itemsExist[$dimId] = $itemsExist;
			$this->itemsShown[$dimId] = $itemsShown;
			$this->itemsAnswered[$dimId] = $itemsAnswered;
		}
		
		$page = &$this->feedbackPage;
		$paras = $page->getParagraphs();

		if (!isset($this->paragraphs)) $this->paragraphs = array();
		foreach ($paras as $para) {
			$doShow = $para->checkConditions($this);
			if ($doShow) {
				$this->paragraphs[] = $para;
			}
		}

	}

	/**
	 * Returns an array of paragraphs that are to be displayed according to the
	 * given answers.
	 */
	function getParagraphs()
	{
		if (!isset($this->paragraphs)) $this->filterParagraphs();

		return $this->paragraphs;
	}

	/**
	 * Helper for returning values that may not exist
	 * @access private
	 */
	function _getIfExists($type, $id = NULL, $default = -1)
	{
		$arr = $this->$type;

		if ($id !== NULL) {
			if (!isset($arr[$id])) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.test.feedback.invalid_id', MSG_RESULT_NEG, array('id' => $id));
				return $default;
			}
			return $arr[$id];
		}
		return $arr;
	}

	/**
	 * Returns an associative array mapping dimension IDs to their associative
	 * array of class sizes. Given a dimension ID, returns the class sizes for
	 * that dimension.
	 */
	function getDimClasses($id = NULL)
	{
		return $this->_getIfExists('dimClasses', $id, array());
	}

	/**
	 * Returns an associative array mapping dimension IDs to the highest possible
	 * score that can be attained for them. Given a dimension ID, returns the
	 * highest possible score for that dimension.
	 */
	function getMaxScores($id = NULL)
	{
		return $this->_getIfExists('dimMax', $id);
	}
	
	function getMinScores($id = NULL)
	{
		return $this->_getIfExists('dimMin', $id);
	}

	/**
	 * Returns an associative array mapping dimension IDs to the scores attained
	 * in the associated test run. Given a dimension ID, returns the score
	 * attained for that dimension.
	 */
	function getScores($id = NULL)
	{
		return $this->_getIfExists('dimResults', $id);
	}

	/**
	 * Returns the first test run registered with this feedback generator.
	 */
	function getTestRun()
	{
		return $this->testRuns[0];
	}

	/**
	 * Returns all test runs registered with this feedback generator.
	 */
	function getTestRuns()
	{
		return $this->testRuns;
	}

	/**
	 * Checks if a given dimension ID is valid.
	 */
	function existsDim($id)
	{
		return isset($this->dimensions[$id]);
	}

	/**
	 * Determines a value to be used in calculations based on its coded form.
	 */
	function getValueByCode($code)
	{
		if (is_int($code)) return $this->getScores($code);

		$arr = explode(':', $code);
		if (count($arr) > 1 && $arr[1] == 'literal') return $arr[0];

		if (count($arr) == 1) return $this->getScores($arr[0]);

		switch ($arr[1]) {
			case 'max': 
				return $this->getMaxScores($arr[0]);
			break;
			case 'min':
				return $this->getMinScores($arr[0]);
			break;
			default: return 0;
		}
	}
	
	function getItemsExistByCode($code) {
		return $this->_getIfExists('itemsExist', $code);
	}
	
	function getItemsShownByCode($code) {
		return $this->_getIfExists('itemsShown', $code);
	}
	
	function getItemsAnsweredByCode($code) {
		return $this->_getIfExists('itemsAnswered', $code);
	}

	/**
	 * Generates a unique ID that can be used by dynamic feedback objects (especially images).
	 */
	function generateSessionKey()
	{
		if (!isset($_SESSION['swuid_sequence'])) {
			$_SESSION['swuid_sequence'] = 1;
			return 1;
		}
		return ++$_SESSION['swuid_sequence'];
	}

	/**
	 * Takes a paragraph of text and expands special sequences in it.
	 */
	function expandText($text, $callback = array(NULL, '_getOutput'), $args = array())
	{
		if (is_array($callback) && $callback[0] === NULL) $callback[0] = $this;
		$text = str_replace('{finish time}', '<feedback _fb_type="finish_time" />', $text);	
		// Handle new syntax
		if (preg_match_all('#<feedback\s+(.*?)\s*/\s*>#i', $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {

				$params = FeedbackGenerator::_parseTagAttrs($match[1], '_fb_');
				$output = call_user_func_array($callback, array_merge(array($params['type'], $params), $args));
				$text = preg_replace('/'. preg_quote($match[0], '/') .'/', $output, $text, 1);
			}
		}
		$attachments = array();
		// Handle old syntax (especially old commands)
		while (preg_match('/\{([\w&;=]+):(.*?)\}/', $text, $matches)) {	
			$type = $matches[1];

			$params = array();
			switch ($matches[1]) {
			case 'score':
				$type = 'value';
				$params = array(
					'mode' => 'sum',
					'ids' => $matches[2],
				);
				break;
			case 'max':
				$type = 'value';
				$params = array(
					'mode' => 'sum',
					'ids' => "$matches[2]:max",
				);
				break;
			case 'min':
				$type = 'value';
				$params = array(
					'mode' => 'sum',
					'ids' => "$matches[2]:min",
				);
				break;
			case 'percent':
				$type = 'value';
				$params = array(
					'mode' => 'ratio',
					'num' => $matches[2],
					'denom' => "$matches[2]:max",
					'percent' => 1,
				);
				break;
			case 'cpercent':
				$type = 'value';
				$nums = explode(':', $matches[2]);
				$params = array(
					'mode' => 'ratio',
					'num' => $nums[1],
					'denom' => "$nums[0]:literal",
					'percent' => 1,
				);
				break;
			case 'prn&lt;':
				$type = 'value_prn';
				$params = array(
					'mode' => 'lt',
					'id' => $matches[2],
				);
				break;
			case 'prn&lt;=':
				$type = 'value_prn';
				$params = array(
					'mode' => 'lte',
					'id' => $matches[2],
				);
				break;
			case 'prn=':
				$type = 'value_prn';
				$params = array(
					'mode' => 'eq',
					'id' => $matches[2],
				);
				break;
			case 'prn&gt;':
				$type = 'value_prn';
				$params = array(
					'mode' => 'gt',
					'id' => $matches[2],
				);
				break;
			case 'prn&gt;=':
				$type = 'value_prn';
				$params = array(
					'mode' => 'gte',
					'id' => $matches[2],
				);
				break;
			case 'feedback_mail':
				$type = 'feedback_mail';
				if (method_exists($this, 'getTestRun')) { 
					$testRun = $this->getTestRun();
					$params = array('testRundId' => $testRun->getId());
				}
				break;
			default:
				$type = $matches[1];
				// Need to unmangle quotes. Bah.
				$matches[2] = str_replace('&quot;', '"', $matches[2]);
				$params = FeedbackGenerator::_parseTagAttrs($matches[2]);
			}		
			$params['type'] = $type;
			$binary = (isset($args['request_binary']) && $type == 'graph');
			if (!$binary) {

				$output = call_user_func_array($callback, array_merge(array($type, $params, $args)));

				// Used for check-only filters. Evil business.
				if ($output === true) return true;

				$text = preg_replace('/\{([\w&;=]+):(.*?)\}/', $output, $text, 1);
			} else {

				$output = call_user_func_array($callback, array($type, $params, $args));
				
				$attachments[] = $output;
				$text = preg_replace('/\{([\w&;=]+):(.*?)\}/', '[[graph]]', $text, 1);

			}
		}
		if(count($attachments) > 0)
		{
			return array($text, $attachments);
		}

		return $text;
	}

	/**
	 * Parses the attributes part of an XML tag into an associative array.
	 * @param string Attribute string to parse.
	 * @param string Optional prefix to strip from attribute names.
	 * @access private
	 * @static
	 */
	function _parseTagAttrs($tagAttrs, $prefix = NULL)
	{
		$params = array();
		while ($tagAttrs) {
			// Make sure it's in key/value form
			$i = strpos($tagAttrs, '=');
			if ($i === FALSE) break;
			// Find key
			$key = substr($tagAttrs, 0, $i);
			if ($key === '' || !preg_match('/^[a-z_]+$/i', $key)) break;
			// Find value
			$i = strpos($tagAttrs, '"', $i);
			if ($i === FALSE || $i == (strlen($tagAttrs)-1)) break;
			$j = strpos($tagAttrs, '"', $i+1);
			if ($j === FALSE) break;
			$value = substr($tagAttrs, $i+1, $j-$i-1);
			// Add to list
			if ($prefix) $key = str_replace($prefix, '', $key);
			$params[strtolower($key)] = $value;
			// Strip string
			$tagAttrs = trim(substr($tagAttrs, $j+1));
		}
		return $params;
	}

	/**
	 * Given a template's parameters, runs its code and returns the result.
	 * @access private
	 */
	function _getOutput($type, $params, $args = NULL)
	{
		if (!Plugin::exists('feedback', $type)) return '???';
		$obj = Plugin::load('feedback', $type, array($this));
		$temp = $obj->getOutput($params, $args);
		return $temp;
	}

}
