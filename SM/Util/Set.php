<?php
namespace SM\Util;

class Set implements \ArrayAccess, \IteratorAggregate, \Countable
{
	const REPLACE_NOT      = 1;
	const REPLACE_RELAXED  = 2;
	const REPLACE_ABSOLUTE = 3;
	
	protected $_replaceMode;
	protected $_array = [];
	
	public function __construct(array $items = [], $replaceMode = self::REPLACE_ABSOLUTE)
	{
		$this->_replaceMode = $replaceMode;
		$this->setItems($items);
	}
	
	public function set($key, $value)
	{
		if (!isset($this->_array[$key])) {
			$this->_array[$key] = $value;
			return;
		}
		
		switch ($this->_replaceMode) {
			case self::REPLACE_NOT:
			break;
			
			case self::REPLACE_RELAXED:
				if (!empty($value)) {
					$this->_array[$key] = $value;
				}
			break;
			
			case self::REPLACE_ABSOLUTE:
			default :
				$this->_array[$key] = $value;
			break;
		}
		return $this;
	}
	
	public function setItems(array $items)
	{
		foreach ($items as $key => $value) {
			$this->set($key, $value);
		}
		return $this;
	}
	
	public function get($key)
	{
		return (isset($this->_array[$key]) ? $this->_array[$key] : null);
	}
	
	public function getItems(array $keys = [])
	{
		$items = [];
		
		if (empty($keys)) {
			return $this->_array;
		}
		
		foreach ($keys as $key) {
			if (isset($this->_array[$key])) {
				$items[$key] = $this->_array[$key];
			}
		}
		return $items;
	}
	
	public function remove($key)
	{
		if (isset($this->_array[$key])) {
			unset($this->_array[$key]);
		}
	}
	
	public function keys()
	{
		return array_keys($this->_array);
	}
	
	public function has($key)
	{
		return isset($this->_array[$key]);
	}
	
	public function count()
	{
		return count($this->_array);
	}
	
	public function clear()
	{
		$this->_array = [];
	}
	
	public function getIterator()
	{
		return new \ArrayIterator($this->_array);
	}
	
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}
	
	public function offsetGet($key)
	{
		return $this->get($key);
	}
	
	public function offsetUnset($key)
	{
		$this->remove($key);
	}
	
	public function offsetExists($key)
	{
		return $this->has($key);
	}
	
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
	
	public function __get($key)
	{
		return $this->get($key);
	}
	
	public function __unset($key)
	{
		$this->remove($key);
	}
	
	public function __isset($key)
	{
		return $this->has($key);
	}
}
