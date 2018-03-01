<?php
namespace SM\Image;

class Point
{
	public $x;
	public $y;
	
	public function __construct($x = null, $y = null)
	{
		$this->x = is_numeric($x) ? intval($x) : 0;
		$this->y = is_numeric($y) ? intval($y) : 0;
	}
	
	public function setX($x)
	{
		$this->x = intval($x);
	}
	
	public function setY($y)
	{
		$this->y = intval($y);
	}
	
	public function setPosition($x, $y)
	{
		$this->setX($x);
		$this->setY($y);
		
		return $this;
	}
	
	public function rotate($angle, Point $pivot)
	{
		$sin = round(sin(deg2rad($angle)), 6);
		$cos = round(cos(deg2rad($angle)), 6);
		
		$x   = $cos * ($this->x - $pivot->x) - $sin * ($this->y - $pivot->y) + $pivot->x;
		$y   = $sin * ($this->x - $pivot->x) + $cos * ($this->y - $pivot->y) + $pivot->y;
		
		return $this->setPosition($x, $y);
	}
}
