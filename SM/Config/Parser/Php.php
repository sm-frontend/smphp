<?php
namespace SM\Config\Parser;

class Php extends \SM\Config\ParserAbstract
{
	protected $cached = false;
	
	public function parse()
	{
		$data = require $this->path;
		
		if (is_callable($data)) {
			$data = call_user_func($data);
		}
		
		if (!is_array($data)) {
			throw new \Exception('PHP file does not return an array.');
		}
		
		return $data;
	}
	
	public static function getSupportedExtensions()
	{
		return ['php'];
	}
}
