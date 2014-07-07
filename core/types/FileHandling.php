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
 * Include the Medium class
 */
require_once(CORE.'types/Medium.php');

/**
 * Upload class for templates and media files
 *
 * @package Core
 */
class FileHandling
{
	/**
	 * @access private
	 */
	var $db;
	var $fileDirectory;
	var $filetype_image 	= array('jpg', 'jpeg', 'gif', 'png');
	var $filetype_flash 	= array('swf','flv');
	var $filetype_document	= array('pdf');

	/**
	 * Constructor
	 */
	function FileHandling()
	{
		$this->db = &$GLOBALS['dao']->getConnection();

		$this->fileDirectory = ROOT.'upload/';
		if (!is_dir($this->fileDirectory))
		{
			trigger_error($this->fileDirectory.' does not exist.');
		}
		if (!is_writeable($this->fileDirectory))
		{
			trigger_error($this->fileDirectory.' is not writeable.');
		}
	}

	/**
	 * return directory for uploaded files
	 * @return string directory
	 */
	function getFileDirectory() {
		return $this->fileDirectory;
	}

	/**
	 * Returns a media connect iD
	 * @param integer Media connect ID to use; if empty, generate one
	 * @return integer
	 */
	function getMediaConnectId($mediaConnectId)
	{
		if ($mediaConnectId == NULL || trim($mediaConnectId) == '') {
			$mediaConnectId = $this->db->nextId(DB_PREFIX.'media_connect');
		}
		return $mediaConnectId;
	}

	/**
	 * Upload a mediafile and create a link between the media and a block
	 *
	 * @param string Filename (or name of filename field if file was uploaded)
	 * @param integer media connect id
	 * @param Whether the file was uploaded (true) or is already in place (false)
	 * @param integer id of the new block when copy a block
	 * @return integer media connect id
	 */
	function uploadMedia($mediaName, $mediaConnectId = NULL, $needsMove = true)
	{
		$umlaut = array('ä', 'ö', 'ü', 'ß');
		$escape = array('ae', 'oe', 'ue', 'ss');

		$uplName = str_replace($umlaut, $escape, ($needsMove ? $_FILES[$mediaName]['name'] : basename($mediaName)));
		$filetype = $this->locateFiletype($uplName);
		if (!$filetype) {
			return false;
		}

		$mediaConnectId = $this->getMediaConnectId($mediaConnectId);

		$filename = $mediaConnectId.'_'.$uplName;
		if ($needsMove) {
			$filename = $this->uploadFile($mediaName, $filename, $mediaConnectId);
			if (!$filename) return false;
		}

		$id = $this->db->nextId(DB_PREFIX.'media');
		$query = "INSERT INTO ".DB_PREFIX."media (id, media_connect_id, filename, filetype) VALUES(?, ?, ?, ?)";
		$result = $this->db->query($query, array($id, $mediaConnectId, $filename, $filetype));

		if (PEAR::isError($result)) {
			return false;
		}

		return array('mediaConnectId' => $mediaConnectId, 'newFilename' => $filename , 'id' => $id);
	}

	/**
	 * Locate filetype
	 *
	 * @param string Filename
	 * @return integer Filetype
	 */
	function locateFiletype($filename)
	{
		$filetypetmp = strtolower(substr(strrchr($filename, '.'), 1));
		$filetype = false;
		if (in_array($filetypetmp, $this->filetype_image)) { $filetype = MEDIA_TYPE_IMAGE; }
		else if (in_array($filetypetmp, $this->filetype_flash)) { $filetype = MEDIA_TYPE_FLASH; }
		else if (in_array($filetypetmp, $this->filetype_document)) { $filetype = MEDIA_TYPE_DOCUMENT; }
		else $filetype = MEDIA_TYPE_UNKNOWN;
		return $filetype;
	}

	/**
	 * Copy a mediafile and create a link between the media and a block
	 *
	 * @param integer media id of medium which should be copied
	 * @param integer optionally media connect id of target
	 * @param integer id of the new block when copy a block
	 * @return integer media connect id
	 */
	function copyMedia($mediaId, $mediaConnectId = NULL, $newNodeId = NULL)
	{

		$query = "SELECT filename FROM ".DB_PREFIX."media WHERE id=?";
		$filename = $this->db->getOne($query, array($mediaId));

		//strip old media connect id
		$filename = substr(strchr($filename, '_'), 1);

		if (PEAR::isError($filename)) {
			return false;
		}

		$filetypetmp = strtolower(substr(strrchr($filename, '.'), 1));
		$filetype = false;
		if (in_array($filetypetmp, $this->filetype_image)) { $filetype = MEDIA_TYPE_IMAGE; }
		if (in_array($filetypetmp, $this->filetype_flash)) { $filetype = MEDIA_TYPE_FLASH; }
		if (in_array($filetypetmp, $this->filetype_document)) { $filetype = MEDIA_TYPE_DOCUMENT;}
		if (!$filetype) return false;

		if($mediaConnectId == NULL || trim($mediaConnectId) == '') {
			$mediaConnectId = $this->db->nextId(DB_PREFIX.'media_connect');
		}

		$newFilename = $mediaConnectId.'_'.$filename;
		

		$id = $this->db->nextId(DB_PREFIX.'media');
		$query = "INSERT INTO ".DB_PREFIX."media (id, media_connect_id, filename, filetype) VALUES(?, ?, ?, ?)";
		$result = $this->db->query($query, array($id, $mediaConnectId, $newFilename, $filetype));

		
		if(!$this->copyFile($mediaId, $id, $newNodeId, $mediaConnectId)) {
			return false;
		}

		if (PEAR::isError($result)) {
			return false;
		}

		return $mediaConnectId;
	}

	/**
	 * Delete a file and the link between the file and his block
	 *
	 * @param integer media ID
	 * @return boolean Delete action was successful or not
	 */
	function deleteMedia($mediaId)
	{
		return $this->deleteFile($mediaId);
	}

	/**
	 * Delete a file and the link between the file and his block by it's file name
	 * (uses deleteMedia function)
	 * 
	 * @param String file name of the media
	 * @return boolean Delete action was successful or not
	 */
	function deleteMediaByFilename($filename)
	{
		$mediaId = $this->db->getOne("SELECT id FROM ".DB_PREFIX."media WHERE filename=?", array($filename));
		return $this->deleteMedia($mediaId);
	}

	/**
	 * Delete all files in given media connection and all database links
	 *
	 * @param integer media ID
	 * @return boolean Delete action was successful or not
	 */
	function deleteMediaConnection($mediaConnectId)
	{
		// check if media is a logo and if it is used more than once
		// only delete actual file, if no other test uses this logo
		
		$logoUses = $this->db->getOne("SELECT COUNT(id) FROM ".DB_PREFIX."item_style WHERE logo = ".$mediaConnectId);

		if($logoUses == 1)
		{
			$query = 'SELECT * FROM '.DB_PREFIX.'media WHERE media_connect_id = ?';
			$result = $this->db->getAll($query, array($mediaConnectId));
			if($this->db->isError($result)) {
				return false;
			}
	
			for($i = 0; $i < count($result); $i++)
			{
				if(!$this->deleteMedia($result[$i]['id'])) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Copy all files in given media connection and all database links
	 *
	 * @param integer media ID of media connection whoch should be copied
	 * @param integer id of the new block when copy a block
	 * @return integer media connect id of new connection
	 */
	function copyMediaConnection($mediaConnectId, $newNodeId)
	{

		$query = 'SELECT * FROM '.DB_PREFIX.'media WHERE media_connect_id = ?';
		$result = $this->db->getAll($query, array($mediaConnectId));
		if($this->db->isError($result)) {
			return false;
		}

		$newMediaConnectId = $this->db->nextId(DB_PREFIX.'media_connect');

		for($i = 0; $i < count($result); $i++)
		{
			if(!$this->copyMedia($result[$i]['id'], $newMediaConnectId, $newNodeId)) {
				return false;
			}
		}

		return $newMediaConnectId;
	}

	/**
	 * Upload a mediafile to the media directory
	 *
	 * @param string Filename
	 * @param string Upload subdirectory
	 * @param string optionally: new file target name
	 * @return boolean Upload action was successful or not
	 */
	function uploadFile($file, $newFilename = NULL, $mediaConnectId)
	{

		$modulo = $mediaConnectId % 100;
		$dirname = ROOT."upload/media/".$modulo;
		if (!is_dir($dirname)) 
			mkdir($dirname);
	
		$dirname2 = ROOT."upload/media/".$modulo."/".$mediaConnectId;
		if (!is_dir($dirname2)) 
			mkdir($dirname2);
		
		$dir = $dirname2."/";
		if (!is_dir($dir)) 
		{
			trigger_error($this->fileDirectory.$dir.' not exists.');
		}

		if($newFilename == NULL) {
			$newFilename = $_FILES[$file]['name'];
		}

		if (file_exists($this->fileDirectory.$dir.$newFilename))
		{
			// Divide file name into name and extension if possible, else take it as name with empty extension
			if (preg_match("#^(.*)(\\.[^\\.]+)$#", $newFilename, $match)) {
				$firstPart = $match[1];
				$secondPart = $match[2];
			}
			else {
				$firstPart = $newFilename;
				$secondPart = "";
			}

			// Find a unique filename by appending _2, _3 and so forth
			$i = 2;
			do {
				$newFilename = $firstPart."_".($i++).$secondPart;
			} while (file_exists($dir.$newFilename));
		}
		$destFileName = $dir.$newFilename;
		if (!move_uploaded_file($_FILES[$file]['tmp_name'] , $destFileName))
		{
			return false;
		}
		chmod($destFileName, 0644);

		return $newFilename;
	}

	/**
	 * Delete a Mediafile
	 *
	 * @param integer Media ID
	 * @return boolean Delete action was successful or not
	 */
	function deleteFile($id)
	{
		$query = "SELECT filename, media_connect_id FROM ".DB_PREFIX."media WHERE id=?";
		@$res = $this->db->query($query, array($id));
		$res->fetchInto($row);
		
		if (PEAR::isError($row['filename'])) {
			return false;
		}

		//Only one, because media connect id is unique
		$modulo = $row['media_connect_id'] % 100;
		$name = $this->fileDirectory."media/".$modulo."/".$row['media_connect_id']."/".$row['filename'];
		
		// If the file doesn't exist, we just assume it was deleted
		// manually and proceed anyway
		if(file_exists($name) && !is_dir($name) && !unlink($name)) {
				return false;
		}

		$query = "DELETE FROM ".DB_PREFIX."media WHERE id=?";
		$result = $this->db->query($query, array($id));
		if($this->db->isError($result)) {
			return false;
		}

		return true;
	}

	/**
	 * Copy a Mediafile
	 *
	 * @param integer Media ID of file which should be copied
	 * @param string Upload subdirectory where you want to copy the new file to
	 * @param string optionally: new file target name
	 ' @param the new media connect id for the new block
	 * @return boolean Copy action was successful or not
	 */
	function copyFile($oldId, $newId, $newNodeId, $mediaConnectId)
	{
		$name = $this->getMediaPath($oldId);
		$newName = $this->getMediaPath($newId, $newNodeId, $mediaConnectId);
		$this->newMediaDir($oldId, $newNodeId, $mediaConnectId);
		
		if(file_exists($newName) OR !file_exists($name)) {
			return false;
		}

		if (!copy($name , $newName))
		{
			return false;
		}

		return true;
	}

	/**
	 * Return a list of all Medias in Database
	 *
	 * @param integer Media connect id
	 * @param integer Filetype
	 * @return mixed Media files in Database
	 */
	function listMedia($mediaConnectId = NULL, $filetype = NULL)
	{
		if (!$mediaConnectId && !$filetype) return;
		$mediaList = array();

		$infos = array();
		$query = "SELECT id FROM ".DB_PREFIX."media";
		if ($mediaConnectId  && $filetype )
		{
			$query .= " WHERE media_connect_id = ? AND filetype = ?";
			$infos[] = $mediaConnectId;
			$infos[] = $filetype;
		}
		elseif ($mediaConnectId )
		{
			$query .= " WHERE media_connect_id = ?";
			$infos[] = $mediaConnectId;
		}
		elseif ($filetype)
		{
			$query .= " WHERE filetype = ?";
			$infos[] = $filetype;
		}
		$result = $this->db->query($query, $infos);

		if (PEAR::isError($result)) {
			return false;
		}
		while ($media = $result->fetchRow())
		{
			$mediaList[] = new Medium($media['id']);
		}

		return $mediaList;
	}


	/**
	* Return the complete path to the media file for a media
	  @param integer id of the media
	  @param integer id of the new block when copy a block
	  @param integer mediaConnectId of the new block when copy a block
	*/
	function getMediaPath($id, $newNodeId = NULL, $mediaConnectId = NULL, $absolute = true)
	{
		$query = "SELECT filename FROM ".DB_PREFIX."media WHERE id=?";
		$fileName = $this->db->getOne($query, array($id));

		if (PEAR::isError($fileName)) {
			return false;
		}
			
		$query = "SELECT filename, media_connect_id FROM ".DB_PREFIX."media WHERE id=?";
		@$res = $this->db->query($query, array($id));
		$res->fetchInto($row);

		if (PEAR::isError($row['filename'])) {
			return false;
		}

		$mediaConnectId = $row['media_connect_id'];
		
		$modulo = $mediaConnectId % 100;

		if ($absolute) 
			$name = $this->fileDirectory."media/".$modulo."/".$mediaConnectId."/".$row['filename'];
		else
			$name = "upload/media/".$modulo."/".$mediaConnectId."/".$row['filename'];
		return $name;
	}
	/**
	  * make a diretory for a media. Name of the directory depends on the media connect id.
	  @param integer id of the media
	  @param integer id of the new block when copy a block
	  @param integer mediaConnectId of the new block when copy a block
	  @return result of the block table
	 */
	
	function newMediaDir($id, $newNodeId = NULL, $mediaConnectId)
	{
		$query = "SELECT filename FROM ".DB_PREFIX."media WHERE id=?";
		$fileName = $this->db->getOne($query, array($id));

		if (PEAR::isError($fileName)) {
			return false;
		}
		
		$query = "SELECT filename, media_connect_id FROM ".DB_PREFIX."media WHERE id=?";
		@$res = $this->db->query($query, array($id));
		$res->fetchInto($row);
		
		if (PEAR::isError($row['filename'])) {
			return false;
		}

		$modulo = $mediaConnectId % 100;
	
		$dirname = ROOT."upload/media/".$modulo;
		if (!is_dir($dirname)) 
			mkdir($dirname);
	
		$dirname2 = ROOT."upload/media/".$modulo."/".$mediaConnectId;

		if (!is_dir($dirname2)) 
			mkdir($dirname2);
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
