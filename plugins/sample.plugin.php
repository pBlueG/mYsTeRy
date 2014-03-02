<?php

/**
 *
 * This is a template for all custom written plugins.
 * Make sure you extend the Main class to all plugins as parent, otherwise
 * you will not be able to access any functions. Comment or remove 
 * all callbacks you don't require. The callbacks MUST be public in order
 * to get called.
 *
 */

Class Sample
{


	public function __construct()
	{
		echo ">> Sample plugin has been loaded.";
	}

	public function onBotNotice($bot, $user, $message, $ident) 
	{

	}

	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{

	}

	public function onChannelMessage($bot, $channel, $user, $message, $ident)
	{
	
	}

	public function onPrivateMessage($bot, $user, $message, $ident)
	{
	
	}

	public function onInvite($bot, $user, $channel, $ident)
	{
	
	}

	public function onChannelJoin($bot, $channel, $user, $ident)
	{
	
	}

	public function onChannelPart($bot, $channel, $user, $partmsg, $ident)
	{
	
	}

	public function onChannelKick($bot, $channel, $user, $victim, $reason, $ident)
	{
	
	}

	public function onChannelMode($bot, $channel, $mode, $option, $ident)
	{
	
	}

	public function onNickChange($bot, $nick, $newnick, $ident)
	{
	
	}
	
	public function onUserQuit($bot, $user, $quitmsg, $ident)
	{
	
	}

	public function onChannelTopic($bot, $channel, $user, $topic, $ident)
	{
	
	}

	public function onTick()
	{
	
	}
}	

?>