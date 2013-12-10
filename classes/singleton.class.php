<?php

/*
	Copyright (c):
	http://www.phpbar.de/w/Abstract_Singleton
	
	A class to avoid twice instances of the same class
*/

abstract Class Singleton
{
	private static $instances = array();
 
	final public static function getInstance()
	{
		$class = get_called_class();
		if (empty(self::$instances[$class])) {
			$rc = new ReflectionClass($class);
			self::$instances[$class] = $rc->newInstanceArgs(func_get_args());
		}
		return self::$instances[$class];
	}
 
	final public function __clone() {
		throw new Exception('This singleton must not be cloned.');
	}
}

?>