<?php
namespace SM\Db\Query;

class Field implements ExpressionInterface
{
	protected $name;
	protected $alias;
	protected $tablename;
	protected $columnname;
	
	public function __construct($name, $alias = null)
	{
		$this->name  = $name;
		$this->alias = $alias;
		
		if (preg_match('/^(.+)\.(.+)$/', $name, $match)) {
			$this->tablename  = $match[1];
			$this->columnname = $match[2];
		} else {
			$this->columnname = $name;
		}
	}
	
	public function getTablename()
	{
		return $this->tablename;
	}
	
	public function getColumnname()
	{
		return $this->columnname;
	}
	
	public function toSql($db = null)
	{
		if ($this->columnname == '*') {
			if ($this->tablename) {
				return $db->quoteName($this->tablename) . '.' . $this->columnname;
			}
			return $this->columnname;
		}
		return $db->quoteName($this->name) . ($this->alias ? ' AS ' . $db->quoteName($this->alias) : '');
	}
}
