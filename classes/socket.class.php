<?php

/**
 * Socket class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Socket
{
	/**
	 * Just a pointer to the bot instance
	 *
	 * @var object 
	 * @access private
	 */
	private $m_oBot;

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
	protected $m_aSettings = array();

	/**
	 * Do we use SSL or not?
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
	protected $m_pSocket;

	/**
	 * Not really in use
	 *
	 * @var mixed
	 * @access private
	 */
	private $m_IdentD;

	private $m_bConnected;


	/**
	 * C'tor
	 *
	 * @param object $parent A pointer to the bot class
	 * @param array $aConfig A array with all data required to connect
	 * @access public
	 */
	public function __construct($pBot, array $aConfig)
	{
		$this->m_oBot = $pBot;
		$this->m_bSSL = false;
		$this->m_bIsConnected = false;
		$this->m_aSettings = $aConfig;
		if($this->m_aSettings['SSL'] == true)
			$this->m_bSSL = true;
	}


	/**
	 * This function connects to the IRC server
	 *
	 */
	public function _connect()
	{
		$sServer = $this->_select_server($this->m_aSettings['Servers']);
		if(!$this->m_bSSL) {
			//$this->_runIdentServer();
			$this->m_pSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			//socket_listen(
			if($this->m_pSocket !== false) {
				if(@socket_connect($this->m_pSocket, $sServer['Host'], $this->m_aSettings['Port'])) {
					socket_set_nonblock($this->m_pSocket);
					$this->m_bIsConnected = true;
					return true;
				}
			}
		} else {
			// Create the stream context for a SSL connection
			$context = stream_context_create();

			//http://www.php.net/manual/en/context.ssl.php - for further reference
			stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
			stream_context_set_option($context, 'ssl', 'local_cert', __DIR__ . $this->m_aSettings['SSL_CRT']);
			//stream_context_set_option($context, array('ssl' => array('allow_self_signed' => true, 'local_cert' => $this->m_aSettings['SSL_CRT'])));

			// Attempt to connect
			$this->m_pSocket = @stream_socket_client('ssl://'.$sServer['Host'].':6697', $errno, $errstr, 3.0, STREAM_CLIENT_CONNECT, $context);
			if($this->m_pSocket !== false) {
				// Our connection attempt was successful
				stream_set_blocking($this->m_pSocket, 0);
				$this->m_bIsConnected = true;
				return true;
			}
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
		$this->_send('QUIT :'. (func_num_args() > 0 ? func_get_arg(0) : ''));
		if(!$this->m_bSSL) 
			socket_close($this->m_pSocket);
		else 
			stream_socket_shutdown($this->m_pSocket, STREAM_SHUT_RDWR);
		$this->m_pSocket = NULL;
		$this->m_bIsConnected = false;
		return true;
	}

	public function _isConnected()
	{
		return $this->m_bIsConnected;
	}
			
	public function _rawData() 
	{
		if(!$this->_isConnected()) 
			return false;
		if($this->m_bSSL) {
			if(($sData = @fread($this->m_pSocket, 2048)) === FALSE) {
				$this->_disconnect();
				return;
			}
		} else {
			/*$aClients = array($this->m_IdentD);
			$testicle = socket_select($aClients, $write=NULL, $except=NULL, 0);
			if (socket_select($aClients, $write, $except, 0) > 0) {
				if(in_array($this->m_IdentD, $aClients)) {
					$irc_ident_check = socket_accept($this->m_IdentD);
					echo "Listening to ".$irc_ident_check;
				}
				
				
			}*/
			if(socket_last_error($this->m_pSocket) == 104) {
				$this->_disconnect();
				return;
			}
			$sData = @socket_read($this->m_pSocket, 2048/*, PHP_NORMAL_READ*/);
		}
		if(empty($sData) || $sData == "\n") return;
		$aRaw = explode("\n", $sData);
		if(count($aRaw) == 1) return;
		unset($aRaw[(count($aRaw)-1)]);
		foreach($aRaw as $sRawline) {
			$this->m_oBot->_EventHandler(trim($sRawline));
			$this->m_gBytesReceived += strlen($sRawline);
		}
		return true;
		
	}

	public function _send($command)
	{
		if(!$this->_isConnected()) 
			return false;
		if(!$this->m_bSSL)
			socket_write($this->m_pSocket, $command."\r\n");
		else
			//stream_socket_sendto($this->m_pSocket, $command. "\r\n");
			fwrite($this->m_pSocket, $command. "\r\n");
		$this->m_gBytesSent += strlen($command);
		return true;
	}

	private function _select_server(array $aServers)
	{
		if(count($aServers) > 1) shuffle($aServers);
		foreach($aServers as $sHost) {
			$sIPv4 = gethostbyname($sHost);
			if(@filter_var($sIPv4, FILTER_VALIDATE_IP) !== FALSE) {
				return ($aServer = array('Host' => $sHost, 'IP' => $sIPv4));
			}
		}
		Log::Error('-- No valid server found to connect. Check your server settings:'.PHP_EOL.var_export($aServers, true).PHP_EOL);
		exit();
		return;
			
	}

	protected function _print_stats()
	{
		//$pLog = Log::getInstance();
		//return $pLog->_L('--'.PHP_EOL.'Total Bytes Received: '.$this->m_gBytesReceived.' / Sent: '.$this->m_gBytesSent.PHP_EOL.'--'.PHP_EOL);
	}
}	



?>