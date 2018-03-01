<?php
namespace SM\Transform\Driver;

class Json
{
	public function encode($data)
	{
		$opt = empty($data) ? JSON_FORCE_OBJECT : JSON_UNESCAPED_UNICODE;
		return json_encode($data, $opt);
	}
	
	public function decode($data, $assoc = true)
	{
		return json_decode($data, $assoc);
	}
}
