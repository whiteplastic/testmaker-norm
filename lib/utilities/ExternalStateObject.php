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
 * Base class for objects with external state (useful for sessions)
 *
 * Such objects are meant to be created at run-time, with an empty constructor.
 * To initialize an object as you would by calling the constructor, use an <kbd>init()</kbd> method.
 * To store the state of the object, call {@link ExternalStateObject::saveState()}, to load
 * a state, provide it to {@link ExternalStateObject::loadState()}.
 *
 * Example (not using {@link ExternalStateObject}):
 * <code>
 * if (! isset($_SESSION["foos"])) {
 *   $foos = array();
 *   for ($i = 0; $i < 10; $i++) {
 *     $foos[$i] = new Foo($i);
 *   }
 *   $_SESSION["foos"] = $foos;
 * }
 * // ...
 * $thirdfoo = $_SESSION["foos"][3];
 * </code>
 *
 * Example (using {@link ExternalStateObject}):
 * <code>
 * if (! isset($_SESSION["foos"])) {
 *   $foo = new Foo();
 *   $foos = array();
 *   for ($i = 0; $i < 10; $i++) {
 *     $foo->init($i);
 *     $foo->saveState($foos[$i]);
 *   }
 *   $_SESSION["foos"] = $foos;
 * }
 * // ...
 * $thirdfoo = new Foo();
 * $thirdfoo->loadState($_SESSION["foos"][3]);
 * </code>
 *
 * @package Core
 */
class ExternalStateObject
{
	/**
	 * Sets the state
	 * @param array The state to set
	 */
	function loadState($data)
	{
		foreach (array_keys(get_object_vars($this)) as $variableName) {
			if (array_key_exists($variableName, $data)) {
				$this->$variableName = $data[$variableName];
			}
		}
	}

	/**
	 * Stores the state in the array referenced by the argument
	 * @param array Reference to the storage array
	 */
	function saveState(&$data)
	{
		foreach (array_keys(get_object_vars($this)) as $variableName) {
			$data[$variableName] = $this->$variableName;
		}
	}
}

?>