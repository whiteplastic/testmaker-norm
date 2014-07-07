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
 * Include base file browser class
 */
require_once(PORTAL.'FCKPage.php');
require_once(CORE.'types/FileHandling.php');
require_once(CORE.'types/MimeTypes.php');

/**
 * File browser page
 *
 * @package Portal
 */
class BrowserPage extends FCKPage
{
	/**#@+
	 * @access private
	 */
	var $block;
	var $workingPath;
	var $item;
	var $answer;
	/**#@-*/

	/**
	 * Init block structur
	 */
	function init($workingPath, $itemId = NULL, $answerId = NULL, $fileType)
	{
		$this->workingPath = $workingPath;
		$ids = $this->splitPath($this->workingPath);
		$blocks = array();
		$blocks[] = $GLOBALS["BLOCK_LIST"]->getBlockById(0);
		for($i = 1; $i < count($ids); $i++)
		{
			$blocks[] = $blocks[count($blocks) - 1]->getChildById($ids[$i]);
		}
		$this->blocks = $blocks;
		$this->block = end($blocks);

		$this->tpl->setGlobalVariable('item_id', $itemId);
		$this->tpl->setGlobalVariable('working_path', $workingPath);
		$this->tpl->setGlobalVariable('answer_id', $answerId);
		$this->tpl->setGlobalVariable('type', $fileType);

		return TRUE;
	}

	/**
	 * Create filelisting
	 */
	function doDefault()
	{
		$workingPath = get('working_path');
		$itemId = get('item_id');
		$answerId = get('answer_id');
		$fileType = get('type');
		$settings = get('settings', false);

		$this->tpl->setCallbackFunction('media', 'sigmaMedia');
		$this->loadDocumentFrame();

		if ($settings)
		{
			$media_connect_id = Setting::get('intro_page_mc_id');
		}
		else
		{
			if (! $this->init($workingPath, $itemId, $answerId, $fileType)) {
				return;
			}

			$deleteLink = '';

			$media_connect_id = $this->block->getMediaConnectId();
		}

		if ($settings) {
			$this->tpl->setVariable('url', linkTo('browser', array('action' => 'upload_media', 'settings' => 'true', 'type' => $fileType)));
		}
		elseif ($this->answer)
		{
			$this->tpl->setVariable('url', linkTo('browser', array('action' => 'upload_media', 'working_path' => $workingPath, 'item_id' => $itemId, 'answer_id' => $answerId, 'type' => $fileType)));
		}
		else
		{
			$this->tpl->setVariable('url', linkTo('browser', array('action' => 'upload_media', 'working_path' => $workingPath, 'item_id' => $itemId, 'type' => $fileType)));
		}

		$mediaList = array();
		$filehandling = new FileHandling();
		if ($media_connect_id)
		{
			if ($fileType == 'image') $mediaList = $filehandling->listMedia($media_connect_id, MEDIA_TYPE_IMAGE);
			elseif ($fileType == 'flash') $mediaList = $filehandling->listMedia($media_connect_id, MEDIA_TYPE_FLASH);
		}
		foreach(MimeTypes::$knownTypes as $mediaTypes) {
			$types[] = $mediaTypes["filetype"];
		}	
		$types = array_unique($types);
		foreach($types as $type) {
			$this->tpl->setVariable('mediatype', $type);
			$this->tpl->parse('medialist');
		}
		if ($mediaList)
		{
			foreach ($mediaList as $media)
			{
				$this->tpl->parse('cell_block');
				$this->tpl->setVariable('filename', $media->getFilePath()."/".$media->getFilename());
				$this->tpl->setVariable('para_id', $media->getId());

				if ($settings)
				{
					$deleteLink = linkTo('browser', array('action' => 'delete_media', 'settings' => 'true', 'type' => $fileType));
				}
				elseif ($this->answer)
				{
					$deleteLink = linkTo('browser', array('action' => 'delete_media', 'working_path' => $workingPath, 'item_id' => $itemId, 'answer_id' => $this->answer->getId(), 'type' => $fileType, 'media' => $media->getId()));
				}
				else
				{
					$deleteLink = linkTo('browser', array('action' => 'delete_media', 'working_path' => $workingPath, 'item_id' => $itemId, 'type' => $fileType, 'media' => $media->getId()));
				}
				$this->tpl->setVariable('del_link', $deleteLink);
			}
		}
		else
		{
			$this->tpl->touchBlock('empty_block');
			$this->tpl->hideBlock('full_block');
		}

		$this->tpl->show();
	}

	function doUploadMedia()
	{
		$workingPath = get('working_path');
		$itemId = get('item_id');
		$answerId = get('answer_id');
		$fileType = get('type');
		$settings = get('settings', false);

		if (!$settings)
		{
			if (! $this->init($workingPath, $itemId, $answerId, $fileType)) {
				return;
			}
		}

		$fileHandling = new FileHandling();

		$expectedFiletype = 0;
		if ($fileType == 'image') $expectedFiletype = MEDIA_TYPE_IMAGE;
		elseif ($fileType == 'flash') $expectedFiletype = MEDIA_TYPE_FLASH;
		
		if ($_FILES['media'])
		{
			$mimeType = $_FILES['media']['type'];
			if (! array_key_exists($mimeType, MimeTypes::$knownTypes)) 
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.media_organizer.upload_failed.mime', MSG_RESULT_NEG, array('file' => $_FILES['media']['name']));
			}
			else if(preg_match('/[^a-zA-Z0-9._-]/',$_FILES['media']['name']))
			{
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.media_organizer.upload_failed.filename', MSG_RESULT_NEG, array('file' => $_FILES['media']['name']));
			}
			else
			{
				$mediaConnectId = 0;
				if ($settings)
				{
					$mediaConnectId = Setting::get('intro_page_mc_id');
				}
				else
				{
					$mediaConnectId = $this->block->getMediaConnectId();
				}
				if ($mediaConnectIdTmp = $fileHandling->uploadMedia('media', $mediaConnectId, true))
				{
					$mediaConnectIdTmp = current($mediaConnectIdTmp);
					if ($settings)
					{
						Setting::set('intro_page_mc_id', $mediaConnectIdTmp);
					}
					else
					{
						$this->block->modify(array('media_connect_id' => $mediaConnectIdTmp));
					}
					$GLOBALS["MSG_HANDLER"]->addMsg('pages.media_organizer.upload_success', MSG_RESULT_POS, array('file' => $mediaConnectId.'_'.$_FILES['media']['name']));
				}
				else
				{
					$GLOBALS["MSG_HANDLER"]->addMsg('pages.media_organizer.upload_failed', MSG_RESULT_NEG, array('file' => $_FILES['media']['name']));
				}
			}
		}
		else
		{
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.media_organizer.upload_error', MSG_RESULT_NEG);
		}

		if ($settings)
		{
			redirectTo('browser', array('settings' => 'true', 'type' => $fileType, 'resume_messages' => 'true'));
		}
		if ($this->answer) {
			redirectTo('browser', array('working_path' => $workingPath, 'item_id' => $itemId, 'answer_id' => $this->working->getId(), 'type' => $fileType, 'resume_messages' => 'true'));
		}
		redirectTo('browser', array('working_path' => $workingPath, 'item_id' => $itemId, 'type' => $fileType, 'resume_messages' => 'true'));
	}

	function doDeleteMedia()
	{
		$workingPath = get('working_path');
		$itemId = get('item_id');
		$answerId = get('answer_id');
		$fileType = get('type');
		$delete = post('id');
		$settings = get('settings', false);
		if (!$settings && !$this->init($workingPath, $itemId, $answerId, $fileType)) {
			return;
		}
		$fileHandling = new FileHandling();
		if ($fileHandling->deleteMedia($delete)) {
			sendXMLStatus('', array('type' => 'ok', 'id' => $delete));
		}
		sendXMLMessages();
	}
}

?>
