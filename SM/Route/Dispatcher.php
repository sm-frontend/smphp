<?php
namespace SM\Route;

class Dispatcher
{
	private $_suffix;
	private $_classPath;
	
	public function __construct()
	{
		$this->setSuffix('');
	}
	
	public function setSuffix($suffix)
	{
		$this->_suffix = $suffix;
	}
	
	public function setClassPath($path)
	{
		$this->_classPath = rtrim($path, '\\') . '\\';
	}
	
	public function dispatch(Route $route)
	{
		$class  = $route->getMapClass();
		$method = $route->getMapMethod();
		$params = $route->getMapArguments();
		
		if (empty($class)) {
			throw new \Exception('Class Name not specified.');
		}
		
		if (empty($method)) {
			throw new \Exception('Method Name not specified.');
		}
		
		$class  = ucfirst($class);
		$method = str_replace('-', '_', $method);
		
		if (!preg_match('/^[a-zA-Z0-9_\\\]+$/', $class)) {
			throw new \Exception('Disallowed characters in class name ' . $class);
		}
		
		$class = $this->_classPath . $class . $this->_suffix;
		
		if (false === class_exists($class, true)) {
			throw new \Exception('Class not found ' . $class);
		}
		
		if (false === method_exists($class, $method)) {
			throw new \Exception('Method not found ' . $method);
		}
		
		$params += \SM\Http\Input::request();
		$data    = call_user_func([new $class($params), $method], $params);
		
		return ['params' => $params, 'data' => $data];
	}
}
