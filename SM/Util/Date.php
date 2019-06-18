<?php
namespace SM\Util;

class Date
{
	const DEFAULT_FORMAT  = 'Y-m-d H:i:s';
	
	private static $astro = '摩羯水瓶双鱼白羊金牛双子巨蟹狮子处女天秤天蝎射手摩羯';
	private static $week  = ['日', '一', '二', '三', '四', '五', '六'];
	private static $map   = [
		'y' => [31536000, '年'],
		'M' => [2592000, '个月'],
		'w' => [604800, '周'],
		'd' => [86400, '天'],
		'h' => [3600, '小时'],
		'm' => [60, '分钟'],
		's' => [1, '秒']
	];
	private static $timeago = '前';
	private static $timenow = '刚刚';
	
	public static function timeZone($timezone)
	{
		return function_exists('date_default_timezone_set') ? date_default_timezone_set($timezone) : putenv("TZ={$timezone}");
	}
	
	public static function getTimeStamp($dateTime = null)
	{
		return $dateTime ? (is_numeric($dateTime) ? $dateTime : strtotime($dateTime)) : TIMENOW;
	}
	
	public static function getMillisecond()
	{
		return sprintf('%.0f', microtime(true) * 1000);
	}
	
	public static function format($format = null, $dateTime = null)
	{
		return date($format ? $format : static::DEFAULT_FORMAT, static::getTimeStamp($dateTime));
	}
	
	public static function timeDiff($timestamp, $timeMax = 0, $format = 'Y-m-d H:i')
	{
		$time = TIMENOW - $timestamp;
		
		if ($timeMax > 0 && $time > $timeMax && !empty($format)) {
			return static::format($format, $timestamp);
		}
		
		foreach (static::$map as $k => $v) {
			if (0 != ($c = floor($time / $v[0]))) {
				return $c . $v[1] . static::$timeago;
			}
		}
		return static::$timenow;
	}
	
	public static function timeConvert($time, $scope = ['h', 'm'])
	{
		$d = '';
		foreach (static::$map as $k => $v) {
			if ((!$scope || in_array($k, $scope)) && 0 != ($c = floor($time / $v[0]))) {
				$time -= $c * $v[0];
				$d    .= $c . $v[1];
			}
		}
		return $d;
	}
	
	public static function timeVideo($time)
	{
		$h = intval($time / 3600);
		
		$m = intval(($time % 3600) / 60);
		$m = str_pad($m, 2, '0', STR_PAD_LEFT);
		
		$s = intval($time % 60);
		$s = str_pad($s, 2, '0', STR_PAD_LEFT);
		
		return ($h ?  $h . ':' : '') . $m . ':' . $s;
	}
	
	public static function getWeekday($w = null, $name = '周')
	{
		return $name . static::$week[$w !== null ? $w : date('w')];
	}
	
	public static function getAstro($month, $day)
	{
		$num = '102123444543';
		return mb_substr(static::$astro, $month * 2 - ($day < $num{$month - 1} + 19) * 2, 2);
	}
}
