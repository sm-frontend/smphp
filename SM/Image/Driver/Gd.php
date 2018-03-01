<?php
namespace SM\Image\Driver;

use SM\Util\File;
use SM\Image\Size;
use SM\Image\Font;
use SM\Image\Point;
use SM\Image\Color;
use SM\Image\Image;

class Gd extends \SM\Image\ImageAbstract
{
	public function __construct()
	{
		if (!$this->checkAvailable()) {
			throw new \Exception('GD extension is not loaded.');
		}
	}
	
	protected function initFromPath($path)
	{
		$info = getimagesize($path);
		
		if (false === $info) {
			throw new \Exception("Unable to read image from file ({$path}).");
		}
		
		switch ($info[2]) {
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg($path);
			break;
			
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng($path);
			break;
			
			case IMAGETYPE_GIF:
				$image = imagecreatefromgif($path);
			break;
			
			default:
				throw new \Exception('Unable to read image type. GD driver is only able to decode JPG, PNG or GIF files.');
		}
		
		if (false === $image) {
			throw new \Exception("Unable to read image from file ({$path}).");
		}
		
		$this->gdResourceToTruecolor($image);
		
		$this->mime  = $info['mime'];
		$this->image = $image;
		
		return $this;
	}
	
	protected function initFromBinary($binary)
	{
		$image = imagecreatefromstring($binary);
		
		if (false === $image) {
			throw new \Exception('Unable to init from given binary data.');
		}
		
		$this->mime  = File::mime($binary, 0);
		$this->image = $image;
		
		return $this;
	}
	
	protected function initFromGdResource($resource)
	{
		$this->image = $resource;
		return $this;
	}
	
	protected function initFromImagick(\Imagick $object)
	{
		throw new \Exception('Gd driver is unable to init from Imagick object.');
	}
	
	protected function checkAvailable()
	{
		return (extension_loaded('gd') && function_exists('gd_info'));
	}
	
	protected function processJpeg($quality)
	{
		ob_start();
		imagejpeg($this->image, null, $quality);
		$buffer = ob_get_contents();
		ob_end_clean();
		
		return $buffer;
	}
	
	protected function processPng()
	{
		ob_start();
		imagealphablending($this->image, false);
		imagesavealpha($this->image, true);
		imagepng($this->image, null, -1);
		$buffer = ob_get_contents();
		ob_end_clean();
		
		return $buffer;
	}
	
	protected function processGif()
	{
		ob_start();
		imagegif($this->image);
		$buffer = ob_get_contents();
		ob_end_clean();
		
		return $buffer;
	}
	
	protected function getSize()
	{
		return new Size(imagesx($this->image), imagesy($this->image));
	}
	
	protected function gdResourceToTruecolor(&$resource)
	{
		$width  = imagesx($resource);
		$height = imagesy($resource);
		
		$canvas = imagecreatetruecolor($width, $height);
		
		imagealphablending($canvas, false);
		
		$transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
		
		imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
		imagecolortransparent($canvas, $transparent);
		imagealphablending($canvas, true);
		
		imagecopy($canvas, $resource, 0, 0, 0, 0, $width, $height);
		imagedestroy($resource);
		
		$resource = $canvas;
	}
	
	protected function modify($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
	{
		$modified   = imagecreatetruecolor($dst_w, $dst_h);
		$transIndex = imagecolortransparent($this->image);
		
		if ($transIndex != -1) {
			$rgba       = imagecolorsforindex($modified, $transIndex);
			$transColor = imagecolorallocatealpha($modified, $rgba['red'], $rgba['green'], $rgba['blue'], 127);
			
			imagefill($modified, 0, 0, $transColor);
			imagecolortransparent($modified, $transColor);
		} else {
			imagealphablending($modified, false);
			imagesavealpha($modified, true);
		}
		
		imagecopyresampled($modified, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		imagedestroy($this->image);
		
		$this->image = $modified;
		return $this;
	}
	
	public function resize($width, $height, \Closure $callback = null)
	{
		$size    = $this->getSize();
		
		$resized = clone $size;
		$resized = $resized->resize($width, $height, $callback);

		return $this->modify(0, 0, 0, 0, $resized->getWidth(), $resized->getHeight(), $size->getWidth(), $size->getHeight());
	}
	
	public function fit($width, $height = null, \Closure $callback = null, $position = 'center')
	{
		$cropped = $this->getSize()->fit(new Size($width, $height), $position);
		
		$resized = clone $cropped;
		$resized = $resized->resize($width, $height, $callback);
		
		return $this->modify(0, 0, $cropped->pivot->x, $cropped->pivot->y, $resized->getWidth(), $resized->getHeight(), $cropped->getWidth(), $cropped->getHeight());
	}
	
	public function flip($mode = 'h')
	{
		$size = $this->getSize();
		$dst  = clone $size;
		
		switch (strtolower($mode)) {
			case 'v':
				$size->pivot->y = $size->height - 1;
				$size->height   = $size->height * (-1);
			break;
			
			default:
				$size->pivot->x = $size->width - 1;
				$size->width    = $size->width * (-1);
			break;
		}
		
		return $this->modify(0, 0, $size->pivot->x, $size->pivot->y, $dst->width, $dst->height, $size->width, $size->height);
	}
	
	public function crop($width, $height, $x = null, $y = null)
	{
		$cropped  = new Size($width, $height);
		$position = new Point($x, $y);
		
		if (is_null($x) && is_null($y)) {
			$position = $this->getSize()->align('center')->relativePosition($cropped->align('center'));
		}
		
		return $this->modify(0, 0, $position->x, $position->y, $cropped->width, $cropped->height, $cropped->width, $cropped->height);
	}
	
	public function watermark($source, $position = null, $x = 0, $y = 0)
	{
		$watermark      = Image::load($source);
		
		$image_size     = $this->getSize()->align($position, $x, $y);
		$watermark_size = $watermark->getSize()->align($position);
		
		$target         = $image_size->relativePosition($watermark_size);
		
		imagealphablending($this->image, true);
		imagecopy($this->image, $watermark->image, $target->x, $target->y, 0, 0, $watermark_size->width, $watermark_size->height);
		
		return $this;
	}
	
	public function rotate($angle, $bgcolor = null)
	{
		$color       = new Color($bgcolor);
		$this->image = imagerotate($this->image, $angle, $color->getInt());
		
		return $this;
	}
	
	public function sharpen($amount = 10)
	{
		$min = $amount >= 10 ? $amount * -0.01 : 0;
		$max = $amount * -0.025;
		$abs = ((4 * $min + 4 * $max) * -1) + 1;
		
		$matrix = [
			[$min, $max, $min],
			[$max, $abs, $max],
			[$min, $max, $min]
		];
		
		imageconvolution($this->image, $matrix, 1, 0);
		return $this;
	}
	
	public function blur($amount = 1)
	{
		for ($i = 0; $i < intval($amount); $i++) {
			imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
		}
		return $this;
	}
	
	public function text($text, $x, $y, \Closure $callback = null)
	{
		$font = new Font($text);
		
		if ($callback instanceof \Closure) {
			$callback($font);
		}
		
		if (!$font->hasApplicableFontFile()) {
			throw new \Exception('Font file must be provided to apply text to image.');
		}
		
		$color      = new Color($font->color);
		
		$boxed      = $this->calculateTextBox($font->text, $font->file, $font->size, $font->angle);
		
		$image_size = $this->getSize()->align($font->position, $x, $y);
		
		$boxed_size = new Size($boxed['width'], $boxed['height']);
		
		$target     = $image_size->relativePosition($boxed_size->align($font->position));
		
		imagealphablending($this->image, true);
		imagettftext($this->image, $font->size, $font->angle, $target->x + $boxed['left'], $target->y + $boxed['top'], $color->getInt(), $font->file, $font->text);
		
		return $this;
	}
	
	protected function calculateTextBox($text, $fontFile, $fontSize, $fontAngle)
	{
		$rect = imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
		
		$minX = min([$rect[0], $rect[2], $rect[4], $rect[6]]);
		$maxX = max([$rect[0], $rect[2], $rect[4], $rect[6]]);
		$minY = min([$rect[1], $rect[3], $rect[5], $rect[7]]);
		$maxY = max([$rect[1], $rect[3], $rect[5], $rect[7]]);
		
		return [
			'left'   => abs($minX) - 1,
			'top'    => abs($minY) - 1,
			'width'  => $maxX - $minX,
			'height' => $maxY - $minY
		];
	}
	
	public function __destruct()
	{
		empty($this->image) || imagedestroy($this->image);
	}
}
