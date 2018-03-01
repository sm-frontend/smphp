<?php
namespace SM\Log;

class Log
{
	const INFO    = 'info';
	const ALERT   = 'alert';
	const FATAL   = 'fatal';
	const DEBUG   = 'debug';
	const NOTICE  = 'notice';
	const WARNING = 'warning';
	
	public static $phpErrorToLevel = [
		E_WARNING    => self::WARNING,
		E_NOTICE     => self::NOTICE,
		E_STRICT     => self::WARNING,
		E_DEPRECATED => self::WARNING
	];
	
	protected static $_log     = [];
	protected static $_traceid = null;
	
	private static function getInstance($driver, $policy)
	{
		$driver = strtolower($driver);
		$class  = __NAMESPACE__ . '\Driver\\' . ucfirst($driver);
		
		if (class_exists($class, true)) {
			return \SM::getContainer()->singleton($class)->make($class, $policy);
		} else {
			throw new \Exception("Log Driver [$driver] does not exist.");
		}
	}
	
	public static function setTraceId($traceid)
	{
		if (!empty($traceid) && is_null(static::$_traceid)) {
			static::$_traceid = $traceid;
		}
	}
	
	public static function record($msg, $level)
	{
		$msg = static::formatMsg($msg);
		
		if (is_null(static::$_traceid)) {
			static::$_traceid = \SM\Util\Str::random();
		}
		
		static::$_log[] = date('[Y-m-d H:i:s]') . ' ' . $level . ': ' . $msg . ' [' . static::$_traceid . ']';
	}
	
	public static function save($driver = 'file', $policy = [])
	{
		if (empty(static::$_log)) {
			return;
		}
		
		$msg = implode(PHP_EOL, static::$_log);
		
		static::getInstance($driver, $policy)->write($msg);
		static::clear();
	}
	
	public static function write($msg, $driver = 'file', $policy = [])
	{
		$msg = static::formatMsg($msg, true);
		
		static::getInstance($driver, $policy)->write($msg);
	}
	
	public static function clear()
	{
		static::$_log = [];
	}
	
	public static function getLog()
	{
		return static::$_log;
	}
	
	protected static function formatMsg($msg, $lineBreak = false)
	{
		if (!is_string($msg)) {
			$msg = var_export($msg, true);
		}
		
		$msg = trim($msg);
		return $lineBreak ? $msg : str_replace(["\r\n", "\r", "\n"], ' ', $msg);
	}
	
	public static function debug($msg)
	{
		static::record($msg, static::DEBUG);
	}
	
	public static function fatal($msg)
	{
		static::record($msg, static::FATAL);
	}
	
	public static function notice($msg)
	{
		static::record($msg, static::NOTICE);
	}
	
	public static function warning($msg)
	{
		static::record($msg, static::WARNING);
	}
	
	public static function alert($msg)
	{
		static::record($msg, static::ALERT);
	}
	
	public static function info($msg)
	{
		static::record($msg, static::INFO);
	}
}
