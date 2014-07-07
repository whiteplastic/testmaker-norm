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
 * Sends a file to the browser.
 * The file is sent with a Content-Disposition header,
 * hopefully resulting in a "Save as..." dialog in the browser.
 *
 * @param string The file to send
 * @param string The MIME type of the file
 * @param string Optional file name to override the real name
 */

function sendFile($path, $mimeType, $fileName = NULL)
{
	if (! is_file($path)) {
		trigger_error("File <b>".$path."</b> does not exist.", E_USER_ERROR);
	}

	if (! isset($fileName)) {
		$fileName = basename($path);
	}

	header("Content-Type: ".$mimeType);
	header("Content-Disposition: attachment; filename=\"".$fileName."\"");
	header("Content-Length: ".filesize($path));

	$fileHandle = fopen($path, "rb");
	fpassthru($fileHandle);
	@fclose($fileHandle);
}

/**
 * Sends any data as a file to the browser.
 * The file is sent with a Content-Disposition header,
 * hopefully resulting in a "Save as..." dialog in the browser.
 *
 * @param string The data to send
 * @param string The MIME type of the data
 * @param string File name to simulate
 */
function sendVirtualFile($contents, $mimeType, $fileName)
{
	header("Content-Type: ".$mimeType);
	header("Content-Disposition: attachment; filename=\"".$fileName."\"");
	header("Content-Length: ".strlen($contents));

	echo $contents;
}

?>