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

define('SCORE_TYPE_MC', 1);
define('SCORE_TYPE_SCALE', 2);
define('SCORE_TYPE_CUSTOM', 3);

/**
 * A group of answers in a set of {@link ItemBlock}s that can be summed up for
 * generating feedback.
 * @package Core
 */
class Dimension extends DataObject
{

    static protected $table = 'dimensions';
	
	static protected $prototype = array (
		'name' => array(),
		'description' => NULL,
		'block_id' => NULL,
		'reference_value' => NULL,
		'reference_value_type' => NULL,
		// Standard Deviation
		'std_dev' => NULL,
		'score_type' => NULL,
	);
	
	static protected $retrievalQueries = array(
		'getAllByBlockId' => 'SELECT * FROM @T WHERE block_id = @{*} ORDER BY name',
	);
	
	static protected $manipulationQueries = array(
		'deleteById' => 'DELETE FROM @T WHERE id = @{*}',
		'deleteByBlockId' => 'DELETE FROM @T WHERE block_id = @{*}',
	);
	
	var $scores;


	/**
	 * Builds a nested array of questions and answers along with their current
	 * scores.
	 *
	 * @param array A list of item IDs that specifies to which items
	 * information should be obtained. Score information will be fetched as
	 * available per question.
	 *
	 * @return array An associative array that contains information about the
	 * items/answers/scores associated with this dimension. The array is
	 * structured like this:
	 *
	 * <pre>
	 * array(
	 *   item_id => array(
	 *     'text' => question,
	 *     'answers' => array(
	 *       answer_id => array(
	 *         'text' => answer,
	 *         'score' => score (0 if not yet set)
	 *       ),
	 *       [...]
	 *     )
	 *   ),
	 *   [...]
	 *   'max' => highest possible score (single-answer items)
	 * )
	 * </pre>
	 */
	function getAnswerScores($itemIds = array())
	{
		$ids = array();
		foreach ($itemIds as $q) {
			$ids[] = intval($q);
		}

		// Fetch answers we already have scores for
		$query = $this->db->getAll("SELECT *, qs.type AS item_type, qs.title AS item_title FROM ".DB_PREFIX."dimensions_connect AS dc,
			".DB_PREFIX."item_answers AS qa, ".DB_PREFIX."items AS qs,
			".DB_PREFIX."item_blocks AS qb
			WHERE dimension_id=? AND qa.id = dc.item_answer_id AND qs.id = qa.item_id
			AND qb.id = qs.block_id AND qb.type <> 1
			ORDER BY qs.block_id, qs.pos, qa.pos",
			array($this->get('id')));
		// Process results
		if ($this->db->isError($query)) {
			return array();
		}
		$s = array();
		$qId = -1;
		$max = 0;
		$min = 0;
		$lastItemId = 1;
		$answer = 0;
		foreach ($query as $answer) {
			// Skip those that aren't in the params list
			if (count($ids) != 0 && !in_array($answer['item_id'], $ids)) continue;

			// collect extreme values
			if ($answer['item_id'] != $qId) {
				// new question with new answers
				$qId = $answer['item_id'];
				
				// create new min/max for this qestion id
				// make first value the min and the max
				$s[$answer['item_id']]['min'] = (int)$answer['score'];
				$s[$answer['item_id']]['max'] = (int)$answer['score'];
				$s[$answer['item_id']]['sum'] = (int)$answer['score'];
			} else {
				// add score to sum (needed for items with multiple answers
				$s[$answer['item_id']]['sum'] += $answer['score'];
				if ((int)$answer['score'] > $s[$answer['item_id']]['max']) {
					// store new current max
					$s[$answer['item_id']]['max'] =(int) $answer['score'];
				}

				if	((int)$answer['score'] < $s[$answer['item_id']]['min'] ) {
					// store new current min
					$s[$answer['item_id']]['min'] = (int) $answer['score'];
				}
			}
			
			$s[$answer['item_id']]['title'] = $answer['item_title'];
			$s[$answer['item_id']]['block_id'] = $answer['block_id'];
			$s[$answer['item_id']]['text'] = $answer['question'];
			$s[$answer['item_id']]['item_type'] = $answer['item_type'];
			$s[$answer['item_id']]['answers'][$answer['item_answer_id']] = array(
				'text' => $answer['answer'],
				'score' => (int) $answer['score'],
			);
			
		}
		
		$min = 0;
		$max = 0;
		
		// now get min/max for all questions		
		foreach ($s as $value) {
			// sum min for every question to a total min
			$min += $value['min'];
			// sum max for every question to a total max
			if ($value['item_type']=="McmaItem") {
				// item allows multiple answers
				$max += $value['sum'];
			} else {
				// item allows just one answer
				$max += $value['max'];
			}
		}
		
		$s['max'] = (int) $max;
		$s['min'] = (int) $min;
		
		$lastItemId = $answer['item_id'];

		if (count($ids) == 0) return $s;

		// Fetch remaining answers
		$query = $this->db->getAll("SELECT *, qa.id AS item_answer_id, qs.type AS item_type, qs.title AS item_title
			FROM ".DB_PREFIX."item_answers AS qa,
			".DB_PREFIX."items AS qs, ".DB_PREFIX."item_blocks as qb
			WHERE qa.item_id = qs.id AND qs.id IN (". implode(', ', $ids) .")
			AND qb.id = qs.block_id AND qb.type <> 1
			ORDER BY qs.block_id, qs.pos, qa.pos");

		// Process results
		if ($this->db->isError($query)) {
			return array();
		}
		foreach ($query as $answer) {
			if (isset($s[$answer['item_id']]) && isset($s[$answer['item_id']]['answers'][$answer['item_answer_id']])) {
				continue;
			}
			if (!isset($s[$answer['item_id']])) {
				$s[$answer['item_id']]['title'] = $answer['item_title'];
				$s[$answer['item_id']]['block_id'] = $answer['block_id'];
				$s[$answer['item_id']]['text'] = $answer['question'];
				$s[$answer['item_id']]['item_type'] = $answer['item_type'];
			}
			$s[$answer['item_id']]['answers'][$answer['item_answer_id']] = array(
				'text' => $answer['answer'],
				'score' => $answer['correct'],
			);
		}
		return $s;
	}

	/**
	 * Returns a list of class sizes for this dimension (associative array
	 * mapping class scores to class sizes).
	 *
	 * @param integer The subject's score. If not given, gets all scores.
	 * @param boolean Whether to get the classes below that score (otherwise,
	 *   get those above that score).
	 * @param boolean Whether to include the class for the subject's score.
	 */
	function getClassSizes($score = null, $below = true, $includeEqual = true)
	{
		$query = "SELECT score, class_size FROM ".DB_PREFIX."dimension_class_sizes
			WHERE dimension_id = ?";
		$params = array($this->get('id'));

		if ($score !== null) {
			$query .= " AND score ";
			if ($below) {
				$query .= "<";
			} else {
				$query .= ">";
			}
			if ($includeEqual) $query .= "=";
			$query .= " ? ORDER BY score";
			if (!$below) $query .= " DESC";
			$params[] = $score;
		}

		$res = $this->db->getAll($query, $params);
		$sizes = array();
		foreach ($res as $row) {
			$sizes[$row['score']] = $row['class_size'];
		}
		return $sizes;
	}

	/**
	 * Sets group class sizes.
	 * @param array Associative array mapping scores to class sizes.
	 */
	function setClassSizes($sizes)
	{
		$res = $this->db->query("DELETE FROM ".DB_PREFIX."dimension_class_sizes WHERE dimension_id = ?", array($this->get('id')));
		if (PEAR::isError($res)) return false;

		foreach ($sizes as $score => $size) {
			$res = $this->db->query("INSERT INTO ".DB_PREFIX."dimension_class_sizes VALUES (?, ?, ?)", array($this->get('id'), $score, $size));
			if (PEAR::isError($res)) return false;
		}
		return true;
	}

	/**
	 * Sets reference value.
	 * @param int Reference value
	 */
	function setReferenceValue($value)
	{
		$res = $this->db->query("UPDATE ".DB_PREFIX."dimensions SET reference_value = ? WHERE id = ?", array($value, $this->get('id')));
		return (PEAR::isError($res)) ? false : true;
	}

	/**
	 * Sets dimension standard deviation.
	 * @param int Reference value
	 */
	function setStdDev($value)
	{
		$res = $this->db->query("UPDATE ".DB_PREFIX."dimensions SET std_dev = ? WHERE id = ?", array($value, $this->get('id')));
		return (PEAR::isError($res)) ? false : true;
	}



	/**
	 * Sets reference value type.
	 * Valid reference values are: 0 (=points), 1 (=percent)
	 * @param int Reference value type
	 */
	function setReferenceValueType($type)
	{
		$res = $this->db->query("UPDATE ".DB_PREFIX."dimensions SET reference_value_type = ? WHERE id = ?", array((int)$type, $this->get('id')));
		return (PEAR::isError($res)) ? false : true;
	}

	/**
	 * Sets score type type.
	 * Valid score types are: 1 (=mc), 2 (=scale), 3 (=custom)
	 * @param int score type
	 */
	function setScoreType($value)
	{
		$res = $this->db->query("UPDATE ".DB_PREFIX."dimensions SET score_type = ? WHERE id = ?", array((int)$value, $this->get('id')));
		return (PEAR::isError($res)) ? false : true;
	}
	
	
	/**
	 * Changes title and description of this dimension.
	 * @param string Title
	 * @param string Description
	 */
	function updateInfo($title, $description)
	{
		$res = $this->db->query("UPDATE ".DB_PREFIX."dimensions SET name=?, description=? WHERE id =? LIMIT 1",
			array($title, $description, $this->get('id')));
		return (PEAR::isError($res)) ? false : true;
	}

	/**
	 * Creates a copy of the current dimension
	 * @return integer ID of the dimension
	 * @param array Associative array that specifies where to store changed
	 *   IDs from item blocks and item answers for feedback blocks and
	 *   dimensions, in the following format:
	 *   <code>
	 *   array(
	 *     'blocks' => array(id1, id2, ...),
	 *     'item_answers' => array(id1, id2, ...)
	 *   )
	 *   </code>
	 */
	function copyDimension($parentId, &$changedIds)
	{
		$db = &$this->db;
		$query = 'SELECT * from '.DB_PREFIX.'dimensions WHERE id = ?';
		$result = $db->getRow($query, array($this->get('id')));

		if ($db->isError($result)) {
			return false;
		}

		$newDimId = $this->db->nextId(DB_PREFIX.'dimensions');
		$changedIds['dimensions'][$this->get('id')] = $newDimId;

		$rows = array();
		$values = array();
		for(reset($result); list($key, $value) = each($result);)
		{
			switch($key)
			{
				case 'id':
					$value = $newDimId;
					break;
				case 'block_id':
					if(isset($changedIds['blocks'][$parentId])) {
						$value = $changedIds['blocks'][$parentId];
					} else {
						$value = $parentId;
					}
					break;
			}
			$rows[] = $key;
			$values[] = $value;
		}
		$query = 'INSERT INTO '.DB_PREFIX.'dimensions ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';

		$result = $db->query($query, $values);
		if($db->isError($result)) {
			return false;
		}

		$query = 'SELECT * FROM '.DB_PREFIX.'dimensions_connect WHERE dimension_id = ?';
		$results = $db->getAll($query, array($this->get('id')));
		if($db->isError($results)) {
			return false;
		}
		foreach($results as $result) {
			$rows = array();
			$values = array();
			for(reset($result); list($key, $value) = each($result);)
			{
				switch($key)
				{
					case 'dimension_id':
						$value = $newDimId;
						break;
					case 'item_answer_id':
						if(isset($changedIds['item_answers'][$value])) {
							$value = $changedIds['item_answers'][$value];
						}
						break;
				}
				$rows[] = $key;
				$values[] = $value;
			}
			$query = 'INSERT INTO '.DB_PREFIX.'dimensions_connect ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';
			if ($db->isError($db->query($query, $values))) return false;
		}

		$query = 'SELECT * FROM '.DB_PREFIX.'dimension_class_sizes WHERE dimension_id = ?';
		$results = $db->getAll($query, array($this->get('id')));
		if($db->isError($results)) {
			return false;
		}
		foreach($results as $result) {
			$rows = array();
			$values = array();
			for(reset($result); list($key, $value) = each($result);)
			{
				switch($key)
				{
					case 'dimension_id':
						$value = $newDimId;
						break;
				}
				$rows[] = $key;
				$values[] = $value;
			}
			$query = 'INSERT INTO '.DB_PREFIX.'dimension_class_sizes ('.implode(', ', $rows).') VALUES ('.ltrim(str_repeat(', ?', count($values)), ', ').')';
			$db->query($query, $values);
		}

		return $newDimId;
	}

	/**
	 * Creates a new dimension.
	 * @static
	 * @return The id of the new dimension or false if creation failed.
	 */
	function createNew($blockId, $title, $description, $reference_value = NULL, $reference_value_type = NULL, $std_dev = NULL)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$id = $db->nextId(DB_PREFIX.'dimensions');
		$res = $db->query("INSERT INTO ".DB_PREFIX."dimensions VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
			array($id, $title, $description, $blockId, $reference_value, $reference_value_type, $std_dev = NULL, 0));

		if ($db->isError($res)) {
			return false;
		}
		return $id;
	}

	/**
	 * Returns the name of this dimension.
	 */
	function getTitle()
	{
		return $this->get('name');
	}
	/**
	*Use this function only until all other class have replace the function getId() by get('id')
	*/
	function getId()
	{
		return $this->get('id');
	}

	/**
	 * Returns the parent feedback block of the current dimension.
	 */
	function &getParent()
	{
		$parent = $GLOBALS["BLOCK_LIST"]->getBlockById($this->get('block_id'), BLOCK_TYPE_FEEDBACK);
		return $parent;
	}

	/**
	 * Checks if the parent feedback block of the current dimension has more
	 *   than one parent.
	 * @return boolean
	 */
	function hasMultipleParents()
	{
		$parent = $this->getParent();
		return $parent->hasMultipleParents();
	}

	/**
	 * Add a bunch of answer scores to this dimension. Deletes all existing
	 * scores for this dimension.
	 *
	 * @param array Associative array mapping answer IDs to scores.
	 */
	function setScores($scores)
	{
		// Clean up first
		$res = $this->db->query("DELETE FROM ".DB_PREFIX."dimensions_connect
			WHERE dimension_id = ?", array($this->get('id')));
		if ($this->db->isError($res)) {
			return false;
		}

		foreach ($scores as $aid => $score) {
			if (!is_numeric($score)) $score = 0;
			$res = $this->db->query("INSERT INTO ".DB_PREFIX."dimensions_connect
				VALUES (?, ?, ?)", array($this->get('id'), $aid, intval($score)));
			if ($this->db->isError($res)) {
				return false;
			}
		}
		unset($this->scores);
		return true;
	}

	/**
	 * Return the reference value of this dimension
	 *
	 * @return Reference value of dimension
	 */
	function getReferenceValue()
	{
		return $this->get('reference_value');
	}

       /**
Return the Standard Deviation
*/

	function getStdDev()
	{
		return $this->get('std_dev');
	}



	/**
	 * Return type of the reference value for this dimension
	 * 0: points, 1:percentage
	 * 
	 * @return bool: reference value type
	 */
	function getReferenceValueType()
	{
		return $this->get('reference_value_type');
	}
	
	/**
	 * Return score type of this dimension
	 * 
	 * @return integer scoretype 
	 */
	function getScoreType()
	{
		return $this->get('score_type');
	}
	
	/**
	 * Deletes this dimension from the database, along with all scores associated with it.
	 */
	function delete()
	{
		// Delete scores
		if (!$this->setScores(array())) return false;
		if (!$this->setClassSizes(array())) return false;
		$id = $this->get('id');
		
		// And now ourselves
		DataObject::query('Dimension','deleteById', $id);
		
		//  Delete from dimension groups
		$res = $this->db->getAll("SELECT id as dimgroup_id, dimension_ids FROM ".DB_PREFIX."dimension_groups WHERE block_id = ?", array($this->get('block_id')));
		if ($this->db->isError($res)) {
			return false;
		} else {
			if($res) foreach($res as $value) {
				$dim_ids = explode(',', $value['dimension_ids']);
				$altered = FALSE;
				for ($i = 0; $i < count($dim_ids); $i++) {
					if ($dim_ids[$i] == $id)
						unset($dim_ids[$i]);
						$altered = TRUE;
				}
				if ($altered) {
					$tmp = $this->db->query("UPDATE ".DB_PREFIX."dimension_groups SET dimension_ids = ? WHERE id = ?", array(implode(',', $dim_ids), $value['dimgroup_id']));
					if ($this->db->isError($tmp)) return false;
				}
			}
		}
		return true;
	}

}
?>
