<?php
namespace SM\Security;

class Sign
{
	const SALT = '_-_SiGn_-_';
	
	public static function create(array $params, $salt = null)
	{
		if ($salt === null) {
			$salt = static::SALT;
		}
		
		unset($params['sign']);
		ksort($params);
		
		$sign = '';
		foreach ($params as $key => $val) {
			if (is_array($val)) {
				$sign .= static::create($val, $salt);
			} elseif ($key != '' && $val != '') {
				$sign .= $key . $val;
			}
		}
		
		return strtoupper(md5($salt . $sign . $salt));
	}
	
	public static function valid(array $params, $salt = null)
	{
		if ($salt === null) {
			$salt = static::SALT;
		}
		
		if (isset($params['sign'])) {
			$sign = $params['sign'];
			
			if ($sign === static::create($params, $salt)) {
				return true;
			}
		}
		return false;
	}
}
