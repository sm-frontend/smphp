<?php
namespace SM\Log\Driver;

class File extends \SM\Log\LogAbstract
{
	protected $_default_policy = [
		'dest' => ''
	];
	
	public function __construct(array $policy = [])
	{
		$this->_default_policy = array_merge($this->_default_policy, $policy);
	}
	
	public function write($log)
	{
		if (isset($this->_default_policy['dest']) && '' != $this->_default_policy['dest']) {
			error_log($log . PHP_EOL, 3, $this->_default_policy['dest']);
		} else {
			error_log($log);
		}
	}
}
