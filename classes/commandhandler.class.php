<?php

/**
 * Commandhandler class
 * - parses PRIVMSG data for commands
 * - creates a table for commands and registers admin cmds:
 *   !join, !part, !quit, !addcmd, !delcmd, !load, !unload)
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class CommandHandler extends Singleton
{
	public $m_aCommands = array();
	private $m_sTable;

	public function __construct()
	{
		$this->m_sTable = 'bot_commands';
		$pDB = Database::getInstance();
		if(!$pDB->_table_exists($this->m_sTable)) {
			// the command table does not exist, therefore we will have to register all admin commands
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
			$this->_registerCommand(
				'!join',
				'if(count($aParams) < 1)
					$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !join (#channel) [key]");
				else
					if(isset($aParams[1])) 
						$bot->Join($aParams[0], $aParams[1]);
					else 
						$bot->Join($aParams[0]);',
				Privileges::LEVEL_BOT_ADMIN,
				'Joins the given channel.'
			);
			$this->_registerCommand(
				'!part',
				'if(count($aParams) < 1)
					$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !part (#channel) [part message]");
				else
					if(isset($aParams[1])) 
						$bot->Part($aParams[0], $aParams[1]);
					else 
						$bot->Part($aParams[0]);',
				Privileges::LEVEL_BOT_ADMIN,
				'Parts the given channel.'
			);	
			$this->_registerCommand(
				'!quit',
				'if(is_array($aParams))
					$bot->Quit(implode(" ", $aParams));
				else
					$bot->Quit();',
				Privileges::LEVEL_BOT_ADMIN,
				'Quits'
			);
			$this->_registerCommand(
				'!!',
				'if($bot->_isChild()) 
					return;
				if(is_array($aParams)) {
					$sEval = implode(" ", $aParams);
					ob_start();
					eval($sEval);
					$sReturn = ob_get_contents();
					ob_end_flush();
					$aReturn = array_filter(explode("\n", $sReturn));
					foreach($aReturn as $sEcho) 
						$bot->Say($sRecipient, trim($sEcho));
				} else {
					$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !! (code)");
				}',
				Privileges::LEVEL_BOT_ADMIN,
				'Evaluates the given code as php.'
			);
			$this->_registerCommand(
				'!addcmd',
				'if($bot->_isChild()) 
					return;
				if(count($aParams) < 3) {
					$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !addcmd (command) (privilege) (phpcode)");
				} else {
					if($this->_command_exists($aParams[0])) {
						$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] This command already exists.");
					} else {
						$code = trim(implode(" ", array_slice($aParams, 2)));
						$aError = array();
						if($this->_validateSyntax($code, $aError)) {
							$this->_registerCommand($aParams[0], $code, $aParams[1]);
							$bot->Say($sRecipient, "[b][color=green]Success:[/color][/b] The command [b]".$aParams[0]."[/b] has been succesfully added.");
						} else {
							$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] You have an error in your php syntax:");
							foreach($aError as $sError)
								$bot->Say($sRecipient, trim($sError));
						}
					}
				}',
				Privileges::LEVEL_BOT_ADMIN,
				'Adds a command to the database.'
			);
			$this->_registerCommand(
				'!delcmd',
				'if($bot->_isChild()) 
					return;
				if(count($aParams) < 1) {
					$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !delcmd (command)");
				} else {
					$key = NULL;
					$key = (object)$key;
					if(!$this->_command_exists($aParams[0], $key)) {
						$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] This command does not exists.");
					} else {
						unset($this->m_aCommands[$key->index]);
						$aCmd = array("command" => $aParams[0]);
						Database::getInstance()->_delete($this->m_sTable, $aCmd);
						$bot->Say($sRecipient, "[b][color=green]Success:[/color][/b] The command has been successfully removed.");
						$this->m_aCommands = array_values($this->m_aCommands);
					}
					unset($key);
				}',
				Privileges::LEVEL_BOT_ADMIN,
				'Deletes a command from the database.'
			);
			$this->_registerCommand(
				'!cmds',
				'if($bot->_isChild()) 
					return;
				$sCmds = NULL;
				foreach($this->m_aCommands as $sCmd) {
					$sCmds .= $sCmd["command"].", ";
				}
				$sCmds = substr($sCmds, 0, -2);
				$bot->Notice($sUser, "Commands: ".$sCmds);',
				Privileges::LEVEL_BOT_ADMIN,
				'Displays all registered commands.'
			);
			$this->_registerCommand(
				'!load',
				'if($bot->_isChild()) 
					return;
				if(count($aParams) < 1) {
					$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !load (plugin name)");
				} else {
					$ptr = Plugins::getInstance();
					$sPlugin = $aParams[0];
					if($ptr->_plugin_exists($sPlugin)) {
						if(!$ptr->_isLoaded($sPlugin)) {
							$sError = NULL;
							try {
								$ptr->_load($sPlugin);
							} catch (Exception $e) {
								$sError = $e->getMessage();
								$sError = str_replace(array("\n", "\r", "\t"), "", $sError);
							}
							if(is_null($sError))
								$bot->Say($sRecipient, "[b][color=green]Success:[/color][/b] The plugin `".$sPlugin."` has been successfully loaded.");
							else {
								$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] An error has occured:");
								$bot->Say($sRecipient, $sError);
							}
						} else {
							$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] This plugin is already loaded. Use !unload ".$sPlugin.".");
						}
					} else {
						$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] `".$sPlugin."` does not exist.");
					}
					unset($ptr);
				}',
				Privileges::LEVEL_BOT_ADMIN,
				'Loads a plugin.'
			);
			$this->_registerCommand(
				'!unload',
				'if($bot->_isChild()) 
					return;
				if(count($aParams) < 1) {
					$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !unload (plugin name)");
				} else {
					$ptr = Plugins::getInstance();
					$sPlugin = $aParams[0];
					if($ptr->_unload($sPlugin))
						$bot->Say($sRecipient, "[b][color=green]Success:[/color][/b] `".$sPlugin."` has been successfully unloaded.");
					else
						$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] `".$sPlugin."` is not loaded.");
					unset($ptr);
				}',
				Privileges::LEVEL_BOT_ADMIN,
				'Unloads a plugin.'
			);	
			$this->_registerCommand(
				'!plugins',
				'if($bot->_isChild()) 
					return;
				$sPlugins = NULL;
				$ptr = Plugins::getInstance();
				reset($ptr->m_aPlugins);
				while(current($ptr->m_aPlugins)) {
					$sPlugins .= key($ptr->m_aPlugins). ".class.php, ";
					next($ptr->m_aPlugins);
				}
				reset($ptr->m_aPlugins);
				$sPlugins = substr($sPlugins, 0, -2);
				$bot->Say(
					$sRecipient, 
					"[b]Active plugin instances:[/b] ".$sPlugins
				);',
				Privileges::LEVEL_BOT_ADMIN,
				'Displays all active plugins.'
			);
			$this->_registerCommand(
				'!ident',
				'if($bot->_isChild()) 
					return;
				$bot->PM(
					$sUser, 
					"Your ident is [i]".$sIdent."[/i]"
				);',
				Privileges::LEVEL_NONE,
				'Shows the ident.'
			);
			$this->_registerCommand(
				'!mem',
				'if($bot->_isChild()) 
					return;
				$bot->Say(
					$sRecipient, 
					"Current memory usage is [b]".Misc::formatBytes(memory_get_usage(), "MB")."[/b]"
				);',
				Privileges::LEVEL_BOT_ADMIN,
				'Displays the current memory usage allocated by mYsTeRy.'
			);
			$this->_registerCommand(
				'!uptime',
				'$sUptime = time() - $bot->m_aPing["Uptime"];
				$bot->PM(
					$sRecipient, 
					"I have been up for ".Misc::SecondsToString($sUptime)
				);',
				Privileges::LEVEL_BOT_ADMIN,
				'Displays the current memory usage allocated by mYsTeRy.'
			);
		} else {
			$this->_loadCommands();
		}
		unset($pDB);
	}

	//public function _call_command($sCommand, $aParams, 

	public function _command_exists($sCommand)
	{
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
		reset($this->m_aCommands);
		return false;
	}

	public function _registerCommand($sCommand, $sCode, $privilege, $description = NULL, $save_to_db = true)
	{
		if(!$this->_command_exists($sCommand)) {
			if(!is_numeric($privilege)) {
				$aPrivileges = array('0' => 0, '+' => 1, '%' => 2, '@' => 4, '&' => 8, '~' => 16, '*' => 1337);
				$iPermission = $aPrivileges[$privilege];
			} else
				$iPermission = $privilege;
			$finalCode = str_replace(array("\r", "\n", "\t"), "", $sCode);
			$this->m_aCommands[] = array(
				'command' 	=> $sCommand,
				'code' 		=> $finalCode,
				'description' 	=> $description,
				'privilege' 	=> $iPermission
			);
			if($save_to_db)
				Database::getInstance()->_insert($this->m_sTable, end($this->m_aCommands));
			return true;
		}
		return false;
	}
			
	public function _parse($bot, $sCommand, $aParams, $sUser, $sRecipient, $sIdent)
	{
		$bExists = false;
		foreach($this->m_aCommands as $aCommand) {
			if(!strcasecmp($aCommand['command'], $sCommand) && ($bot instanceof Bot)) {
				$RequiredPrivilege = $aCommand['privilege'];
				$bIsAdmin = Privileges::IsBotAdmin($sIdent);
				$bExecute = false;
				if($RequiredPrivilege > 0 && !$bIsAdmin && $sRecipient[0] == '#') {
					//$UserPrivilege = Privileges::GetUserPrivilege($sUser, $sRecipient);
					switch($RequiredPrivilege) {
						case Privileges::LEVEL_VOICE:
							$bExecute = Privileges::IsVoiced($sUser, $sRecipient);
							break;
						case Privileges::LEVEL_HALFOP:
							$bExecute = Privileges::IsHalfop($sUser, $sRecipient);
							break;
						case Privileges::LEVEL_OPERATOR:
							$bExecute = Privileges::IsOperator($sUser, $sRecipient);
							break;
						case Privileges::LEVEL_SUPER_OPERATOR:
							$bExecute = Privileges::IsSuperOperator($sUser, $sRecipient);
							break;
						case Privileges::LEVEL_OWNER:
							$bExecute = Privileges::IsOwner($sUser, $sRecipient);
							break;
						default: 
							break;
					}
				}
				$bExists = true;
				if($bExecute || !$RequiredPrivilege || $bIsAdmin)
					eval($aCommand['code']);
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
			$this->_registerCommand($row['command'], $row['code'], $row['privilege'], $row['description'], false);
		}
		unset($pDB);
		unset($result);
		return true;
	}

	protected function _validateSyntax($sCode, &$errorReturn)
	{
		$tempFile = time().".temp.php";
		file_put_contents($tempFile, "<?php ".$sCode." ?>");
		exec("php -ddisplay_errors=On --syntax-check ".$tempFile, $retarr, $retvar);
		unlink($tempFile);
		if(count($retarr) > 1) {
			// remove all date_* warnings
			$errorReturn = array_splice($retarr, 3);
			array_pop($errorReturn);
			return false;
		}
		return true;
	}
}

?>