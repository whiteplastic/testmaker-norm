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
 * Shortcut for calling index.php?page=test_make&action=start_test
 * @see TestMakePage::doStartTest()
 * @package Portal
 */

if(isset($_GET["tan"]) && $_GET["tan"] != NULL) {
	$_GET["page"] = "tan_login";
	$_GET["action"] = "use_tan";
} else {
	$_GET["page"] = "test_make";
	$_GET["action"] = "start_test";
}
if (isset($_GET["test-id"])) {
	$_GET["test_id"] = str_replace("-","_",$_GET["test-id"]);
}
if (isset($_GET["test-path"])) {
	$_GET["test_path"] = str_replace("-","_",$_GET["test-path"]);
}
if (!preg_match('/_$/', $_GET['test_path'])) {
	$_GET['test_path'] .= '_';
}

/**
 * Include index.php
 */
include("index.php");

