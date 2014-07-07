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


$LOG_OP_VAL2STR = array(
	 1 => 'delete',
	 2 => 'user_edit',
	 3 => 'user_create',
	 4 => 'user_delete',
	 5 => 'group_edit',
	 6 => 'group_create',
	 7 => 'group_delete',
	 8 => 'publish',
	 9 => 'privs_edit',
	10 => 'user_activate',
	11 => 'user_deactivate',
	12 => 'block_delete_logo',
	13 => 'data_export',
	14 => 'privacy_policy',
	15 => 'maintenance_mode',
	16 => 'test_survey',
	17 => 'user_show',
	18 => 'testrun_delete',
);
$LOG_OP_STR2VAL = array_flip($LOG_OP_VAL2STR);
foreach ($LOG_OP_STR2VAL as $key => $val) {
	define('LOG_OP_'. strtoupper($key), $val);
}


require_once(dirname(__FILE__).'/UserList.php');

/**
 * Manages log entries for edit operations.
 */
class EditLog
{
	/**
	 * Adds a log entry.
	 *
	 * @param User The user who performed the operation.
	 * @param int The type of operation (any LOG_OP_ constant).
	 * @param mixed A target object (User, Block etc.).
	 * @param mixed A structure containing more information.
	 *
	 * @static
	 */
	function addEntry($operation, $target, $details)
	{
		$db = $GLOBALS['dao']->getConnection();

		$uid = $GLOBALS['PORTAL']->getUserId();

		$targetType = ''; $targetId = 0; $targetTitle = '';
		if ($target !== NULL) {	
			if((gettype($target) == "object") && ($targetType = get_class($target)) && ($targetId = $target->getId()) && ($targetTitle = $target->getTitle())){
			}else if ($operation == 13 || $operation == 18){
				$targetType = 'ContainerBlock';
				$targetId = $target;
				$targetTitle = 'total';					
			}else if ($operation == 14){
				//$targetType = 'containerblock';
				$targetType = 'PrivacyPolicy';
				$targetId = $target;
				$target = ' current version';					
			}else if ($operation == 15){
				$targetType = 'MaintenanceMode';
				$targetId = $target;
				//$target = ' current version';					
			}else if ($operation == 16){
				$targetType = 'TestSurvey';
				$targetId = $target;
				$targetTitle = 'e-mails';					
			}
		}
		$details = serialize($details);

		$id = $db->nextId(DB_PREFIX.'edit_logs');
		$db->query("INSERT INTO ".DB_PREFIX."edit_logs VALUES(?, ?, ?, ?, ?, ?, ?, ?)", array(
			$id,
			$uid,
			NOW,
			$operation,
			$targetType,
			$targetId,
			$targetTitle,
			$details
		));
	}
	
	

	/**
	 * Finds log entries by certain criteria, most recent first.
	 *
	 * @param User A user to match (or NULL to match all users).
	 * @param integer A LOG_OP_ constant (or NULL to match all types of operations).
	 * @param string A target class name (or NULL to match all types of targets).
	 * @param integer Offset.
	 * @param integer Maximum number of entries to return.
	 *
	 * @return mixed[] A list of associative array with keys 'user', 'stamp', 'operation', 'target', and 'details'.
	 *
	 * @static
	 */
	function findEntries($user, $operation, $targetType, $offset, $limit)
	{
		$db = $GLOBALS['dao']->getConnection();
		$args = array();
		$query = "SELECT * FROM ".DB_PREFIX."edit_logs";
		$ul = new UserList();
		if (!$ul->getUserByName($user) && $user) return FALSE;

		if ($user !== NULL || $operation !== NULL || $targetType !== NULL) {
			$query .= ' WHERE';
			$and = false;
		}
		if ($user) {
			if ($and) $query .= ' AND'; else $and = true;
			$query .= ' user_id = ?';
			$args[] = $ul->getUserByName($user)->get('id');
		}
		if ($operation) {
			if ($and) $query .= ' AND'; else $and = true;
			$query .= ' optype = ?';
			$args[] = $operation;
		}
		if ($targetType) {
			if ($and) $query .= ' AND'; else $and = true;
			$query .= ' target_type = ?';
			$args[] = $targetType;
		}
		$query .= ' ORDER BY stamp DESC LIMIT ?, ?';
		$args[] = $offset; $args[] = $limit;

		$res = $db->query($query, $args);
		if (PEAR::isError($res)) return NULL;

		$rres = array();
		while ($res->fetchInto($row)) {
			$rresRow = array();
			$userId = $row['user_id'];
			$rresRow['user'] = DataObject::getById('User', $userId);
			$rresRow['stamp'] = $row['stamp'];
			$rresRow['operation'] = $row['optype'];
			$rresRow['target'] = NULL;
			$rresRow['target_title'] = $row['target_title'];
			$rresRow['target_type'] = $row['target_type'];

			$rresRow['details'] = unserialize($row['details']);

			// Item/Page entries are no longer supported
			if (preg_match('/item$|page$/', $row['target_type'])) {
				$rres[] = $rresRow;
				continue;
			}
			if ($row['target_id']) {
				$type = $row['target_type'];

				// TODO: overhaul when new data architecture is done --jk
				switch ($type) {
				case 'MaintenanceMode':
				case 'PrivacyPolicy': if ($row['target_id'] == 0) $row['target_id']='null'; break; //??! 		
				case 'User':
				case 'Group':
					$rresRow['target'] = DataObject::getById($type, $row['target_id']);
					break;
				default:
					if (intval($row['target_id']) && $GLOBALS['BLOCK_LIST']->existsBlock($row['target_id'])) $rresRow['target'] = 0; //b/ new $type (intval($row['target_id']));
				 	//b/ printvar($rresRow['target']); 
				 	// function above causes error on certain log-entries?
				 	// !??! $rresRow['target'] is NULL in any case..
				}
				$rresRow['target_id'] = $row['target_id'];
			}
			$rres[] = $rresRow;
		}

		return $rres;
	}
	
	
	function countEntries($user, $operation, $targetType)
	{
		$db = $GLOBALS['dao']->getConnection();
		$args = array();
		$query = "SELECT COUNT(*) FROM ".DB_PREFIX."edit_logs";
		$ul = new UserList();
		if (!$ul->getUserByName($user) && $user) return FALSE;

		if ($user !== NULL || $operation !== NULL || $targetType !== NULL) {
			$query .= ' WHERE';
			$and = false;
		}
		if ($user) {
			if ($and) $query .= ' AND'; else $and = true;
			$query .= ' user_id = ?';
			$args[] = $ul->getUserByName($user)->get('id');
		}
		if ($operation) {
			if ($and) $query .= ' AND'; else $and = true;
			$query .= ' optype = ?';
			$args[] = $operation;
		}
		if ($targetType) {
			if ($and) $query .= ' AND'; else $and = true;
			$query .= ' target_type = ?';
			$args[] = $targetType;
		}

		return $db->getOne($query, $args);
	}
	
	
}
