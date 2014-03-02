<?php

/**
 * Ctcp plugin class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Ctcp
{
	function __construct()
	{
		echo '>> CTCP plugin has been loaded.'.PHP_EOL;
	}
	
	public function onCTCPRequest($bot, $user, $request, $message, $ident)
	{
		// common CTCP requests
		switch($request) {
			case 'VERSION': {
				$bot->Notice($user, 'Running mYsTeRy '.REVISION);
				break;
			}
			case 'PING': {
				$current = NULL;
				if(is_numeric($message))
					$current = round(microtime(true)-floatval($message), 2);
				$bot->Notice($user, 'reply took '.$current.'s');
				break;
			}
			case 'TIME': {
				$sTime = date('l, d. F, H:i:s');
				$bot->Notice($user, $sTime);
				break;
			}
			default:
				// ignore
				break;
		}
	}
}	

?>