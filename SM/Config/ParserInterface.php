<?php
namespace SM\Config;

interface ParserInterface
{
	public function parse();
	public static function getSupportedExtensions();
}
