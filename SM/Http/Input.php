<?php
namespace SM\Http;

use SM\Util\Ip;
use SM\Util\Str;

class Input
{
	public static function request($key = null, $default = null, $xssClean = true)
	{
		global $_REQUEST;
		
		$val = static::gpcs('_REQUEST', $key, $default);
		return $xssClean ? static::xssClean($val) : $val;
	}
	
	public static function get($key = null, $default = null, $xssClean = true)
	{
		$val = static::gpcs('_GET', $key, $default);
		return $xssClean ? static::xssClean($val) : $val;
	}
	
	public static function post($key = null, $default = null, $xssClean = true)
	{
		$val = static::gpcs('_POST', $key, $default);
		return $xssClean ? static::xssClean($val) : $val;
	}
	
	public static function server($key = null, $default = null)
	{
		$key = !is_null($key) ? strtoupper($key) : null;
		return static::gpcs('_SERVER', $key, $default);
	}
	
	private static function gpcs($range, $key, $default)
	{
		global $$range;
		
		if ($key === null) {
			return $$range;
		} else {
			$range = $$range;
			return isset($range[$key]) ? $range[$key] : ($default !== null ? $default : null);
		}
	}
	
	public static function fetchLocalIp()
	{
		return Ip::fetchLocalIp();
	}
	
	public static function fetchIp()
	{
		return Ip::fetchIp();
	}
	
	public static function fetchAltIp()
	{
		return Ip::fetchAltIp();
	}
	
	public static function fetchHost($useForwardedHost = false)
	{
		$host = null;
		
		if ($useForwardedHost && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
		} elseif (isset($_SERVER['HTTP_HOST'])) {
			$host = $_SERVER['HTTP_HOST'];
		}
		
		return $host;
	}
	
	public static function xssClean($data)
	{
		if (is_array($data)) {
			return array_map([__CLASS__, 'xssClean'], $data);
		}
		
		if (!$data) {
			return $data;
		}
		
		if (is_string($data)) {
			if (!Str::isUTF8($data)) {
				$data = Str::convert($data);
			}
			return trim(Str::htmlEscape($data));
		} else {
			return $data;
		}
	}
	
	public static function isCli()
	{
		return PHP_SAPI === 'cli';
	}
	
	public static function isAjax()
	{
		return strtolower(static::server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
	}
	
	public static function isGET()
	{
		return static::server('REQUEST_METHOD') == 'GET' ? true : false;
	}
	
	public static function isPOST()
	{
		return static::server('REQUEST_METHOD') == 'POST' ? true : false;
	}
	
	public static function isSSL()
	{
		if (!isset($_SERVER['HTTPS'])) {
			return false;
		}
		
		$https = static::server('HTTPS');
		
		if ($https === 1 || $https === 'on' || static::server('SERVER_PORT') == 443) {
			return true;
		} else {
			return false;
		}
	}
}
