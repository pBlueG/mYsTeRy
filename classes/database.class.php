<?php

/**
 * Database class 
 * - supports SQLite as well as MySQL (see configuration.ini/mysql.ini)
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Database extends Singleton
{
	static $DB = NULL;

	private $m_bIsConnected;
	private $m_bMySQL;

	function __construct($bMySQL = false, $config = NULL)
	{
		$this->m_bMySQL = $bMySQL;
		if($this->m_bMySQL && is_array($config))
			$sFormat = 'mysql:dbname='.$config['database'].';host='.$config['host'];
		else
			$sFormat = 'sqlite:database/mYsTeRy.db';
		$this->m_bIsConnected = true;
		try {
			self::$DB = 
			$this->m_bMySQL ? new PDO($sFormat, $config['user'], $config['password']) : new PDO($sFormat);
			self::$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		}
		catch(PDOException $e) {
			Log:_Log('logs/database error.log', ___METHOD__ . ' -> ' . $e->getMessage(), date('[d/m/Y | H:i:s] '));
			$this->m_bIsConnected = false;
		}
	}

	public function _type($type, $size)
	{
		switch(strtolower($type))
		{
			case 'auto':
			case 'autoincrement':
			case 'auto_increment':
				if($this->m_bMySQL)
					return 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY';
				else
					return 'INTEGER PRIMARY KEY AUTOINCREMENT';
			case 'text':
			case 'string':
			case 'varchar':
				if($this->m_bMySQL)
					return 'VARCHAR('.$size.')';
				else
					return 'TEXT';
			case 'number':
			case 'int':
			case 'integer':
				if($this->m_bMySQL)
					return 'INT('.$size.')';
				else
					return 'INTEGER';
			default:
				return 'NULL';
		}
	}
				
				

	public function _isConnected()
	{
		return $this->m_bIsConnected;
	}

	public function _table_exists($sTable)
	{
		if(!$this->_isConnected())
			return false;
		$sql = 'SELECT name FROM sqlite_master WHERE type=\'table\' AND name=\''.$sTable.'\';';
		$row = self::$DB->query($sql);
		$iCount = count($row->fetchAll());
		if($iCount > 0) 
			return true;
		else
			return false;
	}

	public function _create_table($sTable, $aColumns)
	{
		if(!$this->_isConnected())
			return false;
		$sColumns = '';
		foreach($aColumns as $key => $val) {
			$sColumns .= '`'.$key.'` '.$val.',';
		}
		$sColumns = rtrim($sColumns, ',');
		//$sColumns = substr($sColumns, 0, -1);
		$sFormat = 'CREATE TABLE IF NOT EXISTS `'.$sTable.'`('.$sColumns.');';
		if(self::$DB->query($sFormat) !== false)
			return true;
		else
 			return self::$DB->errorCode();
	}

	public function _query($sQuery)
	{
		if(!$this->_isConnected())
			return;	
		$result = self::$DB->prepare($sQuery);
		$result->execute();
		return $result;
	}

	// _update('table', array('key' => 'value', 'key2' => 'value2'));
	// INSERT INTO table (key, key2) VALUES ('value', 'value2');
	public function _insert($sTable, $aValues, $limit = 1)
	{
		if(!$this->_isConnected())
			return;
		$sKeys = implode(',', array_keys($aValues));
		$sFormat = implode(',', array_fill(0, count($aValues), '?'));
		$stmt = self::$DB->prepare('INSERT INTO '.$sTable.' ('.$sKeys.') VALUES ('.$sFormat.')');
		$valueIndex = 0;
		foreach($aValues as $key => $val)
			$stmt->bindValue(++$valueIndex, $val);
		$stmt->execute();
		return $stmt->rowCount();
	}

	// _update('table', array('key' => 'value', 'key2' => 'value2'), array('id' => 'valuexyz'));
	// UPDATE table SET key = value, key2 = value2 WHERE id = valuexyz LIMIT 1
	public function _update($sTable, $aValues, $aWhere = NULL, $limit = 1)
	{
		if(!$this->_isConnected())
			return;
		$sFormat = implode('=?,', array_keys($aValues)).'=?';
		if(is_null($aWhere))
			$stmt = self::$DB->prepare('UPDATE '.$sTable.' SET '.$sFormat.' LIMIT '.$limit);
		else {
			$stmt = self::$DB->prepare('UPDATE '.$sTable.' SET '.$sFormat.' WHERE '.key($aWhere).'=:whereclause LIMIT '.$limit);
			$stmt->bindValue(':whereclause', current($aWhere));
		}
		$valueIndex = 0;
		foreach($aValues as $key => $val)
			$stmt->bindValue(++$valueIndex, $val);
		$stmt->execute();
		return $stmt->rowCount();
	}

	// _delete('table', array('key' => 'value'));
	// DELETE FROM table WHERE key = value
	public function _delete($sTable, $aWhere)
	{
		if(!$this->_isConnected())
			return;
		$stmt = self::$DB->prepare('DELETE FROM '.$sTable.' WHERE '.key($aWhere).'=:whereclause');
		$valueIndex = 0;
		$stmt->bindValue(':whereclause', current($aWhere));
		$stmt->execute();
		return $stmt->rowCount();
	}

	public function _free($data)
	{
		// TODO
		//return unset($data);
	}

	public function _fetch_array($result, $type = PDO::FETCH_ASSOC)
	{
		return $result->fetch($type);
	}
}	

?>