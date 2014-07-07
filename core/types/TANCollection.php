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

// Include classes for email handling
libLoad('utilities::validateEmail');

/**
 * Include the ContainerBlock class
 */
require_once(CORE.'types/ContainerBlock.php');

/**
 * This class handles the TANs for a test which are used to avlidate a secure direct access
 *
 * @package Core
 */
class TANCollection
{
	/**#@+
	 *
	 * @access private
	 */
	var $blockId;

	var $db;
	/**#@-*/

	/**
	 * @param integer block id of connected containerblock
	 */
	function TANCollection($blockId)
	{
		if (! isset($GLOBALS["dao"]))
		{
			return;
		}
		$this->db = &$GLOBALS['dao']->getConnection();
		
		if(!$GLOBALS['BLOCK_LIST']->existsBlock($blockId))
		{
			return;
		}

		$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		if(!$block->isContainerBlock())
		{
			return;
		}
		
		$this->blockId = $blockId;
		
	}

	/**
	 * returns the connected block by given tan
	 * @param static
	 * @param id block id
	 * @return ContainerBlock connected block
	 */
	function getBlockByTAN($tan)
	{
		if (! isset($GLOBALS["dao"]))
		{
			trigger_error('<b>TANCollection::getBlockByTan</b>: no global db connection found');
			return NULL;
		}
		$db = &$GLOBALS['dao']->getConnection();
		$query = 'SELECT block FROM  '.DB_PREFIX.'tans WHERE access_key = ?';
		$blockId = $db->getOne($query, array($tan));

		if(empty($blockId))
		{
			return NULL;
		}

		return new ContainerBlock($blockId);
	}

	/**
	 * returns the id of the connected block by given tan
	 * @param static
	 * @return int Block ID
	 */
	function getBlockIdByTAN($tan)
	{
		if (! isset($GLOBALS["dao"]))
		{
			trigger_error('<b>TANCollection::getBlockByTan</b>: no global db connection found');
			return NULL;
		}
		$db = &$GLOBALS['dao']->getConnection();
		$query = 'SELECT block FROM  '.DB_PREFIX.'tans WHERE access_key = ?';
		$blockId = $db->getOne($query, array($tan));
		return $blockId ? $blockId : 0;
	}

	/**
	 * Returns the connected test_path by given tan
	 * @param static
	 * @param integer Tan
	 * @return string Test path
	 */
	function getTestPathByTAN($tan)
	{
		if (! isset($GLOBALS["dao"]))
		{
			trigger_error('<b>TANCollection::getBlockByTan</b>: no global db connection found');
			return NULL;
		}
		$db = &$GLOBALS['dao']->getConnection();
		$query = 'SELECT test_path FROM  '.DB_PREFIX.'tans WHERE access_key = ?';
		$testPath = $db->getOne($query, array($tan));

		return $testPath;
	}

	/**
	 * return false if failed or time of tan creation
	 * @param integer amount of tans which should be generated for current block
	 * @param string test path
	 * @return mixed
	 */
	function generateTANs($amount, $testPath)
	{
		libLoad("utilities::randomString");
		$user = $GLOBALS['PORTAL']->getUser();
		$query1 = 'SELECT count(access_key) FROM '.DB_PREFIX.'tans WHERE access_key = ?';
		$query2 = 'INSERT INTO '.DB_PREFIX.'tans (access_key, block, test_path, t_created, t_modified, u_created, u_modified) VALUES (?, ?, ?, ?, ?, ?, ?)';
		$now = time();
		for($i = 0; $i < $amount; $i++)
		{
			do {
				$tan = strtolower(randomString(6, true));
				$result = $this->db->getOne($query1, array($tan));
				if($this->db->isError($result))
				{
					return false;
				}
			} while($result > 0);
			
			$this->db->query($query2, array($tan, $this->blockId, $testPath,$now, $now, $user->getId(), $user->getId()));
		}
		return $now;
	}

	/**
	 * update mail address of given access key
	 * @param string tan which should be binded to given mail address
	 * @param string mail address which should be set
	 * @return boolean
	 */
	function setMail($tan, $mail)
	{

		if (!validateEmail($mail, $errors))
		{
			return false;
		}
		$user = $GLOBALS['PORTAL']->getUser();
		$query = 'UPDATE '.DB_PREFIX.'tans SET mail = ?, t_modified = ?, u_modified = ? WHERE access_key = ?';
		$this->db->query($query, array($mail, time(), $user->getId(), $tan));
		return true;
	}
	
	/**
	 * get testrun for given access key or return false if no on exists
	 * @param string tan
	 * @return mixed
	 */
	function getTestRun($tan)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$query = 'SELECT test_run FROM  '.DB_PREFIX.'tans WHERE access_key = ?';
		if(!($testRun = $db->getOne($query, array($tan)))) {
			return false;
		}

		return intval($testRun);
	}
	
	/**
	 * check if TAN is valid
	 * @static
	 * @param string tan
	 * @return boolean
	 */
	function existsTAN($tan)
	{
		if (! isset($GLOBALS["dao"]))
		{
			trigger_error('<b>TANCollection::getBlockByTan</b>: no global db connection found');
			return false;
		}
		$db = &$GLOBALS['dao']->getConnection();
		$query = 'SELECT count(access_key) FROM  '.DB_PREFIX.'tans WHERE access_key = ?';
		$countTAN = $db->getOne($query, array($tan));

		if($countTAN > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * set testrun for given access key if not already set
	 * @param string tan
	 * @param integer test run id which should be set
	 * @return boolean
	 */
	function setTestRun($tan, $testRun)
	{
		$query = 'SELECT test_run FROM  '.DB_PREFIX.'tans WHERE access_key = ?';
		$oldTestRun = $this->db->getOne($query, array($tan));
		if(!empty($oldTestRun))
		{
			return false;
		}
		$user = $GLOBALS['PORTAL']->getUser();
		$query = 'UPDATE '.DB_PREFIX.'tans SET test_run = ?, t_modified = ?, u_modified = ? WHERE access_key = ?';
		$this->db->query($query, array($testRun, time(), $user->getId(), $tan));
		
		return true;
	}
	
	/**
	 * returns all tan dates for current block
	 * @return integer[]
	 */
	function getTANDates()
	{
		$query = 'SELECT t_created FROM '.DB_PREFIX.'tans WHERE block = ? GROUP BY t_created ORDER BY t_created DESC';
		return $this->db->getCol($query, 0, array($this->blockId));
	}

	/**
	 * returns all tans for current block and given dates
	 * @return string[]
	 */
	function getTANsByDate($dates)
	{
		if(!is_array($dates)) {
			$dates = array();
		}
		$values = array($this->blockId);
		$where = '';
		foreach($dates as $date) {
			if($where == '') {
				$where .= ' AND ( ';
			} else {
				$where .= ' OR ';
			}
			$where .= 't_created = ?';
			$values[] = $date;
		}
		if($where != '') {
			$where .= ' )';
		}
		$query = 'SELECT access_key FROM '.DB_PREFIX.'tans WHERE block = ?'.$where;
		return $this->db->getCol($query, 0, $values);
	}

	/**
	 * returns all tans for current block
	 * @return mixed[][] 2-dimensional array containing all tans in first dimension and the db fields in second dimension
	 */
	function getTANs($startEntry = 0, $entryNum = 0, $sortBy = '', $order = 'ASC')
	{
		
		switch($sortBy) {
			case 'test_run': case 't_created': case 't_modified':
				break;
			default:
				$sortBy = 'test_run';
		}
		
		if(strtoupper($order) != 'DESC') {
			$order = 'ASC';
		}
		
		$limit = 'LIMIT ?';
		$limitValues = array($startEntry);
		if(is_numeric($entryNum) && $entryNum != 0) {
			$limit .= ', ?';
			$limitValues[] = $entryNum;
		}
		$query = 'SELECT access_key, block as block_id, test_run as test_run_id, mail, t_created, t_modified, u_created, u_modified FROM  '.DB_PREFIX.'tans WHERE block = ? ORDER BY '.$sortBy.' '.$order.' '.$limit;
		return $this->db->getAll($query, array_merge(array($this->blockId), $limitValues));
	}

	/**
	 * returns number of all tans for current block
	 * @return integer
	 */
	function getTANCount()
	{
		$query = 'SELECT count(access_key) FROM  '.DB_PREFIX.'tans WHERE block = ?';
		return $this->db->getOne($query, array($this->blockId));
	}
	
	/**
	 * returns email address of a tan user for a test run
	 * @return string
	 */
	function getEmailByTestRun($testRun) {
		$db = &$GLOBALS['dao']->getConnection();
		$query = 'SELECT mail FROM '.DB_PREFIX.'tans WHERE test_run = ?';
		return $db->getOne($query, array($testRun));
	}

	/**
	 * Returns the version of the Privacy Policy a tan-user has accepted
	 *
	 * @return int (timestamp)
	 */
	function getPrivacyPolicyAcc()
	{
		$db = &$GLOBALS['dao']->getConnection();
		$query = 'SELECT privacy_policy_ok FROM  '.DB_PREFIX.'tans WHERE access_key = ?';
		if(!($version = $db->getOne($query, array($tan)))) {
			return false;
		}
		return intval($version);
	}
	
	
	/**
	 * Set the version of the Privacy Policy wich a tan-user has accepted
	 * (necessary when a email-address is provided)
	 *
	 * @return bool
	 */
	function setPrivacyPolicyAcc($tan, $pp_acc_version)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$query = "UPDATE ".DB_PREFIX."tans SET privacy_policy_ok = ".$pp_acc_version." WHERE access_key = ?";
		$res =$db->query($query, array($tan));
		
		return $res;
	}
	
}

?>
