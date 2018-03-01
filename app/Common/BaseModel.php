<?php
namespace App\Common;

class BaseModel
{
	protected $db = null;

	public function __construct()
	{
		$config   = \SM::getContainer()->make('config');
		$options  = $config->get('db');
		$this->db = \SM::getDb($options);
	}
}
