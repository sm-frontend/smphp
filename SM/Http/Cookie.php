<?php
namespace SM\Http;

use SM\Util\Str;
use SM\Security\Encrypt;

class Cookie
{
	public static function set($name, $value, $encode = false, $expire = null, $path = null, $domain = null, $secure = false, $httponly = false)
	{
		if ($encode && $value) {
			$value = static::getEncrypt()->encode(serialize($value));
		}
		
		if ($expire !== null && $expire != 0) {
			$expire += TIMENOW;
		}
		
		$path   = !empty($path) ? $path : '/';
		$secure = (Input::isSSL() && $secure) ? true : false;
		
		if (!headers_sent()) {
			$_COOKIE[$name] = $value;
			return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
		} else {
			return false;
		}
	}
	
	public static function get($name = '', $decode = false, $default = null)
	{
		if (static::has($name)) {
			$value = $_COOKIE[$name];
			
			if ($value && $decode) {
				$value = unserialize(static::getEncrypt()->decode($value));
			}
			
			return is_string($value) ? Str::htmlEscape($value) : $value;
			
		} elseif ('' === $name) {
			return $_COOKIE;
		}
		return $default;
	}
	
	public static function delete($name, $path = null, $domain = null)
	{
		if (static::has($name)) {
			static::set($name, '', false, -86400, $path, $domain);
			unset($_COOKIE[$name]);
		}
	}
	
	public static function has($name)
	{
		return isset($_COOKIE[$name]);
	}
	
	protected static function getEncrypt()
	{
		return new Encrypt('__CoOkIe__');
	}
}
