<?php

/**
 * San Andreas Multiplayer Echo Class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class SAMPEcho extends Main
{
	private $m_bEcho = false;
	private $m_iTicks = 0;

	public function __construct()
	{
	}

	public function onTick()
	{
		if($this->m_bEcho) {
			$this->m_iTicks = 0;
			if($this->m_iTicks >= ($this->iTickPers*2)) {
				$this->m_iTicks = 0;
			}
		}
	}

}	



?>