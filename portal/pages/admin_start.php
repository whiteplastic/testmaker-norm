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
require_once(PORTAL.'BlockPage.php');

/**
 * Displays a test block tree
 *
 * Default action: {@link doStart()}
 *
 * @package Portal
 */
class AdminStartPage extends BlockPage
{
	/**
	 * @access private
	 */
	var $defaultAction = "start";

	function doStart()
	{
		$this->tpl->loadTemplateFile("AdminStartPage.html");

		// Non-privileged users don't get to see this
		$user = $GLOBALS['PORTAL']->getUser();
		if (!$user->isSpecial())
			redirectTo('test_listing', array());

		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.admin_start.title"));

		$this->tpl->show();
	}

	function doCreateContainer()
	{
		$this->checkAllowed('create', true, NULL);

		$data['title'] = T('pages.block.root_container_block_new').' '.(count($this->block->getChildren()) + 1);

		if($newBlock = $this->block->createChild(BLOCK_TYPE_CONTAINER, $data)) {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_success', MSG_RESULT_POS, $data);
		} else {
			$GLOBALS["MSG_HANDLER"]->addMsg('pages.block.msg.created_failure', MSG_RESULT_NEG, $data);
		}

		redirectTo('container_block', array('action' => 'edit', 'working_path' => '_0_'.$newBlock->getId().'_', 'resume_messages' => 'true'));
	}

}

?>
