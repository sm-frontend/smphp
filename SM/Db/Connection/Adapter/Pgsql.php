<?php
namespace SM\Db\Connection\Adapter;

use SM\Db\Connection\Connection;
use SM\Db\Connection\AdapterAbstract;

class Pgsql extends AdapterAbstract
{
	protected function doConnect($config)
	{
		$dsn = 'pgsql:dbname=' . $config['database_name'] . ';host=' . $config['server'];
		
		if (!empty($config['port'])) {
			$dsn .= ';port=' . $config['port'];
		}
		
		$conn = new Connection($dsn, $config['username'], $config['password'], $config['option']);
		
		if (!empty($config['charset'])) {
			$conn->exec("SET NAMES '{$config['charset']}'");
		}
		
		return $conn;
	}
}
