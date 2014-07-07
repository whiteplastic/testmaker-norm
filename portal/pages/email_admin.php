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
 * include UserList
 */
require_once(CORE.'types/UserList.php');

/**
 * Loads the base class
 */
require_once(PORTAL.'AdminPage.php');
require_once(CORE."types/Email.php");
require_once(CORE."types/TestRun.php");
require_once(CORE."types/TestRunList.php");

libLoad('utilities::getCorrectionMessage');

/**
 * Load page selector widget
 */
require_once(PORTAL.'PageSelector.php');


/**
 * Allows an adminstrator to manage user accounts
 *
 * Default action: {@link doListUser()}
 *
 * @package Portal
 */
class EmailAdminPage extends AdminPage
{
    /**
	 * @access private
	 */
	var $defaultAction = "edit_email";
	var $correctionMessages = array();
    var $db;

	/**
	 * Creates a new user without an activation key
	 */
    
    
    
    function doEditEmail()
	{
		require_once(CORE."types/TestRunList.php");

		$this->checkAllowed('edit', true);

		$this->tpl->loadTemplateFile("ManageEmail.html");
		$this->initTemplate("edit_email");

		// Create & Populate Email Form
		foreach($GLOBALS["BLOCK_LIST"]->getTestList() as $test) {
                $tmpTitle = shortenString($test->getTitle(),64);
				$this->tpl->setVariable('test_title', $tmpTitle);
				$this->tpl->setVariable('test_id', $test->getId());
				$this->tpl->parse('tests');
		}

		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);
		$this->tpl->setVariable('page_title', T('menu.user.admin'));
		$this->tpl->show();
   }
 
   function doGetEmailAddresses()
   {
        $this->db = &$GLOBALS['dao']->getConnection();
        
        $testRunList = new TestRunList();
        
		$testId = $_POST["title"];
        $testRuns = $testRunList->getTestRunsForTest($testId,TRUE,NULL);
        $addresses = array();

        foreach($testRuns as $testRun) {
            $userId = $testRun->getUserId();
            $query = "SELECT email FROM ".DB_PREFIX."users WHERE id = ?";
            $addresses[$userId] =  $this->db->getOne($query, array($userId));
        }

		// build with list of email addresses in temp directory
		$random = sprintf("%04d", rand(0, 9999));
		$tempFileName = TM_TMP_DIR."/email_list_".$random.".txt";
		$tempFile = fopen($tempFileName, "w+");
		foreach($addresses as $address => $values)
		{
			if(!fwrite($tempFile,$address."  --  ".$values."\n"))
			{
				$GLOBALS["MSG_HANDLER"]->addMsg("pages.email.get_email_addresses.error_writing_file");
				redirectTo("email_admin", array("action" => "edit_email", "email_id" => $emailId));
			}
		}
		fclose($tempFile);

		// deliver the file
		header("Content-Type: text/plain");
		header("Content-Disposition: attachment; filename=\"email_addresses.txt\"");
		readfile($tempFileName);

		unlink($tempFileName);
   }

}
