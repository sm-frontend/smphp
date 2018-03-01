<?php
namespace SM\Lock;

abstract class LockAbstract
{
	protected $expire    = 60;
	protected $lockCache = [];
	
	public function setExpire($expire = 60)
	{
		$this->expire = $expire;
		return $this;
	}
	
	abstract public function lock($key, $wouldblock = false);
	abstract public function unlock($key);
}
