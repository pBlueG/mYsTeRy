<?php

Class DamianC
{
	public function __construct()
	{
	}

	public function onChannelMessage($bot, $channel, $user, $message, $ident)
	{
		if(!strcmp($user, 'DamianC')) {
			$bot->Say($channel, 'DamianC has said something in this channel.');
		}
	}

}

?>