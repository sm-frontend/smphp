<?php
namespace SM\Util\Flexihash;

class Crc32Hasher implements Hasher
{
	public function hash($string)
	{
		return crc32($string);
	}
}
