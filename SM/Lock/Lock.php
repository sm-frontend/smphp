<?php
namespace SM\Lock;

class Lock
{
	public static function getInstance($driver = 'redis', $policy = [])
	{
		$driver = strtolower($driver);
		$class  = __NAMESPACE__ . '\Driver\\' . ucfirst($driver);
		
		if (class_exists($class, true)) {
			return \SM::getContainer()->singleton($class)->make($class, $policy);
		} else {
			throw new \Exception("Lock Driver [$driver] does not exist.");
		}
	}
}
