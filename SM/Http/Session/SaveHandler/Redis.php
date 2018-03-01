<?php
namespace SM\Http\Session\SaveHandler;

class Redis extends \SessionHandler
{
	protected $redis;
	protected $lifetime;
	
	public function __construct($redis = null)
	{
		$this->redis = $redis;
	}
	
	public function open($savePath, $name)
	{
		$this->lifetime = ini_get('session.gc_maxlifetime');
		return true;
	}
	
	public function close()
	{
		return true;
	}
	
	public function read($id)
	{
		if ($data = $this->redis->get($id)) {
			return $data;
		}
		return null;
	}
	
	public function write($id, $data)
	{
		return $this->redis->set($id, $data, $this->lifetime);
	}
	
	public function destroy($id)
	{
		$this->redis->remove($id);
		return true;
	}
	
	public function gc($maxlifetime)
	{
		return true;
	}
}
