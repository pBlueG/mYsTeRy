<?php

Class Test extends Main
{

	private $count = 0;
	private $ticka = 0;
	private $tickb = 0;

	public function _callMainFunction()
	{
		//$reflection_method = new ReflectionMethod(get_parent_class(get_parent_class($this)));
		//invoke_args()
		//invoke()
	}		

	function __construct()
	{
		echo "TEST plugin has been loaded".PHP_EOL;
	}

	public function onChannelMessage($bot, $channel, $user, $message)
	{
		//echo 'WOOT I WAS CALLED :)' . PHP_EOL . $channel . PHP_EOL . $user . PHP_EOL . $message. PHP_EOL;
	}

	public function onPrivateMessage($bot, $user, $message, $ident)
	{
		//$var = $bot->_isChild();
		//echo 'I WAS PMD BY ' . $user . PHP_EOL . $message. PHP_EOL;
	}

	public function onChannelJoin($bot, $channel, $user, $ident)
	{
		echo PHP_EOL . $user . ' has joined '.$channel . PHP_EOL;
	}
	

	public function onChannelPart($bot, $channel, $user, $partmsg, $ident)
	{
		echo PHP_EOL . $user . ' has left '.$channel;
		if(strlen($partmsg) > 0)
			echo ' ('.$partmsg.')';
		//parent::_get_bot_object
	}

	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{
		echo "Command called: ".$command." / ".print_r($params, true)." / ".$user." / ".$recipient." / ".$ident.PHP_EOL;
	}

	public function onTick()
	{
		$this->count++;
		// 25 ticks = 1 second
		// 50 ticks = 2 seconds
		// 75 ticks = 3 seconds
		// 100 ticks = 4 seconds
		// 1000000 microseconds = 1 second
		// 1000 microseconds  = 1 milisecond
		// 1000 miliseconds = 1 second
		// 40000 microseconds = 40 miliseconds
		// 40 miliseconds = 0,04 seconds
		// 1000000/sleeptime = tick amount per second
		// round((1000000/sleeptime), 0, PHP_ROUND_HALF_UP);
		/*if($this->count >= 100) {
			echo "CALLED";
			if($this->ticka > 0) {
				$this->tickb = microtime(true);
				echo 'TICK RESOLVED : '.($this->tickb-$this->ticka);
				$this->ticka = 0;
			} else {
				echo 'FIRST TICK SET';
				$this->ticka = microtime(true);
			}
			$this->count = 0;
		}*/
	}
}	

?>