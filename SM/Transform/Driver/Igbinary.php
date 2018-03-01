<?php
namespace SM\Transform\Driver;

class Igbinary
{
	public function __construct()
	{
		if (!extension_loaded('igbinary')) {
			throw new \Exception('PHP extension "igbinary" is required for this method');
		}
	}
	
	public function encode($data)
	{
		return igbinary_serialize($data);
	}
	
	public function decode($data)
	{
		return igbinary_unserialize($data);
	}
}
