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
 * Contains the central user interface object
 * @package Portal
 */

libLoad("environment::fixMagicQuotes");
libLoad("http::RequestVars");
libLoad("http::sendXML");

if (defined("DEFAULT_LANGUAGE"))
{
	$GLOBALS["TRANSLATION"]->setLanguage(DEFAULT_LANGUAGE);
}

/**
 * Wrapper for {@link Portal::linkTo}
 */
function linkTo($page, $params = array(), $escape = FALSE, $absolute = FALSE, $external = FALSE)
{
	return $GLOBALS["PORTAL"]->linkTo($page, $params, $escape, $absolute, $external);
}

/**
 * Wrapper for {@link Portal::linkToFile}
 */
function linkToFile($file, $params = array(), $escape = FALSE, $absolute = FALSE)
{
	return $GLOBALS["PORTAL"]->linkToFile($file, $params, $escape, $absolute);
}

/**
 * Wrapper for {@link Portal::redirectTo}
 */
function redirectTo($page, $params = array(), $permanent = FALSE)
{
	redirectToLink(linkTo($page, $params, FALSE, TRUE), $permanent);
}

/**
 * Wrapper for {@link Portal::redirectToLink}
 */
function redirectToLink($link, $permanent = FALSE)
{
	if (isset($GLOBALS["ERROR_HANDLER"]) && $GLOBALS["ERROR_HANDLER"]->hasErrors()) {
		trigger_error("Redirect to <code>".htmlentities($link)."</code> aborted because there are errors to report", E_USER_WARNING);
	}
	else
	{
		// Clear all output done so far. Code taken from the PHP manual section about ob_end_clean()
		while (@ob_end_clean());

		$page = &$GLOBALS["PORTAL"]->loadPage("redirect");
		$page->redirectTo($link, $permanent);
	}
	exit();
}

/**
 * Central user interface object
 * @package Portal
 */
class Portal
{
	/**
	 * Starts Portal
	 *
	 * Looks for a GET parameter named <kbd>page</kbd>.
	 * If found, it loads and the corresponding page object.
	 * The session is loaded if a session ID is given, which is determined by {@link getSessionSource()}
	 */
	function run()
	{
		start("Preparing");
		start("Session");
		$this->startSession();
		stop();

		start("Special Tasks");
		if ($specialTasks = post("PORTAL"))
		{
			$command = @$specialTasks["command"];
			if ($command == "set_language")
			{
				$language = @array_shift(array_keys($specialTasks["language"]));
				if (in_array($language, $GLOBALS["TRANSLATION"]->getAvailableLanguages())) {
					$this->setLanguage($language);
				}
			}
			redirectToLink(server("REQUEST_URI"));
		}
		stop();

		start("Page");
		
		$pageName = get("page", post("page", $this->defaultPage));
		$pageName = str_replace("-", "_", $pageName);

		$mMode = ($GLOBALS['inInstaller'] ? false : Setting::get('maintenance_mode_on') == 1);
		if ($mMode && $pageName != "start" && $pageName != "admin_start" && $pageName != "user_login" && !$this->getUser()->checkPermission('admin')) {
			redirectTo("start", array('resume_messages' => 'true'));
		}
		
		$page = &$this->loadPage($pageName);
		$this->page = &$page;
		
		stop();
		$action = post("action", get("action", $page->defaultAction));
		$action = str_replace("-", "_", $action);
		$this->actionName = $action;
		stop();
		start("Running ".$pageName);
		$page->run($action);
		stop();
	}

	/**
	 * Starts the session if it has not already been started
	 */
	function startSession()
	{
		// Abort if the session is already started
		if (session_id() != "") {
			return;
		}

		ini_set("session.use_cookies", TRUE);
		ini_set("session.use_only_cookies", TRUE);
		ini_set("session.use_trans_sid", FALSE);
		ini_set("url_rewriter.tags", "");

		session_name("TMID");
		libLoad("environment::reliableSessionStarter");
		reliableSessionStarter();

		// Initialize a fresh session
		if (empty($_SESSION["initDone"]))
		{
			$_SESSION = array();

			$_SESSION["userId"] = $this->userId;
			$_SESSION["initDone"] = TRUE;

			// If the previous session expired, notify and redirect the user,
			// but not if we're in the process of logging in

			if (getSessionSource() != "4" && (!isset($_REQUEST['page']) || ($_REQUEST['page'] != 'user_login' &&  $_REQUEST['page'] != 'tan_login'))
			    && getSessionSource() != "0" && $_REQUEST['page'] != NULL) {

				$GLOBALS['MSG_HANDLER']->addMsg('portal.session.notfound', MSG_RESULT_NEG);
				redirectTo("", array('resume_messages' => 'true'));
			}
		}
		if (!isset($_SESSION['language'])) {
			$_SESSION["language"] = $GLOBALS["TRANSLATION"]->getLanguage();
		}

		if (isset($GLOBALS["enable_debug"]) && $GLOBALS["enable_debug"] == true && !array_key_exists("DEBUG", $_SESSION))
		{
			$_SESSION["DEBUG"] = array();
		}
		
		$resetLang = get("reset_lang",0);
		if ((isset($_SESSION['languageOld'])) && ($resetLang ==1))
				$_SESSION['language'] = $_SESSION['languageOld'];

		$this->userId = &$_SESSION["userId"];
		if (!$this->userId) $this->userId = 0;

		// Handle magic session settings
		$user = $this->getUser();
		if ($user && $user->checkPermission('admin')) {
			if (get('magic_fpr')) $_SESSION['show_error_fingerprints'] = (get('magic_fpr') == 1);
			if (get('magic_dbc')) $_SESSION['show_database_count'] = (get('magic_dbc') == 1);
		}

		// Identity switched?
		if (isset($_SESSION['origUserId'])) {
			$this->origUserId = $_SESSION['origUserId'];
		}

		// Prevent information disclosure after the user logs out
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		// "Expires" hack to prevent browsers from keeping it in their cache
		header("Expires: ". date(NOW));

		$GLOBALS["TRANSLATION"]->setLanguage($_SESSION["language"]);

		// Prevent empty POST requests
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
			libLoad("html::preInitErrorPage");
			preInitErrorPage('portal.empty_post_request');
		}
	}

	/**
	 * Name of the default page
	 * @access private
	 */
	var $defaultPage = "default";

	/**
	 * Sets the name of the default page
	 * @param string Name of the default page
	 */
	function setDefaultPage($defaultPage) {
		$this->defaultPage = $defaultPage;
	}

	/**
	 * Returns the name of the default page
	 * @return string Current name of the default page
	 */
	function getDefaultPage() {
		return $this->defaultPage;
	}

	/**
	 * Loads a page
	 * @param string Name of the page to load
	 * @return Page An object representing the page
	 */
	function &loadPage($pageName)
	{	
		start("Including Page");
		require_once(PORTAL."Page.php");
		stop();
		start("Including ErrorPage");
		require_once(PORTAL."ErrorPage.php");
		stop();

		start("Error checks");

		start("Name format");
		if (! preg_match("/^[a-z0-9]+(_[a-z0-9]+)*$/", $pageName)) {
			$errorPage = new ErrorPage(NULL);
			$errorPage->setErrorMessage("Invalid page name <b>".htmlentities($pageName)."</b>");
			return $errorPage;
		}
		stop();

		start("File name");
		$pageFile = PORTAL."pages/".$pageName.".php";
		if (! file_exists($pageFile)) {
			$errorPage = new ErrorPage(NULL);
			$errorPage->setHttpStatus("404 Not Found");
			$errorPage->setErrorMessage("Page <b>".$pageName."</b> not found, <i>".$pageFile."</i> does not exist.");
			if ($pageName == "default") {
				$errorPage->addRemark("You can also set a different default page with <code>\$GLOBALS[\"PORTAL\"]->setDefaultPage()</code>.");
			}
			return $errorPage;
		}
		stop();

		start("Inclusion");
		ob_start();
		require_once($pageFile);
		$output = ob_get_contents();
		ob_end_clean();

		if ($output != "") {
			$errorPage = new ErrorPage(NULL);
			$errorPage->setErrorMessage("There were errors while loading page <b>".$pageName."</b> (all output produced while including the page file is regarded as an error)");
			$errorPage->setErrors($output);
			return $errorPage;
		}
		stop();

		start("Class");
		$pageClass = preg_replace("/(^|_)(.)/e", "strtoupper('\\2')", $pageName)."Page";
		if (! class_exists($pageClass)) {
			$errorPage = new ErrorPage(NULL);
			$errorPage->setErrorMessage("Page <b>".$pageName."</b> did not defined a class named <b>".$pageClass."</b>");
			return $errorPage;
		}

		libLoad("oop::isSubclassOf");
		if (! isSubclassOf($pageClass, "Page")) {
			$errorPage = new ErrorPage(NULL);
			$errorPage->setErrorMessage("The object for page <b>".$pageName."</b> is not a subclass of <b>Page</b>");
			return $errorPage;
		}
		stop();

		stop();

		start("Creating object");
		$page = new $pageClass($pageName);
		stop();
		
		return $page;
	}

	/**
	 * The ID of the currently logged in user
	 * @access private
	 */
	var $userId = 0;

	/**
	 * The real ID of the currently logged in user (if identity switched)
	 * @access private
	 */
	var $origUserId = NULL;

	/**
	 * Sets the ID of the currently logged in user
	 * @param string ID of the user to log in
	 */
	function setUserId($userId)
	{
		$this->userId = $userId;

		if (isset($this->userId)) {
			$userList = new UserList();
			$user = $userList->getUserById($userId);
			$this->setLanguage($user->get('lang'));
		}
	}

	/**
	 * Returns the ID of the currently logged in user
	 * @return integer ID of the currently logged in user
	 */
	function getUserId()
	{
		return intval($this->userId);
	}

	/**
	 * Returns the currently logged in user as a User object
	 * @return User object for the currently logged in user
	 */
	function getUser()
	{
		$userId = $this->getUserId();
		if (!isset($userId) || (!$userId && $userId !== 0) ||
			$GLOBALS['inInstaller']) return NULL;

		return DataObject::getById('User', $userId);
	}

	/**
	 * Returns the original user ID if the user has switched identity, or NULL
	 * else.
	 */
	function getOrigUserId()
	{
		return $this->origUserId;
	}

	/**
	 * Returns the original User object if identity has been switched.
	 * @return User
	 */
	function getOrigUser()
	{
		$userId = $this->getOrigUserId();
		if (!isset($userId) || (!$userId && $userId !== 0)) return NULL;

		return DataObject::getById('User', $userId);
	}

	/**
	 * Switches to another user ID, retaining the current user ID in the
	 * session.
	 */
	function switchUserId($userId)
	{
		$_SESSION['origUserId'] = $this->getUserId();
		$this->origUserId = $this->getUserId();
		$this->userId = $userId;

		// Save export filters
		if (isset($_SESSION['TEST_RUN_FILTERS'])) {
			$_SESSION['ORIG_TEST_RUN_FILTERS'] = $_SESSION['TEST_RUN_FILTERS'];
			unset($_SESSION['TEST_RUN_FILTERS']);
		}
	}

	/**
	 * Switches back to real user ID (see {@link switchUserId}).
	 */
	function unswitchUserId()
	{
		if (!isset($_SESSION['origUserId'])) return;
		$userId = $_SESSION['origUserId'];
		unset($_SESSION['origUserId']);
		$this->origUserId = NULL;
		$this->setUserId($userId);

		// Restore export filters
		if (isset($_SESSION['ORIG_TEST_RUN_FILTERS'])) {
			$_SESSION['TEST_RUN_FILTERS'] = $_SESSION['ORIG_TEST_RUN_FILTERS'];
			unset($_SESSION['ORIG_TEST_RUN_FILTERS']);
		}
	}

	/**
	 * Returns the current language
	 * Wrapper for {@link Translation::getLanguage()}
	 * @return String Language code
	 */
	function getLanguage() {
		return $GLOBALS["TRANSLATION"]->getLanguage();
	}

	/**
	 * Returns the current page object
	 * @return Page
	 */
	function &getCurrentPage() {
		return $this->page;
	}

	/**
	 * Returns the current action name
	 * @return String
	 */
	function getCurrentActionName() {
		return $this->actionName;
	}

	/**
	 * Sets the language
	 * Wraps {Translation::setLanguage()} and stores the language in <var>$_SESSION["language"]</var> if a session was started.
	 * @param String Language code
	 */
	function setLanguage($language) {
		if (isset($_SESSION)) {
			$_SESSION["language"] = $language;
		}
		$GLOBALS["TRANSLATION"]->setLanguage($language);
	}

	/**
	 * Returns the available languages
	 *
	 * @return array Associative array with language name ("English") as key and language code as value ("en")
	 */
	function getAvailableLanguages()
	{
		return array(
			"English" => "en",
			"Deutsch" => "de",
		);
	}

	/**
	 * Constructs a link to a block.
	 *
	 * @param mixed A Block object or a working path string.
	 */
	function linkToBlock($block, $escape = FALSE, $absolute = FALSE)
	{
		if (is_string($block)) {
			return $this->linkTo('block_edit', array('working_path' => $block), $escape, $absolute);
		}

		// Construct working path
		$path = '_'. $block->getId() .'_';
		$user = $GLOBALS['PORTAL']->getUser();
		while ($parents = $block->getParents()) {
			// Filter out paths that the user is not allowed to see
			while (count($parents) > 0) {
				$parent = $parents[0];
				if (!$user->checkPermission('view', $parent)) {
					array_splice($parents, 0, 1);
				} else {
					break;
				}
			}
			if (count($parents) == 0) break;
			$block = $parents[0];
			$path = '_'. $block->getId(). $path;
		}

		return $this->linkTo('block_edit', array('working_path' => $path), $escape, $absolute);
	}

	/**
	 * Constructs a link to an arbitary object, using its display title as the link's label.
	 * Where a link does not make sense, just returns the title.
	 */
	function labeledLinkToObject($obj) {
		if(!$obj) return 0;
		
		$title = htmlspecialchars($obj->getTitle());
		$link = NULL;

		if (is_a($obj, 'User') && $obj->getId() != 0) {
			$link = $this->linkTo('user_admin', array('action' => 'edit_user', 'id' => $obj->getId()), TRUE);
		} elseif (is_a($obj, 'Group') && $obj->get('id') > 0) {
			$link = $this->linkTo('group_admin', array('action' => 'edit_group', 'id' => $obj->get('id')), TRUE);
		} elseif (is_a($obj, 'Block')) {
			$link = $this->linkToBlock($obj, TRUE);
		}

		if ($link) return '<a href="'. $link .'">'. $title .'</a>';
		return $title;
	}

	/**
	 * Constructs a link to a page, automatically inserting a session ID and
	 * the like. If you want to customize the layout of all links within
	 * testMaker, start here.
	 *
	 * @param string Name of page to link to
	 * @param array Associative array of additional GET parameters
	 * @param bool Whether the URL should be HTML-escaped
	 * @param bool Whether an absolute URL should be returned
	 * @param bool Whether this URL will be used externally, e.g. in an email (in other words: whether the session ID should be omitted)
	 */
	function linkTo($page, $params = array(), $escape = FALSE, $absolute = FALSE, $external = FALSE)
	{
		$sessionName = session_name();

		// Delete reserved keywords from the $param array
		foreach (array("page", $sessionName) as $key) {
			if (isset($params[$key])) {
				unset($params[$key]);
			}
		}

		// Make sure the page name is the first parameter
		if ($page != "") {
			$params = array_merge(array("page" => $page), $params);
		}
		// Add the session ID if a session was started
		if (! $external && ! isset($_COOKIE[$sessionName]) && session_id()) {
			$params[$sessionName] = session_id();
		}

		return $this->linkToFile("index.php", $params, $escape, $absolute);
	}

	/**
	 * Constructs a link to a file on the server
	 * @param String The file to link to
	 * @param array Associative array of additional GET parameters
	 * @param bool Whether the URL should be HTML-escaped
	 * @param bool Whether an absolute URL should be returned
	 */
	function linkToFile($file, $params = array(), $escape = FALSE, $absolute = FALSE)
	{
		// Make up the URL
		$link = $file;
		if ($absolute) {
			$dirName = dirname($_SERVER["SCRIPT_NAME"]);
			if($dirName == "/" || $dirName == "\\\\") $dirName = "";
			$link = (server("HTTPS", "off") == "on" ? "https://" : "http://").$_SERVER['HTTP_HOST'].$dirName.'/'.$link;
		}
		libLoad("http::getQueryString");
		$link .= getQueryString($params);
		if ($escape) {
			return htmlspecialchars($link);
		} else {
			return $link;
		}
	}
}


