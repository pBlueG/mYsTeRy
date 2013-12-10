<?php

/**
 * Class for several permissions
 * - parses user privileges in all joined channels
 * - runs admin ident checks
 * 
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Privileges
{
	const LEVEL_NONE		= 0;
	const LEVEL_VOICE 		= 1;
	const LEVEL_HALFOP 		= 2;
	const LEVEL_OPERATOR 		= 4;
	const LEVEL_SUPER_OPERATOR 	= 8;
	const LEVEL_OWNER 		= 16;
	const LEVEL_BOT_ADMIN 		= 1337;

	private static $m_aAccessLevel = 
		array(
			self::LEVEL_VOICE,		// Voice +v
			self::LEVEL_HALFOP,		// Halfop +h
			self::LEVEL_OPERATOR,		// Operator +o
			self::LEVEL_SUPER_OPERATOR,	// Superoperator +a
			self::LEVEL_OWNER		// Owner +q
		);

	private static $m_aPrivileges = array('+', '%', '@', '&', '~');

	private static $m_aChannels = array(); 

	private static $m_aAdmins = array();
	
	public static function ParseChannelPrivileges($sChannel, $aUsers) {
		foreach($aUsers as $sUser) {
			if(in_array($sUser[0], self::$m_aPrivileges)) {
				$sKey = substr($sUser, 1);
				self::$m_aChannels[$sChannel][$sKey] = str_replace(self::$m_aPrivileges, self::$m_aAccessLevel, substr($sUser, 0, 1));
			} else 
				self::$m_aChannels[$sChannel][$sUser] = 0;	
		}
	}

	public static function AddUser($sChannel, $sUser, $mode = 0) {
		if(isset(self::$m_aChannels[$sChannel])) {
			self::$m_aChannels[$sChannel][$sUser] = $mode;
			return true;
		} else 
			return false;
	}

	public static function RenameUser($sUser, $new_name)
	{
		foreach(self::$m_aChannels as $sKey => $sVal) {
			if(isset(self::$m_aChannels[$sKey][$sUser])) {
				self::$m_aChannels[$sKey][$new_name] = self::$m_aChannels[$sKey][$sUser];
				unset(self::$m_aChannels[$sKey][$sUser]);
			}
		}
	}

	public static function UpdateUserPrivileges($sChannel, $sUser, $modes) {
		$iLen = strlen($modes);
		$SetUnset = NULL;
		$aUser = explode(' ', trim($sUser));
		$aModes = array('v', 'h', 'o', 'a', 'q');
		for($i = 0, $uIdx = 0; $i < $iLen;$i++) {
			$sMode = $modes[$i];
			if($sMode == '-' || $sMode == '+') {
				// did the server set or unset the given mode?
				$SetUnset = $sMode;
				continue;
			}
			if(in_array($sMode, $aModes)) {
				$check_mode = (int)str_replace($aModes, self::$m_aAccessLevel, $sMode);
				if($SetUnset == '+') {
					if(!($check_mode & self::$m_aChannels[$sChannel][$aUser[$uIdx]]))
						self::$m_aChannels[$sChannel][$aUser[$uIdx]] |= $check_mode;
				} else {
					if($check_mode & self::$m_aChannels[$sChannel][$aUser[$uIdx]])
						self::$m_aChannels[$sChannel][$aUser[$uIdx]] ^= $check_mode;					 
				}
			}
			$uIdx++; // increase the user index	
		}
		return true;
	}

	public static function RemoveChannel($sChannel)
	{
		if(isset(self::$m_aChannels[$sChannel])) 
			unset(self::$m_aChannels[$sChannel]);
	}

	public static function RemoveUser($sChannel, $sUser)
	{
		if(is_null($sChannel)) {
			foreach(self::$m_aChannels as $sChan => &$val) {
				if(isset(self::$m_aChannels[$sChan][$sUser])) 
					unset(self::$m_aChannels[$sChan][$sUser]);
			}
		} else {
			if(isset(self::$m_aChannels[$sChannel][$sUser])) 
				unset(self::$m_aChannels[$sChannel][$sUser]);
		}
	}

	public static function GetUserPrivilege($sUser, $sChannel)
	{
		if(isset(self::$m_aChannels[$sChannel][$sUser]))
			return self::$m_aChannels[$sChannel][$sUser];
		else
			return false;
	}

	private static function _privilege_bit($offset = 0)
	{
		$aTemp = array_slice(self::$m_aAccessLevel, $offset);
		$iResult = array_reduce($aTemp, function($bit, $bit_) { return $bit | $bit_; }, 0);
		return $iResult;
	}

	public static function IsVoiced($sUser, $sChannel) 
	{ 
		if(isset(self::$m_aChannels[$sChannel][$sUser])) {
			$Bit = self::_privilege_bit();
			if(self::$m_aChannels[$sChannel][$sUser] & $Bit)
				return true;
		}
		return false;		
	}
	
	public static function IsHalfop($sUser, $sChannel) 
	{ 
		if(isset(self::$m_aChannels[$sChannel][$sUser])) {
			$Bit = self::_privilege_bit(1);
			if(self::$m_aChannels[$sChannel][$sUser] & $Bit)
				return true;
		}
		return false;
	}

	public static function IsOperator($sUser, $sChannel) 
	{ 
		if(isset(self::$m_aChannels[$sChannel][$sUser])) {
			$Bit = self::_privilege_bit(2);
			if(self::$m_aChannels[$sChannel][$sUser] & $Bit)
				return true;
		}
		return false;
	}

	public static function IsSuperOperator($sUser, $sChannel) 
	{ 
		if(isset(self::$m_aChannels[$sChannel][$sUser])) {
			$Bit = self::_privilege_bit(3);
			if(self::$m_aChannels[$sChannel][$sUser] & $Bit)
				return true;
		}
		return false;
	}
		
	public static function IsOwner($sUser, $sChannel) 
	{ 
		if(isset(self::$m_aChannels[$sChannel][$sUser])) {
			$Bit = self::_privilege_bit(4);
			if(self::$m_aChannels[$sChannel][$sUser] & $Bit)
				return true;
		}
		return false;
	}

	public static function IsBotAdmin($sIdent) 
	{ 
		if(in_array($sIdent, self::$m_aAdmins))
			return true;
		else
			return false;
	}

	public static function AddBotAdmin($sIdent)
	{
		// damn what a ugly regex
		if(preg_match('/[a-zA-Z0-9^-_`|]+\![a-zA-Z0-9^~=_.]+\@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+/', $sIdent))  {
			self::$m_aAdmins[] = $sIdent;
			return true;
		}
		return false;
	}
}	



?>