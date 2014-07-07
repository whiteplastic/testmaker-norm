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

class FbFeedbackMail extends DynamicDataPlugin
{
	var $title = array(
		'de' => 'Feedback Email',
		'en' => 'Feedback Email',
	);
	var $desc = array(
		'de' => 'Zeigt den Button für die Feedback Email an.',
		'en' => 'Show the button for a feedback email',
	);
	

	function init($generator)
	{
		$this->generator = $generator;
	}
	
	function getOutput($params, $args) 
	{
		return '<a class="Button" href="index.php?page=test_listing&amp;action=show_feedback&amp;email=2&amp;layout=print&amp;test_run='.$params['testRundId'].'" target="_blank">'.T("pages.testmake.send_feedback_email").'</a>';

	}

}
