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
 * Shortcut for calling index.php?page=user_register&action=confirm_register&key=<key>
 * @see UserRegisterPage::doConfirmRegister()
 * @package Portal
 */
$_GET["page"] = "user_register";
$_GET["action"] = "confirm_register";
/**
 * Include index.php
 */
include("index.php");

?>