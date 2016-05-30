
<?php

/**
 * Commandhandler class
 * - parses PRIVMSG data for commands
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class CommandHandler extends Singleton
{
	private $m_aCommands = array();
	private $m_sTable;

	public function __construct()
	{
		$this->m_sTable = 'bot_commands';
		$pDB = Database::getInstance();
		if(!$pDB->_table_exists($this->m_sTable)) {
			$pDB->_create_table(
				$this->m_sTable,
				array(
					'id' 		=> $pDB->_type('auto_increment', 5),
					'command' 	=> $pDB->_type('string', 30),
					'code' 		=> $pDB->_type('text', 800),
					'description' 	=> $pDB->_type('text', 100),
					'privilege' 	=> $pDB->_type('int', 2)
				)
			);
		} else {
			$this->_loadCommands();
		}
		unset($pDB);
	}

	public function _commandExists($sCommand)
	{
		reset($this->m_aCommands);
		while($aCommand = current($this->m_aCommands)) {
			if(!strcasecmp($aCommand['command'], $sCommand)) {
				if(func_num_args() > 1) {
					$key = func_get_arg(1);
					$key->index = key($this->m_aCommands);
				}
				reset($this->m_aCommands);
				return true;
			}
			next($this->m_aCommands);
		}	
		return false;
	}

	public function _listCommands($privilege)
	{
		$iPermission = $this->_getPermission($privilege);
		$sRet = NULL;
		foreach($this->m_aCommands as $aCmd) {
			if($aCmd['privilege'] <= $iPermission)
				$sRet .= $aCmd['command'].', ';
		}
		if(strlen($sRet) > 0)
			$sRet = substr($sRet, 0, -2);	
		return $sRet;
	}	

	public function _getCommandPermission($key)
	{
		return $this->m_aCommands[$key]['privilege'];
	}

	public function _getCommandDescription($key)
	{
		return $this->m_aCommands[$key]['description'];
	}

	private function _getPermission($privilege)
	{
		$iPermission = Privileges::LEVEL_NONE;
		if(!is_numeric($privilege)) {
			$aPrivileges = array('0' => 0, '+' => 1, '%' => 2, '@' => 4, '&' => 8, '~' => 16, '*' => 1337);
			if(!is_null($aPrivileges[$privilege]))
				$iPermission = $aPrivileges[$privilege];
		} else
			$iPermission = $privilege;
		return $iPermission;
	}

	public function _registerCommand($sCommand, $mixed, $sCallback, $privilege, $description)
	{
		if(!$this->_commandExists($sCommand)) {
			if(is_object($mixed)) {
				if(method_exists($mixed, $sCallback) && is_callable(array($mixed, $sCallback))) {
					$this->m_aCommands[] = array(
						'command' 	=> $sCommand,
						'code' 		=> NULL,
						'description'	=> $description,
						'privilege' 	=> $this->_getPermission($privilege),
						'class'		=> $mixed,
						'callback'	=> $sCallback
					);
					return $this;
				}
			} else {
				$sCode = str_replace(array("\r", "\n", "\t"), "", $mixed);
				$this->m_aCommands[] = array(
					'command'	=> $sCommand,
					'code' 		=> $sCode,
					'description' 	=> $description,
					'privilege' 	=> $this->_getPermission($privilege)
				);
				end($this->m_aCommands);
				$func = debug_backtrace();
				if(strcmp($func[1]['function'], '_saveCommand') !== 0)
					$this->_addClosure($mixed);
			}
			
		}
		return false;
	}

	public function _saveCommand($sCommand, $sCode, $privilege, $description = NULL)
	{
		if(!$this->_commandExists($sCommand)) {
			$this->_registerCommand($sCommand, $sCode, NULL, $privilege, $description);
			$ret = key($this->m_aCommands);
			Database::getInstance()->_insert($this->m_sTable, $this->m_aCommands[$ret]);
			$this->_addClosure($sCode, $ret);
			return true;
		}
		return false;
	}
    
    	public function _unregisterCommand($sCommand, $db_reset = false)
    	{
        	$key = NULL;
        	$key = (object)$key;
        	if(!$this->_commandExists($sCommand, $key))
            		return false;
        	unset($this->m_aCommands[$key->index]);
        	if($db_reset)
            		Database::getInstance()->_delete($this->m_sTable, array('command' => $sCommand));
        	return true;
    	}

	private function _addClosure($sCode, $key = NULL)
	{
		$closure = create_function('$bot, $sCommand, $aParams, $sUser, $sRecipient, $sIdent', $sCode);
		if(is_null($key))
			$key = key($this->m_aCommands);
		$this->m_aCommands[$key]['closure'] = $closure;
	}
			
	public function _parse($bot, $sCommand, $aParams, $sUser, $sRecipient, $sIdent)
	{
		$bExists = false;
		foreach($this->m_aCommands as $aCommand) {
			if(!strcasecmp($aCommand['command'], $sCommand) && ($bot instanceof Bot) && $bot->_isConnected()) {
				$RequiredPrivilege = $aCommand['privilege'];
				$bExists = true;
				if(Privileges::IsBotAdmin($sIdent) || !$RequiredPrivilege || (Misc::isChannel($sRecipient) && Privileges::GetUserPrivilege($sUser, $sRecipient) >= $RequiredPrivilege)) {
					if(array_key_exists('callback', $aCommand))
						call_user_func_array(array($aCommand['class'], $aCommand['callback']), array($bot, $sUser, $sRecipient, $aParams, $sIdent));
					else
						//$aCommand['closure']($bot, $sCommand, $aParams, $sUser, $sRecipient, $sIdent);
						call_user_func($aCommand['closure'], $bot, $sCommand, $aParams, $sUser, $sRecipient, $sIdent);
				}
				break;
			}
		}
		if(!$bExists)
			Plugins::getInstance()->_triggerEvent($bot, "onCommand", $sCommand, $aParams, $sUser, $sRecipient, $sIdent);
	}

	private function _loadCommands()
	{
		$pDB = Database::getInstance();
		$result = $pDB->_query('SELECT `command`, `code`, `description`, `privilege` FROM `'.$this->m_sTable.'`');
		while($row = $pDB->_fetch_array($result)) {
			$this->_registerCommand($row['command'], $row['code'], NULL, $row['privilege'], $row['description'], false);
		}
		unset($pDB);
		unset($result);
		return true;
	}

	public function _validateSyntax($sCode, &$errorReturn)
	{
		$tempFile = time().'.temp.php';
		file_put_contents($tempFile, '<?php '.$sCode.' ?>');
		exec('php -ddisplay_errors=On --syntax-check '.$tempFile, $retarr, $retvar);
		unlink($tempFile);
		if(count($retarr) > 1) {
			$errorReturn = array_slice($retarr, 1);
			return false;
		}
		return true;
	}
}

?>