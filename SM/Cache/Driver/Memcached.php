<?php
namespace SM\Cache\Driver;

class Memcached extends \SM\Cache\CacheAbstract
{
	protected $_conn;
	
	protected $_default_server = [
		'host'   => '127.0.0.1',
		'port'   => '11211',
		'weight' => 100
	];
	
	protected $_default_policy = [
		'servers'    => [],
		'life_time'  => 900,
		'compressed' => true,
		'consistent' => false
	];
	
	public function __construct(array $policy = [])
	{
		if (!extension_loaded('memcached')) {
			throw new \Exception('Memcached extension is not loaded.');
		}
		
		$policy = array_merge($this->_default_policy, $policy);
		
		if (empty($policy['servers'])) {
			$policy['servers'][] = $this->_default_server;
		}
		
		$this->_default_policy = $policy;
		
		$this->_initMemcached();
	}
	
	protected function _initMemcached()
	{
		$conn = new \Memcached();
		$conn->setOption(\Memcached::OPT_COMPRESSION, (bool) $this->_default_policy['compressed']);
		
		if (isset($this->_default_policy['connect_timeout']) && $this->_default_policy['connect_timeout'] > 0) {
			$conn->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->_default_policy['connect_timeout']);
		}
		
		if ($this->_default_policy['consistent']) {
			$conn->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
			$conn->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
			$conn->setOption(\Memcached::OPT_REMOVE_FAILED_SERVERS, true);
		}
		
		$servers = [];
		while (list(, $server) = each($this->_default_policy['servers'])) {
			if (empty($server['weight'])) {
				$server['weight'] = 100;
			}
			
			$servers[] = [$server['host'], $server['port'], $server['weight']];
		}
		
		$conn->addServers($servers);
		$this->_conn = $conn;
	}
	
	protected function doSet($key, $value, $lifeTime = null)
	{
		$lifeTime = isset($lifeTime) ? (int) $lifeTime : $this->_default_policy['life_time'];
		
		if (!$lifeTime) {
			$expireTime = 0;
		} else {
			$expireTime = TIMENOW + $lifeTime;
		}
		return $this->_conn->set($key, $value, $expireTime);
	}
	
	protected function doGet($key)
	{
		return $this->_conn->get($key);
	}
	
	protected function doDelete($key)
	{
		return $this->_conn->delete($key);
	}
	
	protected function doSetMulti(array $items, $lifeTime = null)
	{
		$lifeTime = isset($lifeTime) ? (int) $lifeTime : $this->_default_policy['life_time'];
		
		if (!$lifeTime) {
			$expireTime = 0;
		} else {
			$expireTime = TIMENOW + $lifeTime;
		}
		return $this->_conn->setMulti($items, $expireTime);
	}
	
	protected function doGetMulti(array $keys)
	{
		return $this->_conn->getMulti($keys);
	}
	
	public function __call($name, $arguments)
	{
		if (!empty($arguments)) {
			$key = array_shift($arguments);
			$key = $this->getNamespacedKey($key);
			
			array_unshift($arguments, $key);
		}
		
		return call_user_func_array([$this->_conn, $name], $arguments);
	}
}
