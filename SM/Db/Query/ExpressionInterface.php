<?php
namespace SM\Db\Query;

interface ExpressionInterface
{
	public function toSql($db = null);
}
