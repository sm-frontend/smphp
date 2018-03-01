<?php
namespace SM\Config;

use SM\Util\Arr;
use SM\Util\Dir;
use SM\Util\File;

class Config implements \ArrayAccess
{
	private $_supportedParsers = ['Php', 'Xml', 'Json', 'Ini'];
	private $_config           = [];
	private $_policy           = [
		'delimiter'  => '.',
		'cache'      => false,
		'cache_path' => ''
	];
	
	public static function load($path, array $policy = [])
	{
		return new static($path, $policy);
	}
	
	private function __construct($path, $policy)
	{
		$this->_policy = array_merge($this->_policy, $policy);
		
		if ($this->_policy['cache']) {
			$this->_policy['cache_path'] = Dir::handle($this->_policy['cache_path']);
		}
		
		foreach ($this->getValidPath($path) as $v) {
			$this->_config = array_replace_recursive($this->_config, $this->getParser($v)->getData());
		}
	}
	
	private function getValidPath($path)
	{
		$paths = [];
		
		foreach (Arr::toArray($path) as $v) {
			if (is_dir($v)) {
				$paths = array_merge($paths, Dir::getFiles($v));
			} elseif (File::isFile($v)) {
				$paths[] = $v;
			}
		}
		return $paths;
	}
	
	private function getParser($path)
	{
		$ext = strtolower(File::extension($path));
		
		foreach ($this->_supportedParsers as $v) {
			$parser = __NAMESPACE__ . '\Parser\\' . $v;
			
			if (in_array($ext, $parser::getSupportedExtensions())) {
				return new $parser($path, $this->_policy);
			}
		}
		
		throw new \Exception("Config Parser [$ext] does not exist.");
	}
	
	public function get($key = null, $default = null)
	{
		if (is_null($key)) {
			return $this->_config;
		}
		
		return Arr::getValue($this->_config, $key, $this->_policy['delimiter'], $default);
	}
	
	public function set($key, $value)
	{
		Arr::setValue($this->_config, $key, $value, $this->_policy['delimiter']);
	}
	
	public function has($key)
	{
		return Arr::has($this->_config, $key, $this->_policy['delimiter']);
	}
	
	public function forget($key)
	{
		Arr::forget($this->_config, $key, $this->_policy['delimiter']);
	}
	
	public function offsetGet($key)
	{
		return $this->get($key);
	}
	
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}
	
	public function offsetExists($key)
	{
		return $this->has($key);
	}
	
	public function offsetUnset($key)
	{
		$this->forget($key);
	}
}
