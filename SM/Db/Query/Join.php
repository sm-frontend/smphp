<?php
namespace SM\Db\Query;

class Join extends Criteria
{
	protected $type;
	protected $table;
	protected $alias;
	
	public function __construct($table, $type = 'JOIN', $alias = null)
	{
		parent::__construct('AND');
		
		$this->table = $table;
		$this->type  = strtoupper(trim($type));
		$this->alias = $alias;
	}
	
	public function on($column, $operator = null, $value = null)
	{
		return $this->whereColumn($column, $operator, $value);
	}
	
	public function toSql($db = null)
	{
		$on = '';
		
		if (count($this->criteria) > 0) {
			$on = ' ON ' . parent::toSql($db);
		}
		
		$joinTarget = $db->quoteName($this->table);
		
		if ($this->alias) {
			$joinTarget .= ' AS ' . $db->quoteName($this->alias);
		}
		
		return $this->type . ' ' . $joinTarget . $on;
	}
}
