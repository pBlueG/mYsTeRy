<?php

/**
 * Auto perform class
 * Executes RAW commands once a bot is connected
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class AutoPerform
{
	public function __construct() 
	{
		echo ">> Auto Perform plugin called.".PHP_EOL;
	}

	public function onBotConnect($bot)
	{
		foreach($bot->m_aBotInfo['Commands'] as $Command) {
			$bot->_sendCommand($Command);
		}
	}

}

?>