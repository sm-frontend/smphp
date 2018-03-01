<?php
namespace SM\Cache\Driver;

use SM\Util\Dir;
use SM\Util\File as FileUtil;

class File extends \SM\Cache\CacheAbstract
{
	protected static $_static_head = '<?php die(); ?>';
	protected $_default_policy     = [
		'cache_dir'       => '',
		'cache_dir_depth' => 2,
		'cache_dir_umask' => 0755,
		'life_time'       => 900
	];
	
	public function __construct(array $policy = [])
	{
		$policy                = array_merge($this->_default_policy, $policy);
		
		$policy['cache_dir']   = Dir::handle($policy['cache_dir']);
		
		$this->_default_policy = $policy;
	}
	
	protected function doSet($key, $value, $lifeTime = null)
	{
		$lifeTime = isset($lifeTime) ? (int) $lifeTime : $this->_default_policy['life_time'];
		
		$filename = $this->getCacheFile($key);
		
		$filedata = static::$_static_head . $this->encode($value);
		
		if (FileUtil::write($filename, $filedata, $this->_default_policy['cache_dir_umask'])) {
			touch($filename, time() + $lifeTime);
			return true;
		}
		return false;
	}
	
	protected function doGet($key)
	{
		$filename = $this->getCacheFile($key);
		
		$filedata = FileUtil::get($filename);
		
		if (false !== $filedata) {
			if (time() > FileUtil::lastModified($filename)) {
				@unlink($filename);
				return false;
			}
			return $this->decode(substr($filedata, strlen(static::$_static_head)));
		}
		
		return false;
	}
	
	protected function doDelete($key)
	{
		$filename = $this->getCacheFile($key);
		
		return is_file($filename) && @unlink($filename);
	}
	
	protected function doSetMulti(array $items, $lifeTime = null)
	{
		$lifeTime = isset($lifeTime) ? (int) $lifeTime : $this->_default_policy['life_time'];
		
		while (list($key, $value) = each($items)) {
			$this->doSet($key, $value, $lifeTime);
		}
	}
	
	protected function getCacheFile($key)
	{
		$hash     = md5($key);
		$cacheDir = $this->_default_policy['cache_dir'] . DIRECTORY_SEPARATOR;
		
		if ($this->_default_policy['cache_dir_depth'] > 0) {
			for ($i = 1; $i <= $this->_default_policy['cache_dir_depth']; $i++) {
				$cacheDir .= substr($hash, ($i - 1) * 2, 2) . DIRECTORY_SEPARATOR;
			}
		}
		
		return $cacheDir . 'cache_' . $hash . '.php';
	}
}
