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

require_once("../../lib/utilities/StopWatch.php");
require_once(dirname(__FILE__)."/../../init.php");
require_once(ROOT.'portal/init.php');

function selfURL() { 
$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['PHP_SELF']; } 

function strleft($s1, $s2) { 
return substr($s1, 0, strpos($s1, $s2));
}


if(empty($_GET["time"]))
$time = 25;
else if($_GET["time"] == 25)
$time = 120;
else if($_GET["time"] == 120)
$time = 25;

$url = selfURL()."?time=$time";;
echo"<head><meta http-equiv=\"refresh\" content=\"$time;url=$url\" /></head> Time: $time";
if($time != 120)
mysql_query("OPTIMIZE TABLE `".DB_PREFIX."test_run_block_content`");

?>