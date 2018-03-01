<?php
namespace SM\Db\Connection\Adapter;

use SM\Db\Connection\Connection;
use SM\Db\Connection\AdapterAbstract;

class Sqlite extends AdapterAbstract
{
	protected function doConnect($config)
	{
		$dsn  = 'sqlite:' . $config['database_name'];
		
		$conn = new Connection($dsn);
		
		return $conn;
	}
}
