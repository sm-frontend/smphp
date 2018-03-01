<?php
namespace SM\Util;

class Dir
{
	public static function handle($dir)
	{
		if (!empty($dir)) {
			$dir = static::normalizePath($dir);
		} else {
			$dir = static::getTempDir();
		}
		
		return $dir;
	}
	
	public static function normalizePath($path)
	{
		return rtrim(preg_replace('#/+#', '/', $path), '\\/');
	}
	
	public static function getTempDir()
	{
		return sys_get_temp_dir();
	}
	
	public static function create($path, $mode = 0777)
	{
		$path = static::normalizePath($path);
		
		if (is_dir($path)) {
			if (!is_writable($path)) {
				@chmod($path, $mode);
			}
			return true;
		}
		return @mkdir($path, $mode, true);
	}
	
	public static function delete($path)
	{
		if (is_dir($path)) {
			$fp = opendir(static::normalizePath($path));
			
			while (false !== ($file = readdir($fp))) {
				if ($file == '.' || $file == '..') {
					continue;
				}
				
				$pf = $path . DIRECTORY_SEPARATOR . $file;
				
				if (is_dir($pf)) {
					static::delete($pf);
				} elseif (is_writable($pf)) {
					@unlink($pf);
				}
			}
			closedir($fp);
			return @rmdir($path);
		} else {
			return @unlink($path);
		}
	}
	
	public static function getFiles($dir)
	{
		$files = [];
		
		if (is_dir($dir)) {
			$dirs = [static::normalizePath($dir)];
			
			do {
				$pop_dir = array_pop($dirs);
				
				foreach (scandir($pop_dir) as $file) {
					if ($file == '.' || $file == '..') {
						continue;
					}
					
					$path = $pop_dir . DIRECTORY_SEPARATOR . $file;
					
					if (is_dir($path)) {
						array_push($dirs, $path);
					} elseif (is_file($path)) {
						$files[] = $path;
					}
				}
			} while ($dirs);
		}
		return $files;
	}
}
