<?php
namespace SM\Security;

use SM\Cache\CacheInterface;

class Nonce
{
	const DEFAULT_TIME_INTERVAL = 60;
	
	private $disableExpired = false;
	private $secret         = '_-_NoNcE_-_';
	private $cache          = null;
	
	public function __construct($secret = null, CacheInterface $cache = null)
	{
		if ($secret !== null) {
			$this->secret = $secret;
		}
		
		if ($cache !== null) {
			$this->cache = $cache;
		}
	}
	
	public function disableExpiredCheck()
	{
		$this->disableExpired = true;
	}
	
	public function generate($token = null, $timestamp = null)
	{
		return implode('.', array_values($this->generateNonce($token, $timestamp)));
	}
	
	public function validate($string, $token, $timeInterval = self::DEFAULT_TIME_INTERVAL)
	{
		$nonceData = explode('.', $string);
		
		if (!$nonceData || count($nonceData) < 2) {
			return false;
		}
		
		list($nonce, $created, $digest) = $nonceData;
		
		return $this->checkExpired($created, $timeInterval) && $this->checkNonce($nonce, $created, $digest, $token) && $this->checkReplayAttack($nonce, $timeInterval);
	}
	
	private function checkReplayAttack($nonce, $timeInterval = self::DEFAULT_TIME_INTERVAL)
	{
		return $this->checkCacheKey($nonce, $timeInterval);
	}
	
	private function checkCacheKey($key, $timeInterval)
	{
		if (!$this->cache instanceof CacheInterface) {
			return true;
		}
		
		if ($this->cache->get($key)) {
			return false;
		}
		
		$this->cache->set($key, 1, $timeInterval);
		return true;
	}
	
	private function checkExpired($nonceCreated, $timeInterval = self::DEFAULT_TIME_INTERVAL)
	{
		if ($this->disableExpired === true) {
			return true;
		}
		return ($nonceCreated + $timeInterval) > TIMENOW;
	}
	
	private function checkNonce($nonce, $created, $digest, $token)
	{
		if (!$nonce || !$digest) {
			return false;
		}
		
		if ($created < 0 || !is_numeric($created)) {
			return false;
		}
		
		$nonceData = $this->generateNonce($token, $created);
		
		if ($nonce != $nonceData['nonce']) {
			return false;
		}
		
		$checkDigest = md5($nonce . ',' . $created . ',' . $this->secret);
		
		if ($checkDigest != $digest) {
			return false;
		}
		
		return true;
	}
	
	private function generateNonce($token, $timestamp = null)
	{
		if ($timestamp === null) {
			$timestamp = TIMENOW;
		}
		
		$nonce  = md5($token . ',' . $timestamp);
		$digest = md5($nonce . ',' . $timestamp . ',' . $this->secret);
		
		return ['nonce' => $nonce, 'created' => $timestamp, 'digest' => $digest];
	}
}
