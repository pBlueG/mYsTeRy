<?php

/**
 * Administration commands
 */

Class Commands
{
	private $Handler;

	public function __construct()
	{
		$this->Handler = CommandHandler::getInstance();
		$this->Handler
			->_registerCommand('!cmds', $this, 'cmds', Privileges::LEVEL_NONE, 'Displays a list of all commands unlocked for you.')
			->_registerCommand('!join', $this, 'join', Privileges::LEVEL_BOT_ADMIN, 'Joins the given channel.')
			->_registerCommand('!part', $this, 'part', Privileges::LEVEL_BOT_ADMIN, 'Leaves the given channel.')
			->_registerCommand('!quit', $this, 'quit', Privileges::LEVEL_BOT_ADMIN, 'Disconnects the bot from the network.')
			->_registerCommand('!!', $this, 'boteval', Privileges::LEVEL_BOT_ADMIN, 'Evaluates PHP code.')
			->_registerCommand('!addcmd', $this, 'addcmd', Privileges::LEVEL_BOT_ADMIN, 'Adds a custom command.')
			->_registerCommand('!delcmd', $this, 'delcmd', Privileges::LEVEL_BOT_ADMIN, 'Deletes a custom made command.')
			->_registerCommand('!load', $this, 'load', Privileges::LEVEL_BOT_ADMIN, 'Loads a plugin.')
			->_registerCommand('!unload', $this, 'unload', Privileges::LEVEL_BOT_ADMIN, 'Unloads a plugin.')
			->_registerCommand('!plugins', $this, 'plugins', Privileges::LEVEL_BOT_ADMIN, 'Displays a list of all active plugin instances.')
			->_registerCommand('!ident', $this, 'ident', Privileges::LEVEL_NONE, 'Shows your current ident.')
			->_registerCommand('!mem', $this, 'mem', Privileges::LEVEL_NONE, 'Shows the bots current memory usage.')
			->_registerCommand('!uptime', $this, 'uptime', Privileges::LEVEL_NONE, 'Shows how long the bot has been up.')
			->_registerCommand('!help', $this, 'help', Privileges::LEVEL_NONE, 'Commands help.');
		echo '>> Commands plugin has been loaded.' . PHP_EOL;
	}

	public function cmds($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		if(Privileges::IsBotAdmin($sIdent))
			$priv = Privileges::LEVEL_BOT_ADMIN;
		else
			$priv = Privileges::GetUserPrivilege($sUser, $sRecipient);
		$bot->Notice($sUser, 'Commands: '.$this->Handler->_listCommands($priv));
	}

	public function join($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		if(count($aParams) < 1)
			$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !join (#channel) [key]");
		else
			if(isset($aParams[1])) 
				$bot->Join($aParams[0], $aParams[1]);
			else 
				$bot->Join($aParams[0]);
	}

	public function part($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		if(count($aParams) < 1)
			$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !part (#channel) [part message]");
		else
			if(isset($aParams[1])) 
				$bot->Part($aParams[0], $aParams[1]);
			else 
				$bot->Part($aParams[0]);
	}

	public function quit($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		if(is_array($aParams))
			$bot->Quit(implode(" ", $aParams));
		else
			$bot->Quit();		
	}

	public function boteval($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
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
		}
	}

	public function addcmd($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		if(count($aParams) < 3) {
			$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !addcmd (command) (privilege) (phpcode)");
		} else {
			if($this->Handler->_commandExists($aParams[0])) {
				$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] This command already exists.");
			} else {
				$code = trim(implode(" ", array_slice($aParams, 2)));
				$aError = array();
				if($this->Handler->_validateSyntax($code, $aError)) {
					$this->Handler->_saveCommand($aParams[0], $code, $aParams[1]);
					$bot->Say($sRecipient, "[b][color=green]Success:[/color][/b] The command [b]".$aParams[0]."[/b] has been succesfully added.");
				} else {
					$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] You have an error in your php syntax:");
					foreach($aError as $sError)
						$bot->Say($sRecipient, trim($sError));
				}
			}
		}
	}

	public function delcmd($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		if(count($aParams) < 1) {
			$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !delcmd (command)");
		} else {
                    	if($this->Handler->_unregisterCommand($aParams[0], true))
                       		$bot->Say($sRecipient, "[b][color=green]Success:[/color][/b] The command has been successfully removed.");
                    	else
                        	$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] This command does not exists.");
		}
	}

	public function load($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
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
		}
		unset($ptr);
	}

	public function unload($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
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
		}
	}

	public function plugins($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
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
		);
	}

	public function ident($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		$bot->Notice(
			$sUser, 
			"Your ident is ".$sIdent
		);
	}

	public function mem($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		$bot->Say(
			$sRecipient, 
			"Current memory usage is [b]".Misc::formatBytes(memory_get_usage(), "MB")."[/b]"
		);
	}

	public function uptime($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		$sUptime = time() - $bot->m_aPing["Uptime"];
		$bot->PM(
			$sRecipient, 
			"I have been up for ".Misc::SecondsToString($sUptime)
		);		
	}

	public function help($bot, $sUser, $sRecipient, $aParams, $sIdent)
	{
		if($bot->_isChild())
			return;
		if(count($aParams) < 1) {
			$bot->Say($sRecipient, "[b][color=red]Syntax:[/color][/b] !help (command)");
		} else {
			$key = NULL;
			$key = (object)$key;
			if($this->Handler->_commandExists($aParams[0], $key)) {	
				if(Privileges::GetUserPrivilege($sUser, $sRecipient) >= $this->Handler->_getCommandPermission($key->index) || Privileges::IsBotAdmin($sIdent)) {
					$bot->Say($sRecipient, '[b]Command:[/b] '.$aParams[0]);
					$bot->Say($sRecipient, '-> '.$this->Handler->_getCommandDescription($key->index));
				}
			} else {
				$bot->Say($sRecipient, "[b][color=red]Error:[/color][/b] Command: `".$aParams[0]."` does not exist!");
			}
		}
		
	}

}	

?>