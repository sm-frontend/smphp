<?php
namespace SM\Transform\Driver;

class Base64
{
	public function encode($data, $target = 'default')
	{
		$data = base64_encode($data);
		
		if ($target == 'url') {
			return strtr(rtrim($data, '='), '+/', '-_');
		}
		
		return $data;
	}
	
	public function decode($data, $target = 'default')
	{
		if ($target == 'url') {
			$data = strtr($data, '-_', '+/');
		}
		
		return base64_decode($data);
	}
}
