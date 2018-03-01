<?php
namespace SM\Route;

use SM\Util\Arr;

class Route
{
	private $_path;
	private $_class;
	private $_classPrefix;
	private $_method;
	private $_extension;
	private $_dynamicElements = [];
	private $_mapArguments    = [];

	/*
	 * ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
	 */
	private $_requestMethod   = [];
	
	public function __construct($path = null)
	{
		if (null !== $path) {
			$this->setPath($path);
		}
	}
	
	public function setPath($path)
	{
		$this->_path = $path;
	}
	
	public function getPath()
	{
		return $this->_path;
	}
	
	public function setMapClass($class)
	{
		if ($class == ':class') {
			$this->addDynamicElement(':class', ':class');
		} else {
			$this->_class = $class;
		}
	}
	
	public function getMapClass()
	{
		return $this->_class;
	}
	
	public function setMapMethod($method)
	{
		if ($method == ':method') {
			$this->addDynamicElement(':method', ':method');
		} else {
			$this->_method = $method;
		}
	}
	
	public function getMapMethod()
	{
		return $this->_method;
	}
	
	public function setMapExtension($extension)
	{
		$this->_extension = Arr::toArray($extension);
	}
	
	public function getMapExtension()
	{
		return $this->_extension;
	}
	
	public function setMapClassPrefix($prefix)
	{
		$this->_classPrefix = $prefix;
	}
	
	public function getMapClassPrefix()
	{
		return $this->_classPrefix;
	}
	
	public function addDynamicElement($key, $value)
	{
		$this->_dynamicElements[$key] = $value;
	}
	
	public function getDynamicElements()
	{
		return $this->_dynamicElements;
	}
	
	private function _addMapArgument($key, $value)
	{
	 	$this->_mapArguments[$key] = $value;
	}
	
	public function getMapArguments()
	{
		return $this->_mapArguments;
	}

	public function getRequestMethod()
	{
		return $this->_requestMethod;
	}

	public function setRequestMethod($methods)
	{
		$this->_requestMethod = array_map('strtoupper', Arr::toArray($methods));
	}
	
	private function removeExtension($path)
	{
		if (!empty($this->_extension)) {
			$path = preg_replace('/\.(' . implode('|', $this->_extension) . ')$/i', '', $path);
		}
		return $path;
	}
	
	public function checkRequestMethod($method)
	{
		return !empty($this->_requestMethod) && !empty($method) && !in_array($method, $this->_requestMethod);
	}
	
	public function matchMap($path_to_match)
	{
		$found_dynamic_class  = null;
		$found_dynamic_method = null;
		$found_dynamic_args   = [];
		
		$path_to_match        = $this->removeExtension($path_to_match);
		
		$this_path_elements   = explode('/', ltrim($this->_path, '/'));
		$match_path_elements  = explode('/', ltrim($path_to_match, '/'));
		
		if (count($this_path_elements) !== count($match_path_elements)) {
			return false;
		}
		
		$possible_match_string = '';
		
		foreach ($this_path_elements as $i => $this_path_element) {
			if (preg_match('/^:/', $match_path_elements[$i])) {
				return false;
			}
			
			if ($this_path_element === $match_path_elements[$i]) {
				$possible_match_string .= "/{$match_path_elements[$i]}";
				continue;
			}
			
			if (isset($this->_dynamicElements[$this_path_element])) {
				if ($this->_dynamicElements[$this_path_element] === $this_path_element) {
					$possible_match_string .= "/{$match_path_elements[$i]}";
					
					if (':class' === $this_path_element && null === $this->getMapClass()) {
						$found_dynamic_class = $match_path_elements[$i];
					} elseif (':method' === $this_path_element && null === $this->getMapMethod()) {
						$found_dynamic_method = $match_path_elements[$i];
					} elseif (':class' !== $this_path_element && ':method' !== $this_path_element) {
						$found_dynamic_args[$this_path_element] = $match_path_elements[$i];
					}
					continue;
				}
				
				$regexp = '/' . $this->_dynamicElements[$this_path_element] . '/';
				
				if (preg_match($regexp, $match_path_elements[$i]) > 0) {
					if (':class' === $this_path_element && null === $this->getMapClass()) {
						$found_dynamic_class = $match_path_elements[$i];
					} elseif (':method' === $this_path_element && null === $this->getMapMethod()) {
						$found_dynamic_method = $match_path_elements[$i];
					} elseif (':class' !== $this_path_element && ':method' !== $this_path_element) {
						$found_dynamic_args[$this_path_element] = $match_path_elements[$i];
					}
					
					$possible_match_string .= "/{$match_path_elements[$i]}";
					continue;
				}
			}
			
			return false;
		}
		
		if ($possible_match_string === $path_to_match) {
			if (null !== $found_dynamic_class) {
				$this->setMapClass(($this->_classPrefix ?: '') . $found_dynamic_class);
			}
			
			if (null !== $found_dynamic_method) {
				$this->setMapMethod($found_dynamic_method);
			}
			
			foreach ($found_dynamic_args as $key => $found_dynamic_arg) {
				$this->_addMapArgument($key, $found_dynamic_arg);
			}
			
			return true;
		}
		
		return false;
	}
}
