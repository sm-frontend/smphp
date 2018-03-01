<?php
namespace App\Common;

use SM\Http\Output;

class BaseController
{
	protected $view = null;

	public function __construct()
	{
		$config     = \SM::getContainer()->make('config');
		$tplPath    = $config->get('view.tplPath', BASE_PATH . '/app/view/');
		$this->view = \SM::getView($tplPath);
	}

	public function json($data)
	{
		Output::contentType('application/json');
		return json_encode($data);
	}

	public function render($tpl, $data = [])
	{
		return $this->view->render($tpl, $data);
	}
}
