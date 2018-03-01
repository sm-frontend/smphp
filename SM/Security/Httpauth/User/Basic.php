<?php
namespace SM\Security\Httpauth\User;

class Basic implements \SM\Security\Httpauth\UserInterface
{
	private $name;
	private $password;
	
	public function __construct()
	{
		$this->parse();
	}
	
	public function isValid($name, $password, $realm = null)
	{
		return ($name == $this->name) && ($password == $this->password);
	}
	
	public function parse()
	{
		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$this->name     = $_SERVER['PHP_AUTH_USER'];
			$this->password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
		} elseif (isset($_SERVER['HTTP_AUTHENTICATION'])) {
			if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']), 'basic') === 0) {
				$userdata = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHENTICATION'], 6)));
				list($this->name, $this->password) = $userdata;
			}
		}
	}
}
