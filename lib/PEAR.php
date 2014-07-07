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
 * Adds the PEAR directory to the include_path
 *
 * PEAR should be located in external/PEAR/.
 *
 * @package Library
 */

// Add the PEAR directory to the include path
libLoad("environment::IncludePath");
$pathToPear = EXTERNAL."PEAR/";
if (! is_dir($pathToPear)) {
	trigger_error("PEAR is not installed in <b>".$pathToPear."</b>", E_USER_ERROR);
}
addIncludePath($pathToPear);
unset($pathToPear);

require_once("PEAR.php");

?>