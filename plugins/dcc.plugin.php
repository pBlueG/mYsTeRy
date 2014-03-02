<?php

/**
 * DCC class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Dcc_Prtcl
{
	private $m_RequiredPrivilege = Privileges::LEVEL_VOICE; // Privileges::IsVoiced($channel, $user);
	private $m_aFileTransfer = array(); // !allowfiletransfer [user/ident] !disallow [user/ident]
	
	public function __construct()
	{
		echo '>> DCC plugin has been loaded'.PHP_EOL;
	}

	public function onCTCPRequest($bot, $user, $request, $message, $ident)
	{
		switch(strtolower($request)) {
			case 'dcc':
				$aParts = explode(' ', $message);
				$sType = $aParts[0];
				$sIP = $aParts[2];
				$iPort = $aParts[3];
				switch(strtolower($sType)) {
					case 'send':
						$sFilename = $aParts[1];
						$iFilesize = $aParts[4];
						break;
					case 'chat':
						break;
					default: 
						echo '>> Invalid DCC protocol has been sent by '.$user.PHP_EOL;
						break;
				}
				break;
			default:
				// ignore
				break;
		}
	}

	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{
		//
	}
}

?>