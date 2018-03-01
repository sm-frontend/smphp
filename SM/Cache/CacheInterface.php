<?php
namespace SM\Cache;

interface CacheInterface
{
	public function set($key, $value, $life_time = null);
	public function get($key);
	public function remove($key);
	public function getMulti(array $keys);
	public function setMulti(array $items, $life_time = null);
}
