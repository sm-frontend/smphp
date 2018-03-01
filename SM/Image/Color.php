<?php
namespace SM\Image;

class Color
{
	public $r = 255;
	public $g = 255;
	public $b = 255;
	
	public function __construct($value = null)
	{
		if (is_string($value)) {
			$this->hex2rgb($value);
		}
	}
	
	public function hex2rgb($value)
	{
		if (preg_match('/^#?([a-f0-9]{3}|[a-f0-9]{6})$/i', $value, $matches)) {
			$color = $matches[1];
			
			if (strlen($color) == 3) {
				$color = $color . $color;
			}
			
			list($this->r, $this->g, $this->b) = $this->parseHex($color);
		}
	}
	
	public function parseHex($hex)
	{
		return array_map('hexdec', str_split($hex, 2));
	}
	
	public function getInt()
	{
		return ($this->r << 16) | ($this->g << 8) | $this->b;
	}
	
	public function getRgba()
	{
		return sprintf('rgba(%d, %d, %d, %.2f)', $this->r, $this->g, $this->b, 1);
	}
}
