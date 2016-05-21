<?php

/**
 * Misc class
 * - miscellaneous functions
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Misc
{
	// formats seconds to a readable string e.g. 5 days, 2 hours, 10 minutes and 10 seconds
	public function SecondsToString($seconds)
	{
		$sFormat = NULL;
		if(($rest = $seconds % (60*60*24*365)) != $seconds) {
			$sFormat .= ($seconds-$rest)/(60*60*24*365).' years, ';
			$seconds = $rest;
		}
		if(($rest = $seconds % (60*60*24*7)) != $seconds) {
			$sFormat .= ($seconds-$rest)/(60*60*24*7).' weeks, ';
			$seconds = $rest;
		}
		if(($rest = $seconds % (60*60*24)) != $seconds) {
			$sFormat .= ($seconds-$rest)/(60*60*24).' days, ';
			$seconds = $rest;
		}
		if(($rest = $seconds % (60*60)) != $seconds) {
			$sFormat .= ($seconds-$rest)/(60*60).' hours, ';
			$seconds = $rest;
		}
		if(($rest = $seconds % 60) != $seconds) {
			$sFormat .= ($seconds-$rest)/(60).' minutes, ';
			$seconds = $rest;
		}
		if($seconds > 0)
			$sFormat .= $seconds.' seconds, ';
		$sFormat = substr($sFormat, 0, -2);
		if(($pos = strrpos($sFormat, ',')) !== FALSE) {
			$sFormat = substr_replace($sFormat, ' and ', $pos, 2);
		}
		return $sFormat;
	}

	public function glueParams($string, $delimiter = ' ')
	{
		return explode($delimiter, $string);
	}

	public function isChannel($string)
	{
		return (substr($string, 0, 1) == '#' && strlen($string) > 1);
	}

	function formatBytes($size)
	{
    		$unit = array('b','kb','mb','gb','tb','pb');
    		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}


}

?>