<?php
namespace SM\Cache\Driver;

class Redis extends \SM\Cache\CacheAbstract
{
	protected $_hash;
	protected $_connectPool = [];
	protected $_serverNode  = [];
	
	protected $_default_server = [
		'host' => '127.0.0.1',
		'port' => '6379'
	];
	
	protected $_default_policy = [
		'servers'   => [],
		'timeout'   => 0,
		'password'  => '',
		'life_time' => 900
	];
	
	protected $_multi     = [];
	protected $_select    = [];
	protected $_dbIndex   = null;
	protected $_multiMode = null;
	
	public function __construct(array $policy = [])
	{
		if (!extension_loaded('redis')) {
			throw new \Exception('Redis extension is not loaded.');
		}
		
		$policy = array_merge($this->_default_policy, $policy);
		
		if (empty($policy['servers'])) {
			$policy['servers'][] = $this->_default_server;
		}
		
		$this->_default_policy = $policy;
		
		$this->_initRedis();
	}
	
	protected function _initRedis()
	{
		$this->_hash = new \SM\Util\Flexihash();
		
		while (list(, $server) = each($this->_default_policy['servers'])) {
			if (isset($server['alias']) && $server['alias'] != '') {
				$node = $server['alias'];
			} else {
				$node = $server['host'] . ':' . $server['port'];
			}
			
			$this->_hash->addTarget($node);
			
			if (!isset($this->_serverNode[$node])) {
				$this->_serverNode[$node] = $server;
			} else {
				throw new \Exception('The node "' . $node . '" has existed.');
			}
		}
	}
	
	protected function _getConn($key)
	{
		$node = $this->_hash->lookup($key);
		
		if (!isset($this->_connectPool[$node])) {
			$server = $this->_serverNode[$node];
			
			$conn   = new \Redis();
			$bool   = $conn->connect($server['host'], $server['port'], $this->_default_policy['timeout']);
			
			if (!$bool) {
				throw new \Exception(sprintf('Connect redis server [%s:%s] failed!', $server['host'], $server['port']));
			}
			
			if (isset($server['password']) && $server['password'] != '') {
				$conn->auth($server['password']);
			} elseif ($this->_default_policy['password'] != '') {
				$conn->auth($this->_default_policy['password']);
			}
			
			$this->_connectPool[$node] = $conn;
		}
		
		if (null !== $this->_dbIndex && (!isset($this->_select[$node]) || $this->_select[$node] != $this->_dbIndex)) {
			
			$this->_select[$node] = $this->_dbIndex;
			$this->_connectPool[$node]->select($this->_dbIndex);
		}
		
		if (null !== $this->_multiMode && !isset($this->_multi[$node])) {
			$this->_multi[$node] = 1;
			$this->_connectPool[$node]->multi($this->_multiMode);
		}
		
		return $this->_connectPool[$node];
	}
	
	protected function doSet($key, $value, $lifeTime = null)
	{
		$value = $this->encode($value);
		
		if (isset($lifeTime) && is_array($lifeTime)) {
			return $this->_getConn($key)->set($key, $value, $lifeTime);
		}
		
		$lifeTime = isset($lifeTime) ? (int) $lifeTime : $this->_default_policy['life_time'];
		
		if ($lifeTime > 0) {
			return $this->_getConn($key)->setex($key, $lifeTime, $value);
		} else {
			return $this->_getConn($key)->set($key, $value);
		}
	}
	
	protected function doGet($key)
	{
		$value = $this->_getConn($key)->get($key);
		return $this->decode($value);
	}
	
	protected function doDelete($key)
	{
		return $this->_getConn($key)->delete($key);
	}
	
	protected function doSetMulti(array $items, $lifeTime = null)
	{
		$lifeTime = isset($lifeTime) ? (int) $lifeTime : $this->_default_policy['life_time'];
		
		while (list($key, $value) = each($items)) {
			$this->doSet($key, $value, $lifeTime);
		}
	}
	
	public function __call($name, $arguments)
	{
		if (!empty($arguments)) {
			$key = array_shift($arguments);
			$key = $this->getNamespacedKey($key);
			
			array_unshift($arguments, $key);
		}
		
		return call_user_func_array([$this->_getConn($key), $name], $arguments);
	}
	
	public function select($dbIndex)
	{
		$this->_dbIndex = intval($dbIndex);
	}
	
	public function multi($mode = null)
	{
		$this->_multiMode = $mode;
	}
	
	public function exec()
	{
		foreach ($this->_connectPool as $node => $conn) {
			$conn->exec();
		}
		$this->multi();
	}
	
	public function pipeline($func, array $params, $lifeTime = null)
	{
		$this->multi(\Redis::PIPELINE);
		
		foreach ($params as $param) {
			call_user_func_array([$this, $func], $param);
			
			if (null !== $lifeTime) {
				$this->expire(array_shift($param), (int) $lifeTime);
			}
		}
		$this->exec();
	}
}
