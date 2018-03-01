<?php
namespace SM\Image;

class Image
{
	private static $driver = 'gd';
	
	public static function load($data)
	{
		return static::createDriver()->init($data);
	}
	
	public static function setDriver($driver)
	{
		static::$driver = $driver;
	}
	
	private static function createDriver()
	{
		$driver = strtolower(static::$driver);
		$class  = __NAMESPACE__ . '\Driver\\' . ucfirst($driver);
		
		if (class_exists($class, true)) {
			return new $class();
		} else {
			throw new \Exception("Image Driver [$driver] does not exist.");
		}
	}
}
