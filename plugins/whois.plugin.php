<?php

/**
 * Whois class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Whois extends Main implements RawEvents
{
	private $m_RequiredPrivilege = Privileges::LEVEL_VOICE;
	private $m_aRequest = array();

	public function __construct()
	{
	}

	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{
		switch(strtolower($command)) {
			case '!whois': 
				break;
			default:
				break;
		}
	}

	public function onRawEvent($bot, $rawcode, $data, $server_ident)
	{
		switch($rawcode) {
			case self::WHOIS_USER: // ident reply
				break;
			case '307': // is a registered nick
				break;
			case self::WHOIS_CHANNELS: // channels is on reply
				break;
			case self::WHOIS_SERVER: // server
				break;
			case self::WHOIS_END: // end of whois -> send answer
				$tempArr = end($this->m_aRequest);
				$bot->_sendCommand(
					Commands::Say(
						$tempArr['channel'],
						$tempArr['message']
					)
				);
				
				break;
			case '401': // no such nick/channel
				break;
			default:
				// ignore
				break;
		}
	}
}

?>