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
 * @package Core
 */

/**
 * TemplateList class
 *
 * @package Core
 */
class TemplateList {
	
	/**#@+
	 *
	 * @access private
	 */
	var $db;
	/**#@-*/
	
	/**
	 * Constructor, set database connection
	 */
	function TemplateList()
	{
		$this->db = &$GLOBALS['dao']->getConnection();
	}
	
	/**
	 * Receive the description of a template from the database in a certain language
	 *
	 * @param string $name Name of the template
	 * @param string $language Language of description
	 * @return description (string) or false
	 */
	function getDescription($name, $language)
	{
		if(array_search($language, $GLOBALS['TRANSLATION']->getAvailableLanguages()) !== false)
		{
			$description = $this->db->getOne("SELECT description_$language FROM ".DB_PREFIX."templates WHERE name=? LIMIT 1", array($name));
			if($description) return $description;
			else return false;
		}
	}
	
	/**
	 * Receive a list with the info of the currently existing templates
	 *
	 * @return array Currently existing templates
	 */
	function getList()
	{
		$templatesList = array();
		$res = $this->db->query("SELECT id,name,description_de,description_en,editable FROM ".DB_PREFIX."templates ORDER BY name");
		
		while($row = $res->fetchRow())
		{
			$templatesList[$row['name']] = array('id' => $row['id'], 'description_de' => $row['description_de'], 'description_en' => $row['description_en'], 'editable' => $row['editable']);
		}
		return $templatesList;
	}
	
	/**
	 * Receive the info about a template through it's id
	 *
	 * @param int $id ID of the template
	 * @return array Array with the template data
	 */
	function getTemplate($id)
	{
		$template = array();
		$res = $this->db->query("SELECT name,image_type,description_de,description_en,editable FROM ".DB_PREFIX."templates WHERE id=?", array($id));
		$template = $res->fetchRow();
		return $template;
	}
	
	/**
	 * Save the details of a template to the database
	 *
	 * @param string $name Name of the template
	 * @param string $descGerman Description in german
	 * @param string $descEnglish Description in english
	 * @return success (true or false)
	 */
	function saveTemplateDescriptions($name, $imageType, $descGerman, $descEnglish)
	{
		$id = $this->db->nextID(DB_PREFIX."templates");
		return $this->db->query("INSERT INTO ".DB_PREFIX."templates SET id=?, name=?, image_type=?, description_de=?, description_en=?, editable=1", array($id, $name, $imageType, $descGerman, $descEnglish));
	}
	
	/**
	 * Update the descriptions of a template
	 *
	 * @param int $id ID of the template
	 * @param string $descGerman Description in german
	 * @param string $descEnglish Description in english
	 * @return success (true or false)
	 */
	function updateTemplateDescriptions($id, $descGerman, $descEnglish)
	{
		//$nextId = $this->db->query(DB_PREFIX."templates");
		return $this->db->query("UPDATE ".DB_PREFIX."templates SET description_de=?, description_en=? WHERE id=? LIMIT 1", array($descGerman, $descEnglish, $id));
	}
	
	/**
	 * Delete a template from database
	 *
	 * @param int $id ID of the template which should be deleted
	 * @return success (true or false)
	 */
	function deleteTemplate($id)
	{
		return $this->db->query("DELETE FROM ".DB_PREFIX."templates WHERE id=? LIMIT 1", array($id));
	}
}
?>
