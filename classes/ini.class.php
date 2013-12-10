<?php

/**
 * Class to parse .ini files 
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Ini extends Singleton
{
	public function __construct()
	{
		/*foreach(glob("configurations/bots/*.ini") as $File) {
			$this->m_aBotIni[] = $File;
		}
		
		if(empty($this->m_aBotIni)) {
			Log::getInstance()->_Error('[ERROR] : No bot configuration files found. Closing..');
			die();
		}*/
		
	}

	public function _getArrayConfig($dir, $section)
	{
		$aConfig = array();
		foreach(glob('configuration/'.$dir.'/*.ini') as $File) {
			$aConfig[] = $this->_getConfig($File, $section);
		}
		return $aConfig;
	
	}

	public function _getConfig($file, $section)
	{
		$aConfiguration = $this->_parseIni($file);
		if(empty($section)) {
			return $aConfiguration;
		}
		return $aConfiguration[$section];
	}
	
	private function _parseIni($file)
	{
		if(!file_exists($file)) return Log::getInstance()->_Error('[ERROR] : `'.$file.'` does not exist');
		return parse_ini_file($file, true);
	}
}
?>