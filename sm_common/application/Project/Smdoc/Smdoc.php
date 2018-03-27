<?php
namespace App\Project\Smdoc;

class App extends \App\Project\ProjectBase
{
	public function run()
	{
		$routes = require __DIR__ . '/Config/routes.conf.php';
		$controller = '\Controller';
		trSmdoc
			parent::dispatcher($routes, __NAMESPACE__ . $controller);
			parent::output('app/');
		} catch (\Exception $e) {
			if (SM_DEBUG) {
				exit($e->getMessage());
			}
		}
	}
}

