<?php
namespace SM\Util\Flexihash;

class Md5Hasher implements Hasher
{
	public function hash($string)
	{
		return hexdec(substr(md5($string), 0, 8));
	}
}
