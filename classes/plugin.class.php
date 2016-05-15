<?php

/**
 * Plugin class
 * - loads/unloads custom written modules (plugins/*)
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Plugins extends Singleton
{
	// public variables
	public $m_aPlugins = array();
	
	public function __construct()
	{
	}

	public function _unload($plugin_name)
	{
		if(!$this->_isLoaded($plugin_name)) 
			return false;
		unset($this->m_aPlugins[$plugin_name]);
		return true;
		
	}

	public function _getPlugin($plugin_name)
	{
		if(!$this->_isLoaded($plugin_name)) 
			return false;
		return $this->m_aPlugins[$plugin_name];
	}

	public function _isLoaded($plugin_name)
	{
		return isset($this->m_aPlugins[$plugin_name]);
	}

	public function _reload($plugin_name)
	{
		$this->_unloadPlugin($plugin_name);
		return $this->_loadPlugin($plugin_name);
	}

	public function _plugin_exists($plugin_name)
	{
		//return @file_exists($this->m_pParent->m_aSettings['PluginDir'] . $this->m_szPlugin . '.plugin.php');
		return @file_exists('plugins/'.$plugin_name.'.plugin.php');
	}

	private function _validate($plugin_name)
	{
		exec('php -ddisplay_errors=On --syntax-check plugins/'.$plugin_name.'.plugin.php', $retarr, $retvar);
		$iArrCount = count($retarr);
		if($iArrCount > 1) {
			$retarr = array_slice($retarr, 1);
			throw new Exception(str_replace("\n", "\r\n", print_r($retarr, true)));
		}
	}

	// might be converted to use runkit in future => more efficient
	public function _load($plugin_name)
	{
		if(!$this->_plugin_exists($plugin_name) || $this->_isLoaded($plugin_name)) 
			return false;
		$this->_validate($plugin_name);
		$szContent = file_get_contents('plugins/'.$plugin_name.'.plugin.php');
		// first of all, we will have to find the classname in the given class
		$aTokens = token_get_all($szContent);
		$iSize = count($aTokens);
		$class_name = NULL;
		for($i = 0;$i < $iSize;$i++) {
			if($aTokens[$i][0] == T_CLASS) {
				for($a = $i+1;$a < $iSize;$a++) {
					if($aTokens[$a] == '{') {
						$class_name = $aTokens[$i+2][1];
						break;
					}
				}

			}
		}
		$szClassName = $class_name . '_' .substr(md5(time()), 0, 10);
		// now we rename the class with a random string; that allows us the re-initialize the class unlimited times
		$szContent = preg_replace('/'.$class_name.'/', $szClassName, $szContent, 1);
		// eval() does not like neither comments nor php opening/closing tags
		$szContent = str_replace(array('<?php','?>','<?'), '', $szContent); //
		foreach ($aTokens as $token) {
    			if ($token[0] != T_COMMENT) 
        			continue;
    			$szContent = str_replace($token[1], '', $szContent);
		}
		/*$szContent = preg_replace('/(\/\*)(.*?)(\*\/)/isU', '', $szContent);
		$szContent = preg_replace('/^(\/\/)(.*?)$/m', '', $szContent);*/
		file_put_contents('abc.txt', $szContent);
		// This is a bit tricky, we will have to evaluate the class
		eval(
			$szContent . '
			if(class_exists($szClassName)) 
				$this->m_aPlugins[$plugin_name] = new '.$szClassName.'();
		');
		return true;
	}

	/**
	 * This function will trigger the IRC events such as onChannelMessage, 
	 * onChannelJoin etc. to all plugins
	 *
	 * @param object $object a pointer to the bot instance/interface, 
	 * @param string $callback name of the given event
	 * @param array $params an array containing all event parameters
	 * @return none
	 * @access public
	 */
	public function _triggerEvent()
	{
		$aArguments = func_get_args();
		//if(@get_class($aArguments[0]) !== FALSE) {
		if(is_object($aArguments[0])) {
			$aPass = $aArguments[0];
			$aArguments[0] = $aArguments[1];
			$aArguments[1] = $aPass;
			$aPass = array_slice($aArguments, 1);
		} else
			$aPass = array_slice($aArguments, 1);
		foreach($this->m_aPlugins as $pName => $pPlugin) {
			if(method_exists($this->m_aPlugins[$pName], $aArguments[0]) && is_callable(array($this->m_aPlugins[$pName], $aArguments[0]))) {
				if(count($aPass) > 0)
					call_user_func_array(array($this->m_aPlugins[$pName], $aArguments[0]), $aPass);
				else
					call_user_func(array($this->m_aPlugins[$pName], $aArguments[0]));

			}
		}
	}

}	



?>