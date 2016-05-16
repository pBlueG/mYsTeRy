<?php

function parse($dir, $section, $use_glob, $sortby = NULL)
{
	$ini = function($f) use ($section) {
		$ret = parse_ini_file($f, true);
		return $ret[$section];
	};
	if($use_glob) {
		$data = array();
		foreach(glob($dir.'*.ini') as $file) {
			$data[] = $ini($file);
		}
		if(!is_null($sortby)) {
			foreach($data as $key => $val) {
				$data[$data[$key][$sortby]] = $data[$key];
				unset($data[$key]);
			}
		}
		return $data;
	}
	return $ini($dir);
}

?>