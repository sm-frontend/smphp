<?php
namespace SM\Lock\Driver;

use SM\Util\Dir;
use SM\Lock\LockAbstract;

class File extends LockAbstract
{
	protected $folder;
	
	public function __construct($policy)
	{
		$this->folder = Dir::handle($policy['folder']);
	}
	
	public function lock($key, $wouldblock = false)
	{
		if (empty($key)) {
			return false;
		}
		
		if (isset($this->lockCache[$key])) {
			return true;
		}
		
		$fileName = $this->getLockFilename($key);
		
		if (!$fp = fopen($fileName, 'w+')) {
			return false;
		}
		
		if (flock($fp, LOCK_EX | LOCK_NB)) {
			$this->lockCache[$fileName] = $fp;
			return true;
		}
		
		if (!$wouldblock) {
			return false;
		}
		
		do {
			usleep(200);
		} while (!flock($fp, LOCK_EX | LOCK_NB));
		
		$this->lockCache[$fileName] = $fp;
		return true;
	}
	
	public function unlock($key)
	{
		$fileName = $this->getLockFilename($key);
		
		if (isset($this->lockCache[$fileName])) {
			$this->unlockFile($fileName);
		}
	}
	
	private function unlockFile($fileName)
	{
		flock($this->lockCache[$fileName], LOCK_UN);
		fclose($this->lockCache[$fileName]);
		
		is_file($fileName) && @unlink($fileName);
		
		$this->lockCache[$fileName] = null;
		unset($this->lockCache[$fileName]);
	}
	
	private function getLockFilename($key)
	{
		return sprintf('%s%s%s.lock', $this->folder, DIRECTORY_SEPARATOR, md5($key));
	}
	
	public function __destruct()
	{
		foreach ($this->lockCache as $fileName => $fp) {
			$this->unlockFile($fileName);
		}
	}
}
