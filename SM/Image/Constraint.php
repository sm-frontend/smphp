<?php
namespace SM\Image;

class Constraint
{
	const ASPECTRATIO = 1;
	const UPSIZE = 2;
	
	private $size;
	private $fixed = 0;
	
	public function __construct(Size $size)
	{
		$this->size = $size;
	}
	
	public function getSize()
	{
		return $this->size;
	}
	
	public function fix($type)
	{
		$this->fixed |= $type;
	}
	
	public function isFixed($type)
	{
		return ($this->fixed & $type) === $type;
	}
	
	public function reset()
	{
		$this->fixed = 0;
	}
	
	public function aspectRatio()
	{
		$this->fix(static::ASPECTRATIO);
	}
	
	public function upsize()
	{
		$this->fix(static::UPSIZE);
	}
}
