<?php

/**
 * Channel log class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class ChannelLog extends Main
{
	private $m_aChannels = array();
	private $m_sTable;

	const CHANNEL_LOG_TABLE = 'channel_log';

	public function __construct()
	{
		$this->m_sTable = 'bot_channel_log';
		// register_shutdown_function()
		// if table exists
	}

	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{
		switch(strtolower($command)) {
			case '!addchannel': 
				break;
			case '!addchannel': 
				break;
			default:
				break;
		}
	}
}

?>