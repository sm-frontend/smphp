<?php
namespace SM\Acl\Adapter;

use SM\Acl\Acl;
use SM\Acl\Role;
use SM\Acl\RoleInterface;
use SM\Acl\Resource;
use SM\Acl\ResourceInterface;
use SM\Acl\Adapter;

class Memory extends Adapter
{
	protected $roles;
	protected $rolesNames;
	protected $roleInherits;
	protected $resources;
	protected $resourcesNames;
	protected $access;
	protected $accessList;
	
	public function addRole($role, $accessInherits = null)
	{
		if (is_object($role) && $role instanceof RoleInterface) {
			$roleName   = $role->getName();
			$roleObject = $role;
		} else {
			$roleName   = $role;
			$roleObject = new Role($roleName);
		}
		
		if ($this->isRole($roleName)) {
			return false;
		}
		
		$this->roles[]               = $roleObject;
		$this->rolesNames[$roleName] = true;
		
		if ($accessInherits !== null) {
			$this->addInherit($roleName, $accessInherits);
		}
		
		return true;
	}
	
	public function addInherit($roleName, $roleToInherit)
	{
		if (!$this->isRole($roleName)) {
			throw new \Exception("Role '" . $roleName . "' does not exist in the role list");
		}
		
		if (is_object($roleToInherit)) {
			$roleInheritName = $roleToInherit->getName();
		} else {
			$roleInheritName = $roleToInherit;
		}
		
		if (isset($this->roleInherits[$roleInheritName])) {
			foreach ($this->roleInherits[$roleInheritName] as $deepInheritName) {
				$this->addInherit($roleName, $deepInheritName);
			}
		}
		
		if (!$this->isRole($roleInheritName)) {
			throw new \Exception("Role '" . $roleInheritName . "' (to inherit) does not exist in the role list");
		}
		
		if (!isset($this->roleInherits[$roleName])) {
			$this->roleInherits[$roleName] = [];
		}
		
		array_unshift($this->roleInherits[$roleName], $roleInheritName);
		
		return true;
	}
	
	public function isRole($roleName)
	{
		return isset($this->rolesNames[$roleName]);
	}
	
	public function isResource($resourceName)
	{
		return $resourceName === '*' || isset($this->resourcesNames[$resourceName]);
	}
	
	public function isResourceAccess($resourceName, $accessName)
	{
		return $resourceName === '*' || $accessName === '*' || isset($this->accessList[$resourceName][$accessName]);
	}
	
	public function addResource($resource, $accessList)
	{
		if (is_object($resource) && $resource instanceof ResourceInterface) {
			$resourceName   = $resource->getName();
			$resourceObject = $resource;
		} else {
			$resourceName   = $resource;
			$resourceObject = new Resource($resourceName);
		}
		
		if (!$this->isResource($resourceName)) {
			$this->resources[]                   = $resourceObject;
			$this->resourcesNames[$resourceName] = true;
		}
		
		return $this->addResourceAccess($resourceName, $accessList);
	}
	
	public function addResourceAccess($resourceName, $accessList)
	{
		if (!$this->isResource($resourceName)) {
			throw new \Exception("Resource '" . $resourceName . "' does not exist in ACL");
		}
		
		if (is_string($accessList)) {
			$accessList = explode(' ', $accessList);
		}
		
		foreach ($accessList as $accessName) {
			$this->accessList[$resourceName][$accessName] = true;
		}
		return true;
	}
	
	public function dropResourceAccess($resourceName, $accessList)
	{
		if (!$this->isResource($resourceName)) {
			throw new \Exception("Resource '" . $resourceName . "' does not exist in ACL");
		}
		
		if (is_string($accessList)) {
			$accessList = explode(' ', $accessList);
		}
		
		foreach ($accessList as $accessName) {
			if (isset($this->accessList[$resourceName][$accessName])) {
				unset($this->accessList[$resourceName][$accessName]);
			}
		}
	}
	
	protected function getHaveAccess($roleName, $resourceName, $access)
	{
		$accessKey = $roleName . '!' . $resourceName . '!' . $access;
		
		if (isset($this->access[$accessKey])) {
			return $this->access[$accessKey];
			
		} elseif (isset($this->roleInherits[$roleName])) {
			$inheritedRoles = $this->roleInherits[$roleName];
			
			if (is_array($inheritedRoles)) {
				foreach ($inheritedRoles as $inheritedRole) {
					$accessKey = $inheritedRole . '!' . $resourceName . '!' . $access;
					
					if (isset($this->access[$accessKey])) {
						return $this->access[$accessKey];
					}
				}
			}
		}
	}
	
	public function getRolesNames()
	{
		return $this->rolesNames;
	}
	
	public function getResourcesNames()
	{
		return $this->resourcesNames;
	}
	
	public function getRoles()
	{
		return $this->roles;
	}
	
	public function getResources()
	{
		return $this->resources;
	}
	
	public function getResourceAccess($resourceName)
	{
		return array_keys($this->accessList[$resourceName]);
	}
	
	protected function setAccess($roleName, $resourceName, $accessName, $action)
	{
		if (!$this->isResourceAccess($resourceName, $accessName)) {
			throw new \Exception("Access '" . $accessName . "' does not exist in resource '" . $resourceName . "'");
		}
		
		$accessKey                = $roleName . '!' . $resourceName . '!' . $accessName;
		$this->access[$accessKey] = $action;
	}
	
	public function dropAccess($roleName, $resourceName, $accessName)
	{
		$accessKey = $roleName . '!' . $resourceName . '!' . $accessName;
		
		if (isset($this->access[$accessKey])) {
			unset($this->access[$accessKey]);
		}
	}
}
