<?php
namespace SM\Transform\Driver;

class Ser
{
	public function encode($data)
	{
		return serialize($data);
	}
	
	public function decode($data)
	{
		return unserialize($data);
	}
}
