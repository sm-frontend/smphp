<?php
namespace SM\Util;

class Profiler
{
	protected static $_marks    = [];
	protected static $_dpkeys   = [];
	protected static $_nowIndex = [];
	protected static $_allowDp  = false;

	public static function allowDuplicate(bool $bool)
	{
		static::$_allowDp = $bool;
	}

	public static function start($name, $start_time = null)
	{
		if (isset(static::$_marks[$name])) {
			if (static::$_allowDp) {
				static::$_dpkeys[$name]++;
				$name = $name . static::$_dpkeys[$name];
			} else {
				throw new \Exception('Profiler named "' . $name . '" is already running.');
			}
		}

		static::$_marks[$name] = [
			'start_time'   => !$start_time ? microtime(true) : $start_time,
			'start_memory' => static::memoryUsage(),
			'stop_time'    => false,
			'stop_memory'  => false,
		];
	}

	public static function stop($name)
	{
		$originName = $name;
		$name       = static::$_allowDp && isset(static::$_nowIndex[$originName]) ? ($name . static::$_nowIndex[$originName]) : $name;
		
		if (isset(static::$_marks[$name])) {
			static::$_marks[$name]['stop_time']   = microtime(true);
			static::$_marks[$name]['stop_memory'] = static::memoryUsage();
			if (static::$_allowDp) {
				static::$_nowIndex[$originName]++;
			}
		}
	}

	public static function stats()
	{
		$stats = [];

		foreach (static::$_marks as $name => $mark) {
			list($time, $memory) = static::total($name);
			$stats[$name]        = [
				'time'   => $time,
				'memory' => $memory,
			];
		}
		return $stats;
	}

	public static function total($name)
	{
		$mark = static::$_marks[$name];

		if ($mark['stop_time'] === false) {
			$mark['stop_time']   = microtime(true);
			$mark['stop_memory'] = static::memoryUsage();
		}

		return [
			$mark['stop_time'] - $mark['start_time'],
			$mark['stop_memory'] - $mark['start_memory'],
		];
	}

	protected static function memoryUsage()
	{
		return function_exists('memory_get_usage') ? memory_get_usage() : 0;
	}
}
