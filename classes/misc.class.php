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

	// (c) http://www.if-not-true-then-false.com/2009/format-bytes-with-php-b-kb-mb-gb-tb-pb-eb-zb-yb-converter/
	public function formatBytes($bytes, $unit = "", $decimals = 2) 
	{
		$units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4);
		$value = 0;
		if($bytes > 0) {
			if(!array_key_exists($unit, $units)) {
				$pow = floor(log($bytes)/log(1024));
				$unit = array_search($pow, $units);
			}
			$value = ($bytes/pow(1024,floor($units[$unit])));
		}
		if(!is_numeric($decimals) || $decimals < 0) {
			$decimals = 2;
		}
		return sprintf('%.' . $decimals . 'f '.$unit, $value);
  	}


}

?>