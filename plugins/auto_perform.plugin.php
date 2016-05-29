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

Class AutoPerform extends Main
{
	public function __construct() 
	{
		echo ">> Auto Perform has been loaded.".PHP_EOL;
	}

	public function onBotConnect($bot)
	{
		if(array_key_exists('Commands', $bot->m_aBotInfo)) {
			foreach($bot->m_aBotInfo['Commands'] as $Command) {
				$bot->_sendCommand($Command);
			}
		}
	}

}

?>