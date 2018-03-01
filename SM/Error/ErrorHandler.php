<?php
namespace SM\Error;

use SM\Log\Log;
use SM\Util\Ip;

class ErrorHandler
{
	private static $noticeErrors = [E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED];
	private static $fatalErrors  = [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];
	private static $serverInfo   = null;
	
	public static function register()
	{
		set_error_handler([__CLASS__, 'errorHandler']);
		set_exception_handler([__CLASS__, 'exceptionHandler']);
		register_shutdown_function([__CLASS__, 'shutdownHandler']);
	}
	
	public static function errorHandler($errno, $errstr, $errfile = '', $errline = 0, $errcontext = [])
	{
		if (in_array($errno, static::$noticeErrors, true)) {
			return false;
		}
		
		if (isset(Log::$phpErrorToLevel[$errno])) {
			$level    = Log::$phpErrorToLevel[$errno];
			
			$message  = $errfile . '(' . $errline . '): ' . $errstr;
			$message .= static::serverInfo();

			Log::$level('[PHP ' . $level . '] ' . $message);
			
			if (SM_DEBUG) {
				echo 'PHP ' . $level . ': ', $message, PHP_EOL;
			}
		}
	}
	
	public static function exceptionHandler($exception)
	{
		$message  = $exception->getFile() . '(' . $exception->getLine() . '): ' . $exception->getMessage();
		$message .= static::serverInfo();

		Log::fatal('[PHP exception] ' . $message);
		
		if (SM_DEBUG) {
			echo 'PHP exception: ', $message, PHP_EOL;
		}
	}
	
	public static function shutdownHandler()
	{
		$lastError = error_get_last();
		
		if ($lastError && static::isFatalError($lastError)) {
			$message  = $lastError['file'] . '(' . $lastError['line'] . '): ' . $lastError['message'];
			$message .= static::serverInfo();

			Log::fatal('[PHP Fatal Error] ' . $message);
			
			if (SM_DEBUG) {
				echo 'PHP Fatal Error: ', $message, PHP_EOL;
			}
		}
		
		Log::save();
	}
	
	public static function isFatalError($error)
	{
		return isset($error['type']) && in_array($error['type'], static::$fatalErrors, true);
	}
	
	public static function serverInfo()
	{
		if (!static::$serverInfo) {
			static::$serverInfo  = ' $_SERVER = ' . var_export($_SERVER, true);
			static::$serverInfo .= ' [' . Ip::fetchLocalIp() . ']';
			
			return static::$serverInfo;
		}
		return '';
	}
}
