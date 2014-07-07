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
 * Fake classes to trick phpDocumentor
 * @package Fakes
 */

trigger_error("This file is only there to trick phpDocumentor. Please do not use it.", E_USER_ERROR);
exit();

/**
 * Fake DB_Table class
 * @package Fakes
 */
class DB_Table
{
}

/**
 * Fake HTML_Template_Sigma class
 * @package Fakes
 */
class HTML_Template_Sigma
{
}

?>