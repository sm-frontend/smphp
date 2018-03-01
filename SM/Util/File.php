<?php
namespace SM\Util;

class File
{
	public static function exists($filename)
	{
		return file_exists($filename);
	}
	
	public static function get($filename)
	{
		if (static::isFile($filename)) {
			return file_get_contents($filename);
		}
		return false;
	}
	
	public static function getRequire($filename, $once = false)
	{
		if (static::isFile($filename)) {
			if (!$once) {
				return require $filename;
			} else {
				return require_once $filename;
			}
		}
	}
	
	public static function put($filename, $data, $lock = false)
	{
		return file_put_contents($filename, $data, $lock ? LOCK_EX : 0);
	}
	
	public static function append($filename, $data)
	{
		return file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);
	}
	
	public static function extension($path)
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}
	
	public static function filename($path)
	{
		return pathinfo($path, PATHINFO_FILENAME);
	}
	
	public static function dirname($path)
	{
		return pathinfo($path, PATHINFO_DIRNAME);
	}
	
	public static function size($filename)
	{
		return filesize($filename);
	}
	
	public static function mime($filename, $isFile = true)
	{
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		
		if ($isFile && static::isFile($filename)) {
			return $finfo->file($filename);
		} else {
			return $finfo->buffer($filename);
		}
	}
	
	public static function lastModified($filename)
	{
		return filemtime($filename);
	}
	
	public static function isWritable($filename)
	{
		return is_writable($filename);
	}
	
	public static function isFile($filename)
	{
		return is_file($filename);
	}
	
	public static function delete($filename)
	{
		return @unlink($filename);
	}
	
	public static function write($filename, $data, $mode = 0755)
	{
		$dirname = static::dirname($filename);
		
		if (!Dir::create($dirname, $mode)) {
			return false;
		}
		
		$tmpFile = tempnam($dirname, 'swap');
		
		if (static::put($tmpFile, $data) !== false && rename($tmpFile, $filename)) {
			return true;
		}
		
		static::delete($tmpFile);
		return false;
	}
}
