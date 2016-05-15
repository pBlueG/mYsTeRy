<?php

/**
 * Main handler class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.1a
 */

Class Main extends Singleton
{
	/**
	 * This array stores the bot instances
	 *
	 * @var array
	 * @access private
	 */
	protected $m_aBots = array();

	/**
	 * This array stores the network data
	 *
	 * @var array
	 * @access private
	 */
	private $m_aNetData = array();

	/**
	 * This array stores the (custom) plugin instances
	 * 
	 * @var array
	 * @access private
	 */
	private $m_aPlugins = array();

	/**
	 * This array contains all settings which have been used in configuration/*.ini
	 *
	 * @var array
	 * @access protected
	 */
	public $m_aSettings;

	/**
	 * Pointer to timer class
 	 * 
	 * @var pointer
	 * @access protected
	 */
	protected $m_pTimer;

	/**
	 * Pointer to plugin class
 	 * 
	 * @var pointer
	 * @access protected
	 */
	protected $m_pModule;

	/**
	 * Class constructor
	 *
	 * @param array $aConfig array containing all settings
	 * @return /
	 * @access public
	 */
	public function __construct($aConfig, $aNetworks)
	{
                foreach($aConfig['General'] as $key => $value) {
                        $this->m_aSettings[$key] = $value;
                }
                $this->m_aNetData = $aNetworks;
		if(!strcmp($this->m_aSettings['AdminPass'], 'defaultpass')) 
			$this->m_aSettings['AdminPass'] = md5(time());
		$this->m_pTimer = Timer::getInstance();
		$this->m_pModule = Plugins::getInstance();
                $this->_initBots($aConfig['Bots']);
                $this->_initPlugins($this->m_aSettings['Plugins']);
	}
	
	/**
	 * A function to load all plugins which are listed in the settings file
	 *
	 * @param array $aPlugins array containing all plugin names
	 * @return boolean
	 * @access private
	 */	
	private function _initPlugins(array $aPlugins)
	{
		if(empty($aPlugins)) 
			return false;
		foreach($aPlugins as $sName) {
			try {
				$this->m_pModule->_load($sName);
			}
			catch (Exception $e) {
				Log::Error(__METHOD__.' -> Plugin `'.$sName.'` could not be loaded:' . PHP_EOL . '>> '.$e->getMessage(), "logs/Plugin Error.log");
			}
		}
		return true;
	}	

	/**
	 * A function to load all bots which have been added 
	 * to the bots/ folder
	 *
	 * @param array $aBots array containing all bot settings
	 * @param array $aNetworks array containing all network settings
	 * @return none
	 * @access private
	 */
	private function _initBots(array $aBots)
	{
		foreach($aBots as $botData) {
			$netname = $botData['Network'];
			if(array_key_exists(strtolower($netname), array_change_key_case($this->m_aNetData, CASE_LOWER))) {
				$this->m_aBots[] = new Bot($this->m_aNetData[$netname], $botData);
				$this->m_pTimer->_add(end($this->m_aBots), '_Ping', 0, $this->m_aSettings['Ping'], true);
			} else
				Log::Error(__METHOD__.'-> Bot `'.$botData['Nick'].'` can\'t connect:'.PHP_EOL.'>> Please check network settings for `'.$netname.'`');
		}
	}

	/**
	 * This function will create a child (slave) bot with the given params
	 * Child bots might be useful for a SA-MP echo channel without
	 * processing most of the raw irc events
	 *
	 * @param string $nickname nickname of the child bot
	 * @param string $altnick alternative nickname
	 * @param string $username username of the child bot
	 * @param string $realname realname of the child bot
	 * @param string $password password of the child bot (incase it's registered)
	 * @param array $channels an array with all channels the bot will join
	 * @param array $ctcp an array with CTCP answers such as FINGER, VERSION etc
	 * @param array $network an array with network settings
	 * @return array 
	 * @access public
	 */
	public function _createChild($nickname, $altnick, $username, $realname, $password, $channels = array(), $ctcp = array(), $netname)
	{
		$aChild = array(
			'Nick'		=> $nickname,
			'AltNick'	=> $altnick,
			'Username'	=> $username,
			'Realname'	=> $realname,
			'Password'	=> $password,
			'Child'		=> true,
			'Quit' 		=> 'Leaving',
			'CTCP'		=> $ctcp,
			'Channels'	=> $channels
		);
		if(array_key_exists(strtolower($netname), array_change_key_case($this->m_aNetData, CASE_LOWER))) {
			$this->m_aBots[] = new Bot($this->m_aNetData[$netname], $aChild);
			return end($this->m_aBots);
		} else
			return Log::Error(__METHOD__.'-> Bot `'.$botData['Nick'].'` can\'t connect:'.PHP_EOL.'>> Please check network settings for `'.$netname.'`');
	}

	/**
	 * This function gets executed in the main loop
	 * and keeps anything (bots, instances) alive
	 *
	 * @return none
	 * @access public
	 */
	public final function _Run()
	{
		foreach($this->m_aBots as $pFamilyMember) {
			$pFamilyMember->_Update();
			//if(!$pFamilyMember->_isChild()) 
			//	$pFamilyMember->_triggerPluginEvent("onTick");
		}
		$this->m_pTimer->_update();
		$this->m_pModule->_triggerEvent("onTick");
		
	}
}


?>
