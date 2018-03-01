<?php
namespace SM\Image;

class Font
{
	public $text;
	public $size = 12;
	public $color = '#000000';
	public $angle = 0;
	public $position;
	public $file;
	
	public function __construct($text = null)
	{
		$this->text = $text;
	}
	
	public function text($text)
	{
		$this->text = $text;
	}
	
	public function getText()
	{
		return $this->text;
	}
	
	public function size($size)
	{
		$this->size = $size;
	}
	
	public function getSize()
	{
		return $this->size;
	}
	
	public function color($color)
	{
		$this->color = $color;
	}
	
	public function getColor()
	{
		return $this->color;
	}
	
	public function angle($angle)
	{
		$this->angle = $angle;
	}
	
	public function getAngle()
	{
		return $this->angle;
	}
	
	public function position($position)
	{
		$this->position = $position;
	}
	
	public function getPosition()
	{
		return $this->position;
	}
	
	public function file($file)
	{
		$this->file = $file;
	}
	
	public function getFile()
	{
		return $this->file;
	}
	
	public function hasApplicableFontFile()
	{
		if (is_string($this->file)) {
			return file_exists($this->file);
		}
		return false;
	}
}
