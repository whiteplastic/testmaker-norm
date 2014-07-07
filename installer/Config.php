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
 * Handles creation of the main configuration file.
 *
 * @package Installer
 */

/**
 * Handles creation of the main configuration file.
 *
 * @package Installer
 */
class Config
{
	/**#@+
	 * @access private
	 */
	var $form_values = array();
	var $form_descriptions = array(
		'db_type'           => 'Type of database server; currently only "mysql" is supported',
		'db_host'           => 'The hostname of the database server',
		'db_user'           => 'The database username to authenticate to',
		'db_password'       => 'The database authentication password',
		'db_name'           => 'The name of the database to use',
		'db_prefix'         => 'The prefix attached to testMaker\'s table names during installation',
		'system_mail'       => 'The e-mail address to use as sender for mails sent by testMaker',
		'system_mail_b'		=> 'Second e-mail address, for example for test surveys',
		'default_language'  => 'The default interface language (we currently support "de" and "en")',
		'project_name'      => 'A distinctive title for this installation, e.g. "testMaker RWTH Aachen"',
		'error_tracker_url' => 'The URL to the tracker for the verbose error handler (e.g. http://www.example.org/errorTracker/)',
		'error_mails_to'    => 'An e-mail address that the error tracker can access',
	);
	var $optional_fields = array(
		'error_tracker_url' => '',
		'error_mails_to' => '',
	);
	var $front;
	var $path;
	/**#@-*/

	function Config(&$front)
	{
		$this->front = &$front;
		$front->addStep('config_edit', $this);
		$front->addStep('upload_perms', $this);
		$this->path = CORE.'init/configuration.php';
		$this->upPath = ROOT.'upload/';
	}

	function configExists()
	{
		return file_exists($this->path);
	}

	function tryWriteConfig($contents)
	{
		if ($fd = @fopen($this->path, 'a')) {
			fwrite($fd, $contents);
			fclose($fd);
			return true;
		} else {
			return false;
		}
	}

	function constructConfigFile()
	{
		$cfgfile = "<?php\n//\n// Configuration file for testMaker\n//\n";
		foreach ($this->form_values as $key => $value) {
			$cname = strtoupper($key);
			if (isset($this->form_descriptions[$key])) {
				$cfgfile .= "\n// ". $this->form_descriptions[$key] ."\n";
			}
			$cfgfile .= "define('$cname', '{$value}');\n";
		}

		foreach ($this->optional_fields as $key => $value) {
			$cname = strtoupper($key);
			if (isset($this->form_descriptions[$key])) {
				$cfgfile .= "\n// ". $this->form_descriptions[$key] ."\n";
			}
			$cfgfile .= "// define('$cname', '{$value}');\n";
		}

		return $cfgfile;
	}

	function checkConfigEdit()
	{
		return (!$this->configExists());
	}

	function doConfigEdit()
	{
		// Download config file
		if (isset($_SESSION['install_config_file']) && get('download')) {
			$cfgfile = $_SESSION['install_config_file'];
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; ".
				"filename=\"configuration.php\"");
			header("Content-Length: ". strlen($cfgfile));
			echo $cfgfile;
			exit;
		}

		//form fields required?
		$cfg_fields = array(
			'db_type'     => true,
			'db_host'     => true,
			'db_user'     => true,
			'db_password' => false,
			'db_name'     => true,
			'db_prefix'   => false,
			'system_mail' => true,
			'project_name'	=> false,
			'default_language'	=> false, 
		);
		$have_post = true;

	
		
		foreach ($cfg_fields as $field => $required) {
			$this->form_values[$field] = post($field, '');
			//Check if this page is entered from the InstallWelcome-Page, if it is, no errormessage of missing entries will be shown
			if (!$this->form_values[$field] && $required) {
			  $have_post = false;
			  if (isset($_POST['button'])) {
			  	$message = "ui.step.config_edit.no_".$field;
			  	$GLOBALS['MSG_HANDLER']->addMsg($message,MSG_RESULT_NEG);
			  }
			}
		}
		
		$this->form_values['system_mail_b'] = $this->form_values['system_mail'];
		
		if($this->form_values['project_name'] == '')
			$this->form_values['project_name'] = 'testMaker';
		
		
		// Don't proceed if it already exists
		if ($this->configExists()) return false;

		if ($have_post) {
			$vals =& $this->form_values;
			$res = $this->front->database->try_connect($vals['db_type'],
				$vals['db_user'], $vals['db_password'],
				$vals['db_host'], $vals['db_name'],
				$vals['db_prefix']);
			if (!$res) {
				$GLOBALS['MSG_HANDLER']->addMsg("ui.step.config_edit.no_db",
					MSG_RESULT_NEG);
				$have_post = false;
			}
		}

		// Ask the user for configuration data
		if (!$have_post) {
			$link = get('action');

			$languages = '';
			foreach($GLOBALS["TRANSLATION"]->getAvailableLanguages() as $language)
			{
				$languages .= '<option value="'.$language.'">'.T('language.'.$language)."</option>";
			}

            $vals =& $this->form_values;
			$this->front->disableLink();
			return $this->front->page->renderTemplate(
				"InstallConfigForm.html", array(
					'link_current' => $link,
					'lang' => $languages,
					'db_hostname' => $vals['db_host'],
					'db_name' => $vals['db_name'],
					'system_mail' =>  $vals['system_mail'],
					'db_user' =>  $vals['db_user'],
					'project_name' => $vals['project_name']
				), true);
		}

		$cfgfile = $this->constructConfigFile();

		// Let's try if we can write it without user interaction
		if ($this->tryWriteConfig($cfgfile)) return false;

		// Otherwise, offer it for download
		$_SESSION['install_config_file'] = $cfgfile;
		return $this->front->page->renderTemplate("InstallConfigUpl.html",
			array('config_path' => $this->path), true);
	}

	function checkUploadPerms()
	{
		return !(is_writable($this->upPath));
	}

	function doUploadPerms()
	{
		return $this->front->page->renderTemplate("InstallUploadPermsInfo.html", array(), true);
	}

}

?>
