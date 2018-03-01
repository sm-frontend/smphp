<?php
namespace SM\Image\Driver;

use SM\Util\File;
use SM\Image\Size;
use SM\Image\Font;
use SM\Image\Point;
use SM\Image\Color;
use SM\Image\Image;

class Imagick extends \SM\Image\ImageAbstract
{
	public function __construct()
	{
		if (!$this->checkAvailable()) {
			throw new \Exception('Imagick extension is not loaded.');
		}
	}
	
	protected function initFromPath($path)
	{
		$image = new \Imagick;
		
		try {
			$image->setBackgroundColor(new \ImagickPixel('transparent'));
			$image->readImage($path);
			$image->setImageType(defined('\Imagick::IMGTYPE_TRUECOLORALPHA') ? \Imagick::IMGTYPE_TRUECOLORALPHA : \Imagick::IMGTYPE_TRUECOLORMATTE);
		} catch (\ImagickException $e) {
			throw new \Exception("Unable to read image from file ({$path}).");
		}
		
		$this->mime  = str_replace('/x-', '/', $image->getImageMimeType());
		$this->image = $image->coalesceImages();
		
		return $this;
	}
	
	public function initFromBinary($binary)
	{
		$image = new \Imagick;
		
		try {
			$image->readImageBlob($binary);
		} catch (\ImagickException $e) {
			throw new \Exception('Unable to init image from binary data.');
		}
		
		$this->mime  = File::mime($binary, 0);
		$this->image = $image;
		
		return $this;
	}
	
	protected function initFromGdResource($resource)
	{
		throw new \Exception('Imagick driver is unable to init from GD resource.');
	}
	
	protected function initFromImagick(\Imagick $imagick)
	{
		$imagick->setImageOrientation(\Imagick::ORIENTATION_UNDEFINED);
		$imagick = $imagick->coalesceImages();
		
		$this->image = $imagick;
		return $this;
	}
	
	protected function checkAvailable()
	{
		return (extension_loaded('imagick') && class_exists('imagick'));
	}
	
	protected function processJpeg($quality)
	{
		$format      = 'jpeg';
		$compression = \Imagick::COMPRESSION_JPEG;
		
		$imagick = $this->image;
		$imagick->setImageBackgroundColor('white');
		$imagick->setBackgroundColor('white');
		
		$imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_MERGE);
		
		$imagick->setFormat($format);
		$imagick->setImageFormat($format);
		$imagick->setCompression($compression);
		$imagick->setImageCompression($compression);
		$imagick->setCompressionQuality($quality);
		$imagick->setImageCompressionQuality($quality);
		
		return $imagick->getImagesBlob();
	}
	
	protected function processPng()
	{
		$format      = 'png';
		$compression = \Imagick::COMPRESSION_ZIP;
		
		$imagick = $this->image;
		$imagick->setFormat($format);
		$imagick->setImageFormat($format);
		$imagick->setCompression($compression);
		$imagick->setImageCompression($compression);
		
		return $imagick->getImagesBlob();
	}
	
	protected function processGif()
	{
		$format      = 'gif';
		$compression = \Imagick::COMPRESSION_LZW;
		
		$imagick = $this->image;
		$imagick->setFormat($format);
		$imagick->setImageFormat($format);
		$imagick->setCompression($compression);
		$imagick->setImageCompression($compression);
		
		return $imagick->getImagesBlob();
	}
	
	protected function getSize()
	{
		return new Size($this->image->getImageWidth(), $this->image->getImageHeight());
	}
	
	public function resize($width, $height, \Closure $callback = null)
	{
		$resized = $this->getSize()->resize($width, $height, $callback);
		
		foreach ($this->image as $frame) {
			$frame->scaleImage($resized->getWidth(), $resized->getHeight());
		}
		
		return $this;
	}
	
	public function fit($width, $height = null, \Closure $callback = null, $position = 'center')
	{
		$cropped = $this->getSize()->fit(new Size($width, $height), $position);
		
		$resized = clone $cropped;
		$resized = $resized->resize($width, $height, $callback);
		
		foreach ($this->image as $frame) {
			$frame->cropImage($cropped->width, $cropped->height, $cropped->pivot->x, $cropped->pivot->y);
			$frame->scaleImage($resized->getWidth(), $resized->getHeight());
			$frame->setImagePage(0, 0, 0, 0);
		}
		
		return $this;
	}
	
	public function flip($mode = 'h')
	{
		foreach ($this->image as $frame) {
			$frame->{$mode == 'v' ? 'flipImage' : 'flopImage'}();
		}
		return $this;
	}
	
	public function crop($width, $height, $x = null, $y = null)
	{
		$cropped  = new Size($width, $height);
		$position = new Point($x, $y);
		
		if (is_null($x) && is_null($y)) {
			$position = $this->getSize()->align('center')->relativePosition($cropped->align('center'));
		}
		
		foreach ($this->image as $frame) {
			$frame->cropImage($cropped->width, $cropped->height, $position->x, $position->y);
			$frame->setImagePage(0, 0, 0, 0);
		}
		
		return $this;
	}
	
	public function watermark($source, $position = null, $x = 0, $y = 0)
	{
		$watermark      = Image::load($source);
		
		$image_size     = $this->getSize()->align($position, $x, $y);
		$watermark_size = $watermark->getSize()->align($position);
		
		$target         = $image_size->relativePosition($watermark_size);
		
		foreach ($this->image as $frame) {
			$frame->compositeImage($watermark->image, \Imagick::COMPOSITE_DEFAULT, $target->x, $target->y);
		}
		
		$watermark->image->destroy();
		
		return $this;
	}
	
	public function rotate($angle, $bgcolor = null)
	{
		$color      = new Color($bgcolor);
		$background = $this->getPixel($color->getRgba());
		
		foreach ($this->image as $frame) {
			$frame->rotateImage($background, ($angle * -1));
		}
		
		return $this;
	}
	
	public function sharpen($amount = 10)
	{
		foreach ($this->image as $frame) {
		 	$frame->unsharpMaskImage(1, 1, $amount / 6.25, 0);
		}
		return $this;
	}
	
	public function blur($amount = 1)
	{
		foreach ($this->image as $frame) {
			$frame->blurImage(1 * $amount, 0.5 * $amount);
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
		
		$draw       = new \ImagickDraw();
		
		$boxed      = $this->calculateTextBox($draw, $font->text, $font->file, $font->size, $font->color);
		
		$image_size = $this->getSize()->align($font->position, $x, $y);
		
		$boxed_size = new Size($boxed['width'], $boxed['height']);
		
		$target     = $image_size->relativePosition($boxed_size->rotate($font->angle)->align($font->position));
		
		$canvas     = new \Imagick;
		
		$canvas->newImage($boxed['width'], $boxed['height'], 'none', 'png');
		$canvas->annotateImage($draw, $boxed['left'], $boxed['top'], 0, $font->text);
		$canvas->rotateImage($this->getPixel('none'), ($font->angle * -1));
		
		foreach ($this->image as $frame) {
			$frame->compositeImage($canvas, \Imagick::COMPOSITE_DEFAULT, $target->x, $target->y);
		}
		
		$canvas->destroy();
		
		return $this;
	}
	
	protected function calculateTextBox($draw, $text, $fontFile, $fontSize, $fontColor)
	{
		$color = new Color($fontColor);
		
		$draw->setFillColor($this->getPixel($color->getRgba()));
		$draw->setStrokeAntialias(true);
		$draw->setTextAntialias(true);
		$draw->setFont($fontFile);
		$draw->setFontSize($fontSize);
		$draw->setResolution(96, 96);
		
		$metrics = $this->image->queryFontMetrics($draw, $text);
		
		return [
			'left'   => 0,
			'top'    => $metrics['ascender'],
			'width'  => $metrics['textWidth'],
			'height' => $metrics['textHeight']
		];
	}
	
	protected function getPixel($color)
	{
		return new \ImagickPixel($color);
	}
	
	public function __destruct()
	{
		empty($this->image) || $this->image->destroy();
	}
}
