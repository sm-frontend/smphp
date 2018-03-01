<?php
namespace SM\Util;

class Google2FA
{
	const KEY_REGENERATION     = 30;// 动态密码更新时间30秒
	
	private static $codeLength = 6;// 动态密码长度
	private static $lut = [
		'A' => 0,  'B' => 1,
		'C' => 2,  'D' => 3,
		'E' => 4,  'F' => 5,
		'G' => 6,  'H' => 7,
		'I' => 8,  'J' => 9,
		'K' => 10, 'L' => 11,
		'M' => 12, 'N' => 13,
		'O' => 14, 'P' => 15,
		'Q' => 16, 'R' => 17,
		'S' => 18, 'T' => 19,
		'U' => 20, 'V' => 21,
		'W' => 22, 'X' => 23,
		'Y' => 24, 'Z' => 25,
		'2' => 26, '3' => 27,
		'4' => 28, '5' => 29,
		'6' => 30, '7' => 31
	];
	
	public static function setCodeLength($length)
	{
		static::$codeLength = (int) $length;
	}
	
	public static function generateSecretKey($length = 16)
	{
		$b32 = '234567QWERTYUIOPASDFGHJKLZXCVBNM';
		
		$s   = '';
		for ($i = 0; $i < $length; $i++) {
			$s .= $b32[mt_rand(0, 31)];
		}
		return $s;
	}
	
	public static function getTimestamp()
	{
		return floor(microtime(true) / static::KEY_REGENERATION);
	}
	
	public static function base32Decode($b32)
	{
		$b32 = strtoupper($b32);
		
		if (!preg_match('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+$/', $b32)) {
			throw new \Exception('Invalid characters in the base32 string.');
		}
		
		$l      = strlen($b32);
		$n      = 0;
		$j      = 0;
		$binary = '';
		
		for ($i = 0; $i < $l; $i++) {
			$n = $n << 5;
			$n = $n + static::$lut[$b32[$i]];
			$j = $j + 5;
			
			if ($j >= 8) {
				$j       = $j - 8;
				$binary .= chr(($n & (0xFF << $j)) >> $j);
			}
		}
		return $binary;
	}
	
	public static function oathHotp($key, $counter)
	{
		if (strlen($key) < 8) {
			throw new \Exception('Secret key is too short. Must be at least 16 base 32 characters');
		}
		
		$binCounter = pack('N*', 0) . pack('N*', $counter);
		$hash       = hash_hmac('sha1', $binCounter, $key, true);
		
		return str_pad(static::oathTruncate($hash), static::$codeLength, '0', STR_PAD_LEFT);
	}
	
	public static function verifyKey($b32seed, $key, $window = 4, $useTimeStamp = true)
	{
		$timeStamp = static::getTimestamp();
		
		if ($useTimeStamp !== true) {
			$timeStamp = (int) $useTimeStamp;
		}
		
		$binarySeed = static::base32Decode($b32seed);
		
		for ($ts = $timeStamp - $window; $ts <= $timeStamp + $window; $ts ++) {
			if (static::oathHotp($binarySeed, $ts) == $key) {
				return true;
			}
		}
		return false;
	}
	
	public static function oathTruncate($hash)
	{
		$offset = ord($hash[19]) & 0xf;
		
		return (((ord($hash[$offset + 0]) & 0x7f) << 24) |
			 ((ord($hash[$offset + 1]) & 0xff) << 16) |
			  ((ord($hash[$offset + 2]) & 0xff) << 8) |
			   (ord($hash[$offset + 3]) & 0xff)) % pow(10, static::$codeLength);
	}
}
