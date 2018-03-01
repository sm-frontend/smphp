<?php
namespace SM\Util\Lunar;

use SM\Util\File;

class Worktime
{
	private static $worktime = [];
	
	public static function getWorktime(&$data)
	{
		$year = date('Y', $data['timestamp']);
		
		if (!isset(static::$worktime[$year])) {
			$file = __DIR__ . '/Year/' . $year . '.php';
			
			if (File::isFile($file)) {
				static::$worktime[$year] = require $file;
			} else {
				static::$worktime[$year] = [];
			}
		}
		
		$md = date('md', $data['timestamp']);
		
		if (isset(static::$worktime[$year][$md])) {
			$data['worktime'] = static::$worktime[$year][$md];//1工作，2放假
		}
	}
}
