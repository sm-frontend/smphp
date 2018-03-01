<?php
namespace App;

use SM\Config\Config;
use SM\Route\Router;
use SM\Route\Dispatcher;

class App
{
	protected $data = [];

	public function run()
	{
		$config    = $this->getConfig();
		$routes    = $config->get('router.routes', []);
		$classPath = $config->get('router.class_path', 'App\\Controller');
		$suffix    = $config->get('router.suffix', '');

		try {
			$this->data = $this->dispatcher($routes, $classPath, $suffix);
			$this->output();

		} catch (\Exception $e) {
			exit($e->getMessage());
		}
	}

	protected function dispatcher($routes, $classPath, $suffix = null)
	{
		$router = new Router();
		$router->addRoutes($routes);

		$foundRoute = $router->findRoute($_SERVER['REQUEST_URI']);

		$dispatcher = new Dispatcher();
		$dispatcher->setSuffix($suffix);
		$dispatcher->setClassPath($classPath);

		return $dispatcher->dispatch($foundRoute);
	}

	protected function output()
	{
		if (!empty($this->data['data'])) {
			echo $this->data['data'];
			exit;
		}
	}

	protected function getConfig()
	{
		return \SM::getContainer()->singleton('config', function () {
			return Config::load(APP_PATH . '/Config/main.php');
		})->make('config');
	}
}
