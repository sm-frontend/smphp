<?php
namespace SM\Route;

class Dispatcher
{
	private $_suffix;
	private $_classPath;
	private $_xssClean = true;
	
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

	public function setXssClean($xssClean)
	{
		$this->_xssClean = (bool) $xssClean;
	}
	
	public function dispatch(Route $route)
	{
		$class  = $route->getMapClass();
		$method = $route->getMapMethod();
		$params = $route->getMapArguments();

		if (empty($class)) {
			throw new RouteException('Class Name not specified.');
		}
		
		if (empty($method)) {
			throw new RouteException('Method Name not specified.');
		}
		
		$class  = ucfirst($class);
		$method = str_replace('-', '_', $method);
		
		if (!preg_match('/^[a-zA-Z0-9_\\\]+$/', $class)) {
			throw new RouteException('Disallowed characters in class name ' . $class);
		}
		
		$class = $this->_classPath . $class . $this->_suffix;
		
		if (false === class_exists($class, true)) {
			throw new RouteException('Class not found ' . $class);
		}
		
		$params += \SM\Http\Input::request(null, null, $this->_xssClean);
		$obj     = new $class($params);
		
		if (false === is_callable([$obj, $method])) {
			throw new RouteException('Method not found ' . $method);
		}
		
		$data = call_user_func([$obj, $method], $params);
		
		return ['params' => $params, 'data' => $data];
	}
}
