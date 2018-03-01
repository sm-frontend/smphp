<?php
namespace SM\Security;

class Random
{
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
