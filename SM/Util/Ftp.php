<?php
namespace SM\Util;

class Ftp
{
	const ASCII       = FTP_ASCII;
	const TEXT        = FTP_TEXT;
	const BINARY      = FTP_BINARY;
	const IMAGE       = FTP_IMAGE;
	const TIMEOUT_SEC = FTP_TIMEOUT_SEC;
	const AUTOSEEK    = FTP_AUTOSEEK;
	const AUTORESUME  = FTP_AUTORESUME;
	const FAILED      = FTP_FAILED;
	const FINISHED    = FTP_FINISHED;
	const MOREDATA    = FTP_MOREDATA;
	
	private static $aliases = [
		'sslconnect' => 'ssl_connect',
		'getoption'  => 'get_option',
		'setoption'  => 'set_option',
		'nbcontinue' => 'nb_continue',
		'nbfget'     => 'nb_fget',
		'nbfput'     => 'nb_fput',
		'nbget'      => 'nb_get',
		'nbput'      => 'nb_put'
	];
	
	private $resource = null;
	private $state    = [];
	
	public function __construct($url = null, $passiveMode = true)
	{
		if (!$this->ftpCheck()) {
			throw new \Exception('PHP extension FTP is not loaded.');
		} elseif (!empty($url)) {
			$this->ftpConnect($url, $passiveMode);
		}
	}
	
	private function ftpCheck()
	{
		return extension_loaded('ftp');
	}
	
	private function ftpConnect($url, $passiveMode)
	{
		$parts = parse_url($url);
		
		if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['ftp', 'ftps', 'sftp'])) {
			throw new \Exception('Invalid URL.');
		}
		
		$func = $parts['scheme'] === 'ftp' ? 'connect' : 'ssl_connect';
		$port = empty($parts['port']) ? null : (int) $parts['port'];
		
		$this->$func($parts['host'], $port);
		
		if (!empty($parts['user']) && !empty($parts['pass'])) {
			$this->login(urldecode($parts['user']), urldecode($parts['pass']));
		}
		
		$this->pasv((bool) $passiveMode);
		
		if (isset($parts['path'])) {
			$this->chdir($parts['path']);
		}
	}
	
	public function __call($name, $args)
	{
		$ret  = null;
		$name = strtolower($name);
		$func = 'ftp_' . (isset(static::$aliases[$name]) ? static::$aliases[$name] : $name);
		
		if (!function_exists($func)) {
			throw new \Exception("Call to undefined method Ftp::$name().");
		}
		
		if ($func === 'ftp_connect' || $func === 'ftp_ssl_connect') {
			$this->state    = [$name => $args];
			$this->resource = call_user_func_array($func, $args);
			
		} elseif (!is_resource($this->resource)) {
			throw new \Exception("Not connected to FTP server. Call connect() or ssl_connect() first.");
			
		} else {
			if ($func === 'ftp_login' || $func === 'ftp_pasv') {
				$this->state[$name] = $args;
			}
			
			array_unshift($args, $this->resource);
			$ret = call_user_func_array($func, $args);
			
			if ($func === 'ftp_chdir' || $func === 'ftp_cdup') {
				$this->state['chdir'] = [ftp_pwd($this->resource)];
			}
		}
		
		return $ret;
	}
	
	public function reconnect()
	{
		@ftp_close($this->resource);
		
		foreach ($this->state as $name => $args) {
			call_user_func_array([$this, $name], $args);
		}
	}
	
	public function fileExists($file)
	{
		return (bool) $this->nlist($file);
	}
	
	public function isDir($dir)
	{
		$current = $this->pwd();
		
		try {
			$this->chdir($dir);
		} catch (\Exception $e) {
		}
		
		$this->chdir($current);
		return empty($e);
	}
	
	public function mkDirRecursive($dir)
	{
		$parts = explode('/', $dir);
		$path  = '';
		
		while (!empty($parts)) {
			$path .= array_shift($parts);
			
			try {
				if ($path !== '') {
					$this->mkdir($path);
				}
			} catch (\Exception $e) {
				if (!$this->isDir($path)) {
					throw new \Exception("Cannot create directory '$path'.");
				}
			}
			$path .= '/';
		}
	}
	
	public function deleteRecursive($path)
	{
		if (!$this->delete($path)) {
			foreach ((array) $this->nlist($path) as $file) {
				if ($file !== '.' && $file !== '..') {
					$this->deleteRecursive(strpos($file, '/') === false ? "$path/$file" : $file);
				}
			}
			$this->rmdir($path);
		}
	}
}
