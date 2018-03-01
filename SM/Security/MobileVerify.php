<?php
namespace SM\Security;

use SM\Util\Google2FA;
use SM\Http\Session\Session;

class MobileVerify
{
	protected $seed   = 'IEXUMCTHDEZBLFAO';
	protected $window = 6;
	
	protected $session;
	protected $namespace = 'mv';
	
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
	
	public function setSeed($seed)
	{
		if (!empty($seed)) {
			$this->seed = $seed;
		}
	}
	
	public function setWindow($win)
	{
		if ($win > 0) {
			$this->window = $win;
		}
	}
	
	public function getValue($mobile)
	{
		$timeStamp  = Google2FA::getTimestamp();
		$secretKey  = Google2FA::base32Decode($this->seed);
		$verifyCode = Google2FA::oathHotp($secretKey, $timeStamp);
		
		$this->session->set('verify_code', $verifyCode . '~' . $mobile, $this->namespace);
		
		return $verifyCode;
	}
	
	public function validate($verifycode, $mobile)
	{
		$code = explode('~', $this->session->get('verify_code', $this->namespace));
		
		if ($code[0] == $verifycode && $code[1] == $mobile) {
			$this->session->remove('verify_code', $this->namespace);
			return Google2FA::verifyKey($this->seed, $verifycode, $this->window);
		}
		
		return false;
	}
}
