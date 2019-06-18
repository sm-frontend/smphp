<?php
namespace SM\Security;

use SM\Http\Input;
use SM\Cache\CacheInterface;

class RateLimiter
{
	const PREFIX = 'rt:';
	
	protected $redis;
	
	public function __construct(CacheInterface $redis)
	{
		$this->setRedis($redis);
	}
	
	public function setRedis(CacheInterface $redis)
	{
		$this->redis = $redis;
	}
	
	public function getRedis()
	{
		return $this->redis;
	}
	
	public function limitCall($key = null, $limit = 10, $expire = 1, $block = 0, $multi = true)
	{
		$key     = $this->getCurrentKey($key);
		$current = $this->redis->get($key);
		
		if ($current != null && $current >= $limit) {
			if ($block > 0) {
				$this->redis->expire($key, $block);
			}
			return false;
		} else {
			$ttl = $this->redis->ttl($key);
			if ($multi) {
				$this->redis->multi(\Redis::MULTI);
			}

			$this->redis->incr($key);
			
			if ($ttl < 0) {
				$this->redis->expire($key, $expire);
			}
			
			if ($multi) {
				$this->redis->exec();
			}
			return true;
		}
	}

	public function getCurrentKey($key)
	{
		is_null($key) && $key = Input::fetchAltIp();
		
		return static::PREFIX . $key;
	}
	
	public function getCurrentValue($key)
	{
		return $this->redis->get($this->getCurrentKey($key));
	}
}
