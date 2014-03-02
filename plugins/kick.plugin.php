<?php

/**
 * Whois class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class KickBan
{
	
	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{
		switch(strtolower($command)) {
			case '!kick': 
				if(Misc::isChannel($recipient)) {	
					if(count($params) < 1)
						return $bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !kick (user) [reason]');
					if(Privileges::IsOperator($user, $srecipient)) {
						$sTarget = $params[0]; // the user to kick
						$sReason = (count($params) > 1 ? Misc::glueParams(array_slice($params, 1)) : NULL);
						$bot->Kick($recipient, $sTarget, $sReason); 
					}
				}
				break;
			case '!ban':
				if(Misc::isChannel($recipient)) {	
					if(count($params) < 1)
						return $bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !ban (hostmask)');
					if(Privileges::IsOperator($user, $srecipient)) {
						$sTarget = $params[0]; // the user to ban
						$bot->Ban($recipient, $sTarget); 
					}
				}
				break;
			default:
				// skip :o
				break;
		}
	}
}

?>