<?php
namespace App\Project;

use SM\Route\Router;
use SM\Route\Dispatcher;

abstract class ProjectBase
{
	use ProjectTrait;
	
	protected $data   = [];
	protected $params = [];
	
	abstract protected function run();
	
	protected function dispatcher($routes, $classPath, $suffix = null)
	{
		$router = new Router();
		$router->addRoutes($routes);
		
		$foundRoute = $router->findRoute($_SERVER['REQUEST_URI']);
		
		$dispatcher = new Dispatcher();
		$dispatcher->setSuffix($suffix);
		$dispatcher->setClassPath($classPath);
		
		$ret = $dispatcher->dispatch($foundRoute);
		
		if (!empty($ret['data'])) {
			$this->data   = $ret['data'];
			$this->params = $ret['params'];
		}
	}
	
	protected function output($prefix = '')
	{
		$format = isset($this->params['format']) ? $this->params['format'] : 'html';
		
		switch ($format) {
			case 'html':
				if (isset($this->data['tpl'])) {
					if (!isset($this->data['data'])) {
						$this->data['data'] = [];
					}
					print_output($prefix . $this->data['tpl'], $this->data['data']);
				}
			break;
			
			case 'json' :
			case 'jsonp':
				if (isset($this->data['data'])) {
					callback_output(json_encode($this->data['data'], JSON_UNESCAPED_UNICODE));
				}
			break;
		}
	}
	
	protected function getOptions(array $keys)
	{
		$ret = [];
		if (!empty($keys)) {
			foreach ($keys as $v) {
				switch ($v) {
					case 'uc_param_str':
						if (!empty($this->params[$v]) && is_string($this->params[$v]) && strlen($this->params[$v]) % 2 == 0) {
							foreach (str_split($this->params[$v], 2) as $vv) {
								if (isset($this->params[$vv])) {
									$ret[$vv] = $this->params[$vv];
								}
							}
						}
					break;
					
					case 'qt':
						$ret[$v] = TIMENOW;
					break;
					
					default:
						if (isset($this->params[$v])) {
							$ret[$v] = $this->params[$v];
						}
					break;
				}
			}
		}
		return $ret;
	}
}

