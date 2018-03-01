<?php
namespace SM\Db\Query;

class Query extends Criteria implements ExpressionInterface
{
	protected $table;
	protected $alias;
	protected $columns  = [];
	protected $joins    = [];
	protected $unions   = [];
	protected $order    = [];
	protected $groupby  = [];
	protected $limit    = null;
	protected $offset   = null;
	protected $having   = null;
	protected $distinct = false;
	
	public function __construct($table, $alias = null)
	{
		parent::__construct('AND');
		
		$this->table = $table;
		$this->alias = $alias;
	}
	
	public function addJoin($mixed, $type = 'JOIN', $alias = null)
	{
		if ($mixed instanceof Join) {
			return $this->addJoinObject($mixed);
		} else {
			return $this->addJoinObject(new Join($mixed, $type, $alias));
		}
	}
	
	public function addJoins(array $joins)
	{
		foreach ($joins as $join) {
			if ($join instanceof Join) {
				$this->addJoin($join);
			}
		}
	}
	
	protected function addJoinObject(Join $join)
	{
		$this->joins[] = $join;
		return $join;
	}
	
	public function addUnion($mixed, $alias = null, $type = 'DISTINCT')
	{
		$union          = $mixed instanceof Query ? $mixed : new Query($mixed, $alias);
		$this->unions[] = [$union, $type];
		
		return $union;
	}
	
	public function addUnionDistinct($mixed, $alias = null)
	{
		return $this->addUnion($mixed, $alias, 'DISTINCT');
	}
	
	public function addUnionAll($mixed, $alias = null)
	{
		return $this->addUnion($mixed, $alias, 'ALL');
	}
	
	public function addGroupBy($column)
	{
		$groupby         = $column instanceof ExpressionInterface ? $column : new Field($column);
		$this->groupby[] = $groupby;
		
		return $groupby;
	}
	
	public function addColumn($column, $alias = null)
	{
		$this->columns[] = $column instanceof ExpressionInterface ? $column : new Field($column, $alias);
	}
	
	public function addColumns(array $columns)
	{
		foreach ($columns as $column) {
			if ($column instanceof ExpressionInterface) {
				$this->addColumn($column);
			}
		}
	}
	
	public function setHaving($left, $right = null, $operator = '=')
	{
		return $this->having = $left instanceof CriteronInterface ? $left : new Criterion($left, $right, $operator);
	}
	
	public function setOrder($order, $direction = null)
	{
		if ($order != '') {
			$this->addOrder($order, $direction);
		}
	}
	
	public function addOrder($order, $direction = null)
	{
		$this->order[] = [$order instanceof ExpressionInterface ? $order : new Field($order), in_array(strtoupper($direction), ['ASC', 'DESC']) ? $direction : null];
	}
	
	public function setLimit($limit)
	{
		$this->limit = (int) $limit;
	}
	
	public function setOffset($offset)
	{
		$this->offset = (int) $offset;
	}
	
	public function setDistinct($distinct)
	{
		$this->distinct = (bool) $distinct;
	}
	
	public function whereClause($db)
	{
		$sql = '';
		
		if (count($this->criteria) > 0) {
			$sql .= ' WHERE ' . parent::toSql($db);
		}
		
		if (count($this->groupby) > 0) {
			$group = [];
			foreach ($this->groupby as $groupby) {
				$group[] = $groupby->toSql($db);
			}
			$sql .= ' GROUP BY ' . implode(', ', $group);
			
			if ($this->having) {
				$sql .= ' HAVING ' . $this->having->toSql($db);
			}
		}
		
		if (count($this->order) > 0) {
			$order = [];
			foreach ($this->order as $column) {
				$order[] = $column[0]->toSql($db) . ($column[1] ? ' ' . $column[1] : '');
			}
			$sql .= ' ORDER BY ' . implode(', ', $order);
		}
		
		if ($this->limit) {
			$sql .= ' LIMIT ' . $this->limit;
		}
		
		if ($this->offset) {
			$sql .= ' OFFSET ' . $this->offset;
		}
		
		return $sql;
	}
	
	public function toSql($db = null)
	{
		$sql = 'SELECT ' . ($this->distinct ? 'DISTINCT ' : '');
		
		if (count($this->columns) === 0) {
			$columns = '*';
		} else {
			$columns = [];
			foreach ($this->columns as $column) {
				$columns[] = $column->toSql($db);
			}
			$columns = implode(', ', $columns);
		}
		
		$alias = $this->alias;
		
		if ($this->table instanceof Query) {
			$sql .= sprintf('%s FROM (%s)', $columns, $this->table->toSql($db));
			if (!$alias) {
				$alias = 'from_sub';
			}
		} else {
			$sql .= sprintf('%s FROM %s', $columns, $db->quoteName($this->table));
		}
		
		if ($alias) {
			$sql .= ' AS ' . $db->quoteName($alias);
		}
		
		foreach ($this->joins as $join) {
			$sql .= ' ' . $join->toSql($db);
		}
		
		$sql .= $this->whereClause($db);
		
		foreach ($this->unions as $union) {
			$sql .= ' UNION' . ($union[1] === 'DISTINCT' ? '' : ' ' . $union[1]) . ' ' . $union[0]->toSql($db);
		}
		
		return $sql;
	}
	
	public function isMany()
	{
		return true;
	}
}
