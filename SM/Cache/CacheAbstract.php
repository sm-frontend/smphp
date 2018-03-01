<?php
namespace SM\Cache;

abstract class CacheAbstract implements CacheInterface
{
	const NAMESPACE_CACHEKEY = 'NamespaceCacheKey[%s]';
	
	private $namespace = '';
	private $namespaceVersion;
	
	public function setNamespace($namespace)
	{
		$this->namespace        = (string) $namespace;
		$this->namespaceVersion = null;
		
		$this->getNamespaceVersion();
	}
	
	public function getNamespace()
	{
		return $this->namespace;
	}
	
	protected function getNamespacedKey($key)
	{
		if ($this->namespace != '') {
			$namespaceVersion = $this->getNamespaceVersion();
			$namespacedKey    = sprintf('%s[%s][%s]', $this->namespace, $key, $namespaceVersion);
			
			return md5($namespacedKey);
		} else {
			return $key;
		}
	}
	
	private function getNamespaceCacheKey()
	{
		return sprintf(static::NAMESPACE_CACHEKEY, $this->namespace);
	}
	
	private function getNamespaceVersion()
	{
		if (null !== $this->namespaceVersion) {
			return $this->namespaceVersion;
		}
		
		$namespaceCacheKey      = $this->getNamespaceCacheKey();
		$this->namespaceVersion = (int) $this->doGet($namespaceCacheKey) ?: 1;
		
		return $this->namespaceVersion;
	}
	
	public function deleteAll()
	{
		$namespaceCacheKey = $this->getNamespaceCacheKey();
		$namespaceVersion  = $this->getNamespaceVersion() + 1;
		
		if ($this->doSet($namespaceCacheKey, $namespaceVersion, 0)) {
			$this->namespaceVersion = $namespaceVersion;
			return true;
		}
		return false;
	}
	
	public function set($key, $value, $lifeTime = null)
	{
		return $this->doSet($this->getNamespacedKey($key), $value, $lifeTime);
	}
	
	public function get($key)
	{
		return $this->doGet($this->getNamespacedKey($key));
	}
	
	public function remove($key)
	{
		return $this->doDelete($this->getNamespacedKey($key));
	}
	
	public function setMulti(array $items, $lifeTime = null)
	{
		$namespacedItems = [];
		foreach ($items as $k => $v) {
			$namespacedItems[$this->getNamespacedKey($k)] = $v;
		}
		
		return $this->doSetMulti($namespacedItems, $lifeTime);
	}
	
	public function getMulti(array $keys)
	{
		if (empty($keys)) {
			return [];
		}
		
		$namespacedKeys = array_combine($keys, array_map([$this, 'getNamespacedKey'], $keys));
		$items          = $this->doGetMulti($namespacedKeys);
		$foundItems     = [];
		
		foreach ($namespacedKeys as $k => $v) {
			if (isset($items[$v]) || array_key_exists($v, $items)) {
				$foundItems[$k] = $items[$v];
			}
		}
		return $foundItems;
	}
	
	public function removeMulti(array $keys)
	{
		return $this->doDeleteMulti(array_map([$this, 'getNamespacedKey'], $keys));
	}
	
	protected function doGetMulti(array $keys)
	{
		$ret = [];
		foreach ($keys as $key) {
			$ret[$key] = $this->doGet($key);
		}
		return $ret;
	}
	
	protected function doDeleteMulti(array $keys)
	{
		foreach ($keys as $key) {
			$this->doDelete($key);
		}
	}
	
	protected function encode($value)
	{
		return is_array($value) ? json_encode($value) : $value;
	}
	
	protected function decode($value)
	{
		$jsonData = json_decode($value, true);
		return (null === $jsonData) ? $value : $jsonData;
	}
	
	abstract protected function doGet($key);
	abstract protected function doSet($key, $value, $lifeTime = null);
	abstract protected function doDelete($key);
	abstract protected function doSetMulti(array $items, $lifeTime = null);
}
