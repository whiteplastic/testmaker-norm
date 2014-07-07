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
 * Shows a list of general configuration tasks
 *
 * Default action: {@link doList()}
 *
 * @package Portal
 */
/**
 * Include necessary files
 */
require_once(CORE.'types/TemplateList.php');

/**
 * Loads the base class
 */
require_once(PORTAL.'ManagementPage.php');

class TemplateManagementPage extends ManagementPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "manage_templates";
	
	function doManageTemplates()
	{
		$this->checkAllowed('admin', true);
		
		$body = "";
		
		$templatesList = new TemplateList();
		$templates = $templatesList->getList();
		
		$this->tpl->loadTemplateFile("ManageTemplateFiles.html");
		$this->initTemplate("manage_templates");
		
		if(count($templates) > 0)
		{
			$this->tpl->touchBlock("template_list");
			
			foreach($templates as $key => $value)
			{
				$this->tpl->setVariable("templateid", $value['id']);
				$this->tpl->setVariable("templatename", $key);
				if($value['editable'] == 1) 
				{
					$this->tpl->touchBlock("edit_template");
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
	
	function doEditTemplate()
	{
		$this->checkAllowed('admin', true);
		
		$body = "";
		
		$id = get("id", -1);
		$templatesList = new TemplateList();
		$template = $templatesList->getTemplate($id);

		if($template['editable'] != 1) redirectTo("template_management");
		
		$this->tpl->loadTemplateFile("EditTemplate.html");
		$this->initTemplate("");
		
		if($id != -1)
		{
			$this->tpl->setVariable('templatename', $template['name']);
			$this->tpl->setVariable('templateid', $id);
			if(isset($template['image_type']) && $template['image_type'] != "") $imageLink = "upload/item_templates/{$template['name']}.{$template['image_type']}";
			else $imageLink = "portal/images/default_template_thumbnail.png";
			$this->tpl->setVariable('imagelink', $imageLink);
			$this->tpl->setVariable('description_de', $template['description_de']);
			$this->tpl->setVariable('description_en', $template['description_en']);
		}
		else 
		{
			$GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.template_doesnt_exist', MSG_RESULT_NEG, array('id' => $id));
		}
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.manage_templates.title'));
		$this->tpl->show();
	}

	function doSaveTemplate()
	{
		$id = get("id", -1);
		$templatesList = new TemplateList();
		if($id != -1)
		{
			$descGerman = post("templateDescriptionGerman", "");
			$descEnglish = post("templateDescriptionEnglish", "");
			//echo "German: ".$descGerman.", english: ".$descEnglish;
			if($descGerman != "" && $descEnglish != "")
			{
				if(!$templatesList->updateTemplateDescriptions($id, $descGerman, $descEnglish))
				{
					$GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.error_writing_database', MSG_RESULT_NEG);
				}
			}
			else $GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.no_description', MSG_RESULT_NEG);
		}
		else $GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.template_doesnt_exist', MSG_RESULT_NEG, array('id' => $id));
		
		redirectTo('template_management');
	}
	
	function doDeleteTemplate()
	{
		$id = get("id", -1);
		$templatesList = new TemplateList();
		if($id != -1)
		{
			$template = $templatesList->getTemplate($id);
			if(!unlink(ROOT."upload/item_templates/".$template['name'].".html") 
				|| !unlink(ROOT."upload/item_templates/".$template['name'].".".$template['image_type']))
			{
				$GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.error_deleting_files', MSG_RESULT_NEG);		
			}
			if(!$templatesList->deleteTemplate($id))
			{
				$GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.error_writing_database', MSG_RESULT_NEG);
			}
			
		}
		else $GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.template_doesnt_exist', MSG_RESULT_NEG, array('id' => $id));
		
		redirectTo('template_management');
	}
	
	function doViewTemplate()
	{
		$this->checkAllowed('admin', true);
		
		$body = "";
		
		$id = get("id", -1);
		$templatesList = new TemplateList();
		$template = $templatesList->getTemplate($id);
		
		$this->tpl->loadTemplateFile("ViewTemplate.html");
		$this->initTemplate("");
		
		if($id != -1)
		{
			$this->tpl->setVariable('templatename', $template['name']);
			if(isset($template['image_type']) && $template['image_type'] != "") $imageLink = "upload/item_templates/{$template['name']}.{$template['image_type']}";
			else $imageLink = "portal/images/default_template_thumbnail.png";
			$this->tpl->setVariable('imagelink', $imageLink);
			$this->tpl->setVariable('description_de', $template['description_de']);
			$this->tpl->setVariable('description_en', $template['description_en']);
		}
		else 
		{
			$GLOBALS['MSG_HANDLER']->addMsg('pages.manage_templates.msg.template_doesnt_exist', MSG_RESULT_NEG, array('id' => $id));
		}

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('pages.manage_templates.title'));
		$this->tpl->show();
	}

	function doUploadTemplate()
	{
		$errorMessage = "";
		// Upload the template file
		if(!empty($_FILES['uploadTemplateFile']) && $_FILES['uploadTemplateFile']['type'] == "text/html")
		{
			$destinationTemplate = ROOT."/upload/item_templates/".$_FILES['uploadTemplateFile']['name'];
			if(!move_uploaded_file($_FILES["uploadTemplateFile"]["tmp_name"], $destinationTemplate))
			{
				$errorMessage = 'pages.manage_templates.msg.error_moving_file';
			}
		}
		else $errorMessage = 'pages.manage_templates.msg.no_template_file';

		// Upload the preview file
		if($errorMessage == "")
		{
			if(!empty($_FILES['uploadPreviewFile']) 
					&& ($_FILES['uploadPreviewFile']['type'] == "image/png"
					|| $_FILES['uploadPreviewFile']['type'] == "image/jpg"
					|| $_FILES['uploadPreviewFile']['type'] == "image/gif"))
			{
				$temp = explode('.', $_FILES['uploadPreviewFile']['name']);
				$templateName = $temp[0];
				$previewExt = $temp[1];
				$temp = explode('.', $_FILES['uploadTemplateFile']['name']);
				$previewName = $temp[0];
				$name = $templateName;
				if($templateName == $previewName)
				{
					$destinationPreview = ROOT."upload/item_templates/".$_FILES['uploadPreviewFile']['name'];
					$imageSize = @getimagesize($_FILES['uploadPreviewFile']['tmp_name']);
					if(!$imageSize) $imagesize = array(0 => 0, 1 => 0);
					if($imageSize[0] == 150 && $imageSize[1] == 150)
					{
						if(!move_uploaded_file($_FILES["uploadPreviewFile"]["tmp_name"], $destinationPreview))
						{
							if(file_exists($destinationTemplate)) unlink($destinationTemplate);
							$errorMessage = 'pages.manage_templates.msg.error_moving_file';
						}
					}
					else
					{
						if(file_exists($destinationTemplate)) unlink($destinationTemplate);
						$errorMessage = 'pages.manage_templates.msg.wrong_image_size';
					}
				}
				else
				{
					if(file_exists($destinationTemplate)) unlink($destinationTemplate);
					$errorMessage = 'pages.manage_templates.msg.filenames_must_correspond';
				}
			}
			else $errorMessage = 'pages.manage_templates.msg.no_preview_file';
		}

		// Check the descriptions
		$descGerman = post("templateDescriptionGerman", "");
		$descEnglish = post("templateDescriptionEnglish", "");
		if($errorMessage == "")
		{
			// Are both descriptions set
			if($descGerman != "" && $descEnglish != "")
			{
				// Save description
				$templateList = new TemplateList();
				if(!$templateList->saveTemplateDescriptions($name, $previewExt, $descGerman, $descEnglish))
				{
					if(file_exists($destinationTemplate)) unlink($destinationTemplate);
					if(file_exists($destinationPreview)) unlink($destinationPreview);
					$errorMessage = 'pages.manage_templates.msg.error_writing_database';
				}
			}
			else 
			{
				if(file_exists($destinationTemplate)) unlink($destinationTemplate);
				if(file_exists($destinationPreview)) unlink($destinationPreview);
				$errorMessage = 'pages.manage_templates.msg.no_description';	
			}
		}

		// Throw errors or give the success message
		if($errorMessage != "") $GLOBALS['MSG_HANDLER']->addMsg($errorMessage, MSG_RESULT_NEG);
		else $GLOBALS["MSG_HANDLER"]->addMsg('pages.manage_templates.msg.upload_successfull', MSG_RESULT_POS);

		redirectTo('template_management');
	}
}
?>
