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


function preInitErrorPage($message, $args = array())
{
	libLoad('html::Sigma');
	libLoad('environment::MsgHandler');
	MsgHandler::install();

	$tpl = new Sigma(ROOT."portal/templates/");


	if (empty($result)) 
			$logo = "portal/images/tm-logo-sm.png";
		else 
			$logo = "upload/media/".$result;


	$body = T($message, $args);
	$tpl->loadTemplateFile('PreInitErrorPage.html');
	$tpl->setVariable('message', $body);
	$body = $tpl->get();

	$tpl->loadTemplateFile('BareFrame.html');
	$tpl->setVariable('body', $body);
	$tpl->setVariable('logo', $logo);
	$tpl->setVariable('page_title', T('html.pre_init_error_page.title'));
	$tpl->show();
	exit;
}

?>
