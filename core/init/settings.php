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
 * Contains all optionally changeable settings
 *
 * @package Core
 */

/**#@+
 * Configuration of the allowed usernames (usernames are case insensitive)
 */
define('NAME_MIN_CHARS', 3);
define('NAME_MAX_CHARS', 255);
define('FULL_MIN_CHARS', 4);
define('FULL_MAX_CHARS', 255);
define('PASS_MIN_CHARS', 6);
define('REQUESTVAR_STORE_TIME', 300);
define('TAN_MAX_NUM', 100);
define('SUPERUSER_ID', 1);
/**#@-*/

?>
