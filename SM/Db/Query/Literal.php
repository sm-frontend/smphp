<?php
namespace SM\Db\Query;

class Literal implements ExpressionInterface
{
	protected $sql;
	
	public function __construct($sql)
	{
		$this->sql = $sql;
	}
	
	public function isMany()
	{
		return is_array($this->sql);
	}
	
	public function toSql($db = null)
	{
		return is_array($this->sql) ? implode(', ', $this->sql) : $this->sql;
	}
}
