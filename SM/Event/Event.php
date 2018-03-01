<?php
namespace SM\Event;

class Event
{
	private static $emitter = null;
	
	private static function getEmitter()
	{
		if (null === static::$emitter) {
			static::$emitter = new EventEmitter();
		}
		return static::$emitter;
	}
	
	public static function __callStatic($name, $params)
	{
		return call_user_func_array([static::getEmitter(), $name], $params);
	}
}
