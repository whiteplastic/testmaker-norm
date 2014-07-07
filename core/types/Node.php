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
 * @package Core
 */
class Node
{

	/**#@+
	 * @access private
	 */
	var $id;
	var $table;
	var $sequence;
	var $db;
	/**#@-*/

	/**
	 * This constructor has to be overwritten.
	 * In the overwriting constructor the variable $this->table has to be set to the correct database table name.
	 * After initializing this variable the overwring constructor has to call this overwritten constructor.
	 * @param DB The database object
	 * @param integer ID of the node
	 */
	function Node($id)
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		if (!isset($this->table))
		{
			trigger_error('<b>Node</b>: $this->table was not set');
		}
		if(!isset($this->sequence))
		{
			trigger_error('<b>Node</b>: $this->sequence was not set');
		}

		if (is_array($id)) {
			$this->id = $id['id'];
			$this->data = $id;
			return;
		}

		if ($res = retrieve(get_class($this), $id)) {
			$this->id = $res['id'];
			$this->data = $res;
			return;
		}
		
		$id = str_replace (' ', '', $id);
		if (!preg_match('/^[0-9]+$/', $id))
		{
			trigger_error('<b>Node</b>: $id is no valid node id');
			return false;
		}

		$sql = 'SELECT * FROM '.$this->table.' WHERE id = ?';
		$query = $this->db->query($sql, array($id));
		if ($query->numRows() != 1 && $id != 0) {
			//trigger_error('<b>Node</b>: '.$id.' is no valid node id');
			return false;
		}

		$query->fetchInto($this->data);
		$this->id = $id;
	}

	/**
	 * returns object of own class
	 * @return Integer
	 */
	function _returnSelf($id)
	{
       $className = get_class( $this );
       return new $className($id);
	}

	/**
	 * returns the id of the current node
	 * @return Integer
	 */
	function getId()
	{
		return ((int) $this->id);
	}

	var $data = FALSE;
	function _getField($fieldname)
	{
		if (! $this->data) {
			if ($res = retrieve(get_class($this), $this->id)) {
				$this->data = $res;
				return;
			}

			$sql = 'SELECT * FROM '.$this->table.' WHERE id = ?';
			$query = $this->db->query($sql, array($this->id));
			$query->fetchInto($this->data);
		}
		return @$this->data[$fieldname];
	}

	/**
	 * Checks if this node is disabled
	 * @return boolean
	 */
	/*function getDisabled()
	{
		if (!isset($this->data['disabled'])) return false;
		return $this->data['disabled'];
	}*/
	
	/**
	 * Checks if this node is disabled
	 * @return boolean
	 */
	function getDisabled($path = "0")
	{
		//check if node is an item and if disabled
		$nodeId = $this->getId();
		$table = $this->table ? $this->table : DB_PREFIX."items";

	    $res = $this->db->getAll("SELECT * FROM ".$table." WHERE id=? AND disabled=1", array($nodeId));	
		if (count($res) >= 1) {
			return true;
		}
		
		if ($path != "0") {	
			$pathIds = explode("_", $path);
			$maxPos = count($pathIds);
			
			$BlockId = $this->id;
			$parentBlockId = 0;
			
			$backTrack = $maxPos;
			$pathIds[$backTrack] = $BlockId;
			
			//check if block is disabled
			if ($maxPos > 1) {
				$parentBlockId = $pathIds[$maxPos-2];
				$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX."blocks_connect WHERE id=? AND parent_id=? AND disabled=1", array($BlockId, $parentBlockId));
				if (count($res) >= 1) {
					return true;
				}
			}
			
			//check if a parent block is disabled
			for ($i = 0; $i < $maxPos; $i++) {
				if ($BlockId == $pathIds[$i])
					$backTrack = $i;
			}
			
			for ($j = $backTrack; $j > 0; $j--) {
				$BlockId = $pathIds[$j];
				$parentBlockId = $pathIds[$j-1];
				$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX."blocks_connect WHERE id=? AND parent_id=? AND disabled=1", array($BlockId, $parentBlockId));
				if (count($res) >= 1) {
					return true;
				}
			}
			return false;
		}
		return false;
	}
	
	function nodeDisabled($childId, $parentId) {
		$res = $this->db->getAll("SELECT * FROM ".DB_PREFIX."blocks_connect WHERE id=? AND parent_id=? AND disabled=1", array($childId, $parentId));
			if (count($res) >= 1) {
				return true;
			}
		return false;
	}

	/**
	 * Returns the time of creation
	 * @return String
	 */
	function getCreationTime()
	{
		return $this->_getField('t_created');
	}

	/**
	 * Returns the time of last modification
	 * @return String
	 */
	function getModificationTime()
	{
		return $this->_getField('t_modified');
	}

	/**
	 * save given informaitons of associative array in database
	 * @param $modification values to be modified
	 * @return bool
	 */
	 function _modify($modifications) {
		 $this->data = NULL;

		$modifications['t_modified'] = time();
		$modifications['u_modified'] = $GLOBALS['PORTAL']->getUserId();

		$quote = array();
		$sets = '';
		for(reset($modifications); list($column, $value) = each($modifications); ) {
			if(strlen($sets) > 0) {
				$sets .= ', ';
			}
			$sets .= $column.' = ?';
			$quote[] = $value;
		}

		$quote[] = $this->id;
		$query = 'UPDATE '.$this->table.' SET '.$sets.' WHERE id = ?';
		$result = $this->db->query($query, $quote);
		if($this->db->isError($result)) {
			return false;
		}

		return true;
	 }

	/**
	 * modify the given informations in the current node
	 * @param mixed[] $modification values to be modified
	 * @return bool
	 */
	function modify($modifications)
	{
		trigger_error('<b>Node:modify</b>: function not overwritten by inherited class');
	}

	/**
	 * Returns a copy of the current node
	 * @param parent id
	 * @param 2-dimensional array of changed ids. First dimension containing node type as name and seconde dimension assigns old id to new id.
	 * @return Node
	 */
	function copyNode($parent, &$changedIds)
	{
		trigger_error('<b>Node:copyNode</b>: function not overwritten by inherited class');
	}

	/**
	 * prepares everything to delete this node itself
	 * @return boolean
	 */
	function cleanUp()
	{
		//nothing to do, because no infoarmations in other tables stored
		return true;
	}


}
