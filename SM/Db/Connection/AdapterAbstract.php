<?php
namespace SM\Db\Connection;

abstract class AdapterAbstract
{
	public function connect($config)
	{
		if (!isset($config['option'])) {
			$config['option'] = [];
		}
		
		return $this->doConnect($config);
	}
	
	abstract protected function doConnect($config);
}
