<?php
namespace SM\Db\Query;

class Criteria implements CriteronInterface
{
	protected $conjunction;
	protected $criteria = [];
	
	public function __construct($conjunction = 'OR')
	{
		$this->conjunction = strtoupper($conjunction);
	}
	
	public function addCriterion($left, $right = null, $operator = '=')
	{
		return $this->addCriterionObject($left instanceof CriteronInterface ? $left : new Criterion($left, $right, $operator));
	}
	
	public function addConstraint($left, $right, $operator = '=')
	{
		return $this->addCriterionObject(new Criterion(new Field($left), new Field($right), $operator));
	}
	
	protected function addCriterionObject(CriteronInterface $criterion)
	{
		$this->criteria[] = $criterion;
		return $criterion;
	}
	
	public function _and()
	{
		$this->conjunction = 'AND';
		return $this;
	}
	
	public function _or()
	{
		$this->conjunction = 'OR';
		return $this;
	}

	public function where($column, $operator = '=', $value = null)
	{
		if (is_array($column)) {
			foreach ($column as $key => $value) {
				if (is_numeric($key) && is_array($value)) {
					call_user_func_array([$this, 'where'], array_values($value));
				} else {
					$this->where($key, '=', $value);
				}
			}
		} else {
			if (func_num_args() == 2) {
				$value    = $operator;
				$operator = '=';
			}
			$this->addCriterion($column, $value, $operator);
		}
		return $this;
	}
	
	public function whereColumn($column, $operator = '=', $value = null)
	{
		$this->addConstraint($column, $value, $operator);
		return $this;
	}
	
	public function whereNull($column)
	{
		return $this->where($column, '=', null);
	}
	
	public function whereNotNull($column)
	{
		return $this->where($column, '!=', null);
	}
	
	public function whereNot($column, $value)
	{
		return $this->where($column, '!=', $value);
	}
	
	public function whereGt($column, $value)
	{
		return $this->where($column, '>', $value);
	}
	
	public function whereLt($column, $value)
	{
		return $this->where($column, '<', $value);
	}
	
	public function whereGte($column, $value)
	{
		return $this->where($column, '>=', $value);
	}
	
	public function whereLte($column, $value)
	{
		return $this->where($column, '<=', $value);
	}
	
	public function whereIn($column, $value, $db = null)
	{
		return $this->whereInSub($column, '=', $value, $db);
	}
	
	public function whereNotIn($column, $value, $db = null)
	{
		return $this->whereInSub($column, '!=', $value, $db);
	}
	
	protected function whereInSub($column, $operator, $value, $db)
	{
		if ($value instanceof \Closure) {
			$clone = clone $db;
			call_user_func($value, $clone);
			
			$this->where($column, $operator, $clone->getQuery());
		} else {
			$this->where($column, $operator, $value);
		}
		return $this;
	}
	
	public function whereLike($column, $value, $and_or = 'OR')
	{
		if (is_array($value)) {
			return $this->whereNested(function ($q) use ($column, $value) {
				foreach ($value as $v) {
					$q->whereLike($column, $v);
				}
			}, $and_or);
		}
		
		return $this->where($column, 'LIKE', $value);
	}
	
	public function whereNotLike($column, $value, $and_or = 'OR')
	{
		if (is_array($value)) {
			return $this->whereNested(function ($q) use ($column, $value) {
				foreach ($value as $v) {
					$q->whereNotLike($column, $v);
				}
			}, $and_or);
		}
		
		return $this->where($column, 'NOT LIKE', $value);
	}
	
	public function whereBetween($column, $min, $max)
	{
		return $this->where($column, '<>', [$min, $max]);
	}
	
	public function whereNotBetween($column, $min, $max)
	{
		return $this->where($column, '><', [$min, $max]);
	}
	
	public function whereRegExp($column, $value)
	{
		return $this->where($column, 'REGEXP', $value);
	}
	
	public function whereNotRegExp($column, $value)
	{
		return $this->where($column, 'NOT REGEXP', $value);
	}

	public function whereFindInSet($column, $value)
	{
		return $this->where($column, 'FIND_IN_SET', $value);
	}

	public function whereNotFindInSet($column, $value)
	{
		return $this->where($column, 'NOT FIND_IN_SET', $value);
	}
	
	public function whereNested(\Closure $callback, $and_or = 'OR')
	{
		$sub = new self($and_or);
		call_user_func($callback, $sub);
		
		$this->addCriterion($sub);
		return $this;
	}
	
	public function toSql($db = null)
	{
		if (count($this->criteria) === 0) {
			return '';
		}
		
		$criteria = [];
		foreach ($this->criteria as $criterion) {
			$isMany = method_exists($criterion, 'isMany') && $criterion->isMany();
			
			if ($isMany) {
				$criteria[] = '(' . $criterion->toSql($db) . ')';
			} else {
				$criteria[] = $criterion->toSql($db);
			}
		}
		return implode(' ' . $this->conjunction . ' ', $criteria);
	}
	
	public function isMany()
	{
		return count($this->criteria) > 1;
	}
}
