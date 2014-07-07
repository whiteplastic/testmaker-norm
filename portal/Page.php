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

/*
 * Maps sub-pages of admin menu topics to the corresponding topics in order to
 * correctly highlight admin menu links. Not very nice, but works with the Sigma
 * template engine.
 */
$GLOBALS['PAGES'] = array(
	'template_management' => 'intro_management',
	'view_edit_logs' => 'intro_management',
	'email_management' => 'intro_management',
	'maintenance_mode' => 'intro_management',
	'group_admin' => 'user_admin',
	'switch_user' => 'user_admin',
	'test_runresult' => 'test_run', 
);

// usage: $date: (input string) eg. "23.12.84", $format: as php date fucntion eg. "dmy", $sep: seperator eg. "." or "/"
function check_date($date,$format,$sep)
{    
    
    $pos1    = strpos($format, 'd');
    $pos2    = strpos($format, 'm');
    $pos3    = strpos($format, 'Y'); 
    
    $check    = explode($sep,$date);
    
    return @checkdate($check[$pos2],$check[$pos1],$check[$pos3]);

}

/**
 * Displays a progress bar
 *
 * Uses <kbd>ProgressBar.html</kbd>
 *
 * @param int Progress in percent from 0 to 100
 * @param string Width of the progress bar as CSS attribute (i.e. 100px)
 */
function sigmaProgressBar($progress, $width = "7em", $fontSize = "0.8em", $extraStyle = "", $idPrefix = NULL)
{
	static $backupId = 0;
	if ($idPrefix === NULL) {
		$idPrefix = "progress_bar_".$backupId++;
	}

	$page = new Page("");
	$page->tpl->loadTemplateFile("ProgressBar.html");
	$page->tpl->setVariable("width", $width);
	$page->tpl->setVariable("fontSize", $fontSize);
	$page->tpl->setVariable("style", $extraStyle);
	$page->tpl->setVariable("progress", $progress);
	$page->tpl->setVariable("id", $idPrefix);
	return $page->tpl->get();
}

/**
 * Used within templates for generating links using func_pagelink().
 *
 * Apart from the mandatory arguments, you can pass an arbitrary number of
 * key-value pairs to add to the query string. For example, calling
 * <kbd>func_pagelink(installer, expert, step: mess_up, recoverability:
 * none)</kbd> will produce a link like
 * <kbd>index.php?page=installer&amp;action=expert&amp;step=mess_up&amp;recoverability=none</kbd>.
 *
 * @param string The name of the page to link to.
 * @param string The name of the page action to include in the link.
 * @return string The link constructed from the given parameters.
 * @see Portal::linkTo
 */
function sigmaPageLink($page, $action = '')
{
	if(strtolower($page) == 'this') {
		$pageObject = $GLOBALS['PORTAL']->getCurrentPage();
		$page = $pageObject->getName();
	}

	$arg_array = array();
	if ($action) $arg_array['action'] = $action;

	$va = func_num_args() - 1;
	while ($va > 1) {
		$arg = func_get_arg($va);
		$va--;
		$arg = preg_split('/:\s*/', $arg, 2);
		$arg_array[$arg[0]] = $arg[1];
	}

	return linkTo($page, $arg_array, false);
}

/**
 * Generates a link to a help page.
 *
 * @param string The name of the page.
 * @param string The name of the action.
 * @return string The help page link.
 */
function sigmaHelpLink($page, $action)
{
    return linkTo('help', array(
	'action' => 'show_page_help',
	'for_page' => $page,
	'for_action' => $action
    ), true);
}

/**
 * Creates a link and assigns the CSS class "Current" if the link refers to the currently displayed page
 * @param string The translation ID of the link title (or verbatim string prefixed with '!')
 * @param page The name of the page
 * @param action The name of the action
 * @return string HTML link
 */
function sigmaMenuLink($titleId, $page)
{
	global $PAGES;

	$args = func_get_args();
	array_shift($args);
	$link = call_user_func_array("sigmaPageLink", $args);

	if (strlen($titleId) > 0 && $titleId[0] == '!') {
		$titleId = substr($titleId, 1);
	} else {
		$titleId = T($titleId);
	}

	$currentPage = $GLOBALS["PORTAL"]->page->getName();
	$active = FALSE;
	$target = isset($PAGES[$currentPage]) ? $PAGES[$currentPage] : $currentPage;
	if ($target == $page) {
		$active = TRUE;
	}

	if ($active) {
		return "<a href=\"".$link."\" class=\"Current\">".htmlspecialchars($titleId)."</a>";
	}
	return "<a href=\"".$link."\">".htmlspecialchars($titleId)."</a>";
}

/**
 * Used within templates for conditional output using func_iif().
 *
 * @param string Condition to evaluate (by converting the string to boolean).
 * @param string Returned if the condition evaluates to true.
 * @param string Returned if the condition evaluates to false.
 */
function sigmaIif($condition, $true_val, $false_val)
{
	return ($condition ? $true_val : $false_val);
}

/**
 * Used to localize time format.
 *
 * @param string Condition to evaluate (by converting the string to boolean).
 * @param string Returned if the condition evaluates to true.
 * @param string Returned if the condition evaluates to false.
 */
function sigmaDateTime($timestamp)
{
	if($timestamp == '') {
		return T('pages.core.date_time_empty');
	} else {
		return date(T('pages.core.date_time'), $timestamp);
	}
}

/**
 * Used to localize time format.
 *
 * @param string filename of media to insert.
 */
function sigmaMedia($filename)
{
	switch(substr(strtolower(strrchr($filename, '.')), 1)) {
		case 'jpeg':
		case 'jpg':
		case 'png':
		case 'gif':
			$content = "<img src=\"upload/media/$filename\" alt=\"Media Picture\" />\n";
			break;
		case 'swf':
			$content = "<object type=\"application/x-shockwave-flash\" data=\"upload/media/$filename\" width=\"300\" height=\"200\">\n";
			$content .= "	<param name=\"movie\" value=\"upload/media/$filename\" />\n";
			$content .= "	<param name=\"menu\" value=\"false\" />\n";
			$content .= "	<param name=\"Autoplay\" value=\"true\" />\n";
			$content .= "	<param name=\"bgcolor\" value=\"#fff\" />\n";
			$content .= "	Please install Macromedia FlashPlayer\n";
			$content .= "</object>\n";
			break;
		case 'mov':
			$content = "<object type=\"video/quicktime\" classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\">\n";
			$content .= "</object>\n";
			break;
		case 'mpg':
		case 'mpeg':
			$content = "<object type=\"video/mpeg\" data=\"upload/media/$filename\">\n";
			$content .= "</object>\n";
			break;
		case 'avi':
			$content = "<object type=\"video/x-msvideo\" data=\"upload/media/$filename\">\n";
			$content .= "</object>\n";
			break;
		default:
			$content = "";
	}
	return $content;
}

/**
 * Base class for all pages
 *
 * Extend this class, change $defaultAction and implement the corresponding <kbd>run<Action>()</kbd> method to define a page.
 *
 * @package Portal
 * @abstract
 */
class Page
{
	/**#@+
	 * @access private
	 */
	var $pageName;
	var $pageLevel = 0;
	var $correctionMessages = array();
	/**#@-*/

	/**
	 * Should be called by a central pageLoad function or similar
	 * @param string The name of the page
	 */
	function Page($pageName)
	{
		start("Constructor");
		$this->pageName = $pageName;
		$this->portal = &$GLOBALS['PORTAL'];

		libLoad("html::Sigma");
		libLoad("utilities::snakeToCamel");
		$this->tpl = new Sigma(PORTAL."templates/");
		$this->tpl->setErrorHandling(PEAR_ERROR_TRIGGER);
		$this->tpl->setCallbackFunction("T", "T");
		$this->tpl->setCallbackFunction("datetime", "sigmaDateTime");
		$this->tpl->setCallbackFunction("pagelink", "sigmaPageLink");
		$this->tpl->setCallbackFunction("helplink", "sigmaHelpLink");
		$this->tpl->setCallbackFunction("menulink", "sigmaMenuLink");
		$this->tpl->setCallbackFunction("iif", "sigmaIif");
		$this->tpl->setCallbackFunction("medium", "sigmaMedia");
		$this->tpl->setCallbackFunction("progressbar", "sigmaProgressBar");
		stop();
	}

	/**
	 * @return string The name of the page
	 */
	function getName()
	{
		return $this->pageName;
	}

	/**
	 * The default action name
	 *
	 * Child classes should change this.
	 * @access private
	 */
	var $defaultAction = "default";

	/**
	 * Runs the specified action or the default action
	 *
	 * Examples of valid action names:
	 * - test1
	 * - test_xyz
	 * - test2_part3
	 * - how_are_you
	 *
	 * To implement these actions, you would have to define the following methods:
	 * - doTest1()
	 * - doTestXyz()
	 * - doTest2Part3()
	 * - doHowAreYou()
	 *
	 * @param string Name of the action to perform
	 * @return mixed Whatever the action method returns
	 */
	function run($actionName = NULL)
	{
		start("Preparing");
		if (! isset($actionName)) {
			$actionName = $this->defaultAction;
		}
		// Store the action name in the object for loadDocumentFrame()
		$this->actionName = $actionName;
		$actionMethod = "do". snakeToCamel($actionName);

		$errorRemarks = array();

		if (! preg_match("/^[a-z0-9]+(_[a-z0-9]+)*$/", $actionName)) {
			$errorMessage = "Invalid action <b>".htmlentities($actionName)."</b>.";
		}
		elseif (! method_exists($this, $actionMethod)) {
			$errorMessage = "The page <b>".htmlentities($this->getName())."</b> does not have an action named <b>".htmlentities($actionName)."</b> (it did not define a <b>".$actionMethod."()</b> method).";
			if ($actionName == "default") {
				$errorRemarks[] = "The name of the default action can also be specified by changing <kbd>\$this-&gt;defaultAction</kbd> within the page object.";
			}
		}
		else {
			stop();
			start("Running ".$actionMethod."()");
			$result = $this->$actionMethod();
			stop();
			return $result;
		}
		stop();

		if (isset($errorMessage))
		{
			$errorPage = new ErrorPage(NULL);
			$errorPage->setErrorMessage($errorMessage);
			foreach ($errorRemarks as $remark) {
				$errorPage->addRemark($remark);
			}
			return $errorPage->run();
		}
	}

	/**
	 * Joins a list of IDs into a working path.
	 */
	function joinPath($ids)
	{
		return '_'. implode('_', $ids) .'_';
	}

	/**
	 * Splits a working path into its ID components.
	 */
	function splitPath($path)
	{
		return explode("_", preg_replace('/(^_|_$)/', '', trim($path)));
	}

	/**
	 * Returns the parent of a working path.
	 */
	function getParentPath($path)
	{
		if ($path == NULL) return '_0_';
		else return Page::joinPath(array_slice(Page::splitPath($path), 0, -1));
	}

	/**
	 * Renders a template.
	 *
	 *
	 * @param string The template file to use.
	 *
	 * @param array A hash specifying the template variables, blocks et
	 * cetera.
	 *
	 * This argument deserves a little more explanation. The key of each entry
	 * specifies what should be added to the template. The following sorts of
	 * key-value pairs are accepted:
	 *
	 * * <kbd>"variable" => "content"</kbd> sets the given variable to the
	 *   given value.
	 * * <kbd>"block" => array(...)</kbd> fills a block. The array contains
	 *   arrays of arrays of the form <kbd>array("variable", "content")</kbd>,
	 *   setting the block's variable <kbd>variable</kbd> to the given content.
	 *   Alternatively, just use a bunch of associative arrays.  The outer
	 *   arrays are interpreted as 'rows'; the block is parsed after each one.
	 *   Nested blocks are not supported, sorry.
	 * * <kbd>"block" => true</kbd> touches a block.
	 * * <kbd>"block" => false</kbd> hides a block.
	 * * <kbd>"+variable" => "filename"</kbd> replaces the given variable
	 *   with a block of the same name and loads its contents from the given
	 *   file.
	 * * <kbd>"&callback" => "function"</kbd> sets a Sigma callback
	 *   function.
	 *
	 * @param boolean If set, the rendered string will be returned instead of
	 * being sent to the client.
	 */
	function renderTemplate($file, $args, $get = false)
	{
		$this->tpl->loadTemplateFile($file);
		return $this->renderTemplateCore($args, $get);
	}

	/**
	 * @access protected
	 */
	function renderTemplateCore($args, $get)
	{
		foreach ($args as $key => $value) {
			if ($value === false) {
				$this->tpl->hideBlock($key);
			} elseif ($value === true) {
				$this->tpl->touchBlock($key);
			} elseif (is_string($value) && strlen($value) > 0 && $value[0] == '+') {
				$key = substr($key, 1);
				$this->tpl->loadBlockfile($key, $key, $value);
			} elseif (is_string($value) && strlen($value) > 0 && $value[0] == '&') {
				$key = substr($key, 1);
				$this->tpl->setCallbackFunction($key, $value);
			} elseif (is_array($value)) {
				foreach ($value as $sub) {
					foreach ($sub as $subk => $sub2) {
						if (is_int($subk) && is_array($sub2)) {
							$this->tpl->setVariable($sub2[0], $sub2[1]);
						} else {
							$this->tpl->setVariable($subk, $sub2);
						}
					}
					$this->tpl->parse($key);
				}
			} else {
				$this->tpl->setVariable($key, $value);
			}
		}
		if ($get) return $this->tpl->get();
		$this->tpl->show();
	}

	function setupAdminMenu()
	{
		if (!$this->checkAllowed('create', false, NULL)) return;
		$this->tpl->setVariable('page', 'admin_start');
		$this->tpl->setVariable('name', 'action');
		$this->tpl->setVariable('value', 'create_container');
		$this->tpl->parse('menu_admin_item_additional_info');
		$this->tpl->setVariable('title', T('pages.admin_start.new_container'));
		$this->tpl->parse('menu_admin_item');
	}

	/**
	 * Checks if the user is allowed to perform some sort of operation.
	 *
	 * @param string A permission name; see Group::checkPermission.
	 * @param boolean Outputs error message and redirects to start page if
	 *   access really is denied.
	 * @param mixed A target Block or NULL to check for global permissions.
	 * @param boolean Whether to use virtual permissions (GID/UID 1 safety
	 *   belt and owner check).
	 */
	function checkAllowed($permission, $die = false, $target = NULL, $useVirtual = true)
	{
		$perms = Group::getPermissionNames();
		if (!in_array($permission, $perms)) {
			$check = false;
		} elseif ($user = $GLOBALS['PORTAL']->getUser()) {
			$check = $user->checkPermission($permission, $target, $useVirtual);
		} else {
			$check = false;
		}

		if (!$check) {
			if (!$die) return false;
			$GLOBALS["MSG_HANDLER"]->addMsg("pages.permission_denied", MSG_RESULT_NEG);
			redirectTo('', array('resume_messages' => 'true'));
		}
		return true;
	}

	/**
	 * Loads the main document frame template and initializes it
	 *
	 * Basically, this does the following:
	 * <code>
	 * $this->tpl->loadTemplateFile("DocumentFrame.html");
	 * </code>
	 *
	 * It also sets the login name and configures user specific parts of the menu.
	 */
	function loadDocumentFrame()
	{

		$this->db = &$GLOBALS['dao']->getConnection();
		$query = "SELECT content FROM ".DB_PREFIX."settings WHERE name=?";
		$result = $this->db->getOne($query, array("main_logo"));

		if (empty($result)) 
			$logo = "portal/images/tm-logo-sm.png";
		else 
			$logo = "upload/media/".$result;
			

		$this->tpl->loadTemplateFile("DocumentFrame.html");

		//Browser-Title. (PROJECT_NAME defined in configuration.php)
		$this->tpl->setVariable('project_name', defined("PROJECT_NAME") ? PROJECT_NAME." :: " : "");
		
		$this->tpl->setVariable("page_name", $this->pageName);
		$this->tpl->setVariable("action_name", $this->actionName);
		$this->tpl->setVariable("logo", $logo);
		if ($this->tpl->blockExists("help_button"))
		{
			$helpPage = &$GLOBALS["PORTAL"]->loadPage("help");
			$this->tpl->hideBlock("help_available");
			$this->tpl->hideBlock("no_help_available");
			if ($helpPage->getPageHelpFile($this->pageName, $this->actionName)) {
				$this->tpl->setVariable("help_link", $helpPage->getPageHelpLink($this->pageName, $this->actionName));
				$this->tpl->touchBlock("help_available");
			} else {
				$this->tpl->touchBlock("no_help_available");
			}
		}
		$this->tpl->setVariable("request_uri", server("REQUEST_URI"));

		$origUser = $GLOBALS['PORTAL']->getOrigUser();
		if ($origUser) {
			$this->tpl->setVariable('unswitch_username', htmlspecialchars($origUser->getUsername()));
			$this->tpl->touchBlock('menu_unswitch');
		}

		$user = $GLOBALS['PORTAL']->getUser();
		if ($user && $user->getId() != 0)
		{
			$this->tpl->setVariable("loginName", $user->getUsername());

			$this->tpl->hideBlock("menu_login_form");
			$this->tpl->touchBlock("menu_user_area");

			$this->tpl->setVariable("css_file", "portal/css/special_common.css");
			$this->tpl->parse("css_file");

			$onlyTestOverview = true;
			$this->tpl->touchBlock("additional_menu");
			if ($user->checkPermission('admin')) {
				$this->tpl->setVariable('link', sigmaMenuLink('menu.user.management', 'intro_management'));
				$this->tpl->parse('admin_menu');
				$this->tpl->setVariable('link', sigmaMenuLink('menu.user.admin', 'user_admin'));
				$this->tpl->parse('admin_menu');
				$onlyTestOverview = false;
			}
			if ($user->checkPermission('cert')) {
				$this->tpl->setVariable('link', sigmaMenuLink('menu.user.checkCert', 'check_cert'));
				$this->tpl->parse('admin_menu');
				$onlyTestOverview = false;
			}

			if ($user->checkPermission('export', NULL) && $user->canEditSomething()) {
				$this->tpl->setVariable('link', sigmaMenuLink('menu.test.runs', 'test_run'));
				$this->tpl->parse('admin_menu');
				$this->tpl->setVariable('link', sigmaMenuLink('menu.test.survey', 'cronjob_status'));
				$this->tpl->parse('admin_menu');	
				$onlyTestOverview = false;
			}
			if ($user->checkPermission('copy', false) || $user->checkPermission('create')) {
				$this->tpl->setVariable('link', sigmaMenuLink('menu.import_export', 'import_export'));
				$this->tpl->parse('admin_menu');
				$onlyTestOverview = false;
			}
			if ($user->checkPermission('portal', false) || $user->isSpecial()) {
				$this->tpl->setVariable('link', sigmaMenuLink('menu.test.overview', 'test_listing'));
				$this->tpl->parse('admin_menu');
			}
			if($onlyTestOverview) {
				$this->tpl->hideBlock("additional_menu");
				$titleId = T('menu.test.overview');
				$link = "<a class=\"Button\" href=\"".sigmaPageLink('test_listing', '')."\">".htmlspecialchars($titleId)."</a>";
				$this->tpl->setVariable('top_to_overview', $link);
			}
			else
				$this->tpl->touchBlock("sitebar_menu");
			
			$workingPath = getpost('working_path', '_0_');

			// needed for BlockMenu
			require_once(PORTAL.'MenuBlockTree.php');

			$completeTree = FALSE;
			$mbt = new MenuBlockTree($completeTree);
			$itemId = getpost('item_id');

			if (! $completeTree) {
				$this->tpl->touchBlock("menu_block_tree_current");
				$this->tpl->parse("menu_block_tree_current");
				$this->tpl->replaceBlock('menu_block_tree_current', $mbt->getMenuOfCurrent($this->tpl->get('menu_block_tree_current'), $workingPath, $itemId));
				$this->tpl->touchBlock("menu_block_tree_current");
			}

			$this->tpl->touchBlock("menu_block_tree_area");
			$this->tpl->parse("menu_block_tree_area");
			$this->tpl->replaceBlock('menu_block_tree_area', $mbt->getMenuTree($this->tpl->get('menu_block_tree_area'), $workingPath, $itemId));
			$this->tpl->touchBlock("menu_block_tree_area");

			if (!$user->isSpecial())
			{
				$this->tpl->hideBlock("menu_block_tree");
			}

			$this->setupAdminMenu();
		}
		else
		{
			$this->tpl->touchBlock("menu_login_form");
			
			if(isset($_GET['login']))	//display specified username
				$this->tpl->setVariable("form_login_username", $_GET['login']); 
			else
				 $this->tpl->setVariable("form_login_username", "");

			$this->tpl->hideBlock("menu_user_area");

			$this->tpl->setVariable("css_file", "portal/css/special_common.css");
			$this->tpl->parse("css_file");
			$this->tpl->touchBlock("additional_menu");
			$this->tpl->hideBlock("menu_block_tree");
		}

		$currentLanguage = $GLOBALS["PORTAL"]->getLanguage();
		$languages = $GLOBALS["PORTAL"]->getAvailableLanguages();
		foreach ($languages as $code) {
			$this->tpl->setVariable("code", $code);
			if ($currentLanguage == $code) {
				$this->tpl->touchBlock("menu_language_item_current");
				$this->tpl->hideBlock("menu_language_item_other");
			} else {
				$this->tpl->touchBlock("menu_language_item_other");
				$this->tpl->hideBlock("menu_language_item_current");
			}
			$this->tpl->parse("menu_language_item");
		}

		$debugOut = '';
		if (isset($_SESSION['show_database_count']) && isset($GLOBALS['databaseCounter'])) {
			$debugOut .= "Database queries: $GLOBALS[databaseCounter]. ";
		}
		$this->tpl->setVariable('debug_output', $debugOut);
		
		// show information on maintenance status in page header
		if (Setting::get('maintenance_mode_on') == 1){
			$this->tpl->touchBlock("menu_maintenance_info");
		} else {
			$this->tpl->hideBlock("menu_maintenance_info");		
		}
	}
	
	// Returns a page of its real type
	function getPage($id)
	{
		$db = &$GLOBALS['dao']->getConnection();
		$page = new Page($id);
		if (!$page) return null;
		return $page;
	}

	/**
	 * @access protected
	 */
	function initTabs($tabs, $activeTab, $disabledTabs = array())
	{
		$lineCount = 1;
		foreach (array_keys($tabs) as $id)
		{
			$tabs[$id]["title"] = htmlspecialchars(trim(T($tabs[$id]["title"])));
			$tabs[$id]["title_lines"] = count(explode("\n", $tabs[$id]["title"]));
			$lineCount = max($lineCount, $tabs[$id]["title_lines"]);
		}

		foreach ($tabs as $id => $tab)
		{
			$this->tpl->hideBlock("active_tab_item");
			$this->tpl->hideBlock("inactive_tab_item");
			$this->tpl->hideBlock("disabled_tab_item");

			if (in_array($id, $disabledTabs)) {
				$this->tpl->touchBlock("disabled_tab_item");
			}
			elseif ($id == $activeTab) {
				$this->tpl->touchBlock("active_tab_item");
			}
			else {
				$this->tpl->touchBlock("inactive_tab_item");
			}

			$title = $tab["title"];
			for ($i = 0; $i < $lineCount - $tab["title_lines"]; $i++) {
				if ($i % 2 == 0) {
					$title .= "\n".'<span style="display:block;visibility:hidden">&nbsp;</span>';
				} else {
					$title = " \n".$title;
				}
			}
			$title = strtr($title, array("\n" => "<br />"));

			$this->tpl->setVariable("tab_link", htmlentities($tab["link"]));
			$this->tpl->setVariable("tab_title", $title);

			$this->tpl->parse("tab_item");
		}
	}
}