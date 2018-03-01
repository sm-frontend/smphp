<?php
namespace SM\Security\Httpauth;

interface UserInterface
{
	public function isValid($name, $password, $realm);
	public function parse();
}
