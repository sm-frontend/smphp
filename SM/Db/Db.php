<?php
namespace SM\Db;

use PDO;
use Closure;
use SM\Db\Query\Join;
use SM\Db\Query\Query;
use SM\Db\Query\Literal;
use SM\Db\TableGateway\TableGateway;
use SM\Cache\CacheInterface;

class Db
{
	protected $pdo;
	protected $table;
	protected $query;
	protected $cache;
	protected $config = [
		'database_type' => 'mysql',
		'database_name' => '',
		'server'        => '',
		'port'          => '',
		'charset'       => 'utf8',
		'username'      => '',
		'password'      => ''
	];
	
	public function __construct(array $config = [])
	{
		$config  = array_merge($this->config, $config);
		
		$adapter = __NAMESPACE__ . '\Connection\Adapter\\' . ucfirst(strtolower($config['database_type']));
		
		if (class_exists($adapter, true)) {
			$this->pdo = (new $adapter)->connect($config);
		} else {
			throw new \Exception("Db Adapter [$adapter] does not exist.");
		}
	}
	
	public function setCache(CacheInterface $cache)
	{
		$this->cache = $cache;
		return $this;
	}
	
	public function raw($value)
	{
		return new Literal($value);
	}
	
	public function from($table)
	{
		if ($table instanceof Closure) {
			return $this->fromSub($table);
		}
		
		preg_match('/(?<table>\w+)\s*\((?<alias>\w+)\)/i', $table, $match);
		
		if (isset($match['table'], $match['alias'])) {
			$this->query = new Query($match['table'], $match['alias']);
		} else {
			$this->query = new Query($table);
		}
		
		$this->table = $table;
		return $this;
	}
	
	public function fromSub(Closure $table)
	{
		$clone = clone $this;
		call_user_func($table, $clone);
		
		$this->query = new Query($clone->getQuery());
		return $this;
	}
	
	public function select($columns)
	{
		if (!is_array($columns)) {
			$columns = func_get_args();
		}
		
		foreach ($columns as $column) {
			if (is_string($column)) {
				preg_match('/(?<column>[\w\.]+)(?:\s*\((?<alias>\w+)\))?/i', $column, $match);
				
				if (isset($match['column'], $match['alias'])) {
					$this->query->addColumn($match['column'], $match['alias']);
					continue;
				}
			}
			$this->query->addColumn($column);
		}
		return $this;
	}
	
	public function selectDistinct($columns)
	{
		if (!is_array($columns)) {
			$columns = func_get_args();
		}
		
		return $this->distinct(true)->select($columns);
	}
	
	public function selectRaw($column)
	{
		return $this->select($this->raw($column));
	}
	
	public function join($table, $column, $operator = null, $value = null, $type = 'INNER')
	{
		preg_match('/(?<table>\w+)\s*\((?<alias>\w+)\)/i', $table, $match);
		
		if (isset($match['table'], $match['alias'])) {
			$join = new Join($match['table'], $type . ' JOIN', $match['alias']);
		} else {
			$join = new Join($table, $type . ' JOIN');
		}
		
		if ($column instanceof Closure) {
			call_user_func($column, $join);
		} else {
			$join->whereColumn($column, $operator, $value);
		}
		
		$this->query->addJoin($join);
		return $this;
	}
	
	public function leftJoin($table, $column, $operator = null, $value = null)
	{
		return $this->join($table, $column, $operator, $value, 'LEFT');
	}
	
	public function rightJoin($table, $column, $operator = null, $value = null)
	{
		return $this->join($table, $column, $operator, $value, 'RIGHT');
	}
	
	public function union($union, $all = false)
	{
		$clone = clone $this;
		call_user_func($union, $clone);
		
		$this->query->addUnion($clone->getQuery(), null, $all ? 'ALL' : 'DISTINCT');
		return $this;
	}
	
	public function unionAll($union)
	{
		return $this->union($union, true);
	}
	
	public function groupBy($columns)
	{
		if (!is_array($columns)) {
			$columns = func_get_args();
		}
		
		foreach ($columns as $column) {
			$this->query->addGroupBy($column);
		}
		return $this;
	}
	
	public function having($column, $operator = null, $value = null, $function = null)
	{
		if ($function !== null) {
			$column = $this->raw($function . '(' . $column . ')');
		}
		$this->query->setHaving($column, $value, $operator);
		return $this;
	}
	
	public function orderBy($column, $direction = null)
	{
		if (is_array($column)) {
			foreach ($column as $col => $dir) {
				$this->query->setOrder($col, $dir);
			}
		} else {
			$this->query->setOrder($column, $direction);
		}
		return $this;
	}
	
	public function limit($limit)
	{
		$this->query->setLimit($limit);
		return $this;
	}
	
	public function offset($offset)
	{
		$this->query->setOffset($offset);
		return $this;
	}
	
	public function distinct($distinct)
	{
		$this->query->setDistinct($distinct);
		return $this;
	}
	
	public function __call($name, $params)
	{
		if (method_exists($this->query, $name)) {
			if (in_array($name, ['whereIn', 'whereNotIn'])) {
				array_push($params, $this);
			}
			call_user_func_array([$this->query, $name], $params);
		}
		return $this;
	}
	
	public function get($cacheEnabled = false, $cacheExpire = 900)
	{
		$result = $this->limit(1)->getAll($cacheEnabled, $cacheExpire);
		
		return !empty($result) ? $result[0] : false;
	}
	
	public function getAll($cacheEnabled = false, $cacheExpire = 900)
	{
		$sql          = $this->getSQL();
		$bind         = $this->pdo->getBind();
		$cacheEnabled = $cacheEnabled && $this->cache instanceof CacheInterface;
		$result       = false;
		
		if ($cacheEnabled) {
			$md5key = md5($sql . serialize($bind));
			$result = $this->cache->get($md5key);
		}
		
		if (!$result) {
			$stmt = $this->pexecute($sql, $bind);
			
			if ($stmt) {
				$mode   = 1 == $stmt->columnCount() ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC;
				$result = $stmt->fetchAll($mode);
				
				if ($cacheEnabled && $result) {
					$this->cache->set($md5key, $result, $cacheExpire);
				}
			}
		}
		return $result;
	}
	
	public function count($column = '*')
	{
		return $this->aggregate('COUNT', $column);
	}
	
	public function min($column)
	{
		return $this->aggregate('MIN', $column);
	}
	
	public function max($column)
	{
		return $this->aggregate('MAX', $column);
	}
	
	public function sum($column)
	{
		return $this->aggregate('SUM', $column);
	}
	
	public function avg($column)
	{
		return $this->aggregate('AVG', $column);
	}
	
	public function has()
	{
		$this->select($this->raw(1));
		
		$stmt = $this->pexecute('SELECT EXISTS(' . $this->getSQL() . ')', $this->pdo->getBind());
		
		if ($stmt) {
			$result = $stmt->fetchColumn();
			return $result === '1' || $result === true;
		}
		return false;
	}
	
	protected function aggregate($function, $column)
	{
		$this->select($this->raw($function . '(' . $column . ')'));
		
		$stmt = $this->pexecute($this->getSQL(), $this->pdo->getBind());
		
		if ($stmt) {
			return 0 + $stmt->fetchColumn();
		}
		return false;
	}
	
	public function getQuery()
	{
		return $this->query;
	}
	
	public function getSQL()
	{
		return $this->query->toSql($this->pdo);
	}
	
	public function getRawSQL()
	{
		return $this->pdo->rawQuery($this->getSQL(), $this->pdo->getBind());
	}
	
	public function insert(array $data, $replace = false)
	{
		$gateway = new TableGateway($this->table, $this->pdo);
		
		if (!is_array(reset($data))) {
			$data = [$data];
		}
		
		$lastId = [];
		foreach ($data as $v) {
			$lastId[] = $gateway->insert($v, $replace);
		}
		
		return count($lastId) > 1 ? $lastId : $lastId[0];
	}
	
	public function replace(array $data)
	{
		return $this->insert($data, true);
	}
	
	public function batchInsert(array $columns, array $rows)
	{
		$gateway = new TableGateway($this->table, $this->pdo);
		
		return $gateway->batchInsert($columns, $rows);
	}
	
	public function update(array $data)
	{
		$gateway = new TableGateway($this->table, $this->pdo);
		
		return $gateway->update($data, $this->query->whereClause($this->pdo));
	}
	
	public function delete()
	{
		$gateway = new TableGateway($this->table, $this->pdo);
		
		return $gateway->delete($this->query->whereClause($this->pdo));
	}
	
	public function getPdo()
	{
		return $this->pdo;
	}
	
	public function quote($string)
	{
		return $this->pdo->quote($string);
	}
	
	public function query($query)
	{
		return $this->pdo->query($query);
	}
	
	public function exec($query)
	{
		return $this->pdo->exec($query);
	}
	
	public function pexecute($sql, $params = null)
	{
		return $this->pdo->pexecute($sql, $params);
	}
	
	public function beginTransaction()
	{
		$this->pdo->beginTransaction();
	}
	
	public function rollback()
	{
		$this->pdo->rollback();
	}
	
	public function commit()
	{
		$this->pdo->commit();
	}
	
	public function debug()
	{
		$this->pdo->debug();
		return $this;
	}
	
	public function log()
	{
		return $this->pdo->getLogs();
	}
	
	public function error()
	{
		return $this->pdo->errorInfo();
	}
	
	public function info()
	{
		$output = [
			'server'     => 'SERVER_INFO',
			'driver'     => 'DRIVER_NAME',
			'client'     => 'CLIENT_VERSION',
			'version'    => 'SERVER_VERSION',
			'connection' => 'CONNECTION_STATUS'
		];
		
		foreach ($output as $key => $value) {
			$output[$key] = $this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
		}
		
		return $output;
	}
}
