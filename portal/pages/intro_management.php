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

/**
 * Allows editing the intro page for normal users
 *
 * Default action: {@link doEditIntroPage()}
 *
 * @package Portal
 */
class IntroManagementPage extends ManagementPage
{
	/**
	 * @access private
	 */
    var $defaultAction = "edit_intro_page";

	function doEditIntroPage()
	{
		$this->checkAllowed('admin', true);

		// include fckeditor & stuff for mcid
		require_once(PORTAL.'IntroEditor.php');
		require_once(CORE.'types/FileHandling.php');

		$this->tpl->loadTemplateFile("EditIntroPage.html");
		$this->initTemplate("edit_intro_page");

		// create FCKeditor
		$fh = new FileHandling();
		$mcid = $fh->getMediaConnectId(NULL);
		Setting::set('intro_page_mcid', $mcid);
		$editor = new IntroEditor(Setting::get('intro_page'));
		$this->tpl->setVariable('content', $editor->CreateHtml());

		if (Setting::get('intro_page_on') == 0){
			$this->tpl->setVariable('checked', '');
			$this->tpl->setVariable('display', 'none');
		}
		else {
			$this->tpl->setVariable('checked', 'checked=\"checked\"');
			$this->tpl->setVariable('display', 'block');	
		}	
		
		
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable('body', $body);

		$this->tpl->setVariable('page_title', T('pages.general_management.title'));

		$this->tpl->show();
	}

	function doSaveIntroPage()
	{
		$this->checkAllowed('admin', true);

		libLoad("html::defuseScripts");
		libLoad("utilities::deUtf8");
		$content = defuseScripts(deUtf8(post('fckcontent', '')));

		if (!post('enabled', false)) {
			
			if (Setting::set('intro_page_on', 0)) {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.intro_page.msg.deleted_success', MSG_RESULT_POS);
			} else {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.intro_page.msg.deleted_failure', MSG_RESULT_NEG);
			}
		} else {
			if (Setting::set('intro_page', $content)) {
				Setting::set('intro_page_on', 1);
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.intro_page.msg.modified_success', MSG_RESULT_POS);
			} else {
				$GLOBALS["MSG_HANDLER"]->addMsg('pages.intro_page.msg.modified_failure', MSG_RESULT_NEG);
			}
		}

		redirectTo('intro_management', array('resume_messages' => 'true'));
	}

}

?>
