<?php
namespace SM\Lock\Driver;

use SM\Util\Str;
use SM\Cache\Cache;
use SM\Lock\LockAbstract;

class Redis extends LockAbstract
{
	protected $redis;
	
	public function __construct($policy)
	{
		$this->redis = Cache::getInstance('redis', $policy, 'lock');
	}
	
	public function lock($key, $wouldblock = false)
	{
		if (empty($key)) {
			return false;
		}
		
		if (isset($this->lockCache[$key]) && $this->lockCache[$key] == $this->redis->get($key)) {
			return true;
		}
		
		$random = Str::random();
		
		if ($this->redis->set($key, $random, ['nx', 'ex' => $this->expire])) {
			$this->lockCache[$key] = $random;
			return true;
		}
		
		if (!$wouldblock) {
			return false;
		}
		
		do {
			usleep(200);
		} while (!$this->redis->set($key, $random, ['nx', 'ex' => $this->expire]));
		
		$this->lockCache[$key] = $random;
		return true;
	}
	
	public function unlock($key)
	{
		if (isset($this->lockCache[$key])) {
			$this->unlockRedis($key);
		}
	}
	
	private function unlockRedis($key)
	{
		if ($this->lockCache[$key] == $this->redis->get($key)) {
			$this->redis->delete($key);
		}
		
		$this->lockCache[$key] = null;
		unset($this->lockCache[$key]);
	}
	
	public function __destruct()
	{
		foreach ($this->lockCache as $key => $isLock) {
			$this->unlockRedis($key);
		}
	}
}
