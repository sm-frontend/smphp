<?php
namespace SM\Acl;

abstract class Adapter implements AdapterInterface
{
	protected $defaultAccess = 0;
	
	public function setDefaultAction($defaultAccess)
	{
		$this->defaultAccess = $defaultAccess;
	}
	
	public function getDefaultAction()
	{
		return $this->defaultAccess;
	}
	
	public function allow($roleName, $resourceName, $access)
	{
		if ($roleName === '*') {
			$this->rolePermission($resourceName, $access, Acl::ALLOW);
		} elseif ($resourceName === '*') {
			$this->resourcePermission($roleName, $access, Acl::ALLOW);
		} else {
			$this->allowOrDeny($roleName, $resourceName, $access, Acl::ALLOW);
		}
	}
	
	public function deny($roleName, $resourceName, $access)
	{
		if ($roleName === '*') {
			$this->rolePermission($resourceName, $access, Acl::DENY);
		} elseif ($resourceName === '*') {
			$this->resourcePermission($roleName, $access, Acl::DENY);
		} else {
			$this->allowOrDeny($roleName, $resourceName, $access, Acl::DENY);
		}
	}
	
	public function isAllowed($roleName, $resourceName, $access)
	{
		if (!$this->isRole($roleName)) {
			return ($this->getDefaultAction() === Acl::ALLOW);
		}
		
		$haveAccess = $this->getHaveAccess($roleName, $resourceName, $access);
		
		if ($haveAccess === null) {
			$haveAccess = $this->getHaveAccess($roleName, $resourceName, '*');
		}
		
		if ($haveAccess === null) {
			return ($this->getDefaultAction() == Acl::ALLOW);
		}
		
		return ($haveAccess == Acl::ALLOW);
	}
	
	protected function rolePermission($resourceName, $access, $action)
	{
		foreach ($this->getRolesNames() as $roleName => $roleValue) {
			if ($resourceName === '*') {
				$this->resourcePermission($roleName, $access, $action);
			} else {
				$this->allowOrDeny($roleName, $resourceName, $access, $action);
			}
		}
	}
	
	protected function resourcePermission($roleName, $access, $action)
	{
		foreach ($this->getResourcesNames() as $resourceName => $resourceValue) {
			if ($roleName === '*') {
				$this->rolePermission($resourceName, $access, $action);
			} else {
				$this->allowOrDeny($roleName, $resourceName, $access, $action);
			}
		}
	}
	
	protected function allowOrDeny($roleName, $resourceName, $access, $action)
	{
		if (!$this->isRole($roleName)) {
			throw new \Exception("Role '" . $roleName . "' does not exist in ACL");
		}
		
		if (!$this->isResource($resourceName)) {
			throw new \Exception("Resource '" . $resourceName . "' does not exist in ACL");
		}
		
		if ($access === '*') {
			$access = array_merge(['*'], $this->getResourceAccess($resourceName));
		}
		
		if (!is_array($access)) {
			$access = [$access];
		}
		
		foreach ($access as $accessName) {
			$this->setAccess($roleName, $resourceName, $accessName, $action);
		}
	}
}
