<?php

/**
 * Log class
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Log
{


	public static function Error($log, $file = 'logs/Bot Error.log')
	{
		return file_put_contents($file, $log . PHP_EOL, FILE_APPEND);
	}

	public static function DebugHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$szLog = date('[d/m/Y | H:i:s] ');
		switch($errno) {
			case E_ERROR:
			case E_USER_ERROR:
				$szLog .= 'Error ('.$errno.'):'.PHP_EOL;
				exit(0);
				break;
			case E_WARNING: 
			case E_USER_WARNING:
				$szLog .= 'Warning ('.$errno.'):'.PHP_EOL;
				break;	
			case E_NOTICE:
			case E_USER_NOTICE:
				$szLog .= 'Notice ('.$errno.'):'.PHP_EOL;
				break;		
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$szLog .= 'Deprecated function:'.PHP_EOL;
				break;		
			default:
				$szLog .= 'Unknown problem ('.$errno.'):'.PHP_EOL;
				break;
		}
		$szLog .= 'Found on line '.$errline.' -> File['.$errfile.']'.PHP_EOL.$errstr.PHP_EOL.PHP_EOL;
		return file_put_contents('logs/debug.log', $szLog, FILE_APPEND);
	}


	public static function _Log($filename, $data, $timestamp = NULL)
	{
		return file_put_contents($filename.'.log', (is_null($timestamp) ? $data : $timestamp.$data) . PHP_EOL, FILE_APPEND);
	}
}	



?>