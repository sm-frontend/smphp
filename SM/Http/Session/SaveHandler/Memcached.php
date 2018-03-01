<?php
namespace SM\Http\Session\SaveHandler;

class Memcached extends \SessionHandler
{
	protected $memcached;
	protected $lifetime;
	
	public function __construct($memcached = null)
	{
		$this->memcached = $memcached;
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
		if ($data = $this->memcached->get($id)) {
			return $data;
		}
		return null;
	}
	
	public function write($id, $data)
	{
		return $this->memcached->set($id, $data, $this->lifetime);
	}
	
	public function destroy($id)
	{
		$this->memcached->remove($id);
		return true;
	}
	
	public function gc($maxlifetime)
	{
		return true;
	}
}
