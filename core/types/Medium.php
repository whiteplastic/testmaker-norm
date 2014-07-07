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
 * ID for unknown type
 */
define('MEDIA_TYPE_UNKNOWN', 0);
/**
 * ID for image type
 */
define('MEDIA_TYPE_IMAGE', 1);
/**
 * ID for sound type
 */
define('MEDIA_TYPE_SOUND', 2);
/**
 * ID for movie type
 */
define('MEDIA_TYPE_VIDEO', 3);
/**
 * ID for flash type
 */
define('MEDIA_TYPE_FLASH', 4);
/**
 * ID for document type
 */
define('MEDIA_TYPE_DOCUMENT', 5);

 
/**
 * This class for media objects
 *
 * @package Core
 */

class Medium
{
	/**#@+
	 *
	 * @access private
	 */
	var $id;
	var $filename;
	var $filetype;
	var $filepath;

	var $db;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * @param integer Medie ID
	 * @param boolean Whether or not a Media exists
	 */
	function Medium($id)
	{
		$this->db = $GLOBALS['dao']->getConnection();
		$query = "SELECT * FROM ".DB_PREFIX."media WHERE id=?";
		$res = $this->db->query($query, array($id));
		if (!PEAR::isError($res))
		{
			while ($row = $res->fetchRow())
			{
				$this->id = $row['id'];
				$this->filename = $row['filename'];
				$this->filetype = $row['filetype'];
				$mediaConnectId = $row['media_connect_id'];
			}
			$modulo = $mediaConnectId % 100;
			$this->filePath = $modulo."/".$mediaConnectId;

			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Return file ID
	 *
	 * @return integer File ID
	 */
	function getId()
	{
		return $this->id;
	}
	
	function getIdByFilename($filename)
	{
		$db = $GLOBALS['dao']->getConnection();
		$res = $db->getOne('SELECT id FROM '.DB_PREFIX.'media WHERE filename = ?', array($filename));	
		return $res;
	}

	/**
	 * Return meta filetype
	 *
	 * @return int Filetype ID
	 */
	function getMetaFiletype()
	{
		switch(substr(strtolower(strchr($this->filename, '.')), 1)) {
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
				return MEDIA_TYPE_IMAGE;
			case 'wav':
			case 'wave':
			case 'mp3':
			case 'mid':
			case 'midi':
				return MEDIA_TYPE_SOUND;
			case 'mov':
			case 'avi':
			case 'mpg':
			case 'mpeg':
				return MEDIA_TYPE_VIDEO;
			case 'swf':
			case 'flv':
				return MEDIA_TYPE_FLASH;
			case 'pdf':
				return MEDIA_TYPE_DOCUMENT;
			default:
				return MEDIA_TYPE_UNKNOWN;
		}
		
		return; 
	}

	/**
	 * Return filename
	 *
	 * @return string Filname
	 */
	function getFilename()
	{
		return $this->filename;
	}
	
	/**
	 * Return the filepath
	 *
	 * @return string FilePAth
	 */
	function getFilePath()
	{
		return $this->filePath;
	}
	
	/*
		Find the Block type for a media connect id
		@pram integer media connect id
	*/
	
	function getBlockTypeTable($mediaConnectId)
	{
		$res = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'item_blocks WHERE media_connect_id = ?', array($mediaConnectId));
		if ($res > 0) {
			@$res = $this->db->query('SELECT * FROM '.DB_PREFIX.'item_blocks WHERE media_connect_id = ?', array($mediaConnectId));
			return $res;
		}
		
		else {
			$res = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'info_blocks WHERE media_connect_id = ?', array($mediaConnectId));
		}
		if ($res > 0) {
			@$res = $this->db->query('SELECT * FROM '.DB_PREFIX.'info_blocks WHERE media_connect_id = ?', array($mediaConnectId));
			return $res;
		}
		
		else {
			$res = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'feedback_blocks WHERE media_connect_id = ?', array($mediaConnectId));
		}
		if ($res > 0) {
			@$res = $this->db->query('SELECT * FROM '.DB_PREFIX.'feedback_blocks WHERE media_connect_id = ?', array($mediaConnectId));
			return $res;
		}

		
		else {
			$res = $this->db->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'item_style WHERE logo = ?', array($mediaConnectId));
		}
		if ($res > 0) {
			@$res = $this->db->query('SELECT * FROM '.DB_PREFIX.'item_style WHERE logo = ?', array($mediaConnectId));
			return $res;
		}

	}
	
}

?>
