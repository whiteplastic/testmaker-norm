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
 * Load Markdown
 */
require_once(ROOT .'external/markdown/markdown.php');
require_once(ROOT .'external/smartypants/smartypants.php');

/**
 * Displays a test block tree
 *
 * Default action: {@link doShowIndex()}
 *
 * @package Portal
 */
class HelpPage extends Page
{
	/**
	 * @access private
	 */
	var $defaultAction = "show_index";

	/**
	 * Shows a list of global help topics.
	 */
	function doShowIndex()
	{
		$this->tpl->loadTemplateFile("HelpIndex.html");
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.help.index.title"));
		$this->tpl->show();
	}

	/**
	 * Shows a global help topic.
	 */
	function doShowHelp()
	{
		$topic = get("topic");
		if (! in_array($topic, array("java_script"))) {
			redirectTo("help");
		}
		$this->tpl->loadTemplateFile("Help".snakeToCamel($topic).".html");
		$body = $this->tpl->get();
		$this->loadDocumentFrame();
		$this->tpl->setVariable("body", $body);
		$this->tpl->setVariable("page_title", T("pages.help.topics.java_script.title"));
		$this->tpl->show();
	}

	/**
	 * Obtains a link pointing to a page help topic.
	 * @param string The name of the page.
	 * @param string The name of the action.
	 * @return string An URL.
	 */
	function getPageHelpLink($pageName, $actionName)
	{
		return linkTo("help", array("action" => "show_page_help", "for_page" => $pageName, "for_action" => $actionName));
	}

	/**
	 * Obtains the filename of a page help file.
	 * @param string The name of the page.
	 * @param string The name of the action.
	 * @return string The template filename.
	 */
	function getPageHelpFile($pageName, $actionName)
	{
		foreach (array("html", "txt") as $extension) {
			$helpFile = $this->tpl->getFileName("help/".$pageName."/".$actionName.".".$extension);
			if ($this->tpl->verifyPath($helpFile)) {
				return PORTAL .'templates/'. $helpFile;
			}

			// Try the magic "any" page.
			$helpFile = $this->tpl->getFileName("help/any/".$actionName.".".$extension);
			if ($this->tpl->verifyPath($helpFile)) {
				return PORTAL .'templates/'. $helpFile;
			}
		}

		return NULL;
	}

	/**
	 * Obtains the (automagically brushed up) help text for a pge.
	 * @param string The name of the page.
	 * @param string THe name of the action.
	 * @return string The help text.
	 */
	function getPageHelp($pageName, $actionName)
	{
		if ($helpFile = $this->getPageHelpFile($pageName, $actionName)) {
			$this->tpl->setTemplate(implode('', file($helpFile)));
			$helpBody = $this->tpl->get();

			// Deal with plain text
			if (preg_match('/\.txt$/', $helpFile)) {
				$helpBody = SmartyPants(Markdown($helpBody));
			}

			// Callback for generating TOC. Yummy.
			// Sorry for namespace pollution. Anyone got better ideas?
			global $_help_toc_list;
			$_help_toc_list = array();
			$callback = create_function('$matches', '
				global $_help_toc_list;

				$nest = $matches[1];
				$full = $matches[2];
				$text = strip_tags($full);
				$anchor = "toc_". count($_help_toc_list);

				$_help_toc_list[] = array($nest, $anchor, $text);

				return "<h$nest>$full<a name=\"$anchor\"></a></h$nest>";
			');

			// Magick TOC anchors into headings
			$helpBody = preg_replace_callback('|<h(\d)>(.*?)</h\1>|i', $callback, $helpBody);

			// Generate TOC
			$toc = "\n".'<div class="toc"><h3>'. T('generic.toc') ."</h3>\n<ul>\n";
			$lvl = -1;
			$depth = 1;
			$open = false;
			// Careful with this loop, it's rather weird and will
			// eat you if you change it. :E
			foreach ($_help_toc_list as $entry) {
				list($nest, $anchor, $text) = $entry;
				if ($lvl == -1) $lvl = $nest;
				while ($nest < $lvl) {
					$toc .= "</li></ul>";
					$lvl--;
					$depth--;
				}
				while ($nest > $lvl) {
					$lvl++;
					$depth++;
					$toc .= "\n<ul>";
					$open = false;
				}
				if ($open) {
					$toc .= "</li>\n";
				}
				$open = true;
				$toc .= "<li><a href=\"#$anchor\">$text</a>";
			}
			while ($depth--) {
				$toc .= "</li></ul>";
			}
			$toc .= '</div>';
			if (!count($_help_toc_list)) $toc = '';

			unset($_help_toc_list);

			return $toc ."\n\n". $helpBody;
		} else {
			return NULL;
		}
	}

	/**
	 * Shows the help text for a certain page/action.
	 */
	function doShowPageHelp()
	{
		$page = get("for_page");
		$action = get("for_action");

		$helpBody = $this->getPageHelp($page, $action);

		$this->tpl->loadTemplateFile("HelpPageHelp.html");
		$this->tpl->setVariable("page_name", $page);
		$this->tpl->setVariable("action_name", $action);

		if (! isset($helpBody)) {
			$helpBody = T("pages.help.no_help_available");
		}
		$this->tpl->setVariable("body", $helpBody);

		$this->tpl->show();
	}
}

?>
