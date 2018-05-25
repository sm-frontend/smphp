<?php
namespace SM\Db\TableGateway;

use PDO;
use SM\Db\Query\Field;
use SM\Db\Query\Value;
use SM\Db\Query\Literal;
use SM\Db\Connection\Connection;
use SM\Db\Query\CriteronInterface;

class TableGateway
{
	protected $db;
	protected $table;
	
	public function __construct($table, Connection $db)
	{
		if (empty($table)) {
			throw new \Exception('No table given for CRUD.');
		}
		
		$this->db         = $db;
		$this->table      = $table;
		$this->quoteTable = $db->quoteName($table);
	}
	
	public function getDb()
	{
		return $this->db;
	}
	
	public function getTable()
	{
		return $this->table;
	}
	
	protected function getColumns()
	{
		return $this->db->getColumns($this->table);
	}
	
	public function insert($data, $replace = false)
	{
		$data    = $this->parseData($data);
		
		$columns = implode(', ', array_keys($data));
		$values  = implode(', ', array_values($data));
		
		$query   = sprintf('%s INTO %s (%s) VALUES (%s)', ($replace ? 'REPLACE' : 'INSERT'), $this->quoteTable, $columns, $values);
		
		$this->db->pexecute($query, $this->db->getBind());
		
		switch ($this->db->getDriver()) {
			case 'pgsql':
				return $this->db->query('SELECT LASTVAL()')->fetchColumn();
			
			default:
				return $this->db->lastInsertId();
		}
	}
	
	public function batchInsert(array $columns, array $rows)
	{
		$values = [];
		
		foreach ($rows as $row) {
			$vs = [];
			
			foreach ($row as $i => $value) {
				if (is_string($value)) {
					$value = $this->db->quote($value);
				} elseif (is_null($value)) {
					$value = 'NULL';
				} elseif (is_bool($value)) {
					$value = $value ? '1' : '0';
				}
				$vs[] = $value;
			}
			$values[] = '(' . implode(', ', $vs) . ')';
		}
		
		foreach ($columns as $i => $column) {
			$columns[$i] = $this->db->quoteName($column);
		}
		
		$query = sprintf('INSERT INTO %s (%s) VALUES %s', $this->quoteTable, implode(', ', $columns), implode(', ', $values));
		
		return $this->db->exec($query);
	}
	
	public function update($data, $where = null)
	{
		$sets = [];
		
		foreach ($this->parseData($data) as $column => $value) {
			$sets[] = $column . ' = ' . $value;
		}
		
		$query = sprintf('UPDATE %s SET %s', $this->quoteTable, implode(', ', $sets));
		
		if ($where != null) {
			$query .= $this->buildWhere($where);
		}
		
		$result = $this->db->pexecute($query, $this->db->getBind());
		
		return $result->rowCount();
	}
	
	public function delete($where)
	{
		if (empty($where)) {
			throw new \Exception('No conditions given for delete.');
		}
		
		$query  = sprintf('DELETE FROM %s%s', $this->quoteTable, $this->buildWhere($where));
		
		$result = $this->db->pexecute($query, $this->db->getBind());
		
		return $result->rowCount() > 0;
	}
	
	protected function parseData(array $data)
	{
		$result = [];
		
		foreach ($this->getColumns() as $column) {
			if (isset($data[$column])) {
				$key = (new Field($column))->toSql($this->db);
				$val = $data[$column];
				
				if ($val instanceof Literal) {
					$result[$key] = $val->toSql($this->db);
				} else {
					$value        = new Value($val);
					$result[$key] = $this->db->getBindname();
					
					$this->db->bind($result[$key], $value->toSql($this->db));
				}
			}
		}
		
		if (empty($result)) {
			throw new \Exception('No data given for CRUD.');
		}
		
		return $result;
	}
	
	protected function buildWhere($where)
	{
		return $where instanceof CriteronInterface ? ' WHERE ' . $where->toSql($this->db) : $where;
	}
}
