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
 * Loads the base class
 */
require_once(PORTAL.'ManagementPage.php');
require_once(CORE.'types/MimeTypes.php');

/**
 * Allows editing the intro page for normal users
 *
 * Default action: {@link doEditLogoPage()}
 *
 * @package Portal
 */
class LogoManagementPage extends ManagementPage
{
	/**
	 * @access private
	 */
    var $defaultAction = "edit_logo_page";
	var $db;

	function doEditLogoPage()
	{
		$this->checkAllowed('admin', true);

		require_once(CORE.'types/FileHandling.php');

		$this->tpl->loadTemplateFile("EditLogoPage.html");
		$this->initTemplate("logo_management");

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);

		$this->tpl->setVariable('page_title', T('pages.general_management.title'));

		$this->tpl->show();
	}

	function doSaveLogoPage()
	{
		$this->db = &$GLOBALS['dao']->getConnection();
		$this->checkAllowed('admin', true);
		if (isset($_POST['b1'])) {
			
		
			require_once(CORE.'types/FileHandling.php');
			$fileHandling = new FileHandling();
			$fileName = "LOGO_".$_FILES['logo']['name'];
			if (!array_key_exists($_FILES['logo']['type'], MimeTypes::$knownTypes))  {
						$GLOBALS['MSG_HANDLER']->addMsg('pages.media_organizer.upload_failed.mime', MSG_RESULT_NEG, array('file' => $_FILES['logo']['name']));
			}
			else {
				$query = "SELECT content FROM ".DB_PREFIX."settings WHERE name=?";
				$result = $this->db->getOne($query, array("main_logo"));
				
				if($result == NULL) {
					$query = "INSERT INTO ".DB_PREFIX."settings (name, content) VALUES(?, ?)";
					$result = $this->db->query($query, array("main_logo",$fileName));
				}
				else {
					@unlink('upload/media/'.$result);
					$query = "UPDATE ".DB_PREFIX."settings SET content=? WHERE name=?";
					$result = $this->db->query($query, array($fileName,"main_logo"));
				}
				move_uploaded_file($_FILES['logo']['tmp_name'], ROOT.'upload/media/'.$fileName);
			}
			redirectTo("logo_management", array("action" => "edit_logo_page"));	
		}
		//Standard Logo
		else {
			require_once(CORE.'types/FileHandling.php');
			$fileHandling = new FileHandling();
			$query = "SELECT content FROM ".DB_PREFIX."settings WHERE name=?";
			$result = $this->db->getOne($query, array("main_logo"));
			if($result != NULL) {
				@unlink('upload/media/'.$result);
				$query = "DELETE FROM ".DB_PREFIX."settings WHERE name=?";
				$result = $this->db->query($query, array("main_logo"));
			}
			redirectTo("logo_management", array("action" => "edit_logo_page"));
		}
	}

}


