<?php
namespace SM\Db\Query;

class Value implements ExpressionInterface
{
	protected $value;
	
	public function __construct($value)
	{
		$this->value = $value;
	}
	
	public function isNull()
	{
		return is_null($this->value);
	}
	
	public function isMany()
	{
		return is_array($this->value);
	}
	
	public function getValue()
	{
		return $this->parseValue($this->value);
	}
	
	protected function parseValue($value)
	{
		if (is_array($value)) {
			$value = array_map([$this, 'parseValue'], $value);
		} elseif (is_null($value)) {
			$value = 'NULL';
		} elseif (is_bool($value)) {
			$value = $value ? '1' : '0';
		}
		return $value;
	}
	
	public function toSql($db = null)
	{
		$value = $this->getValue();
		
		return is_array($value) ? implode(', ', $value) : $value;
	}
}
