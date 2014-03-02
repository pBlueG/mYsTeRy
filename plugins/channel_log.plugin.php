<?php

/**
 * Channel log class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class ChannelLog
{
	private $m_aChannels = array('#mystery');
	private $m_sTable;
	
	const LOG_PATH = 'logs/channels/';

	public function __construct()
	{
		$this->m_sTable = 'log_channels';
		if(!file_exists(self::LOG_PATH))
			mkdir(self::LOG_PATH);
		$pDB = Database::getInstance();
		if(!$pDB->_table_exists($this->m_sTable)) {
			$pDB->_create_table(
				$this->m_sTable,
				array(
					'id' 		=> $pDB->_type('auto_increment', 5),
					'channel' 	=> $pDB->_type('string', 30)
				)
			);
		} else {
			$result = $pDB->_query('SELECT * FROM `'.$this->m_sTable.'`;');
			while($row = $pDB->_fetch_array($result)) 
				$this->m_aChannels[] = $row['channel'];
		}
		unset($pDB);
	}

	public function __destruct()
	{
		foreach($this->m_aChannels as $sChannel) {
			$szFormat = sprintf('-- Log session ended on %s', date('D d/m/y H:i:s'));
			Log::_Log(self::LOG_PATH.$sChannel, $szFormat);
		}
	}

	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{
		if(!Privileges::IsBotAdmin($ident))
			return;
		switch(strtolower($command)) {
			case '!addchannel': 
				if(!count($params) || !Misc::isChannel($params[0]))
					return $bot->Say($recipient, '[color=red][b]Syntax:[/b][/color] !addlogchannel (#channel)');
				$this->addChannel($params[0]);
				$bot->Say($recipient, '>> The channel has been successfully added to the log buffer');
				break;
			case '!delchannel': 
				if(!count($params) || !Misc::isChannel($params[0]))
					return $bot->Say($recipient, '[color=red][b]Syntax:[/b][/color] !addlogchannel (#channel)');	
				break;
			default:
				break;
		}
	}

	private function addChannel($channel)
	{
		if(!in_array(strtolower($channel), $this->m_aChannels)) {
			Database::getInstance()->_insert($this->m_sTable, array('channel' => $channel));
			$this->m_aChannels[] = strtolower($channel);
			return true;
		}
		return false;
	}

	public function onChannelMessage($bot, $channel, $user, $message, $ident)
	{
		if(in_array(strtolower($channel), $this->m_aChannels)) {
			$szFormat = sprintf('%s <%s> %s', date('[H:i:s]'), $user, $message);
			Log::_Log(self::LOG_PATH.$channel, $szFormat);
		}
	}

	public function onChannelJoin($bot, $channel, $user, $ident)
	{
		if(in_array(strtolower($channel), $this->m_aChannels)) {
			$szFormat = sprintf('%s %s (%s) has joined %s', date('[H:i:s]'), $user, $ident, $channel);
			Log::_Log(self::LOG_PATH.$channel, $szFormat);
		}
	}

	public function onChannelPart($bot, $channel, $user, $partmsg, $ident)
	{
		if(in_array(strtolower($channel), $this->m_aChannels)) {
			$szFormat = sprintf('%s %s (%s) has left %s (%s)', date('[H:i:s]'), $user, $ident, $channel, $partmsg);
			Log::_Log(self::LOG_PATH.$channel, $szFormat);
		}
	}

	public function onUserQuit($bot, $user, $quitmsg, $ident)
	{
		if(count($this->m_aChannels) > 0) {
			$szFormat = sprintf('%s %s (%s) Quit (%s)', date('[H:i:s]'), $user, $ident, $quitmsg);
			foreach($this->m_aChannels as $sChannel) {
				Log::_Log(self::LOG_PATH.$sChannel, $szFormat);
			}
		}
	}
}

?>