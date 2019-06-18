<?php
namespace SM\Route;

use SM\Util\Dir;
use SM\Http\Input;

class Router
{
	private $_routes = [];
	
	public function __construct(array $routes = [])
	{
		if (!empty($routes)) {
			$this->addRoutes($routes);
		}
	}
	
	public function addRoute($name, Route $route)
	{
		$this->_routes[$name] = $route;
	}
	
	public function addRoutes(array $routes)
	{
		foreach ($routes as $name => $value) {
			$route = new Route($value['path']);
			
			if (isset($value['class'])) {
				$route->setMapClass($value['class']);
			}
			
			if (isset($value['method'])) {
				$route->setMapMethod($value['method']);
			}
			
			if (isset($value['extension'])) {
				$route->setMapExtension($value['extension']);
			}
			
			if (isset($value['class_prefix'])) {
				$route->setMapClassPrefix($value['class_prefix']);
			}
			
			if (isset($value['dynamic'])) {
				foreach ($value['dynamic'] as $k => $v) {
					$route->addDynamicElement($k, $v);
				}
			}
			
			if (isset($value['request_method'])) {
				$route->setRequestMethod($value['request_method']);
			}
			
			$this->addRoute($name, $route);
		}
	}
	
	public function getRoutes()
	{
		return $this->_routes;
	}
	
	public function findRoute($path)
	{
		$path = parse_url($path, PHP_URL_PATH);
		$path = Dir::normalizePath($path);
		
		if ($path === '') {
			$path = '/';
		}
		
		$method = Input::getMethod();
		
		foreach ($this->_routes as $route) {
			if ($route->checkRequestMethod($method)) {
				continue;
			}
			
			if (true === $route->matchMap($path)) {
				return $route;
			}
		}
		
		throw new RouteException('Route not found.');
	}
}
