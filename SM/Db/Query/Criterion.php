<?php
namespace SM\Db\Query;

class Criterion implements CriteronInterface
{
	protected $exp = ['=', '!=', '>', '>=', '<', '<=', '<>', '><', 'LIKE', 'NOT LIKE', 'REGEXP', 'NOT REGEXP', 'FIND_IN_SET', 'NOT FIND_IN_SET'];
	protected $left;
	protected $right;
	protected $operator;
	
	public function __construct($left, $right, $operator = '=')
	{
		$this->left     = $left instanceof ExpressionInterface ? $left : new Field($left);
		$this->right    = $right instanceof ExpressionInterface ? $right : new Value($right);
		
		$this->operator = strtoupper(trim($operator));
		
		if (!in_array($this->operator, $this->exp)) {
			throw new \Exception('Error operator: ' . $this->operator);
		}
	}
	
	public function toSql($db = null)
	{
		$bindName = $db->getBindname();
		
		$isNull   = method_exists($this->right, 'isNull') && $this->right->isNull();
		$isMany   = method_exists($this->right, 'isMany') && $this->right->isMany();
		
		if ($isNull) {
			if ($this->operator === '=') {
				return $this->left->toSql($db) . ' IS NULL';
			} elseif ($this->operator === '!=') {
				return $this->left->toSql($db) . ' IS NOT NULL';
			}
		} elseif ($isMany) {
			if ($this->operator === '=' || $this->operator === '!=') {
				if ($this->right instanceof Query) {
					$right = $this->right->toSql($db);
				} else {
					$item  = [];
					foreach ($this->right->getValue() as $k => $v) {
						$key    = $bindName . '_in_' . $k;
						$item[] = $key;
						
						$db->bind($key, $v);
					}
					$right = implode(', ', $item);
				}
				return $this->left->toSql($db) . ($this->operator === '!=' ? ' NOT' : '') . ' IN (' . $right . ')';
				
			} elseif ($this->operator === '<>' || $this->operator === '><') {
				$value = $this->right->getValue();
				
				$db->bind([
					$bindName . '_between_1' => $value[0],
					$bindName . '_between_2' => $value[1]
				]);
				
				return '(' . $this->left->toSql($db) . ($this->operator === '><' ? ' NOT' : '') . ' BETWEEN ' . $bindName . '_between_1 AND ' . $bindName . '_between_2)';
			}
		}
		
		if (!$this->right instanceof Value) {
			return $this->left->toSql($db) . ' ' . $this->operator . ' ' . $this->right->toSql($db);
		} else {
			$db->bind($bindName, $this->right->toSql($db));

			if (strpos($this->operator, 'FIND_IN_SET') !== false) {
				return $this->operator . '(' . $bindName . ', ' . $this->left->toSql($db) . ')';
			}
			
			return $this->left->toSql($db) . ' ' . $this->operator . ' ' . $bindName;
		}
	}
}
