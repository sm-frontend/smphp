<?php
namespace SM\Db\Connection;

use PDO;
use PDOException;
use SM\Db\Query\Query;
use SM\Util\Arr;

class Connection extends PDO
{
	protected $bind  = [];
	protected $logs  = [];
	protected $guid  = 0;
	protected $debug = false;
	
	protected $dbDriver;
	protected $nameOpening;
	protected $nameClosing;
	
	protected $transactionLevel = 0;
	
	public function __construct($dsn, $user = null, $password = null, $attributes = [])
	{
		try {
			parent::__construct($dsn, $user, $password, $attributes);
			$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			throw new \Exception('Database failed: ' . $e->getMessage() . ' in ' . $dsn);
		}
		
		$this->dbDriver = (string) $this->getAttribute(PDO::ATTR_DRIVER_NAME);
		
		switch ($this->dbDriver) {
			case 'mysql':
				$this->nameOpening = $this->nameClosing = '`';
				break;
			default:
				$this->nameOpening = $this->nameClosing = '"';
				break;
		}
	}
	
	public function exec($statement)
	{
		$sql = $statement instanceof Query ? $statement->toSql($this) : $statement;
		
		if ($this->debug) {
			$this->logs[] = $sql;
		}
		
		return parent::exec($sql);
	}
	
	public function query($statement)
	{
		$sql = $statement instanceof Query ? $statement->toSql($this) : $statement;
		
		if ($this->debug) {
			$this->logs[] = $sql;
		}
		
		return parent::query($sql);
	}
	
	public function prepare($sql, $options = [])
	{
		$stmt = parent::prepare($sql, $options);
		
		if ($this->debug) {
			$this->logs[] = $sql;
		}
		
		return $stmt;
	}
	
	public function pexecute($sql, $params = null)
	{
		try {
			$stmt = $this->prepare($sql);

			if (is_array($params)) {
				if ($this->debug) {
					$this->logs[] = $this->rawQuery($sql, $params);
				}

				$stmt->execute($params);
			} else {
				$stmt->execute();
			}
		} catch (PDOException $e) {
			return false;
		}
		
		return $stmt;
	}
	
	public function beginTransaction()
	{
		if ($this->transactionLevel > 0) {
			$this->exec('SAVEPOINT LEVEL' . $this->transactionLevel);
		} else {
			parent::beginTransaction();
		}
		
		$this->transactionLevel++;
	}
	
	public function rollback()
	{
		$this->transactionLevel--;
		
		if ($this->transactionLevel > 0) {
			$this->exec('ROLLBACK TO SAVEPOINT LEVEL' . $this->transactionLevel);
		} else {
			parent::rollback();
		}
	}
	
	public function commit()
	{
		$this->transactionLevel--;
		
		if ($this->transactionLevel > 0) {
			$this->exec('RELEASE SAVEPOINT LEVEL' . $this->transactionLevel);
		} else {
			parent::commit();
		}
	}
	
	public function quoteName($name)
	{
		$names = [];
		foreach (explode('.', $name) as $name) {
			$names[] = $this->nameOpening . $name . $this->nameClosing;
		}
		
		return implode('.', $names);
	}
	
	public function getBindname()
	{
		return ':sMdB' . $this->guid++ . '_bInDnAmE';
	}
	
	public function bind($key, $value = null)
	{
		if (is_array($key)) {
			$this->bind = array_merge($this->bind, $key);
		} else {
			$this->bind[$key] = $value;
		}
	}
	
	public function getBind()
	{
		$bind = $this->bind;
		$this->bind = [];
		
		return $bind;
	}
	
	public function debug()
	{
		$this->debug = true;
	}
	
	public function getLogs()
	{
		return $this->logs;
	}
	
	public function rawQuery($query, $params)
	{
		$keys   = [];
		$values = $params;
		
		foreach ($params as $key => $value) {
			$keys[] = '/' . $key . '/';
			
			if (is_string($value)) {
				$values[$key] = $this->quote($value);
			}
		}
		
		return preg_replace($keys, $values, $query, 1, $count);
	}
	
	public function getColumns($table)
	{
		switch ($this->dbDriver) {
			case 'mysql':
				$stmt = $this->query('SHOW COLUMNS FROM ' . $this->quoteName($table));
				return Arr::getCols($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
			
			case 'pgsql':
				$stmt = $this->pexecute('SELECT column_name FROM information_schema.columns WHERE table_schema = :table_schema AND table_name = :table_name', [':table_schema' => 'public', ':table_name' => $table]);
				return $stmt->fetchAll(PDO::FETCH_COLUMN);
				
			case 'sqlite':
				$stmt = $this->query('PRAGMA table_info(' . $this->quoteName($table) . ')');
				return Arr::getCols($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
		}
	}
	
	public function getDriver()
	{
		return $this->dbDriver;
	}
}
