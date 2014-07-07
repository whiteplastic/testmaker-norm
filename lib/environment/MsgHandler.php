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
 * Flags a message as a report of success.
 */
define('MSG_RESULT_POS', 1);
/**
 * Flags a message as neutral.
 */
define('MSG_RESULT_NEUTRAL', 0);
/**
 * Flags a message as a report of failure.
 */
define('MSG_RESULT_NEG', -1);

libLoad("environment::Translation");

/**
 * This class provides the interface to handle user messages.
 * It stores the messages in the session and makes the access
 * to the messages available through certain methods.
 *
 * @package Library
 */
class MsgHandler
{
	/**
	 * constructor
	 */
	function MsgHandler() {
		if(isset($_SESSION['msgs'])) unset($_SESSION['msgs']);
		$_SESSION['msgs'] = array();
	}

	/**
	 * installs the message handler in the GLOBALS
	 *
	 */
	function install()
	{
		if (isset($GLOBALS["MSG_HANDLER"])) {
			return;
		}

		$GLOBALS["MSG_HANDLER"] = new MsgHandler();
	}

	/**
	 * Adds a message to the message handler by the given translation ID
	 * @param string $msg message
	 * @param int $flag one of MSG_RESULT_POS, MSG_RESULT_NEUTRAL, MSG_RESULT_NEG.
	 * @param array $replace An associative array with values to be replaced in the message (see documentation of 'T()' for more details)
	 */
	function addMsg($msgid, $flag = 0, $replace = array())
	{
		$_SESSION['msgs'][] = array('msg' => T($msgid, $replace), 'flag' => $flag, 'hash' => md5($msgid));
	}

	/**
	 * Adds a already translated/generated message to the message handler
	 * @param string $msg message
	 * @param int $flag one of MSG_RESULT_POS, MSG_RESULT_NEUTRAL, MSG_RESULT_NEG.
	 * @param array $replace An associative array with values to be replaced in the message (see documentation of 'T()' for more details)
	 */
	function addFinishedMsg($msgContent, $flag = 0)
	{
		if(!isset($msgid)) $msgid = 0;
		$_SESSION['msgs'][] = array('msg' => $msgContent, 'flag' => $flag, 'hash' => md5($msgid));
	}

	/**
	 * Adds multiple already translated/generated message to the message handler with the same flag
	 * @param string $msg[] message
	 * @param int $flag one of MSG_RESULT_POS, MSG_RESULT_NEUTRAL, MSG_RESULT_NEG.
	 * @param array $replace An associative array with values to be replaced in the message (see documentation of 'T()' for more details)
	 */
	function addMultipleFinishedMessages($msgContent, $flag = 0) {
		if(!isset($msgid)) $msgid = 0;
		for($i = 0; $i < count($msgContent); $i++) {
			$_SESSION['msgs'][] = array('msg' => $msgContent[$i], 'flag' => $flag, 'hash' => md5($msgid));
		}
	}

	/**
	 * Returns all stored messages in an array
	 *
	 * Each array element is an associative array with the elements 'msg' and 'flag'
	 *
	 * @return array
	 */
	function &getMessages()
	{
		$tmp = $_SESSION['msgs'];
		$_SESSION['msgs'] = array();
		//sort msgs
		usort($tmp, array(get_class($this), '_compareMsgTypes'));

		return $tmp;
	}

	/**
	 * Returns the number of messages stored
	 *
	 * @return integer number of messages
	 */
	function getNumberMessages()
	{
		if(isset($_SESSION['msgs'])) return count($_SESSION['msgs']);
		else
		{
			$_SESSION['msgs'] = array();
			return 0;
		}
	}

	/**
	 * Returns whether a certain message is already in the message queue
	 *
	 * @param $msgid message id of the message to find
	 */
	function isMsgQueued($msgid)
	{
		foreach($_SESSION['msgs'] as $msg)
		{
			if($msg['hash'] == md5($msgid)) return true;
		}
		return false;
	}

	/**
	 * compare function to sort msg array
	 * @access private
	 * @param $a element 1
	 * @param $b element 2
	 * @return int
	 */
	function _compareMsgTypes($a, $b) {
		if($a['flag'] == $b['flag']) {
			return 0;
		} else if($a['flag'] == MSG_RESULT_NEG && $b['flag'] == MSG_RESULT_POS) {
			return -1;
		} else if($a['flag'] == MSG_RESULT_NEG && $b['flag'] == MSG_RESULT_NEUTRAL) {
			return -1;
		} else if($a['flag'] == MSG_RESULT_POS && $b['flag'] == MSG_RESULT_NEUTRAL) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * delete all pending messages
	 */
	function flushMessages()
	{
		$_SESSION['msgs'] = array();
	}
}

?>
