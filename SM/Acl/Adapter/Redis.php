<?php
namespace SM\Acl\Adapter;

use SM\Acl\Acl;
use SM\Acl\Role;
use SM\Acl\RoleInterface;
use SM\Acl\Resource;
use SM\Acl\ResourceInterface;
use SM\Acl\Adapter;
use SM\Cache\CacheInterface;

class Redis extends Adapter
{
	protected $redis;
	
	public function __construct(CacheInterface $redis, $reset = false)
	{
		$this->redis = $redis;
		
		if ($reset !== false) {
			$this->reset();
		}
	}
	
	public function getRedis()
	{
		return $this->redis;
	}
	
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
		
		$this->redis->hMset('roles', [$roleName => $roleObject->getDescription()]);
		
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
		
		if ($this->redis->exists("rolesInherits:$roleInheritName")) {
			$inheritedRoles = $this->redis->sMembers("rolesInherits:$roleInheritName");
			
			if (is_array($inheritedRoles)) {
				foreach ($inheritedRoles as $deepInheritName) {
					$this->addInherit($roleName, $deepInheritName);
				}
			}
		}
		
		if (!$this->isRole($roleInheritName)) {
			throw new \Exception("Role '" . $roleInheritName . "' (to inherit) does not exist in the role list");
		}
		
		$this->redis->sAdd("rolesInherits:$roleName", $roleInheritName);
		
		return true;
	}
	
	public function isRole($roleName)
	{
		return $this->redis->hExists('roles', $roleName);
	}
	
	public function isResource($resourceName)
	{
		return $resourceName === '*' || $this->redis->hExists('resources', $resourceName);
	}
	
	public function isResourceAccess($resourceName, $accessName)
	{
		return $resourceName === '*' || $accessName === '*' || $this->redis->sIsMember("accessList:$resourceName", $accessName);
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
		
		$this->redis->hMset('resources', [$resourceName => $resourceObject->getDescription()]);
		
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
			$this->redis->sAdd("accessList:$resourceName", $accessName);
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
			$this->redis->sRem("accessList:$resourceName", $accessName);
		}
	}
	
	protected function getHaveAccess($roleName, $resourceName, $access)
	{
		$accessKey = $roleName . '!' . $resourceName . '!' . $access;
		
		if ($this->redis->hExists('access', $accessKey)) {
			return $this->redis->hGet('access', $accessKey);
			
		} elseif ($this->redis->exists("rolesInherits:$roleName")) {
			$inheritedRoles = $this->redis->sMembers("rolesInherits:$roleName");
			
			if (is_array($inheritedRoles)) {
				foreach ($inheritedRoles as $inheritedRole) {
					$accessKey = $inheritedRole . '!' . $resourceName . '!' . $access;
					
					if ($this->redis->hExists('access', $accessKey)) {
						return $this->redis->hGet('access', $accessKey);
					}
				}
			}
		}
	}
	
	public function getRolesNames()
	{
		return $this->redis->hGetAll('roles');
	}
	
	public function getResourcesNames()
	{
		return $this->redis->hGetAll('resources');
	}
	
	public function getRoles()
	{
		$roles = [];
		foreach ($this->getRolesNames() as $name => $desc) {
			$roles[] = new Role($name, $desc);
		}
		return $roles;
	}
	
	public function getResources()
	{
		$resources = [];
		foreach ($this->getResourcesNames() as $name => $desc) {
			$resources[] = new Resource($name, $desc);
		}
		return $resources;
	}
	
	public function getResourceAccess($resourceName)
	{
		return $this->redis->sMembers("accessList:$resourceName");
	}
	
	protected function setAccess($roleName, $resourceName, $accessName, $action)
	{
		if (!$this->isResourceAccess($resourceName, $accessName)) {
			throw new \Exception("Access '" . $accessName . "' does not exist in resource '" . $resourceName . "'");
		}
		
		$accessKey = $roleName . '!' . $resourceName . '!' . $accessName;
		$this->redis->hMset('access', [$accessKey => $action]);
	}
	
	public function dropAccess($roleName, $resourceName, $accessName)
	{
		$accessKey = $roleName . '!' . $resourceName . '!' . $accessName;
		$this->redis->hDel('access', $accessKey);
	}
	
	public function reset()
	{
		foreach ($this->getRolesNames() as $roleName => $roleDesc) {
			$this->redis->delete("rolesInherits:$roleName");
		}
		
		foreach ($this->getResourcesNames() as $resourceName => $resourceDesc) {
			$this->redis->delete("accessList:$resourceName");
		}
		
		$this->redis->delete('roles');
		$this->redis->delete('resources');
		$this->redis->delete('access');
	}
}
