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
 
require_once(PORTAL.'BlockPage.php');
require_once("lib/utilities/StopWatch.php");
require_once(ROOT.'portal/init.php');
require_once(CORE.'init/settings.php');
require_once(CORE.'environment/DataAccess.php');
require_once(CORE.'init/configuration.php');

class ConfirmAccountPage extends BlockPage
{

	function doConfirm()
	{
		$dao = new DataAccess(DB_TYPE, DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
		$dao->connect();
		$db = $dao->getConnection();

		$id = $_GET["id"];
		$passwh = $_GET["passwh"];
		$last_login = time();

		
		$result = $db->query("UPDATE ".DB_PREFIX."users SET last_login =  ?, delete_time = ? WHERE id = ? AND password_hash = ? ", array($last_login, NULL, $id, $passwh));
		$result = $db->query("SELECT * FROM ".DB_PREFIX."users WHERE id = ? AND password_hash = ?", array($id, $passwh));
		$numRows = $result->numRows();
		if ($numRows > 0) {
			
			$this->tpl->loadTemplateFile("ConfirmAccount.html");
			$this->tpl->touchBlock("confirm");
			$body = $this->tpl->get();
			$this->loadDocumentFrame();
			$this->tpl->setVariable("body", $body);
			$this->tpl->show();
		}
		if ($numRows == 0) {
			
			$this->tpl->loadTemplateFile("ConfirmAccount.html");
			$this->tpl->touchBlock("confirmNo");
			$body = $this->tpl->get();
			$this->loadDocumentFrame();
			$this->tpl->setVariable("body", $body);
			$this->tpl->show();
		}
	}
}

?>