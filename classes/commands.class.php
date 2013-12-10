<?php

/**
 * IRC Commands class
 * - formats common IRC raw commands
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Commands implements ColorCodes
{
	private static $m_aReplaceBB = 
		array (
			'[b]' 			=> self::BOLD, 
			'[/b]' 			=> self::BOLD,
			'[i]' 			=> self::ITALIC, 
			'[/i]' 			=> self::ITALIC,
			'[u]' 			=> self::UNDERLINE, 
			'[/u]' 			=> self::UNDERLINE,
			'[color=white]' 	=> self::COLOR_WHITE,
			'[color=black]' 	=> self::COLOR_BLACK,
			'[color=blue]' 		=> self::COLOR_BLUE,
			'[color=green]' 	=> self::COLOR_GREEN,
			'[color=red]' 		=> self::COLOR_RED,
			'[color=brown]' 	=> self::COLOR_BROWN,
			'[color=purple]' 	=> self::COLOR_PURPLE,
			'[color=orange]' 	=> self::COLOR_ORANGE,
			'[color=yellow]' 	=> self::COLOR_YELLOW,
			'[color=lightgreen]' 	=> self::COLOR_LIGHTGREEN,
			'[color=teal]' 		=> self::COLOR_TEAL,
			'[color=lightcyan]' 	=> self::COLOR_LIGHTCYAN,
			'[color=lightblue]' 	=> self::COLOR_LIGHTBLUE,
			'[color=pink]' 		=> self::COLOR_PINK,
			'[color=grey]' 		=> self::COLOR_GREY,
			'[color=silver]' 	=> self::COLOR_SILVER,
			'[/color]' 		=> self::COLOR_END
		);
			

	private static function _parseColors($string) 
	{
		// we could also use preg_replace(), but this method was shorter </being lazy>
		$sReturn = strtr($string, self::$m_aReplaceBB);
		return $sReturn;
	}
		
	public static function Say($channel, $message, $parse_bbcodes = true)
	{	
		if($parse_bbcodes)
			$message = self::_parseColors($message);
		$sCommand = 'PRIVMSG '.$channel.' :'.$message;
		return $sCommand;
	}

	public static function PM($receiver, $message, $parse_bbcodes = true)
	{
		if($parse_bbcodes)
			$message = self::_parseColors($message);	
		$sCommand = 'PRIVMSG '.$receiver.' :'.$message;
		return $sCommand;
	}

	public static function Notice($receiver, $message)
	{	
		$sCommand = 'NOTICE '.$receiver.' :'.$message;
		return $sCommand;
	}

	public static function Nick($nickname)
	{	
		$sCommand = 'NICK '.$nickname;
		return $sCommand;
	}

	public static function Pass($password)
	{	
		$sCommand = 'PASS '.$password;
		return $sCommand;
	}

	public static function User($username, $realname)
	{
		$sCommand = 'USER '.$username.' - - :'.$realname;
		return $sCommand;
	} 

	public static function Join($channel)
	{
		$sCommand = 'JOIN '.$channel;
		if(func_num_args() > 1)
			$sCommand .= ' '.func_get_arg(1);
		return $sCommand;
	}

	public static function Part($channel)
	{
		$sCommand = 'PART '.$channel;
		if(func_num_args() > 1)
			$sCommand .= ' :'.func_get_arg(1);
		return $sCommand;
	}

	public static function Action($receiver, $message)
	{
		$sCommand = 'PRIVMSG '.$receiver.' :'.chr(1).'ACTION '.$message.chr(1);
		return $sCommand;
	}

	public static function Quit()
	{
		$sCommand = 'QUIT :';
		if(func_num_args() > 0)
			$sCommand .= func_get_arg(0);
		return $sCommand;
	}
}

?>