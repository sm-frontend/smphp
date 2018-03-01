<?php
namespace SM\Http\Session\SaveHandler;

class Mysql extends \SessionHandler
{
	protected $db;
	protected $lifetime;
	
	public function __construct($db = null)
	{
		$this->db = $db;
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
		$data = $this->db->from('session')->where('sess_id', $id)->get();
		if (false !== $data) {
			return $data['sess_data'];
		}
		return null;
	}
	
	public function write($id, $data)
	{
		$data = [
			'sess_id'       => $id,
			'sess_data'     => $data,
			'sess_lifetime' => TIMENOW
		];
		return $this->db->from('session')->replace($data);
	}
	
	public function destroy($id)
	{
		$this->db->from('session')->where('sess_id', $id)->delete();
		return true;
	}
	
	public function gc($maxlifetime)
	{
		return $this->db->from('session')->whereLt('sess_lifetime', TIMENOW - $this->lifetime)->delete();
	}
}
