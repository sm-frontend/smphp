<?php
namespace SM\Acl;

interface AdapterInterface
{
	public function addRole($role, $accessInherits = null);
	public function addInherit($roleName, $roleToInherit);
	public function isRole($roleName);
	public function isResource($resourceName);
	public function addResource($resourceObject, $accessList);
	public function addResourceAccess($resourceName, $accessList);
	public function dropResourceAccess($resourceName, $accessList);
	public function allow($roleName, $resourceName, $access);
	public function deny($roleName, $resourceName, $access);
	public function isAllowed($roleName, $resourceName, $access);
	public function getRoles();
	public function getResources();
}
