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


libLoad('environment::MsgHandler');

/**
 * @package Library
 */

/**
 * Sends an XML document to the client (for use with asynchronous scripting).
 * <b>Does not return.</b>
 *
 * @param string The raw XML data to send (excluding <kbd><?xml ?></kbd> header).
 */

function sendXML($data)
{
	header("Content-Type: text/xml");
	echo '<?xml version="1.0" encoding="ISO-8859-15" ?>';
	echo "\n";

	echo $data;
	exit;
}

/**
 * Sends a basic XML container for use with asynchronous JavaScript
 * processing.
 *
 * @param string The name of the container.
 * @param string The content of the container.
 * @param array A key/value list of attributes.
 */
function sendXMLContainer($name, $text, $attributes = array())
{
	$res = "<$name";
	foreach ($attributes as $key => $val) {
		$res .= " $key=\"$val\"";
	}
	if (empty($text)) {
		$res .= " />";
	} else {
		$res .= ">$text</$name>";
	}
	sendXML($res);
}

/**
 * Sends a basic <kbd><status /></kbd> container for use with
 * asynchronous JavaScript processing.
 *
 * @param string The content of the container.
 * @param array A key/value list of attributes.
 */
function sendXMLStatus($text, $attributes = array())
{
	sendXMLContainer('status', $text, $attributes);
}

/**
 * Sends the messages collected by the message handler, using the above
 * sendXMLStatus() function.
 *
 * @param boolean Whether to send a successful status.
 */
function sendXMLMessages($success = false)
{
	$msgs = $GLOBALS['MSG_HANDLER']->getMessages();
	$outMsgs = array();
	foreach ($msgs as $msg) {
		$outMsgs[] = $msg['msg'];
	}
	sendXMLStatus(implode("\n", $outMsgs), array('type' => ($success ? 'ok' : 'fail')));
}

/**
 * Sends HTML code embedded into an XML container so sucky browsers don't
 * complain about invalidity.
 *
 * A <kbd><content></kbd> container will be used.
 *
 * @param string The HTML data to mangle and send.
 */
function sendHTMLMangledIntoXML($data, $attributes = array())
{
	sendXMLContainer('content', htmlspecialchars($data, ENT_QUOTES  | ENT_IGNORE, 'utf-8'), $attributes);
}

