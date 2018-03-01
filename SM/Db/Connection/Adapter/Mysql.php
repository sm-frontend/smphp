<?php
namespace SM\Db\Connection\Adapter;

use SM\Db\Connection\Connection;
use SM\Db\Connection\AdapterAbstract;

class Mysql extends AdapterAbstract
{
	protected function doConnect($config)
	{
		$dsn = 'mysql:dbname=' . $config['database_name'];
		
		if (!empty($config['server'])) {
			$dsn .= ';host=' . $config['server'];
		}
		
		if (!empty($config['port'])) {
			$dsn .= ';port=' . $config['port'];
		}
		
		if (!empty($config['socket'])) {
			$dsn .= ';unix_socket=' . $config['socket'];
		}
		
		$conn = new Connection($dsn, $config['username'], $config['password'], $config['option']);
		
		if (!empty($config['charset'])) {
			$conn->exec("SET NAMES '{$config['charset']}'");
		}
		
		$conn->exec('SET SQL_MODE=ANSI_QUOTES');
		
		return $conn;
	}
}
