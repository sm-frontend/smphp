<?php
defined('TIMENOW') or define('TIMENOW', time());
defined('START_TIME') or define('START_TIME', microtime(true));

defined('TIMEZONE') or define('TIMEZONE', 'Asia/Shanghai');

defined('SM_PATH') or define('SM_PATH', __DIR__);
defined('SM_ROOT') or define('SM_ROOT', dirname(SM_PATH));

defined('APP_PATH') or define('APP_PATH', SM_ROOT . '/application');

defined('SM_DEBUG') or define('SM_DEBUG', false);
ini_set('display_errors', SM_DEBUG);

class SM
{
	private static $container = null;
	private static $session   = [];
	private static $loader    = null;
	private static $view      = [];
	private static $db        = [];
	
	public static function start()
	{
		static::getLoader();
		
		SM\Error\ErrorHandler::register();
		SM\Util\Date::timeZone(TIMEZONE);
	}
	
	public static function getLoader()
	{
		if (null !== static::$loader) {
			return static::$loader;
		}
		
		require SM_PATH . '/Autoload/ClassLoader.php';
		static::$loader = $loader = new SM\Autoload\ClassLoader();
		
		$loader->set('SM', SM_ROOT);
		$loader->setPsr4('App\\', APP_PATH);
		$loader->setUseIncludePath(true);
		$loader->register(true);
		
		return $loader;
	}
	
	public static function getView($tplPath)
	{
		if (isset(static::$view[$tplPath])) {
			return static::$view[$tplPath];
		}
		
		static::$view[$tplPath] = new SM\View\View($tplPath . 'compiled');
		static::$view[$tplPath]->addResource('file', new SM\View\Resource\File($tplPath));
		
		return static::$view[$tplPath];
	}
	
	public static function getDb($options)
	{
		$guid = SM\Util\Str::guid($options);
		
		if (isset(static::$db[$guid])) {
			return static::$db[$guid];
		}
		
		static::$db[$guid] = new SM\Db\Db($options);
		return static::$db[$guid];
	}
	
	public static function getSession($saveHandler, $storage, $namespace = '')
	{
		$guid = $saveHandler . $namespace . SM\Util\Str::guid($storage);
		
		if (isset(static::$session[$guid])) {
			return static::$session[$guid];
		}
		
		static::$session[$guid] = new SM\Http\Session\Session();
		static::$session[$guid]->start($saveHandler, $storage);
		
		if (!empty($namespace)) {
			static::$session[$guid]->setNamespace($namespace);
		}
		
		return static::$session[$guid];
	}
	
	public static function getContainer()
	{
		if (is_null(static::$container)) {
			static::$container = new SM\Container\Container();
		}
		return static::$container;
	}
	
	public static function __callStatic($name, $args)
	{
		array_unshift($args, $name);
		return call_user_func_array([static::getContainer(), 'make'], $args);
	}
}
