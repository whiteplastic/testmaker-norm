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
 * @package Library
 */

/**
 * Prints a variable recursively (like print_r, but the output is HTML)
 *
 * Example:
 * <code>
 * $myVar = array("foo" => "bar", "list" => array(1, 2, 3));
 * printVar($myVar, "myVar");
 * </code>
 *
 * @param mixed The variable to show
 * @param string An optional headline
 * @param array Class names to ignore
 * @param integer Used for recursion, leave empty!
 */
function printVar(&$variable, $label = "Variable", $ignoreClasses = array("PEAR"), $depth = 0)
{
	$objects = array();
	$arrays = array();

	if ($depth == 0) {
		echo '<table cellpadding="0" cellspacing="1" style="background-color:#999999">';
		echo "\n";
		echo '<tr>';
		echo '<th style="font-family:Verdana,Tahoma,sans-serif; font-size: 11px; text-align:left; background-color:#990000; color:#FFFFFF; padding:2px;">Name</th>';
		echo '<th style="font-family:Verdana,Tahoma,sans-serif; font-size: 11px; text-align:left; background-color:#990000; color:#FFFFFF; padding:2px;">Value</th>';
		echo '</tr>';
		echo "\n";
	}

	$indent = str_repeat("&nbsp;", $depth * 4);
	$unindent = "";

	echo '<tr>';
	echo '<td style="vertical-align:top;font-family:Verdana,Tahoma,sans-serif; font-size: 11px; padding: 1px; padding-left: 2px; padding-right: 4px; background-color: #FFFFFF;">'.$indent.'<b>'.$label.'</b>'.$unindent.'</td>';
	echo '<td style="font-family:Verdana,Tahoma,sans-serif; font-size: 11px; line-height: 135%; padding: 1px; padding-left: 2px; padding-right: 4px; background-color: #FFFFFF;">';

	if ($variable === NULL) {
		echo '<i>NULL</i>';
	}
	elseif (is_string($variable)) {

		echo "<span style=\"color:#666666\">\"</span>";
		$lastSpace = 0;
		for ($i = 0; $i < strlen($variable); $i++)
		{
			$chr = substr($variable, $i, 1);
			if ($lastSpace > 40) {
				echo '<wbr />';
				$lastSpace = 0;
			}

			if ($chr == " ") {
				echo '<span style="border-bottom:1px solid #999999;margin-left:1px;margin-right:1px;">&nbsp;</span>';
			} elseif ($chr == "\t") {
				echo '<span style="border-bottom:1px solid #999999;padding-right:6px; margin-left:1px;margin-right:1px">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
			} elseif ($chr == "\n") {
				echo '<span style="color: #990000">&para;</span><br>';
			} elseif ($chr == "\r") {
				echo '<span style="color: #990000">\r</span>';
			} elseif ($chr == "\0") {
				echo '<span style="color: #990000">\\0</span>';
			} else {
				echo htmlspecialchars($chr);
			}
			$lastSpace++;
		}
		echo "<span style=\"color:#666666\">\"</span>";
	}
	elseif (is_bool($variable)) {
		echo "<i>".($variable ? "TRUE" : "FALSE")."</i>";
	}
	elseif (is_numeric($variable)) {
		if (gettype($variable) == "double") {
			echo preg_replace("/(\d+?)0*$/", "\\1", sprintf("%2.40f", $variable));
		} else {
			echo $variable;
		}
	}
	elseif (is_array($variable)) {
		if (isset($variable["__PRINT_VAR_SPECIAL_TYPE"])) {
			echo '<i>'.ucfirst($variable["__PRINT_VAR_SPECIAL_TYPE"]).'</i>';
		}
		else {
			echo '<i>Array</i>';
		}
	}
	elseif (is_object($variable)) {
		$classTree = array();

		$current = get_class($variable);
		$classTree[] = $current;
		while($parentClass = get_parent_class($current)) {
			$classTree[] = $parentClass;
			$current = $parentClass;
		}

		$printClassTree = $classTree;
		$printClassTree[0] = "<b>".$printClassTree[0]."</b>";
		echo '<i>Object</i> ('.implode(" &lt; ", $printClassTree).')';
	}
	elseif (is_resource($variable)) {
		echo '<i>Resource</i> ('.get_resource_type($variable).')';
	}
	// E.g. incomplete classes
	else {
		echo '<i>Unknown type</i> ('.gettype($variable).')<br />';
		echo '<pre>'.htmlspecialchars(var_export($variable, TRUE)).'</pre>';
	}

	echo '</td>';
	echo '</tr>';
	echo "\n";

	$recursion = FALSE;
	$ignore = FALSE;

	if (is_array($variable)) {
		if (isset($variable["__PRINT_VAR_RECURSION_MARKER"])) {
			$recursion = TRUE;
		}
		elseif (isset($variable["__PRINT_VAR_SPECIAL_TYPE"])) {
		}
		else {
			$variable["__PRINT_VAR_RECURSION_MARKER"] = TRUE;
			$arrays[] = &$variable;
			foreach (array_keys($variable) as $name) {
				$value = &$variable[$name];
				if ($name === "__PRINT_VAR_RECURSION_MARKER") {
					continue;
				}
				printVar($value, $name, $ignoreClasses, $depth+1);
			}
		}
	}
	elseif (is_object($variable)) {
		foreach ($ignoreClasses as $className)
		{
			if (is_a($variable, $className)) {
				$ignore = TRUE;
				break;
			}
		}
		if (! $ignore)
		{
			if (isset($variable->__PRINT_VAR_RECURSION_MARKER)) {
				$recursion = TRUE;
			}
			else {
				$variable->__PRINT_VAR_RECURSION_MARKER = TRUE;
				$objects[] = &$variable;
				foreach (array_keys(get_object_vars($variable)) as $name) {
					if ($name == "__PRINT_VAR_RECURSION_MARKER") {
						continue;
					}
					printVar($variable->$name, $name, $ignoreClasses, $depth+1);
				}
				$lowerClassTree = array_map("strtolower", $classTree);
				foreach (get_class_methods($variable) as $method)
				{
					$type = in_array(strtolower($method), $lowerClassTree) ? "constructor" : "method";
					$recursive_arr = array(
						"__PRINT_VAR_SPECIAL_TYPE" => $type,
						"__PRINT_VAR_NAME" => $method
					);
					printVar($recursive_arr, $method."()", $ignoreClasses, $depth+1);
				}
			}
		}
	}

	$message = NULL;
	if ($ignore) {
		$message = "Ignored";
	}
	elseif ($recursion) {
		$message = "Recursion";
	}
	if (isset($message))
	{
		$indent = str_repeat("&nbsp;", ($depth+1) * 4);
		$unindent = "";

		echo '<tr><td colspan="2" style="font-family:Verdana,Tahoma,sans-serif; font-size: 11px; padding: 1px; padding-left: 2px; padding-right: 4px; background-color: #FFFFFF; color:#990000;">'.$indent.'<b>'.$message.'</b>'.$unindent.'</td></tr>';
		echo "\n";
	}

	for ($i = 0; $i < count($objects); $i++) {
		unset($objects[$i]->__PRINT_VAR_RECURSION_MARKER);
	}
	for ($i = 0; $i < count($arrays); $i++) {
		unset($arrays[$i]["__PRINT_VAR_RECURSION_MARKER"]);
	}

	if ($depth == 0) {
		echo '</table>';
		echo "\n";
	}
}

?>