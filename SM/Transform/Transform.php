<?php
namespace SM\Transform;

class Transform
{
	private static function getInstance($driver)
	{
		$driver = strtolower($driver);
		$class  = __NAMESPACE__ . '\Driver\\' . ucfirst($driver);
		
		if (class_exists($class, true)) {
			return \SM::getContainer()->singleton($class)->make($class);
		} else {
			throw new \Exception("Transform Driver [$driver] does not exist.");
		}
	}
	
	public static function __callStatic($name, $params)
	{
		$name   = strtolower($name);
		$method = substr($name, -6);
		
		switch ($method) {
			case 'encode':
			case 'decode':
				$driver = substr($name, 0, strlen($name) - 6);
				return call_user_func_array([static::getInstance($driver), $method], $params);
			
			default:
				throw new \Exception("Call to undefined method {$method}");
		}
	}
}
