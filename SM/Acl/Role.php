<?php
namespace SM\Acl;

class Role implements RoleInterface
{
	protected $name;
	protected $description;
	
	public function __construct($name, $description = null)
	{
		if ($name == '*') {
			throw new \Exception("Role name cannot be '*'");
		}
		
		$this->name        = $name;
		$this->description = $description;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
}
