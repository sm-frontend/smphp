<?php
namespace SM\Security;

class Password
{
	public static function hash($str, $algo = PASSWORD_DEFAULT)
	{
		return password_hash($str, $algo);
	}
	
	public static function verifyHash($str, $hash)
	{
		return password_verify($str, $hash);
	}
}
