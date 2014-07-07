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

class ItemTemplate
{
	/** private */
	var $templateDir;
	
	/**
	 * Constructor
	 */
	function ItemTemplate()
	{
		$this->templateDir = 'upload/items/';
	}

	/**
	 * Receive a list with all item templates, including their descriptions and preview images links
	 * @param Int Number of items per page
	 * @return Array Data related to the item templates
	 */
	function getList($itemsPerPage = NULL)
	{
		$items = array();
		$handle = opendir($this->templateDir);
		libLoad('utilities::snakeToCamel');
		while ($item = readdir($handle))
		{
			if ($item == "standardTemplates.php") continue;

			if (! is_file($this->templateDir.$item)) {
				continue;
			}

			if (preg_match("/^(.*)\\.(php)$/", $item))
			{
				require_once($this->templateDir.$item);

				$model = str_replace('.php', '', $item);
				$modelVars = get_class_vars($model);
				$allowedIn = $modelVars['allowedInBlock'];
				
				$items[$model]['description'] = eval("return $model::\$description;");

				$ext = '';
				$rawName = camelToSnake(str_replace('Item', '', $model));
				if(file_exists($this->templateDir.$rawName.'.png')) $ext = '.png';
				elseif(file_exists($this->templateDir.$rawName.'.jpg')) $ext = '.jpg';
				elseif(file_exists($this->templateDir.$rawName.'.gif')) $ext = '.gif';
				$items[$model]['image'] = $this->templateDir.$rawName.$ext;

				if ($itemsPerPage !== NULL && $itemsPerPage > 1 && !in_array(MULTI_BLOCK, $allowedIn)) {
					continue;
				}
				$items[$model]['file'] = $item;
			}
		}
		closedir($handle);
		
		return $items;
	}

	/**
	 * Get the description of a certain item template in a certain language
	 * @param String Name of the template in camel case (e.g. TextFloatLineItem)
	 * @param String Desired language of the description (e.g. de, en, ...)
	 * @return String The description of the template or FALSE when template not found
	 */
	function getTemplateDesc($name, $language = 'en')
	{
		require_once($this->templateDir.$name.'.php');
		$description = eval("return $name::\$description[\$language];");
		if($description) return $description;
		else return false;
	}

	/**
	 * Get the location where the item template are saved
	 * @return String Path to template directory
	 */
	function getTemplateDir()
	{
		return $this->templateDir;
	}
}
