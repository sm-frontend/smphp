<?php
namespace SM\Config\Parser;

class Ini extends \SM\Config\ParserAbstract
{
	public function parse()
	{
		return parse_ini_file($this->path, true);
	}
	
	public static function getSupportedExtensions()
	{
		return ['ini'];
	}
}
