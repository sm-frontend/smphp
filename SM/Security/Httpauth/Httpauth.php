<?php
namespace SM\Security\Httpauth;

class Httpauth
{
	private $type  = 'basic';
	private $realm = 'Secured resource';
	
	private $username;
	private $password;
	
	public function __construct(array $params = [])
	{
		foreach ($params as $param => $value) {
			$this->$param = $value;
		}
		
		if (!$this->username || !$this->password) {
			throw new \Exception('No username or password set for HttpAuthentication.');
		}
	}
	
	public function secure()
	{
		if (!$this->validateUser($this->getUser())) {
			$this->denyAccess();
		}
	}
	
	private function validateUser(UserInterface $user)
	{
		return $user->isValid($this->username, $this->password, $this->realm);
	}
	
	private function denyAccess()
	{
		header('HTTP/1.0 401 Unauthorized');
		
		switch (strtolower($this->type)) {
			case 'digest':
				header('WWW-Authenticate: Digest realm="' . $this->realm .'",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($this->realm) . '"');
			break;
			
			default:
				header('WWW-Authenticate: Basic realm="' . $this->realm . '"');
			break;
		}
		
		die('<strong>HTTP/1.0 401 Unauthorized</strong>');
	}
	
	private function getUser()
	{
		switch (strtolower($this->type)) {
			case 'digest':
				$class = __NAMESPACE__ . '\User\Digest';
			break;
			
			default:
				$class = __NAMESPACE__ . '\User\Basic';
			break;
		}
		return new $class;
	}
}
