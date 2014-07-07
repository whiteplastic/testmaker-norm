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

class MimeTypes {
	
	public static $knownTypes = array(
			 "application/x-shockwave-flash" => array("name" => "Shockwave Flash", "url" => "http://www.adobe.com/shockwave/download/", "filetype" => "*.swf",),
			 "video/x-flv" => array("name" => "Flash Video", "url" => "http://get.adobe.com/flashplayer/", "filetype" => "*.flv",),
			 "image/gif" => array("name" => "Graphics Interchange Format", "url" => "" , "filetype" => "*.gif", ),
			 "image/jpg" => array("name" => "Joint Photographic Experts Group", "url" => "" , "filetype" => "*.jpg",),
			 "image/jpeg" => array("name" => "Joint Photographic Experts Group", "url" => "" , "filetype" => "*.jpeg",),
			 "image/pjpeg" => array("name" => "Joint Photographic Experts Group", "url" => "" , "filetype" => "*.jpeg",),
			 "image/png" => array("name" => "Portable Network Graphics", "url" => "" , "filetype" => "*.png",),
			 "image/x-png" => array("name" => "Portable Network Graphics", "url" => ""  , "filetype" => "*.png",),
			);
}
