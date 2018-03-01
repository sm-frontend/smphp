<?php
namespace SM\Security\Httpauth\User;

class Digest implements \SM\Security\Httpauth\UserInterface
{
	private $nonce;
	private $nc;
	private $cnonce;
	private $qop;
	private $username;
	private $uri;
	private $response;
	
	public function __construct()
	{
		$this->parse();
	}
	
	public function isValid($name, $password, $realm)
	{
		$request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		
		$u1             = md5(sprintf('%s:%s:%s', $this->username, $realm, $password));
		$u2             = md5(sprintf('%s:%s', $request_method, $this->uri));
		$response       = md5(sprintf('%s:%s:%s:%s:%s:%s', $u1, $this->nonce, $this->nc, $this->cnonce, $this->qop, $u2));
		
		return ($response == $this->response) && ($name == $this->username);
	}
	
	public function parse()
	{
		$user     = [];
		$digest   = $this->getDigest();
		$required = ['nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1];
		
		preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);
		
		if (is_array($matches)) {
			foreach ($matches as $m) {
				$key = $m[1];
				$user[$key] = $m[2] ? $m[2] : $m[3];
				unset($required[$key]);
			}
			
			if (count($required) == 0) {
				foreach ($user as $k => $v) {
					$this->$k = $v;
				}
			}
		}
	}
	
	public function getDigest()
	{
		$digest = '';
		
		if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
			$digest = $_SERVER['PHP_AUTH_DIGEST'];
		} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'digest') === 0) {
				$digest = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
			}
		}
		return $digest;
	}
}
