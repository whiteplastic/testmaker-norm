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
 * Include the test wrapper class
 */
require_once(CORE.'types/Test.php');

/**
* This class provides an easier access to Test-Styles
*/

class FrontendPage extends Page
{
	function getStyle($blockId)
	{
		$block = $GLOBALS['BLOCK_LIST']->getBlockById($blockId);
		if (!$block->getUseParentStyle() || $block->isRootBlock()) {
			$testStyle = "";
			foreach ($block->getStyle() as $key => $value)
			{
				$tempStyle = "";
				foreach ($value as $oKey => $oValue)
				{
					if ($oValue) {
						$tempStyle .= "\t\t".$oKey.": ".$oValue.";\n";
					}
				}
				if ($tempStyle != "") {
					$testStyle .= "\t";
					if ($key != 'body') {
						$testStyle .= '.';
					}
					$testStyle .= $key." {\n".$tempStyle."\t}\n";
				}
			}
			return $testStyle;
		} else {
			$parents = $block->getParents();
			return $this->getStyle($parents[0]->getId());
		}
	}
}
