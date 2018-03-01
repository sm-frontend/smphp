<?php
namespace SM\Container;

class Container
{
	protected $binds     = [];
	protected $instances = [];
	
	public function bind($abstract, $concrete = null, $singleton = false)
	{
		$abstract = $this->filter($abstract);
		$concrete = $this->filter($concrete);
		
		if (is_null($concrete)) {
			$concrete = $abstract;
		}
		
		if (!$this->isBind($abstract)) {
			$this->binds[$abstract] = compact('concrete', 'singleton');
		}
		
		return $this;
	}
	
	public function isBind($abstract)
	{
		return isset($this->binds[$abstract]);
	}
	
	private function filter($abstract)
	{
		return is_string($abstract) ? ltrim($abstract, '\\') : $abstract;
	}
	
	public function singleton($abstract, $concrete = null)
	{
		return $this->bind($abstract, $concrete, true);
	}
	
	public function make($abstract, $params = null)
	{
		$abstract  = $this->filter($abstract);
		$params    = func_get_args();
		
		$singleton = \SM\Util\Str::guid($params);
		
		if (isset($this->instances[$singleton])) {
			return $this->instances[$singleton];
		}
		
		if (!$this->isBind($abstract)) {
			throw new \Exception('The resolve service "' . $abstract . '" does not exist');
		}
		
		$concrete = $this->binds[$abstract]['concrete'];
		array_shift($params);
		
		if ($concrete instanceof \Closure) {
			array_unshift($params, $this);
			$instance  = call_user_func_array($concrete, (array) $params);
		} else {
			$reflector = new \ReflectionClass($concrete);
			
			if (!$reflector->isInstantiable()) {
				throw new \Exception('Can not instantiate "' . $reflector->name . '"');
			}
			
			$constructor = $reflector->getConstructor();
			
			if (is_null($constructor)) {
				$instance = new $concrete();
			} else {
				$instance = $reflector->newInstanceArgs($params);
			}
		}
		
		$this->binds[$abstract]['singleton'] && $this->instances[$singleton] = $instance;
		
		return $instance;
	}
	
	public function getBinds()
	{
		return $this->binds;
	}
	
	public function getInstances()
	{
		return $this->instances;
	}
	
	public function flush()
	{
		$this->binds     = [];
		$this->instances = [];
	}
}
