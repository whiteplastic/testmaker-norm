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


function translateUploadError($error, $useHtml = TRUE)
{
	switch($error) {
		case UPLOAD_ERR_OK:
			$message = 'There is no error, the file uploaded with success.';
			break;
		case UPLOAD_ERR_INI_SIZE:
			$message = 'The uploaded file exceeds the <a href="http://php.net/manual/en/ini.core.php#ini.upload-max-filesize">upload_max_filesize</a> directive in <code>php.ini</code>.';
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$message = 'The uploaded file exceeds the <var>MAX_FILE_SIZE</var> directive that was specified in the HTML form.';
			break;
		case UPLOAD_ERR_PARTIAL:
			$message = 'The uploaded file was only partially uploaded.';
			break;
		case UPLOAD_ERR_NO_FILE:
			$message = 'No file was uploaded.';
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$message = 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$message = 'Failed to write file to disk. Introduced in PHP 5.1.0.';
			break;
		default:
			$message = 'Unknown error';
			break;
	}

	if (! $useHtml) {
		$message = strip_tags($message);
	}

	return $message;
}

?>