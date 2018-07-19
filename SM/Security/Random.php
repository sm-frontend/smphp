<?php
namespace SM\Security;

class Random
{
	public static function int($min = 1, $max = 100)
	{
		return mt_rand($min, $max);
	}
	
	public static function text($len)
	{
		if (false !== ($rand = static::getBytes(16))) {
			$hash = bin2hex($rand);
		} else {
			$hash = md5(microtime() . uniqid(mt_rand(), true));
		}
		
		return substr($hash, 0, $len);
	}
	
	public static function getBytes($length = 16)
	{
		if (function_exists('random_bytes')) {
			return random_bytes($length);
		}
		
		if (function_exists('mcrypt_create_iv') && !defined('PHALANGER')) {
			if (false !== ($output = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM))) {
				return $output;
			}
		}
		
		if (function_exists('openssl_random_pseudo_bytes')) {
			return openssl_random_pseudo_bytes($length);
		}
		
		if (is_readable('/dev/urandom') && false !== ($fp = fopen('/dev/urandom', 'rb'))) {
			$output = fread($fp, $length);
			fclose($fp);
			
			if (false !== $output) {
				return $output;
			}
		}
		return false;
	}
}
