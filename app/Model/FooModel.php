<?php
namespace App\Model;

use App\Common\BaseModel;

class FooModel extends BaseModel
{
	private $table = 'foo';

	public function getList()
	{
		return $this->db->from($this->table)->getAll();
	}
}
