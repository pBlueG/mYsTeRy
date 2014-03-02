<?php

/**
 * San Andreas Multiplayer Echo Class
 * - see configuration/sa-mp.echo.ini to configure the echo settings
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
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

	public function __construct()
	{
		$this->m_pConfig = Ini::getInstance()->_getConfig('configuration/sa-mp.echo.ini', 'Echo');
		$this->m_sFile = $this->m_pConfig['file_directory'].$this->m_pConfig['file_name'];
		$this->m_bCheck = file_exists($this->m_sFile);
		if($this->m_bCheck) {
			// file pointer set to end, otherwise the bot will read the whole echo file
			$this->m_lastPos = filesize($this->m_sFile);
			$f = fopen($this->m_sFile, "r");
			fseek($f, $this->m_lastPos, SEEK_END);
			fclose($f);
		}
		if($this->m_pConfig['child_bots'] > 0) {
			for($idx = 0; $idx < $this->m_pConfig['child_bots'];$idx++) {
				$sNick = sprintf($this->m_pConfig['child_name'].$this->m_pConfig['child_prefix'], ($idx+1));
				Main::getInstance()->_createChild(
					$sNick, 
					$sNick.'`', 
					$sNick, 
					'Echo Bot',
					'', 
					array($this->m_pConfig['echo_channel']), 
					array(), 
					$this->m_pConfig['Network']
				);
				end(Main::getInstance()->m_aBots);
				$this->m_childIndex[] = key(Main::getInstance()->m_aBots);
			}
		}
		$this->m_bEcho = true;
	}

	public function __destruct()
	{
		foreach($this->m_childIndex as $idx) {
			Main::getInstance()->m_aBots[$idx]->Quit("Leaving. o/");
			unset(Main::getInstance()->m_aBots[$idx]);
		}
	}

	private function _getRandomBot()
	{
		$rand = array_rand($this->m_childIndex);
		return Main::getInstance()->m_aBots[$this->m_childIndex[$rand]];
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
		if($bot->isChild())
			return;
		switch($command) {
			// TODO
		}
			
	}

}	



?>