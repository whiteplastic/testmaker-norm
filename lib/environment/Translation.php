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
 * Provides a class ({@link Translation}) and a helper function ({@link T}) for translation
 *
 * Translation is once instantiated and stored in the global variable <var>$TRANSLATION</var>
 *
 * @package Library
 */

if (! isset($GLOBALS["TRANSLATION"])) {
	$GLOBALS["TRANSLATION"] = new Translation();
}

/**
 * Returns the string with the given ID and optionally replaces some arguments
 *
 * Calls <kbd>$GLOBALS["TRANSLATION"]->translate()</kbd>
 *
 * @param string The ID of the string to return
 * @param string[] List of arguments that may be used in the string
 * @param string Text to use if no translation is found
 */
function T($id, $arguments = array(), $text = NULL, $language = "default")
{
	return $GLOBALS["TRANSLATION"]->translate($id, $arguments, $text, $language);
}

/**
 * Adds new translation strings to the system
 *
 * Calls <kbd>$GLOBALS["TRANSLATION"]->addTranslations()</kbd>
 *
 * @param string The language to add translations for
 * @param string[] An associative array mapping translation string IDs to translations
 */
function addT($language, $translations)
{
	return $GLOBALS["TRANSLATION"]->addTranslations($language, $translations);
}

/**
 * Framework for translation files
 *
 * Translation works with IDs and arguments:
 * <code>
 * echo T("core.error.file_not_found", array("fileName" => $fileName));
 * </code>
 *
 * You can set the language to use and a fallback language to use
 * in case a string with a certain ID is not found in the primary language.
 *
 * There can be several sources of translations, called repositories.
 * These repositories are simple directories with a specific layout.
 * On the first level there should be directories with names corresponding
 * to a two-character language code (e.g. "de", "en", etc.).
 *
 * Inside these subdirectories you can structure your translation by
 * creating several translation files. A translation file is a file with a name
 * ending with <kbd>.php</kbd>. When included, it should define at least
 * an array named $TRANSLATIONS, containing name/value pairs that
 * define the translations for a certain ID.
 *
 * The translations can request placeholders that must be defined when
 * T() ist asked to return this string. Placeholders are surrounded by
 * square brackets.
 *
 * <code>
 * $TRANSLATIONS = array(
 * 	"core.error.file_not_found" => "The file [fileName] could not be found",
 * );
 * </code>
 *
 * Whenever a language is changed or a repository is added, the translations
 * will be updated. When this happens, all loaded translations of languages
 * that are no longer in use will be removed from memory and those for the new
 * languages are loaded. If you add a repository, only the translation files of that
 * repository are loaded. So it is important that your translation file can be
 * included several times without conflicts.
 *
 * @package Library
 */
class Translation
{
	/**
	 * Default primary language
	 * @access private
	 */
	var $language = "en";

	/**
	 * Returns the primary language
	 * @return string Two-character code of the current language
	 */
	function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Sets the primary language
	 * @param string Two-character code of the wanted language
	 */
	function setLanguage($language)
	{
		if (! preg_match("/^[a-z]{2}$/", $language)) {
			trigger_error("Invalid language code <b>".$language."</b>", E_USER_ERROR);
		}
		$this->language = $language;
		$this->_updateTranslations();
	}

	/**
	 * Default fallback language
	 * @access private
	 */
	var $fallbackLanguage = "en";

	/**
	 * Returns the fallback language
	 */
	function getFallbackLanguage()
	{
		return $this->fallbackLanguage;
	}

	/**
	 * Sets the fallback language
	 * @param string Two-character code of the wanted language
	 */
	function setFallbackLanguage($language)
	{
		$this->fallbackLanguage = $language;
		$this->_updateTranslations();
	}

	/**
	 * Lists available languages
	 *
	 * The list is determined by looking for all two-character subfolders of the repositories.
	 *
	 * @return string[] A list of codes for the available languages
	 */
	function getAvailableLanguages()
	{
		$languages = array();

		foreach (array_keys($this->repositories) as $repository)
		{
			if ($dirHandle = opendir($repository))
			{
				while ($item = readdir($dirHandle))
				{
					if (preg_match("/^[a-z]{2}$/", $item) && is_dir($repository.$item)) {
						$languages[] = $item;
					}
				}
				closedir($dirHandle);
			}
		}

		$languages = array_unique($languages);
		sort($languages);

		return $languages;
	}

	/**
	 * Storage for ID/translation pairs
	 * @access private
	 */
	var $translations = array();

	/**
	 * Looks for a string with the ID <kbd>$id</kbd> and uses <kbd>$arguments</kbd> as the placeholder pool
	 *
	 * Searches for $id in the primary language, if not found in the fallback language and throws an error otherwise.
	 *
	 * @return string The translated string
	 */
	function translate($id, $arguments = array(), $text = NULL, $language = "none")
	{
		// Allows an empty $argument for Sigma by passing an empty string
		if (! $arguments) {
			$arguments = array();
		}
	
		if (! is_array($arguments)) {
			trigger_error("translation arguments must be passed inside an array", E_USER_ERROR);
		}
		foreach (array($language, $this->language, $this->fallbackLanguage) as $language)
		{
			if (isset($this->translations[$language][$id])) {
				$translation = $this->translations[$language][$id];
				break;
			}
		}

		if (! isset($translation))
		{
			$translation = $id;
			if (isset($text)) {
				return $text;
			}
			trigger_error("There is no translation for ID <b>".$id."</b> in ".implode(" nor in ", array_unique(array("<b>".$this->language."</b>", "<b>".$this->fallbackLanguage."</b>"))), E_USER_WARNING);
		}
		else
		{
			preg_match_all("/\\[([a-zA-Z0-9_-]+)\\]/", $translation, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				if (! array_key_exists($match[1], $arguments)) {
					trigger_error("Undefined argument <b>".$match[1]."</b> used in translation of ID <b>".$id."</b> for language <b>".$language."</b>", E_USER_WARNING);
					$translation = str_replace($match[0], "\\[".$match[1]."\\]", $translation);
				}
				else {
					$translation = str_replace($match[0], $arguments[$match[1]], $translation);
				}
			}

			$translation = stripslashes($translation);
		}

		return $translation;
	}

	/**
	 * List of repositories
	 * @access private
	 */
	var $repositories = array();

	/**
	 * Adds a repository
	 * @param string The path to the repository directory
	 */
	function addRepository($directory)
	{
		if (! is_dir($directory)) {
			trigger_error("Cannot add <i>".$directory."</i> to the translation repositories, it is not a directory", E_USER_ERROR);
		}

		$directory = str_replace("\\", "/", $directory);
		if (! substr($directory, -1) == "/") {
			$directory .= "/";
		}

		if (isset($this->repositories[$directory])) {
			return;
		}

		$this->repositories[$directory] = array();
		$this->_updateTranslations();
	}

	/**
	 * Adds new translation strings
	 *
	 * @param string The language to add translations for
	 * @param string[] An associative array mapping translation string IDs to translations
	 */
	function addTranslations($language, $translations)
	{
		if (! isset($this->translations[$language])) {
			$this->translations[$language] = $translations;
		}
		else {
			// This order prevents overwriting of existing translations
			$this->translations[$language] = array_merge($translations, $this->translations[$language]);
		}
	}

	/**
	 * @access private
	 */
	function _updateTranslations()
	{
		// Remove translation for languages that are no longer active
		foreach (array_keys($this->translations) as $language)
		{
			if ($language != $this->language && $language != $this->fallbackLanguage)
			{
				unset($this->translations[$language]);
				foreach(array_keys($this->repositories) as $directory) {
					if (isset($this->repositories[$directory][$language])) {
						unset($this->repositories[$directory][$language]);
					}
				}
			}
		}

		// Load all unloaded translations for the current languages from the repositories
		foreach (array_keys($this->repositories) as $directory)
		{
			foreach (array_unique(array($this->language, $this->fallbackLanguage)) as $language)
			{
				if (! isset($this->repositories[$directory][$language]))
				{
					$root = $directory.$language."/";
					if (file_exists($root))
					{
						if (! $dirHandle = opendir($root)) {
							trigger_error("Could not open directory <b>".$root."</b> for reading", E_USER_ERROR);
						}
						while ($item = readdir($dirHandle))
						{
							if (substr($item, -4) == ".php") {
								$this->_loadTranslationFile($root.$item, $language);
							}
						}
					}
					$this->repositories[$directory][$language] = TRUE;
				}
			}
		}
	}

	/**
	 * @access private
	 */
	function _loadTranslationFile($translationFile, $language)
	{
		include($translationFile);
		if (! isset($TRANSLATIONS)) {
			trigger_error("Invalid translation file <b>".$translationFile."</b> (\$TRANSLATIONS was not defined)", E_USER_ERROR);
		}

		$this->addTranslations($language, $TRANSLATIONS);
	}
}

?>
