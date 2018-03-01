<?php
namespace App\Controller;

use App\Common\BaseController;
use App\Model\FooModel;

class Demo extends BaseController
{
	public function index($params)
	{
		return 'Hello world!';
	}

	public function foo()
	{
		$fooModel = new FooModel;

		return $this->json($fooModel->getList());
	}

	public function page()
	{
		$data = [
			'title' => 'This is SMPHP',
		];

		return $this->render('demo', $data);
	}
}
