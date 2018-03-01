<?php
namespace SM\Log\Driver;

class Udp extends \SM\Log\LogAbstract
{
	protected $_socket;
	protected $_default_policy = [
		'address' => 'udp://127.0.0.1:13131',
		'timeout' => 1
	];
	
	public function __construct(array $policy = [])
	{
		$this->_default_policy = array_merge($this->_default_policy, $policy);
		$this->_socket         = stream_socket_client($this->_default_policy['address'], $errno, $errstr, $this->_default_policy['timeout']);
		
		stream_set_blocking($this->_socket, 0);
	}
	
	public function write($log)
	{
		if (is_resource($this->_socket)) {
			fwrite($this->_socket, $log);
		}
	}
	
	public function __destruct()
	{
		if (is_resource($this->_socket)) {
			fclose($this->_socket);
			$this->_socket = null;
		}
	}
}
