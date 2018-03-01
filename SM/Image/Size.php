<?php
namespace SM\Image;

class Size
{
	public $width;
	public $height;
	public $pivot;
	
	public function __construct($width = null, $height = null, Point $pivot = null)
	{
		$this->width  = is_numeric($width) ? intval($width) : 1;
		$this->height = is_numeric($height) ? intval($height) : 1;
		$this->pivot  = $pivot ? $pivot : new Point;
	}
	
	public function set($width, $height)
	{
		$this->width  = $width;
		$this->height = $height;
	}
	
	public function setPivot(Point $point)
	{
		$this->pivot = $point;
	}
	
	public function getWidth()
	{
		return $this->width;
	}
	
	public function getHeight()
	{
		return $this->height;
	}
	
	public function getRatio()
	{
		return $this->width / $this->height;
	}
	
	public function resize($width, $height, \Closure $callback = null)
	{
		if (is_null($width) && is_null($height)) {
			throw new \InvalidArgumentException('Width or height needs to be defined.');
		}
		
		$dominant_w_size = clone $this;
		$dominant_w_size->resizeHeight($height, $callback);
		$dominant_w_size->resizeWidth($width, $callback);
		
		$dominant_h_size = clone $this;
		$dominant_h_size->resizeWidth($width, $callback);
		$dominant_h_size->resizeHeight($height, $callback);
		
		if ($dominant_h_size->fitsInto(new self($width, $height))) {
			$this->set($dominant_h_size->width, $dominant_h_size->height);
		} else {
			$this->set($dominant_w_size->width, $dominant_w_size->height);
		}
		
		return $this;
	}
	
	private function resizeWidth($width, \Closure $callback = null)
	{
		$constraint = $this->getConstraint($callback);
		
		if ($constraint->isFixed(Constraint::UPSIZE)) {
			$max_width  = $constraint->getSize()->getWidth();
			$max_height = $constraint->getSize()->getHeight();
		}
		
		if (is_numeric($width)) {
			if ($constraint->isFixed(Constraint::UPSIZE)) {
				$this->width = ($width > $max_width) ? $max_width : $width;
			} else {
				$this->width = $width;
			}
			
			if ($constraint->isFixed(Constraint::ASPECTRATIO)) {
				$h = intval(round($this->width / $constraint->getSize()->getRatio()));
				
				if ($constraint->isFixed(Constraint::UPSIZE)) {
					$this->height = ($h > $max_height) ? $max_height : $h;
				} else {
					$this->height = $h;
				}
			}
		}
	}
	
	private function resizeHeight($height, \Closure $callback = null)
	{
		$constraint = $this->getConstraint($callback);
		
		if ($constraint->isFixed(Constraint::UPSIZE)) {
			$max_width  = $constraint->getSize()->getWidth();
			$max_height = $constraint->getSize()->getHeight();
		}
		
		if (is_numeric($height)) {
			if ($constraint->isFixed(Constraint::UPSIZE)) {
				$this->height = ($height > $max_height) ? $max_height : $height;
			} else {
				$this->height = $height;
			}
			
			if ($constraint->isFixed(Constraint::ASPECTRATIO)) {
				$w = intval(round($this->height * $constraint->getSize()->getRatio()));
				
				if ($constraint->isFixed(Constraint::UPSIZE)) {
					$this->width = ($w > $max_width) ? $max_width : $w;
				} else {
					$this->width = $w;
				}
			}
		}
	}
	
	private function fitsInto(Size $size)
	{
		return ($this->width <= $size->width) && ($this->height <= $size->height);
	}
	
	public function fit(Size $size, $position = 'center')
	{
		$auto_height = clone $size;
		
		$auto_height->resize($this->width, null, function ($constraint) {
			$constraint->aspectRatio();
		});
		
		if ($auto_height->fitsInto($this)) {
			$size = $auto_height;
		} else {
			$auto_width = clone $size;
			$auto_width->resize(null, $this->height, function ($constraint) {
				$constraint->aspectRatio();
			});
			$size = $auto_width;
		}
		
		$this->align($position);
		$size->align($position);
		$size->setPivot($this->relativePosition($size));
		
		return $size;
	}
	
	public function relativePosition(Size $size)
	{
		$x = $this->pivot->x - $size->pivot->x;
		$y = $this->pivot->y - $size->pivot->y;
		
		return new Point($x, $y);
	}
	
	public function align($position, $offset_x = 0, $offset_y = 0)
	{
		switch (strtolower($position)) {
			case 'top':
			case 'top-center':
				$x = intval($this->width / 2);
				$y = 0 + $offset_y;
			break;
			
			case 'top-right':
				$x = $this->width - $offset_x;
				$y = 0 + $offset_y;
			break;
			
			case 'left':
			case 'left-center':
				$x = 0 + $offset_x;
				$y = intval($this->height / 2);
			break;
			
			case 'right':
			case 'right-center':
				$x = $this->width - $offset_x;
				$y = intval($this->height / 2);
			break;
			
			case 'bottom-left':
				$x = 0 + $offset_x;
				$y = $this->height - $offset_y;
			break;
			
			case 'bottom':
			case 'bottom-center':
				$x = intval($this->width / 2);
				$y = $this->height - $offset_y;
			break;
			
			case 'bottom-right':
				$x = $this->width - $offset_x;
				$y = $this->height - $offset_y;
			break;
			
			case 'center':
				$x = intval($this->width / 2);
				$y = intval($this->height / 2);
			break;
			
			default:
			case 'top-left':
				$x = 0 + $offset_x;
				$y = 0 + $offset_y;
			break;
		}
		
		$this->pivot->setPosition($x, $y);
		
		return $this;
	}
	
	public function rotate($angle)
	{
		if ($angle != 0) {
			$points = [
				new Point(0, 0),
				new Point($this->width, 0),
				new Point($this->width, $this->height * (-1)),
				new Point(0, $this->height * (-1))
			];
			
			$x_values = [];
			$y_values = [];
			
			$pivot = clone $this->pivot;
			$pivot->y = $pivot->y * (-1);
			
			foreach ($points as $point) {
				$point->rotate($angle, $pivot);
				$x_values[] = $point->x;
				$y_values[] = $point->y;
			}
			
			$max_x_value = max($x_values);
			$max_y_value = max($y_values);
			$min_x_value = min($x_values);
			$min_y_value = min($y_values);
			
			$this->set(abs($min_x_value - $max_x_value), abs($min_y_value - $max_y_value));
			
			$this->setPivot($pivot->setPosition(abs($pivot->x + $min_x_value * (-1)), abs($pivot->y + $max_y_value * (-1))));
		}
		
		return $this;
	}
	
	private function getConstraint(\Closure $callback = null)
	{
		$constraint = new Constraint(clone $this);
		
		if (is_callable($callback)) {
			$callback($constraint);
		}
		
		return $constraint;
	}
}
