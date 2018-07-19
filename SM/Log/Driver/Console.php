<?php
namespace SM\Log\Driver;

class Console extends \SM\Log\LogAbstract
{
	public function write($log)
	{
		echo $log . PHP_EOL;
	}
}
