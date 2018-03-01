<?php
namespace SM\Config\Parser;

use SM\Util\File;
use SM\Transform\Transform;

class Xml extends \SM\Config\ParserAbstract
{
	public function parse()
	{
		return Transform::xmlDecode(File::get($this->path));
	}
	
	public static function getSupportedExtensions()
	{
		return ['xml'];
	}
}
