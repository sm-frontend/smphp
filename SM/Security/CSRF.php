<?php
namespace SM\Security;

use SM\Http\Session\Session;

class CSRF
{
	protected $session;
	protected $namespace = 'csrf';
	
	public function __construct(Session $session)
	{
		$this->setSession($session);
	}
	
	public function setSession(Session $session)
	{
		$this->session = $session;
	}
	
	public function getSession()
	{
		return $this->session;
	}
	
	public function getValue()
	{
		$csrf_token = \SM\Util\Str::random();
		$this->session->set('csrf_token', $csrf_token, $this->namespace);
		
		return $csrf_token;
	}
	
	public function validate($csrf_token)
	{
		if ($csrf_token != '' && $csrf_token === $this->session->get('csrf_token', $this->namespace)) {
			$this->session->remove('csrf_token', $this->namespace);
			return true;
		}
		return false;
	}
}
