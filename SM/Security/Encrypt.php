<?php
namespace SM\Security;

class Encrypt
{
	protected $_key    = '_-_eNcRyPt_-_';
	protected $_config = [
		'hash'    => 'sha1',
		'xor'     => false,
		'openssl' => false,
		'noise'   => true,
		'cipher'  => 'AES-256-CBC'
	];
	
	public function __construct($key = null, array $config = [])
	{
		if (!is_null($key)) {
			$this->_key = $key;
		}
		
		if (!empty($config)) {
			$this->_config = array_merge($this->_config, $config);
		}
		
		if (extension_loaded('openssl')) {
			$this->_config['openssl'] = true;
		}
	}
	
	public function encode($str, $key = null)
	{
		if (is_null($key)) {
			$key = $this->_key;
		}
		
		if ($this->_config['xor']) {
			$str = $this->_xorEncode($str, $key);
		}
		
		if ($this->_config['openssl']) {
			$str = $this->_opensslEncode($str, $key);
		}
		
		if ($this->_config['noise']) {
			$str = $this->_noiseEncode($str, $key);
		}
		
		return base64_encode($str);
	}
	
	public function decode($str, $key = null)
	{
		if (is_null($key)) {
			$key = $this->_key;
		}
		
		if (preg_match('/[^a-zA-Z0-9\/\+=]/', $str)) {
			return false;
		}
		
		$str = base64_decode($str);
		
		if ($this->_config['noise']) {
			$str = $this->_noiseDecode($str, $key);
		}
		
		if ($this->_config['openssl']) {
			$str = $this->_opensslDecode($str, $key);
		}
		
		if ($this->_config['xor']) {
			$str = $this->_xorDecode($str, $key);
		}
		return $str;
	}
	
	protected function _opensslEncode($str, $key)
	{
		$iv    = Random::getBytes(16);
		$value = openssl_encrypt($str, $this->_config['cipher'], $this->_setKey($key), 0, $iv);
		
		$iv    = base64_encode($iv);
		
		return json_encode(compact('iv', 'value'));
	}
	
	protected function _opensslDecode($str, $key)
	{
		$json = json_decode($str, true);
		$iv   = base64_decode($json['iv']);
		
		return openssl_decrypt($json['value'], $this->_config['cipher'], $this->_setKey($key), 0, $iv);
	}
	
	protected function _xorEncode($str, $key)
	{
		$rand    = $this->_config['hash'](mt_rand());
		$randlen = strlen($rand);
		$strlen  = strlen($str);
		
		$code = '';
		for ($i = 0; $i < $strlen; $i++) {
			$r     = substr($rand, ($i % $randlen), 1);
			$code .= $r . ($r ^ substr($str, $i, 1));
		}
		return $this->_xor($code, $key);
	}
	
	protected function _xorDecode($str, $key)
	{
		$str    = $this->_xor($str, $key);
		$strlen = strlen($str);
		
		$code   = '';
		for ($i = 0; $i < $strlen; $i++) {
			$code .= (substr($str, $i++, 1) ^ substr($str, $i, 1));
		}
		return $code;
	}
	
	protected function _xor($str, $key)
	{
		$hash   = $this->_config['hash']($key);
		$strlen = strlen($str);
		
		$code   = '';
		for ($i = 0; $i < $strlen; $i++) {
			$code .= substr($str, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
		}
		return $code;
	}
	
	protected function _noiseEncode($str, $key)
	{
		$hash    = $this->_config['hash']($key);
		$hashlen = strlen($hash);
		$strlen  = strlen($str);
		
		$code    = '';
		for ($i = 0, $j = 0; $i < $strlen; ++$i, ++$j) {
			if ($j >= $hashlen) {
				$j = 0;
			}
			$code .= chr((ord($str[$i]) + ord($hash[$j])) % 256);
		}
		return $code;
	}
	
	protected function _noiseDecode($str, $key)
	{
		$hash    = $this->_config['hash']($key);
		$hashlen = strlen($hash);
		$strlen  = strlen($str);
		
		$code    = '';
		for ($i = 0, $j = 0; $i < $strlen; ++$i, ++$j) {
			if ($j >= $hashlen) {
				$j = 0;
			}
			$temp = ord($str[$i]) - ord($hash[$j]);
			
			if ($temp < 0) {
				$temp = $temp + 256;
			}
			$code .= chr($temp);
		}
		return $code;
	}
	
	protected function _setKey($key)
	{
		if (strlen($key) > 32) {
			return md5($key);
		}
		
		$sizes = [16, 24, 32];
		
		foreach ($sizes as $s) {
			while (strlen($key) < $s) {
				$key = $key . "\0";
			}
			
			if (strlen($key) == $s) {
				break;
			}
		}
		return $key;
	}
}
