<?php
namespace SM\Config\Parser;

use SM\Util\File;
use SM\Transform\Transform;

class Json extends \SM\Config\ParserAbstract
{
	public function parse()
	{
		return Transform::jsonDecode(File::get($this->path), true);
	}
	
	public static function getSupportedExtensions()
	{
		return ['json'];
	}
}
