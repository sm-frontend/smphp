<?php
namespace SM\Cache;

class Cache
{
	public static function getInstance($driver = 'redis', $policy = [], $namespace = '')
	{
		$driver = strtolower($driver);
		$class  = __NAMESPACE__ . '\Driver\\' . ucfirst($driver);
		
		if (class_exists($class, true)) {
			$instance = \SM::getContainer()->singleton($class)->make($class, $policy, $namespace);
			
			if ($namespace != '' && $instance->getNamespace() == '') {
				$instance->setNamespace($namespace);
			}
			
			return $instance;
		} else {
			throw new \Exception("Cache Driver [$driver] does not exist.");
		}
	}
}
