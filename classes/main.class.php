<?php

/**
 * Main handler class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0
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
	 * An integer used to ping the bots
	 *
	 * @var integer
	 * @access private
	 */
	private $m_iTicks = 0;

	/**
	 * This array contains all settings which have been used in configuration/*.ini
	 *
	 * @var array
	 * @access protected
	 */
	protected $m_aSettings;

	/**
	 * An integer used to determine the tick amount per second
	 *
	 * @var integer
	 * @access public
	 */
	public $iTicksPer = 0;

	/**
	 * Pointer to the timer class
 	 * 
	 * @var pointer
	 * @access protected
	 */
	protected $m_pTimer;

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
                $this->iTicksPer = round((1000000/$this->m_aSettings['Sleep']), 0, PHP_ROUND_HALF_UP);
                $this->m_aNetData = $aNetworks;
		if(!strcmp($this->m_aSettings['AdminPass'], 'defaultpass')) 
			$this->m_aSettings['AdminPass'] = md5(time());
                $this->_initBots($aConfig['Bots']);
                $this->_initPlugins($this->m_aSettings['Plugins']);
    		$this->m_pTimer = Timer::getInstance();
		$this->m_pTimer->_add($this, "_pingCheck", 0, $this->m_aSettings['Ping'], true);
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
		if(empty($aPlugins)) return false;
		foreach($aPlugins as $sName) {
			try {
				Plugins::getInstance()->_load($sName);
			}
			catch (Exception $e) {
				Log::Error(__METHOD__.' -> Plugin `'.$sName.'` could not be loaded:' . PHP_EOL . '>> '.$e->getMessage());
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
			if(array_key_exists(strtolower($netname), array_change_key_case($this->m_aNetData, CASE_LOWER)))
				$this->m_aBots[] = new Bot(/*$this,*/$this->m_aNetData[$netname], $botData);
			else
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
			$this->m_aBots[] = new Bot(/*$this,*/$this->m_aNetData[$netname], $aChild);
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
		$this->m_iTicks++;
		foreach($this->m_aBots as $pFamilyMember) {
			if($this->m_iTicks >= ($this->iTicksPer*$this->m_aSettings['Ping']) && $pFamilyMember->_isConnected()) {
				$pFamilyMember->_Ping();
				$this->m_iTicks = 0;
			}
			$pFamilyMember->_Update();
			//if(!$pFamilyMember->_isChild()) 
			//	$pFamilyMember->_triggerPluginEvent("onTick");
		}
		$this->m_pTimer->_update();
		Plugins::getInstance()->_triggerEvent("onTick");
		
	}

	public function _pingCheck()
	{
		$iCurrent = time();
		foreach($this->m_aBots as $pFamilyMember) {
			if($pFamilyMember->_isConnected()) {
				if(($iCurrent-$pFamilyMember->_getLastPing()) > ($this->m_aSettings['Ping']+15)) {
					// TODO
					// bot has most likely timed out
				}
			}
		}	
	}
}


?>
