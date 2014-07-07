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
 * Manage and configure the item templates
 *
 * Default action: {@link doManageTemplates()}
 *
 * @package Portal
 */

/**
 * Loads the base class
 */
require_once(PORTAL.'ManagementPage.php');

define('TEMPLATES', ROOT.'upload/items/');

class ItemTemplateManagementPage extends ManagementPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "manage_templates";
	
	function doManageTemplates()
	{
		$this->checkAllowed('admin', true);
		
		$body = "";
		
		$this->tpl->loadTemplateFile("ManageItemTemplates.html");
		$this->initTemplate("item_template_management");

		require_once(CORE.'types/ItemTemplate.php');
		$itemTemplateObj = new ItemTemplate();
		// get a list of the templates pre-filtered by the items per page of this block
		$templates = $itemTemplateObj->getList();
		$templateDir = $itemTemplateObj->getTemplateDir();

		require_once($templateDir.'standardTemplates.php');
		$standardTemplates = StandardTemplates::$templates;

		if(count($templates) > 0)
		{
			$this->tpl->touchBlock("template_list");

			foreach($templates as $name => $data)
			{
				$this->tpl->setVariable("previewimage", $data['image']);
				$this->tpl->setVariable("templatename", $name);
				$this->tpl->setVariable("description_de", $data['description']['de']);
				$this->tpl->setVariable("description_en", $data['description']['en']);
				if(!array_key_exists($name, $standardTemplates)) 
				{
					$this->tpl->touchBlock("delete_template");
				}

				$this->tpl->parse("template_list");
			}
		}
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.general_management.title'));
		$this->tpl->show();
	}

	function doDeleteTemplate()
	{
		$templateName = get('name', '');
		if($templateName != '')
		{
			require_once(CORE.'types/ItemTemplate.php');
			$itemTemplateObj = new ItemTemplate();
			$templateDir = $itemTemplateObj->getTemplateDir();
			libLoad('utilities::snakeToCamel');
			$htmlFile = camelToSnake(str_replace('Item', '', $templateName));
			$imageFile = $htmlFile;
			$htmlFile .= '.html';
			$phpFile = $templateName.'.php';
			// find the image file(s) that are related to the specific item template
			if(file_exists($templateDir.$imageFile.'.jpg')) $imageFile .= '.jpg';
			elseif(file_exists($templateDir.$imageFile.'.png')) $imageFile .= '.png';
			@unlink($templateDir.$phpFile);
			@unlink($templateDir.$htmlFile);
			@unlink($templateDir.$imageFile);
			if(file_exists($templateDir.$phpFile) || file_exists($templateDir.$htmlFile) || file_exists($templateDir.$imageFile)) {
				$GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.error_deleting_files', MSG_RESULT_NEG);		
			}			
		}
		else $GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.template_doesnt_exist', MSG_RESULT_NEG, array('name' => $templateName));
		
		redirectTo('item_template_management');
	}
	
	function doUploadTemplate()
	{
		require_once(CORE.'types/ItemTemplate.php');
		$itemTemplateObj = new ItemTemplate();
		$templateDir = $itemTemplateObj->getTemplateDir();
		// Get file infos
		$phpFile = array('name' => $_FILES['uploadPhpFile']['name'],
				'tmp_name' => $_FILES['uploadPhpFile']['tmp_name'],
				'type' => $_FILES['uploadPhpFile']['type'], 
				'dest' => $templateDir.$_FILES['uploadPhpFile']['name']);
		$htmlFile = array('name' => $_FILES['uploadHtmlFile']['name'],
				'tmp_name' => $_FILES['uploadHtmlFile']['tmp_name'],
				'type' => $_FILES['uploadHtmlFile']['type'],
				'dest' => $templateDir.$_FILES['uploadHtmlFile']['name']);
		$imageFile = array('name' => $_FILES['uploadImageFile']['name'],
				'tmp_name' => $_FILES['uploadImageFile']['tmp_name'],
				'type' => $_FILES['uploadImageFile']['type'], 
				'dest' => $templateDir.$_FILES['uploadImageFile']['name']);
		// Check for correct file types
		if($phpFile['name'] == '' || $htmlFile['name'] == '' || $imageFile['name'] == '') 
		{
			$GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.files_missing', MSG_RESULT_NEG);
			redirectTo('item_template_management');
		}
		if($phpFile['type'] != 'application/octet-stream') $GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.not_php_file', MSG_RESULT_NEG); 
		if($htmlFile['type'] != 'text/html') $GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.not_html_file', MSG_RESULT_NEG);
		if($imageFile['type'] != 'image/jpg' && $imageFile['type'] != 'image/png' && $imageFile['type'] != 'image/gif') $GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.image_type_incorrect', MSG_RESULT_NEG);
		// Upload files
		if(!@move_uploaded_file($phpFile['tmp_name'], $phpFile['dest'])) $GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.error_uploading_php_file', MSG_RESULT_NEG);
		if(!@move_uploaded_file($htmlFile['tmp_name'], $htmlFile['dest'])) $GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.error_uploading_html_file', MSG_RESULT_NEG);
		if(!@move_uploaded_file($imageFile['tmp_name'], $imageFile['dest'])) $GLOBALS['MSG_HANDLER']->addMsg('pages.item_template_management.msg.error_uploading_image_file', MSG_RESULT_NEG);
		
		redirectTo('item_template_management');
	}
}
?>
