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
 * Page selection widget
 *
 * @package Portal
 */
class PageSelector
{

	/**
	 * Creates a page selector object.
	 * @param int The total number of pages available.
	 * @param int The page number of the currently displayed page.
	 * @param int How many page links to display below/above the current page before inserting ellipses.
	 * @param string The name of the target URL page.
	 * @param string[] Additional URL parameters.
	 */
	function PageSelector($numPages, $currentPage, $linkDistance = 2, $linkPage, $linkParams = array())
	{
		$this->pageLinks = array();
		$this->linkPage = $linkPage;
		$this->linkParams = $linkParams;
		$inEllipsis = false;

		for ($i = 1; $i <= $numPages; $i++) {
			// Check if we want a link
			if ($i == 1 || $i == $numPages || ($i >= ($currentPage - $linkDistance) && $i <= ($currentPage + $linkDistance))) {
				// Current page is not a link
				if ($i == $currentPage) {
					$this->pageLinks[] = array('type' => 'current_page', 'args' => array(
						'page' => $i,
					));
				} else {
					$this->pageLinks[] = array('type' => 'other_page', 'args' => array(
						'page' => $i,
						'direct_page_link' => linkTo($linkPage, array_merge($linkParams, array('page_number' => $i))),
					));
				}
				$inEllipsis = false;
			} elseif (!$inEllipsis) {
				$inEllipsis = true;
				$this->pageLinks[] = array('type' => 'ellipsis', 'args' => array());
			}
		}
	}

	/**
	 * Renders a page selector using the default template (which has to be included in the currently loaded template).
	 * @param Sigma A template object.
	 */
	function renderDefault(&$tpl)
	{
		$tpl->touchBlock('page_selector');
		foreach ($this->pageLinks as $link) {
			$tpl->hideBlock('current_page');
			$tpl->hideBlock('other_page');
			$tpl->hideBlock('ellipsis');
			foreach ($link['args'] as $key => $val) {
				$tpl->setVariable($key, $val);
			}
			$tpl->touchBlock($link['type']);
			$tpl->parse('direct_page_link');
		}
		$tpl->setVariable('goto_page_link', linkTo($this->linkPage, $this->linkParams));
	}

}
