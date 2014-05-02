<?php

/**
 * Bot subclass
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Bot implements RawEvents, ColorCodes
{
	/**
	 * This array stores the bot information
	 *
	 * @var array
	 * @access private
	 */
	public $m_aBotInfo = array();

	/**
	 * This array stores the network info for the given bot
	 *
	 * @var array
	 * @access private
	 */
	private $m_aNetwork;

	/**
	 * Object for the socket instance
	 *
	 * @var object
	 * @access private
	 */
	private $pSocket;

	/**
	 * Bool to determine whether the bot is connected
	 *
	 * @var boolean
	 * @access private
	 */
	private $m_bIsConnected;

	/**
	 * Bool to determine whether the bot is connected
	 *
	 * @var boolean
	 * @access public
	 */
	public $m_aPing = array();	

	public function __construct($aServerInfo, $aBotInfo, $auto_connect = true)
	{
		$this->m_aNetwork = $aServerInfo;
		$this->pSocket = new Socket($this, $this->m_aNetwork);
		foreach($aBotInfo as $key => $value)
			$this->m_aBotInfo[$key] = $value;
		unset($aBotInfo);
		$this->m_bIsConnected = false;
		if($auto_connect) 
			$this->_connectBot();
		$time = time();
		$this->m_aPing['LastHit'] = $time;
		$this->m_aPing['PingID'] = uniqid('p');
		$this->m_aPing['Uptime'] = $time;
	}

	public function _connectBot()
	{
		if($this->pSocket->_connect())
			return $this->_registerBot();
		else {
			Log::Error('>> Bot '.$this->m_aBotInfo['Nick'].' couldn\'t connect to '.$this->m_aNetwork['Name'].'. Trying again in 5 seconds.');
			$Timer = Timer::getInstance();
			$Timer->_add($this, "_connectBot", $Timer::NO_ARGUMENTS, 5);
			return false;
		}
	}

	public function _disconnectBot()
	{
		if($this->_isConnected()) {
			$this->m_bIsConnected = false;
			return $this->pSocket->_disconnect($this->m_aBotInfo['Quit']);
		} else
			return false;
	}

	public function _reconnectBot()
	{
		if($this->_isConnected()) {
			$this->_disconnectBot($this->m_aBotInfo['Quit']);
			$this->_connectBot();
		}	
	}

	public function _isChild()
	{
		// Check whether the given bot is a child
		return $this->m_aBotInfo['Child'];
	}

	public function _isConnected()
	{
		return $this->m_bIsConnected;
	}

	protected function _registerBot() 
	{
		$this->_sendCommand(
			Commands::Pass(substr(md5(time()), 0, 10))
		);
		$this->_sendCommand(
			Commands::Nick($this->m_aBotInfo['Nick'])
		);
		$this->_sendCommand(
			Commands::User($this->m_aBotInfo['Username'], $this->m_aBotInfo['Realname'])
		);
		return true;
	}
	
	protected function _Ping()
	{
		if($this->_isConnected()) 
			return $this->_sendCommand(Commands::Notice($this->m_aBotInfo['Nick'], $this->m_aPing['PingID']));
		else
			return false;
	}

	public function _getLastPing()
	{
		return $this->m_aPing['LastHit'];
	}

	public function _sendCommand($command)
	{
		return $this->pSocket->_send($command);
	}

	public function _getBotNetwork($as_array = false)
	{
		if($as_array)
			return $this->m_aNetwork;
		else
			return $this->m_aNetwork['Name'];
	}

	public function _EventHandler($in_buffer)
	{
		echo $in_buffer.PHP_EOL; //used to analyze raw data
		$sSplit = explode(' ', $in_buffer);
		if(strtolower($sSplit[0]) == 'ping')
			$this->_sendCommand('PONG :'.substr($sSplit[1], 1));
		$sIdent = substr($sSplit[0], 1);
		$ptr = Plugins::getInstance();
		if(is_null($sSplit[1]))
			return;
		switch(@strtolower($sSplit[1])) {
			// let the events begin :o
			case 'notice': {
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sMessage = substr(implode(array_slice($sSplit, 3)), 1);
				if(!strcmp($this->m_aPing['PingID'], $sMessage)) {
					$this->m_aPing['LastHit'] = time();
					continue;
				}
				$ptr->_triggerEvent($this, "onBotNotice", $sUser, $sMessage, $sIdent);
				break;
			}
			case 'privmsg': {
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sMessage = substr($sSplit[3], 1);
				if($sMessage[0] == chr(1)) {
					$sCTCP = str_replace("\001", "", $sMessage);
					if(isset($sSplit[4])) 
						$sMessage = str_replace("\001", "", implode(' ', array_slice($sSplit, 4)));
					else
						$sMessage = NULL;
					$ptr->_triggerEvent($this, "onCTCPRequest", $sUser, $sCTCP, $sMessage, $sIdent);
				} else {
					$sMessage = substr(implode(' ', array_slice($sSplit, 3)), 1);
					if($sMessage[0] == '!') {
						$sMessage = explode(' ', $sMessage);
						//print_r($sSplit);
						if(substr($sSplit[2], 0, 1) == '#')
							$sRecipient = $sSplit[2];
						else
							$sRecipient = $sUser;
						$sCommand = substr($sSplit[3], 1);
						$aParams = isset($sSplit[4]) ? array_slice($sSplit, 4) : NULL;
						CommandHandler::getInstance()->_parse($this, $sCommand, $aParams, $sUser, $sRecipient, $sIdent);
					}
					if($sSplit[2][0] == '#') {
						$sChannel = $sSplit[2];
						$ptr->_triggerEvent($this, "onChannelMessage", $sChannel, $sUser, $sMessage, $sIdent);
					} else {
						$sCheck = explode(' ', $sMessage);
						if(!strcasecmp($sCheck[0], 'login') && !Privileges::IsBotAdmin($sIdent)) {
							if(!strcmp(Main::getInstance()->m_aSettings['AdminPass'], implode(' ', array_slice($sCheck, 1)))) {
								Privileges::AddBotAdmin($sIdent);
								$this->PM($sUser, ">> You have been successfully identified");
							} else {
								// todo: log invalid attempts and black after a certain amount of invalid tries
								$this->PM($sUser, ">> Invalid login attempt. Ident has been logged.");
							}
						}
						$ptr->_triggerEvent($this, "onPrivateMessage", $sUser, $sMessage, $sIdent);
					}
				}
				break;
			}
			case 'invite': {
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = substr($sSplit[3], 1);
				$ptr->_triggerEvent($this, "onInvite", $sUser, $sChannel, $sIdent);
				break;
			}
			case 'join': {
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = substr($sSplit[2], 1);
				Privileges::AddUser($sChannel, $sUser);
				$ptr->_triggerEvent($this, "onChannelJoin", $sChannel, $sUser, $sIdent);
				break;
			}
			case 'part': {
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = $sSplit[2];
				if(count($sSplit) > 3)
					$sPartMessage = substr(implode(' ', array_slice($sSplit, 3)), 1);
				else
					$sPartMessage = '';
				Privileges::RemoveUser($sChannel, $sUser);
				$ptr->_triggerEvent($this, "onChannelPart", $sChannel, $sUser, $sPartMessage, $sIdent);
				break;
			}
			case 'kick': {
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = $sSplit[2];
				$sVictim = $sSplit[3];
				$sReason = substr(implode(' ', array_slice($sSplit, 4)), 1);
				$ptr->_triggerEvent($this, "onChannelKick", $sChannel, $sUser, $sVictim, $sReason, $sIdent);
				break;
			}
 			case 'mode': {
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = $sSplit[2];
				$sMode = $sSplit[3]; // +b/+l/+i etc
				if(count($sSplit) > 4)
					$sOption = implode(' ', array_slice($sSplit, 4));
				else
					$sOption = '';
				Privileges::UpdateUserPrivileges($sChannel, $sOption, $sMode);
				$ptr->_triggerEvent($this, "onChannelMode", $sChannel, $sUser, $sMode, $sOption, $sIdent);
				break;
			}
			case 'nick': {
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sNew = substr($sSplit[2], 1);
				Privileges::RenameUser($sUser, $sNew);
				$ptr->_triggerEvent($this, "onNickChange", $sUser, $sNew, $sIdent);
				break;
			}
			case 'quit': {
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sMessage = substr(implode(' ', array_slice($sSplit, 2)), 1);
				Privileges::RemoveUser(NULL, $sUser);
				$ptr->_triggerEvent($this, "onUserQuit", $sUser, $sMessage, $sIdent);
				break;
			}
			case 'topic': {
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = $sSplit[2];
				$sTopic = substr(implode(' ', array_slice($sSplit, 3)), 1);
				$ptr->_triggerEvent($this, "onChannelTopic", $sChannel, $sUser, $sTopic, $sIdent);
				break;
			}
			case self::NAMES_LIST: {
				if($this->_isChild()) 
					return;
				$sChannel = $sSplit[4];
				$sSplit[5] = substr($sSplit[5], 1);
				$aUsers = array_slice($sSplit, 5);
				Privileges::ParseChannelPrivileges($sChannel, $aUsers);
				/*$this->_sendCommand(
					Commands::Action('#mystery', 'slaps you in the face')
				);*/
				break;
			}
			case self::NICKNAME_ALREADY_IN_USE: {
				$this->_sendCommand(
					Commands::Nick($this->m_aBotInfo['AltNick'])
				);
				break;
			}
			case self::END_MOTD: {
				if(!$this->_isConnected()) {
					$this->_sendCommand('MODE '.$this->m_aBotInfo['Nick'].' +B');
					//if(!empty($this->m_aBotInfo['Password']) && strlen($this->m_aBotInfo['Password']) >= 4)
						//$this->_sendCommand('PRIVMSG NickServ Identify '.$this->m_aBotInfo['Password']);
					foreach($this->m_aBotInfo['Channels'] as $Channel)
						$this->_sendCommand(Commands::Join($Channel));
					$this->m_bIsConnected = true;
					$ptr->_triggerEvent($this, "onBotConnect");
				}
				break;
			}
			default: {
				if($this->_isChild()) 
					return;
				if(is_numeric($sSplit[1])) {
					//$sSplit[3] = substr($sSplit[3], 1);
					$ptr->_triggerEvent($this, "onRawEvent", $sSplit[1], array_slice($sSplit, 3), $sIdent);
				}
				break;
			}
		}
		
	}

	public function _Update()
	{
		return $this->pSocket->_rawData();
	}

	public function __call($method, $args)
	{
		if(method_exists('Commands', $method)) {
			try {
				// allows us to call non-existing methods: $bot->Say() $bot->Notice() etc.
				$pMethod = new ReflectionMethod('Commands', $method);
				$sCommand = $pMethod->invokeArgs(NULL, $args);
				$this->_sendCommand($sCommand);
				unset($pMethod);
			} catch(LogicException $logicError) {
			 	// d'oh
			}
		}
	}
}


?>