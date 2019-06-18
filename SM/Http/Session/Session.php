<?php
namespace SM\Http\Session;

use SM\Util\Str;

class Session
{
	private $_saveHandler          = null;
	private $_namespace            = 'default';
	private $_availableNamespace   = ['default'];
	private $_availableSaveHandler = ['Mysql', 'Memcached', 'Redis'];
	
	public function start($saveHandler = null, $storage = null)
	{
		if ($saveHandler !== null) {
			$saveHandler = Str::nameize($saveHandler);
			$this->setSaveHandler($saveHandler, $storage);
		}
		
		if (!$this->isActived()) {
			session_cache_limiter('');
			session_start([
				'cookie_httponly' => true
			]);
		}
	}
	
	private function isActived()
	{
		return session_status() === PHP_SESSION_ACTIVE ? true : false;
	}
	
	public function setNamespace($namespace)
	{
		if ($this->checkNamespace($namespace)) {
			$this->_namespace = $namespace;
			
			if (!in_array($namespace, $this->_availableNamespace)) {
				$this->_availableNamespace[] = $namespace;
			}
		}
	}
	
	public function getNamespace()
	{
		return $this->_namespace;
	}
	
	public function getAllNamespace()
	{
		return $this->_availableNamespace;
	}
	
	private function checkNamespace($namespace)
	{
		if (empty($namespace) || !is_string($namespace) || !preg_match('/^[a-z]/i', $namespace)) {
			throw new \Exception('Invalid namespace name: ' . $namespace);
		}
		return true;
	}
	
	public function set($id, $value = null, $namespace = null)
	{
		if ($namespace === null) {
			$namespace = $this->getNamespace();
		} else {
			if ($this->checkNamespace($namespace) && !in_array($namespace, $this->_availableNamespace)) {
				$this->_availableNamespace[] = $namespace;
			}
		}
		
		if (is_string($id) && isset($value)) {
			$_SESSION[$namespace][$id] = $value;
		} elseif (is_array($id)) {
			foreach ($id as $k => $v) {
				isset($v) && $_SESSION[$namespace][$k] = $v;
			}
		}
	}
	
	public function get($id = null, $namespace = null)
	{
		if (is_null($id)) {
			return $_SESSION;
		}
		
		if ($namespace === null) {
			$namespace = $this->getNamespace();
		}
		
		if (!isset($_SESSION[$namespace][$id])) {
			return null;
		}
		
		return $_SESSION[$namespace][$id];
	}
	
	public function remove($id, $namespace = null)
	{
		if ($namespace === null) {
			$namespace = $this->getNamespace();
		}
		
		unset($_SESSION[$namespace][$id]);
	}
	
	public function has($id, $namespace = null)
	{
		if ($namespace === null) {
			$namespace = $this->getNamespace();
		}
		
		return isset($_SESSION[$namespace][$id]);
	}
	
	public function setSaveHandler($saveHandler, $storage)
	{
		if ('' !== trim($saveHandler)) {
			if (in_array($saveHandler, $this->_availableSaveHandler) && $storage !== null) {
				$saveHandler = __NAMESPACE__ . '\SaveHandler\\' . $saveHandler;
				$saveHandler = new $saveHandler($storage);
			} else {
				throw new \Exception('Session SaveHandler ' . $saveHandler . ' is not available');
			}
			
			session_set_save_handler($saveHandler, true);
			$this->_saveHandler = $saveHandler;
		}
	}
	
	public function setDomain($domain)
	{
		ini_set('session.cookie_domain', $domain);
	}
	
	public function setTimeout($timeout)
	{
		ini_set('session.gc_maxlifetime', $timeout);
	}
	
	public function getSaveHandler()
	{
		return $this->_saveHandler;
	}
	
	public function getSessionId()
	{
		return session_id();
	}
	
	public function setSessionId($sid)
	{
		session_id($sid);
	}
	
	public function destroy()
	{
		$_SESSION = [];
		session_unset();
		session_destroy();
	}
	
	public function close()
	{
		session_write_close();
	}
}
