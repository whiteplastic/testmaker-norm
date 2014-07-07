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

/**
 * Provides access to deep structures of tests. In particular, allows
 * efficient fetching of all relevant data pertaining to a given test. In this
 * context, "relevant" means "all data actually needed for displaying/exporting
 * etc. Things such as permissions and timestamps are not necessarily included.
 * @package Core
 */
class TestStructure
{

	/**
	 * Just like getTestBlocks but only does one level of fetching.
	 */
	private static function getOneBlockLevel($blockId)
	{
		$strc = array();
		$db = $GLOBALS['dao']->getConnection();
		$sql = 'SELECT DISTINCT t.id AS block_id, t.type AS block_type, c1.parent_id AS parent_id 
			FROM '.DB_PREFIX.'blocks_type t
			JOIN '.DB_PREFIX.'blocks_connect c1 ON (c1.id = t.id) 
			LEFT JOIN '.DB_PREFIX.'container_blocks cb ON (cb.id = t.id)
			WHERE c1.parent_id = ? AND (c1.disabled = 0 OR c1.disabled IS NULL) ORDER BY c1.pos, c1.id';
		$res = $db->query($sql, array($blockId));
		while ($row = $res->fetchRow()) {
			$temp = array(
				'block_id' => $row['block_id'],
				'block_type' => self::getStringifiedType($row['block_type']),
				'parent_id' => $row['parent_id'],
			);
			if($row['block_type'] == 2) $temp['source_ids'] = self::getSourceBlocks($row['block_id']);
			$strc[] = $temp;
		}

		$res->free();
		return $strc;
	}

	/**
	 * Retrieves a list of all blocks that are part of a given test along with
	 * the type of each block (as a list of arrays of the form
	 * <kbd>array($id, $type)<kbd>.
	 */
	private static function getTestBlocks($testId, $flag = 1)
	{
		//the retrieve function is comment out because the object chache don't work correctly
		
		if ($flag == 1) {
			if ($res = retrieve('testBlocks', $testId))  
				return $res;
		}
		
		// To prevent an ultra-join style query that MySQL would
		// probably suffocate on, we do this in three easy steps.

		// Step 1: make an educated guess about the main test block
		$strc[0] = array(
			'block_id' => $testId,
			'block_type' => 'ContainerBlock',
			'parent_id' => 0,
		);

		// Step 2: get direct children
		$strc[$testId] = TestStructure::getOneBlockLevel($testId);

		// Step 3: get stuff in subtests
		// (and lets not worry about the number of queries since we
		// don't do this all that often anyway)
		foreach ($strc[$testId] as $data) {
			if ($data['block_type'] != 'ContainerBlock') continue;
			$strc[$data['block_id']] = TestStructure::getOneBlockLevel($data['block_id']);
		}

		$res = array();
		// Transmogrify result into test-ordered list (i.e. flatten tree)
		$flattenStack = array($testId);
		while (!empty($flattenStack)) {
			$id = end($flattenStack);
			if(array_key_exists($id, $strc)) 
			{
				$cur = array_shift($strc[$id]);
				$res[] = $cur;
				if (empty($strc[$id])) array_pop($flattenStack);
				if (isset($strc[$cur['block_id']])) array_push($flattenStack, $cur['block_id']);
			} else array_pop($flattenStack);
		}
		store('testBlocks', $testId, $res);
		return $res;
	}

	/**
	 * Determines the source blocks of a feedback block
	 */
	private static function getSourceBlocks($id)
	{
		$sourceBlocks = array();
		$db = $GLOBALS['dao']->getConnection();
		$sql = 'SELECT item_block_id FROM '.DB_PREFIX.'feedback_blocks_connect WHERE feedback_block_id = ?';
		$res = $db->query($sql, array($id));
		while($row = $res->fetchRow())
		{
			$sourceBlocks[] = $row['item_block_id'];
		}
		return $sourceBlocks;
	}

	/**
	 * Maps numeric block types to the corresponding class names.
	 */
	private static function getStringifiedType($type)
	{
		switch ($type) {
		case BLOCK_TYPE_CONTAINER:
			return 'ContainerBlock';
		case BLOCK_TYPE_ITEM:
			return 'ItemBlock';
		case BLOCK_TYPE_INFO:
			return 'InfoBlock';
		case BLOCK_TYPE_FEEDBACK:
			return 'FeedbackBlock';
		}
	}

	/**
	 * Maps block class names to the corresponding database tables (sans DB_PREFIX).
	 */
	private static function getBlockTable($blockType)
	{
		switch ($blockType) {
		case 'ContainerBlock':
			return 'container_blocks';
		case 'ItemBlock':
			return 'item_blocks';
		case 'InfoBlock':
			return 'info_blocks';
		case 'FeedbackBlock':
			return 'feedback_blocks';
		case 'FeedbackPage':
			return 'feedback_pages';
		case 'Item':
			return 'items';
		default:
			die("Invalid block type passed to TestStructure::getBlockTable");
		}
	}

	/**
	 * Maps block class names to the database tables containing their children (sans DB_PREFIX).
	 */
	private static function getBlockChildrenTable($blockType)
	{
		switch ($blockType) {
		case 'ContainerBlock':
			return 'container_blocks';
		case 'ItemBlock':
			return 'items';
		case 'InfoBlock':
			return 'info_pages';
		case 'FeedbackBlock':
			return 'feedback_pages';
		case 'FeedbackPage':
			return 'feedback_paragraphs';
		case 'Item':
			return 'item_answers';
		default:
			die("Invalid block type passed to TestStructure::getBlockChildrenTable");
		}
	}

	/**
	 * Maps block class names to the classes of their children.
	 */
	private static function getBlockChildrenClass($blockType)
	{
		switch ($blockType) {
		case 'ContainerBlock':
			return 'ContainerBlock';
		case 'ItemBlock':
			return 'Item';
		case 'InfoBlock':
			return 'InfoPage';
		case 'FeedbackBlock':
			return 'FeedbackPage';
		case 'FeedbackPage':
			return 'FeedbackParagraph';
		case 'Item':
			return 'ItemAnswer';
		default:
			die("Invalid block type passed to TestStructure::getBlockChildrenClass");
		}
	}

	/**
	 * Fetches all blocks/pages/items in a given block (or list of blocks).
	 * For each ID given, the corresponding list of blocks will be returned in a separate array.
	 * This holds even if only one ID was given, i.e. something like <kbd>array(array($block1, $block2, ...))</kbd>
	 * will be returned.
	 * This method also works for getting the children of items (use 'Item' for $blockType regardless of the actual
	 * type of item) and feedback pages, though dimensions and dimension groups will not be fetched.
	 * @param mixed Block ID or list of block IDs.
	 * @param string Block type (class name).
	 * @return mixed[][]
	 */
	static function getSubPages($blockId, $blockType)
	{
		if (!is_array($blockId)) $blockId = array($blockId);
		$ids = array();
		$res = array();
		foreach ($blockId as $id) {
			/*if ($objs = retrieve($blockType.'Children', $id)) {
				$res[$id] = $objs;
				continue;
			}*/
			$ids[] = intval($id);
		}

		if (empty($ids)) return $res;

		$db = $GLOBALS['dao']->getConnection();
		$table = self::getBlockChildrenTable($blockType);
		$subclass = self::getBlockChildrenClass($blockType);
		$col = ($blockType == 'Item' ? 'item_id' : ($blockType == 'FeedbackPage' ? 'page_id' : 'block_id'));

		$dbres = $db->query('SELECT * FROM '. DB_PREFIX . $table .' WHERE '. $col .' IN ('. implode(', ', $ids) .') AND (disabled = 0 OR disabled IS NULL) ORDER BY pos, id');

		while ($row = $dbres->fetchRow()) {
			if ($subclass == 'Item') {
				require_once(ROOT.'upload/items/'. $row['type'] .'.php');
				$res[$row[$col]][] = new $row['type']($row['id']);
			} else {
				$res[$row[$col]][] = new $subclass($row['id']);
			}
			store($blockType, $row['id'], $row);
		}
		// Store collections too
		foreach ($ids as $id) {
			// Blocks may be empty
			if (!isset($res[$id])) continue;
			store($blockType.'Children', $id, $res[$id]);
		}
		return $res;
	}

	/**
	 * Retrieves a list of all pages (text, items, feedback) in a test,
	 * along with a list of subtest IDs for each page:
	 * <kbd>array($pageObjects, array(id1 => subtestId1, ...))</kbd>
	 */
	static function getTestPages($testId, $flag = 1)
	{
		$blocks = self::getTestBlocks($testId, $flag);

		$idLists = array(
			'ItemBlock' => array(),
			'InfoBlock' => array(),
			'FeedbackBlock' => array(),
		);
		$parents = array();
		foreach ($blocks as $block) {
			if (array_key_exists($block['block_type'], $idLists)) {
				$idLists[$block['block_type']][] = $block['block_id'];
				$parents[$block['block_id']] = (($block['parent_id'] == $testId) ? 0 : $block['parent_id']);
			}
		}

		$resList = array();
		foreach ($idLists as $key => $ids) {
			// Suppose there were no blocks of a certain type...
			if (empty($ids)) continue;

			$res = self::getSubPages($ids, $key);
			// Thanks to PHP's builtin functions throwing away numerical
			// indices, we have to do this manually. Hooray!
			foreach ($res as $blockId => $data) {
				$resList[$blockId] = $data;
			}
		}
		$res = array();

		foreach ($blocks as $block) {
			if ($block['block_type'] == 'ContainerBlock') continue;
			if (!isset($resList[$block['block_id']])) continue;
			$res = array_merge($res, $resList[$block['block_id']]);
		}

		return array($res, $parents);
	}

	/**
	 * Generates a structure array for the given test, i.e. a sorted flat
	 * list of arrays, each describing a single page/item of the test.
	 */
	static function getStructure($testId, $flag = 1)
	{
		list($pages, $parents) = self::getTestPages($testId, $flag);	
		$res = array();
		foreach ($pages as $page) {
			$block = $page->getParent();
			if ($page instanceof InfoPage || $page instanceof FeedbackPage) {
				$res[] = array(
					'parent_id' => $block->getId(),
					'parent_type' => get_class($block),
					'id' => $page->getId(),
					'type' => get_class($page),
					'subtest_id' => $parents[$block->getId()],
				);
				continue;
			}
			$children = array();
			foreach ($page->getChildren() as $child) {
				$children[] = $child->getId();
			}
			$memory = memory_get_usage();
			if (($memory + 16000000) > MEMORY_LIMIT && !SPEEDMODE)
				unstore_all();
			$res[] = array(
				'parent_id' => $block->getId(),
				'parent_type' => get_class($block),
				'id' => $page->getId(),
				'type' => get_class($page),
				'subtest_id' => $parents[$block->getId()],
				'child_ids' => $children,
			);
		}
		return $res;
	}

	static function getBlockStructure($testId)
	{
		$assocArray = array();
		$testBlocks = self::getTestBlocks($testId);
		foreach($testBlocks as $block)
		{
			$assocArray[$block['block_id']] = $block;	
		}
		return $assocArray;
	}

	static function getPageStructure($testId)
	{
		return self::getTestPages($testId);
	}

	/**
	 * Returns a previously generated structure array for the given test.
	 * @param int ID of the test.
	 * @param int If set, determines which version of the structure to
	 *   fetch, defaults to the newest. Use false to also default to the
	 *   newest version but fail if there is no stored version (usually we
	 *   automatically store one when necessary).
	 * @return mixed[] Returns an associative array containing the
	 *   version retrieved (key 'version') and the structure array (key
	 *   'structure').
	 */
	static function loadStructure($testId, $version = 0, $accessType = "")
	{
		$db = $GLOBALS['dao']->getConnection();
		$query = 'SELECT version, content FROM '.DB_PREFIX.'test_structures WHERE test_id = ?';
		$data = array($testId);
		if ($version) {
			$query .= ' AND version = ?';
			$data[] = $version;
		}
		$query .= ' ORDER BY version DESC LIMIT 1';
		$res = $db->getRow($query, $data);
		if (!$res) {
			if ($version === false) return NULL;
			// No structure array was generated yet, implicitly generate one
			$description = 'types.structure.auto_created';
			$res = self::storeCurrentStructure($testId, $description, $accessType);
			$structure = $res['content'];
		} else {
			$structure = unserialize($res['content']);
		}
		$res['content'] = $structure;
		return $res;
	}
	
	static function existsStructure($testId) {
		$db = $GLOBALS['dao']->getConnection();
		$res = $db->getOne('SELECT version FROM '.DB_PREFIX.'test_structures WHERE test_id = ? ORDER BY version DESC LIMIT 1', array($testId));
		if (!$res) 
			return FALSE;
		return TRUE;
	}
	
	static function existsStructureOfTests($tests) {
		foreach($tests as $test) {
			if (TestStructure::existsStructure($test->id))
				return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Returns the part of a test structure that describes a given subtest.
	 * @param mixed[] Test structure.
	 * @param int Subtest ID.
	 * @return mixed[] Partial test structure.
	 */
	static function filterStructureBySubtestId($structure, $subtestId)
	{
		return array_filter($structure, create_function('$s', 'return ($s["subtest_id"] == '.$subtestId.');'));
	}

	/**
	 * Checks if test structure changes should be tracked for the given
	 * test.
	 * @param int Test ID.
	 * @return bool
	 */
	static function wantToTrackStructure($testId)
	{
		$block = $GLOBALS['BLOCK_LIST']->getBlockById($testId);
		if ($block->isPublic(false)) {
			return true;
		}
		// We want to track more structures if we already have at least one
		$tmp = TestStructure::loadStructure($testId, false) ? true : false;
		return $tmp;
	}

	/**
	 * Finds all tests that contain the given block in some way and that
	 * need to have its structure tracked.
	 * @param int Block ID.
	 * @return ContainerBlock[]
	 */
	static function getTrackedContainingTests($blockId)
	{
		if ($blockId instanceof Block)
			$block = $blockId;
		else
			$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		$blocks = $block->getUpperPublicBlocks(true);

		// We don't want ourselves, really
		return array_filter($blocks, create_function('$a', 'return ($a->getId() != '. $block->getId() .');'));
	}

	/**
	 * Stores the current structure of a test as a new version.
	 * @return mixed[] Returns an associative array that looks just like
	 *   the one from loadStructure does.
	 */
	static function storeCurrentStructure($testId, $description = NULL, $accessType = "")
	{
		$db = $GLOBALS['dao']->getConnection();
		$structure = self::getStructure($testId, 0);

		// Get newest structure version for test
		$res = $db->getOne('SELECT version FROM '.DB_PREFIX.'test_structures WHERE test_id = ? ORDER BY version DESC LIMIT 1', array($testId));
		if (!$res) $res = 0;

		$block = $GLOBALS['BLOCK_LIST']->getBlockById($testId);
		if ($accessType == "preview") $accessAllowed = $block->isAccessAllowed($accessType, true);
		else $accessAllowed = $block->isAccessAllowed(false, false);
		if ($res == 0 && !$accessAllowed) return NULL;

		$res++;
		$db->query('INSERT INTO '.DB_PREFIX.'test_structures (test_id, version, content, description, stamp, testmaker_version, user_id) VALUES(?, ?, ?, ?, ?, ?, ?)',
			array($testId, $res, serialize($structure), serialize($description), NOW, TM_VERSION.TM_VERSION_SUFFIX, $GLOBALS['PORTAL']->getUserId()));
		return array(
			'version' => $res,
			'content' => $structure,
			'description' => $description,
		);
	}

	/**
	 * Reorders a test structure so that in a given block, two specific
	 * items are reordered so that they occur directly after each other,
	 * in exactly the following situation:
	 *
	 * <code>
	 * X1 -> ... -> Xn -> A -> (zero or more other items) -> B -> ...
	 * </code>
	 *
	 * turns into
	 *
	 * <code>
	 * X1 -> ... -> Xn -> A -> B -> (all remaining items in undefined
	 *                              order)
	 * </code>
	 *
	 * We use this for adaptive test sessions.
	 *
	 * @param mixed[] Test structure.
	 * @param int Item block ID.
	 * @param int ID of item A above (0 to move item B to the front of the
	 * 	block)
	 * @param int ID of item B above (0 to splice away all items of the
	 * 	block after item A)
	 * @return mixed[] Updated structure.
	 */
	static function reorderStructure($structure, $blockId, $firstItemId, $secondItemId)
	{
		$res = array();
		$restOfBlock = array();
		// Step 1: everything up to item A
		while ($row = array_shift($structure)) {
			if ($row['parent_id'] == $blockId) {
				if ($firstItemId == 0) {
					// If we're already at the second item put it back on
					if ($secondItemId == $row['id'])
						array_unshift($structure, $row);
					elseif ($secondItemId != 0)
						$restOfBlock[] = $row;
					break;
				}
				if ($row['id'] == $firstItemId) {
					$res[] = $row;
					break;
				}
			}
			$res[] = $row;
		}
		if (!$row) return NULL;

		// Step 2: collect stuff to be put after item B
		while ($row = array_shift($structure)) {
			if ($row['parent_id'] != $blockId && $secondItemId != 0) {
				return NULL;
			} elseif ($row['parent_id'] != $blockId) {
				// We're outside the target block, put the row back on
				array_unshift($structure, $row);
				break;
			}
			if ($row['id'] == $secondItemId) {
				$res[] = $row;
				break;
			}
			if ($secondItemId != 0) $restOfBlock[] = $row;
		}

		// Step 3: paste on rest of block and rest of structure
		return array_merge($res, $restOfBlock, $structure);
	}

	/**
	 * Remove a specific item from a test structure, e.g. for display
	 * conditions.
	 *
	 * @param mixed[] Test structure.
	 * @param int Item block ID.
	 * @param int ID of item to remove.
	 * @return mixed[] Updated structure.
	 */
	static function filterItemFromStructure($structure, $blockId, $itemId)
	{
		return array_filter($structure, create_function('$a',
			"return (\$a['parent_type'] != 'ItemBlock' && \$a['parent_id'] != $blockId
			&& \$a['id'] != $itemId);")
		);
	}

	/**
	 * Randomizes a test structure, considering the settings made in the test.
	 * @param array Test structure in the format that getStructure returns.
	 * @param int Test Id
	 * @return Returns randomized test structure in the same format
	 */
	static function randomizeStructure($structure, $testId)
	{
		libLoad("utilities::flattenArray");
		
		// Randomize order of blocks on main test level
		if ($GLOBALS['BLOCK_LIST']->getBlockById($testId)->hasRandomItemOrder())  {
			for ($i = 0; $i < count($structure); $i++) {
				$blockId = $structure[$i]['subtest_id'];
				//$blocks[$blockId][$structure[$i]['parent_id']][] = $structure[$i];
				if ($structure[$i]['subtest_id'] == 0)
					$blocks[$structure[$i]['parent_id']][] = $structure[$i];
				else
					$blocks[$blockId][$structure[$i]['parent_id']][] = $structure[$i];
				while (isset($structure[++$i]) && $structure[$i]['subtest_id'] == $blockId) {
					if ($structure[$i]['subtest_id'] == 0)
						$blocks[$structure[$i]['parent_id']][] = $structure[$i];
					else
						$blocks[$blockId][$structure[$i]['parent_id']][] = $structure[$i];
					$found = true;
				}
				if($found) $i--;
			}
			
			if(!empty($blocks)) {
				shuffle($blocks);
				$structure = array();
				$blocks = flattenArray($blocks);
				foreach ($blocks as $block) {
					if(isset($block[0]))
						foreach ($block as $foo)
							$structure[] = ($foo);
					else
						$structure[] = $block;
				}
			}
			else
			$structure = array();
			
		}

		for ($i = 0; $i < count($structure); $i++) {
			//Randomizes item blocks within containers
			if ($structure[$i]['parent_type'] == 'ItemBlock' && $structure[$i]['subtest_id'] != 0 && $GLOBALS['BLOCK_LIST']->getBlockById($structure[$i]['subtest_id'])->hasRandomItemOrder()) {
				//Group together ItemBlocks
				$pos = $i;
				$containerBlockId = $structure[$i]['subtest_id'];
				$itemBlocks = array();
				$itemBlocks[$structure[$i]['parent_id']][] = $structure[$i];
				while (isset($structure[++$i]) && $structure[$i]['subtest_id'] == $containerBlockId) {
					$itemBlocks[$structure[$i]['parent_id']][] = $structure[$i];
					$found = true;
				}
				if($found) $i--;
				shuffle($itemBlocks);
				$itemBlocks = flattenArray($itemBlocks);
				array_splice($structure, $pos, count($itemBlocks), $itemBlocks);
			}
		}

		for ($i = 0; $i < count($structure); $i++) {
			//Randomizes items within item blocks
			if ($structure[$i]['parent_type'] == 'ItemBlock' && $GLOBALS['BLOCK_LIST']->getBlockById($structure[$i]['parent_id'])->hasRandomItemOrder()) {
				$pos = $i;
				$itemBlockId = $structure[$i]['parent_id'];
				$items = array();
				$items[] = $structure[$i];
				while (isset($structure[++$i]) && $structure[$i]['parent_id'] == $itemBlockId) {
					$items[] = $structure[$i];
					$found = true;
				}
				if($found) $i--;
				shuffle($items);
				array_splice($structure, $pos, count($items), $items);
			}
		}
	return $structure;
	}

	/**
	 * Returns the changelog of a test's structure.
	 * @param int Id of the test
	 * @return array
	 */
	static function getChangelog($testId)
	{
		$db = $GLOBALS['dao']->getConnection();
		$sql = 'SELECT ts.*, u.username FROM '.DB_PREFIX.'test_structures as ts
				JOIN '.DB_PREFIX.'users as u ON (ts.user_id = u.id)
				WHERE test_id = ? ORDER BY version DESC';
		$res = $db->query($sql, array($testId));
		$changelog = array();
		
		while ($row = $res->fetchRow()) {
		// Workaround for old Data
		$msg = unserialize($row['description']);
		if(is_string(unserialize($row['description'])))
		$msg = unserialize($row['description']);
		else
		$msg = call_user_func_array('T', unserialize($row['description']));
		
			$changelog[] = array(
				'version' => $row['version'],
				'message' => $msg,
				'date' => date(T('pages.core.date_time'), $row['stamp']),
				'user' => $row['username'],
				'testmaker_version' => $row['testmaker_version'],
			);
		}
		$res->free();
		return $changelog;
	}
}
