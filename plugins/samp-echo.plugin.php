<?php

/**
 * San Andreas Multiplayer Echo Class
 * - configuratin @ configuration/sa-mp.echo.ini 
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.1a
 */

Class SAMPEcho extends Main
{
	private $m_bEcho = false;
	private $m_pTicks = 0;
	private $m_pConfig;
	private $m_lastPos;
	private $m_bCheck = false;
	private $m_sFile;
	private $m_childIndex = array();
	private $m_pBase;

	public function __construct()
	{
		$this->m_pBase = Main::getInstance();
		$this->m_pConfig = parse('configuration/sa-mp.echo.ini', 'Echo', false);
		$this->m_sFile = $this->m_pConfig['file_directory'].$this->m_pConfig['file_name'];
		$this->m_bCheck = file_exists($this->m_sFile);
		if($this->m_pConfig['child_bots'] > 0) {
			for($idx = 0; $idx < $this->m_pConfig['child_bots'];$idx++) {
				$sNick = sprintf($this->m_pConfig['child_name'].$this->m_pConfig['child_prefix'], ($idx+1));
				$this->m_pBase->_createChild(
					$sNick, 
					$sNick.'_', 
					$sNick, 
					'Slave',
					'password', 
					array($this->m_pConfig['echo_channel']), 
					array(), 
					$this->m_pConfig['Network']
				);
				end($this->m_pBase->m_aBots);
				$this->m_childIndex[] = key($this->m_pBase->m_aBots);
			}
		}
		if($this->m_pConfig['auto_start'])
			$this->_enableEcho();
	}

	public function __destruct()
	{
		foreach($this->m_childIndex as $idx) {
			$this->m_pBase->m_aBots[$idx]->Quit("Leaving. o/");
			unset($this->m_pBase->m_aBots[$idx]);
		}
	}

	private function _getRandomBot()
	{
		$rand = array_rand($this->m_childIndex);
		return $this->m_pBase->m_aBots[$this->m_childIndex[$rand]];
	}
		
	private function _enableEcho()
	{
		if(file_exists($this->m_sFile)) {
			$this->m_lastPos = filesize($this->m_sFile);
			$f = fopen($this->m_sFile, "r");
			fseek($f, $this->m_lastPos, SEEK_END);
			fclose($f);
			$this->m_bEcho = true;
			return true;
		}
		return false;
	}

	public function onTick()
	{
		if($this->m_bEcho && $this->m_bCheck) {
			$this->m_pTicks++;
			if($this->m_pTicks >= $this->m_pConfig['ticks_echo']) {
				clearstatcache(false, $this->m_sFile);
				$len = filesize($this->m_sFile);
				if($len < $this->m_lastPos)
					$this->m_lastPos = $len;
				else {
					$f = fopen($this->m_sFile, "rb");
					fseek($f, $this->m_lastPos);
					while(!feof($f)) {
						$buffer = fread($f, 512);
						if(strlen($buffer) > 0) {
							$buffer = array_filter(explode("\n", $buffer), 'trim');
							foreach($buffer as $sLine) {
								$this->_getRandomBot()->Say($this->m_pConfig['echo_channel'], $sLine);
							}
						}
					}
					$this->m_lastPos = ftell($f);
					fclose($f);
				}			
				$this->m_pTicks = 0;
			}
		}
	}

	public function onCommand($bot, $command, $params, $user, $recipient, $ident)
	{
		if($bot->_isChild() || strcasecmp($recipient, $this->m_pConfig['echo_channel']) != 0) // we dont want our childs to handle any commands
			return;
		switch($command) {
			case '!echo':
				if(Privileges::IsBotAdmin($ident)) {
					if(count($params) < 1) {
						$bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !echo [on/off]');
					} else {
						if(!file_exists($this->m_sFile))
							return $bot->Say($recipient, '[color=red]Error:[/color] Path to scriptfiles is invalid ('.$this->m_pConfig['file_directory'].')');
						else if(!strcasecmp($params[0], 'on')) {
							$this->_enableEcho();
							return $bot->Say($recipient, '>> Channel Echo has been turned [color=green]on[/color].');
						}
						else if(!strcasecmp($params[0], 'off')) {
							$this->m_bEcho = false;
							return $bot->Say($recipient, '>> Channel Echo has been turned [color=red]off[/color].');
						}
						else
							return $bot->Say($recipient, '[b][color=green]Syntax:[/color][/b] !echo [on/off]');
					}
				}
				break;
			case '!tickrate':
				if(Privileges::IsBotAdmin($ident)) {
					if(count($params) < 1 || !is_numeric($params[0])) {
						$bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !tickrate [ticks until echo (at least 10)]');
					} else {
						$this->m_pConfig['ticks_echo'] = $params[0];
						$bot->Say($recipient, ">> Tickrate has been changed");
					}
				}
			case '!say':
				if(count($params) > 0 && $this->m_bEcho) {
					$sMessage = '[msg] '.$user.' on IRC: '.implode(' ', $params);
					file_put_contents($this->m_pConfig['file_directory'].$this->m_pConfig['file_pawn'], $sMessage, FILE_APPEND);
				}
				break;	
			case '!players':
				if($this->m_bEcho)
					file_put_contents($this->m_pConfig['file_directory'].$this->m_pConfig['file_pawn'], "[players]", FILE_APPEND);
				break;
			case '!pm':
				if($this->m_bEcho) {
					if(count($params) < 2)
						return $bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !pm [playerid/name] [message]');
					$sMessage = sprintf("[pm] %s %s %s\r\n", $params[0], $user, implode(' ', array_slice($params, 1)));
					file_put_contents($this->m_pConfig['file_directory'].$this->m_pConfig['file_pawn'], $sMessage, FILE_APPEND);
				}
				break;
			case '!kick':
				if($this->m_bEcho && (Privileges::IsOperator($user, $recipient) || Privileges::IsBotAdmin($ident))) {
					if(count($params) < 1)
						return $bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !kick [playerid/name] [reason (optional)]');
					$sMessage = sprintf("[kick] %s %s %s\r\n", $params[0], $user, implode(' ', array_slice($params, 1)));
					file_put_contents($this->m_pConfig['file_directory'].$this->m_pConfig['file_pawn'], $sMessage, FILE_APPEND);
				}
				break;
			case '!ban':
				if($this->m_bEcho && (Privileges::IsOperator($user, $recipient) || Privileges::IsBotAdmin($ident))) {
					if(count($params) < 1)
						return $bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !ban [playerid/name] [reason (optional)]');
					$sMessage = sprintf("[ban] %s %s %s\r\n", $params[0], $user, implode(' ', array_slice($params, 1)));
					file_put_contents($this->m_pConfig['file_directory'].$this->m_pConfig['file_pawn'], $sMessage, FILE_APPEND);
				}
				break;
			case '!rcon':
				if($this->m_bEcho && Privileges::IsBotAdmin($ident)) {
					if(count($params) < 1)
						return $bot->Say($recipient, '[b][color=red]Syntax:[/color][/b] !rcon [command]');
					$sMessage = sprintf("[rcon] %s\r\n", implode(' ', $params));
					file_put_contents($this->m_pConfig['file_directory'].$this->m_pConfig['file_pawn'], $sMessage, FILE_APPEND);
				}
				break;
			default:
				break;
					
		}
			
	}

}	



?>