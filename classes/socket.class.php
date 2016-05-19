<?php

/**
 * Socket class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.1a
 */

Class Socket
{
	/**
	 * Just a pointer to the bot instance
	 *
	 * @var object 
	 * @access private
	 */
	private $m_pBot;

	/**
	 * Integer to keep track of all received bytes
	 *
	 * @var integer
	 * @access public
	 */
	public $m_gBytesReceived;

	/**
	 * Integer to keep track of all sent bytes
	 *
	 * @var integer
	 * @access public
	 */
	public $m_gBytesSent;
	
	/**
	 * This array stores the server settings
	 *
	 * @var array
	 * @access protected
	 */
	private $m_aSettings = array();

	/**
	 * Boolean to determine whether we use SSL
	 *
	 * @var boolean
	 * @access private
	 */
	private $m_bSSL;

	/**
	 * Not in use so far
	 *
	 * @var integer
	 * @access private
	 */
	private $m_iAttempts = 0;

	/**
	 * Pointer to the socket interface
	 *
	 * @var pointer
	 * @access protected
	 */
	private $m_pSocket;

	/**
	 * Are we connected?
	 *
	 * @var boolean
	 * @access private
	 */
	private $m_bConnected;


	/**
	 * C'tor
	 *
	 * @param object $parent A pointer to the bot class
	 * @access public
	 */
	public function __construct(Bot $pBot)
	{
		$this->m_pBot = $pBot;
		$this->m_bIsConnected = false;
		$this->m_aSettings = array(
			'Host'		=> '',
			'Port'		=> 0,
			'SSL'		=> false,
			'SSL_CRT'	=> NULL,
			'BindIP'	=> NULL
		);
	}

	/**
	 * Specifies the server/host we connect to
	 * 
 	 * @param string $server
	 * @return boolean
	 */
	public function _setServer($server)
	{
		$this->m_aSettings['Host'] = $server;
	}

	/**
	 * Picks up a random server to connect to
	 * 
 	 * @param array $server
	 * @return boolean
	 */
	public function _getValidServer(array $server)
	{
		foreach($server as $key) {
			if($this->_checkServer($key))
				return $key;
		}
		throw new Exception('>> No valid server found, please check your network/server settings.');
	}

	/**
	 * Checks whether the given server is reachable
	 * 
 	 * @param string $server
	 * @return boolean
	 */
	public function _checkServer($server)
	{
		$IP = gethostbyname($server);
		if(@filter_var($IP, FILTER_VALIDATE_IP) !== FALSE)
			return true;
		return false;
	}

	/**
	 * Specifies the port we are going to connect to
	 * 
 	 * @param integer $port
	 * @return boolean
	 */
	public function _setPort($port)
	{
		$this->m_aSettings['Port'] = $port;
	}

	/**
	 * Specifies whether we use SSL
	 * 
 	 * @param bool $use
	 * @param string $path
	 * @return boolean
	 */
	public function _setSSL($use, $path = NULL)
	{
		$this->m_aSettings['SSL'] = $use;	
		if(!is_null($path))
			$this->m_aSettings['SSL_CRT'] = $path;
	}


	/**
	 * Specifies the IP & port php is going to use to access the network
	 * 
 	 * @param integer $port
	 * @return boolean
	 */
	public function _setBindTo($ip)
	{
		$this->m_aSettings['BindIP'] = $ip;
	}

	/**
	 * Creates a socket stream to our specified host
	 * 
 	 * @param none
	 * @return boolean
	 */
	public function _connect()
	{
		$context = stream_context_create();
		if($this->m_aSettings['SSL']) {
			//http://www.php.net/manual/en/context.ssl.php - for further reference
			stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
			stream_context_set_option($context, 'ssl', 'local_cert', __DIR__ . $this->m_aSettings['SSL_CRT']);
		}
		if(!is_null($this->m_aSettings['BindIP']))
			stream_context_set_option($context, 'socket', 'bindto', $this->m_aSettings['BindIP']);
		$this->m_pSocket = @stream_socket_client(($this->m_aSettings['SSL'] ? 'ssl' : 'tcp').'://'.$this->m_aSettings['Host'].':'.$this->m_aSettings['Port'], $errno, $errstr, 3.0, STREAM_CLIENT_CONNECT, $context);
		if($this->m_pSocket !== false) {
			stream_set_blocking($this->m_pSocket, 0);
			$this->m_bIsConnected = true;
			return true;
		}
		return false;
	}

	/**
	 * This function lets our bot properly disconnect from the IRC network
	 * 
 	 * @param (optional) string $sQuit The message to display when quitting
	 * @return boolean
	 */
	public function _disconnect()
	{
		if(!$this->_isConnected()) 
			return false;
		$this->m_pBot->Quit((func_num_args() > 0 ? func_get_arg(0) : ''));
		stream_socket_shutdown($this->m_pSocket, STREAM_SHUT_RDWR);
		$this->m_pSocket = NULL;
		$this->m_bIsConnected = false;
		return true;
	}

	/**
	 * Check whether the bot is connected
	 * 
 	 * @param none
	 * @return boolean
	 */
	public function _isConnected()
	{
		return $this->m_bIsConnected;
	}

	/**
	 * This function receives data from our socket stream
	 * 
 	 * @param none
	 * @return boolean
	 */			
	public function _receive() 
	{
		if(($sData = @fread($this->m_pSocket, 2048)) !== FALSE) {
			if(empty($sData) || $sData == "\n") 
				return;
			$aRaw = explode("\n", $sData);
			//if(count($aRaw) == 1) 
			//	return;
			unset($aRaw[(count($aRaw)-1)]);
			foreach($aRaw as $sRawline) {
				$this->m_pBot->_EventHandler(trim($sRawline));
				$this->m_gBytesReceived += strlen($sRawline);
			}
			return true;
		}
		return false;
		
	}

	/**
	 * This function send data to our socket stream
	 * 
 	 * @param string $command 
	 * @return boolean
	 */	
	public function _send($command)
	{
		if(!$this->_isConnected()) 
			return false;
		fwrite($this->m_pSocket, $command. "\r\n");
		$this->m_gBytesSent += strlen($command);
		return true;
	}

	/**
	 * Tells us how many bytes we received/sent yet
	 * 
 	 * @param none
	 * @return boolean
	 */
	private function _print_stats()
	{
		//$pLog = Log::getInstance();
		//return $pLog->_L('--'.PHP_EOL.'Total Bytes Received: '.$this->m_gBytesReceived.' / Sent: '.$this->m_gBytesSent.PHP_EOL.'--'.PHP_EOL);
	}
}	



?>