<?php
namespace SM\Error;

class ErrorTrigger
{
	public static function trigger($msg, $level)
	{
		trigger_error($msg, $level);
	}

	public static function fatal($msg, $file = null, $line = null)
	{
		static::trigger(static::formatMsg($msg, $file, $line), E_USER_ERROR);
	}

	public static function warning($msg, $file = null, $line = null)
	{
		static::trigger(static::formatMsg($msg, $file, $line), E_USER_WARNING);
	}

	public static function notice($msg, $file = null, $line = null)
	{
		static::trigger(static::formatMsg($msg, $file, $line), E_USER_NOTICE);
	}

	protected static function formatMsg($msg, $file, $line)
	{
		if ($file) {
			$msg .= ' FILE: [' . $file .  ($line ? '(' . $line . ')' : '') . ']';
		}

		return $msg;
	}
}
