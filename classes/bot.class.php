<?php

/**
 * Bot subclass
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.1a
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

	public function __construct($aServerInfo, $aBotInfo)
	{
		$this->m_aNetwork = $aServerInfo;
		foreach($aBotInfo as $key => $value) {
			$this->m_aBotInfo[$key] = $value;
		}
		$this->m_bIsConnected = false;
		$this->m_aPing['PingID'] = uniqid('p');
		$this->_install();
		$this->_connect();
	}

	private function _install()
	{
		$this->pSocket = new Socket($this);
		$this->pSocket->_setPort($this->m_aNetwork['Port']);
		$this->pSocket->_setSSL($this->m_aNetwork['SSL'], $this->m_aNetwork['SSL_CRT']);
		try {
			$this->pSocket->_setServer(
				$this->pSocket->_getValidServer($this->m_aNetwork['Servers'])
			);
		} catch (Exception $e) {
			Log::Error($e->getMessage());
			die;
		}
	}

	public function _connect()
	{

		if($this->pSocket->_connect()) {
			$this->m_aPing['Uptime'] = time();
			// register bot info on the network
			$this->Pass(time());
			$this->Nick($this->m_aBotInfo['Nick']);
			$this->User($this->m_aBotInfo['Username'], $this->m_aBotInfo['Realname']);
			return true;
		}
		Log::Error(date('[H:i:s]').' Bot '.$this->m_aBotInfo['Nick'].' couldn\'t connect to '.$this->m_aNetwork['Name'].' (connect error @ logs/debug.log). Trying again in 5 seconds..');
		$Timer = Timer::getInstance();
		$Timer->_add($this, "_connect", $Timer::NO_ARGUMENTS, 5);
		return false;
	}

	public function _disconnect()
	{
		if($this->_isConnected()) {
			$this->m_bIsConnected = false;
			return $this->pSocket->_disconnect($this->m_aBotInfo['Quit']);
		}
		return false;
	}

	public function _reconnect()
	{
		if($this->_isConnected()) {
			$this->_disconnect($this->m_aBotInfo['Quit']);
			$this->_connect();
			return true;
		}
		return false;	
	}

	public function _isChild()
	{
		return $this->m_aBotInfo['Child'];
	}

	public function _isConnected()
	{
		return $this->m_bIsConnected;
	}
	
	public function _ping()
	{
		if($this->_isConnected()) 
			return $this->Notice($this->m_aBotInfo['Nick'], $this->m_aPing['PingID']);
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
		if(!isset($sSplit[1]))
			return;
		switch(@strtolower($sSplit[1])) {
			case 'notice':
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sMessage = substr(implode(array_slice($sSplit, 3)), 1);
				if(!strcmp($this->m_aPing['PingID'], $sMessage))
					continue; // fall through
				$ptr->_triggerEvent($this, "onBotNotice", $sUser, $sMessage, $sIdent);
				break;
			case 'privmsg':
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
								$this->Notice($sUser, "You have been successfully identified. Use !cmds for a list of all available commands.");
							} else {
								// todo: log invalid attempts and black after a certain amount of invalid tries
								$this->Notice($sUser, ">> Invalid login attempt. Ident has been logged.");
							}
						}
						$ptr->_triggerEvent($this, "onPrivateMessage", $sUser, $sMessage, $sIdent);
					}
				}
				break;
			case 'invite':
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = substr($sSplit[3], 1);
				$ptr->_triggerEvent($this, "onInvite", $sUser, $sChannel, $sIdent);
				break;
			case 'join':
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = substr($sSplit[2], 1);
				Privileges::AddUser($sChannel, $sUser);
				$ptr->_triggerEvent($this, "onChannelJoin", $sChannel, $sUser, $sIdent);
				break;
			case 'part':
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
			case 'kick':
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = $sSplit[2];
				$sVictim = $sSplit[3];
				$sReason = substr(implode(' ', array_slice($sSplit, 4)), 1);
				$ptr->_triggerEvent($this, "onChannelKick", $sChannel, $sUser, $sVictim, $sReason, $sIdent);
				break;
 			case 'mode':
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
			case 'nick':
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sNew = substr($sSplit[2], 1);
				Privileges::RenameUser($sUser, $sNew);
				$ptr->_triggerEvent($this, "onNickChange", $sUser, $sNew, $sIdent);
				break;
			case 'quit':
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sMessage = substr(implode(' ', array_slice($sSplit, 2)), 1);
				Privileges::RemoveUser(NULL, $sUser);
				$ptr->_triggerEvent($this, "onUserQuit", $sUser, $sMessage, $sIdent);
				break;
			case 'topic':
				if($this->_isChild()) 
					return;
				$sUser = substr($sSplit[0], 1, strpos($sSplit[0], '!')-1);
				$sChannel = $sSplit[2];
				$sTopic = substr(implode(' ', array_slice($sSplit, 3)), 1);
				$ptr->_triggerEvent($this, "onChannelTopic", $sChannel, $sUser, $sTopic, $sIdent);
				break;
			case self::NAMES_LIST:
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
			case self::NICKNAME_ALREADY_IN_USE:
				Log::Error(date('[H:i:s]').' Nickname is already in use. Using alternative nick ('.$this->m_aBotInfo['AltNick'].')');
				$this->Nick($this->m_aBotInfo['AltNick']);
				break;
			case self::END_MOTD:
				if(!$this->_isConnected()) {
					$this->_sendCommand('MODE '.$this->m_aBotInfo['Nick'].' +B');
					foreach($this->m_aBotInfo['Channels'] as $Channel)
						$this->Join($Channel);
					$this->m_bIsConnected = true;
					$ptr->_triggerEvent($this, "onBotConnect");
				}
				break;
			case self::CHANNEL_REQ_KEY:
				$sChannel = $sSplit[3];
				Log::Error(date('[H:i:s]').' Channel ('.$sChannel.') requires a valid key to join. (+k)');
				break;
			default:
				if($this->_isChild()) 
					return;
				if(is_numeric($sSplit[1])) {
					//$sSplit[3] = substr($sSplit[3], 1);
					$ptr->_triggerEvent($this, "onRawEvent", $sSplit[1], array_slice($sSplit, 3), $sIdent);
				}
				break;
		}
		
	}

	public function _update()
	{
		return $this->pSocket->_receive();
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