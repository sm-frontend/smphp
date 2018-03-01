<?php
namespace SM\Security\Captcha;

class Captcha
{
	protected $phrase              = null;
	protected $contents            = null;
	protected $textColor           = null;
	protected $backgroundColor     = null;
	protected $backgroundImages    = [];
	protected $distortion          = true;
	protected $maxFrontLines       = null;
	protected $maxBehindLines      = null;
	protected $maxAngle            = 8;
	protected $maxOffset           = 5;
	protected $interpolation       = true;
	protected $ignoreAllEffects    = false;
	protected $allowedBgImageTypes = ['image/png', 'image/jpeg', 'image/gif'];
	
	public function __construct($phrase = null)
	{
		if ($phrase === null) {
			$phrase = $this->getPhrase();
		}
		$this->phrase = $phrase;
	}
	
	public static function create($phrase = null)
	{
		return new static($phrase);
	}
	
	public function setPhrase($phrase)
	{
		$this->phrase = (string) $phrase;
	}
	
	public function setInterpolation($interpolate = true)
	{
		$this->interpolation = $interpolate;
		return $this;
	}
	
	public function setDistortion($distortion)
	{
		$this->distortion = (bool) $distortion;
		return $this;
	}
	
	public function setMaxBehindLines($maxBehindLines)
	{
		$this->maxBehindLines = $maxBehindLines;
		return $this;
	}
	
	public function setMaxFrontLines($maxFrontLines)
	{
		$this->maxFrontLines = $maxFrontLines;
		return $this;
	}
	
	public function setMaxAngle($maxAngle)
	{
		$this->maxAngle = $maxAngle;
		return $this;
	}
	
	public function setMaxOffset($maxOffset)
	{
		$this->maxOffset = $maxOffset;
		return $this;
	}
	
	public function setTextColor($r, $g, $b)
	{
		$this->textColor = [$r, $g, $b];
		return $this;
	}
	
	public function setBackgroundColor($r, $g, $b)
	{
		$this->backgroundColor = [$r, $g, $b];
		return $this;
	}
	
	public function setIgnoreAllEffects($ignoreAllEffects)
	{
		$this->ignoreAllEffects = $ignoreAllEffects;
		return $this;
	}
	
	public function setBackgroundImages(array $backgroundImages)
	{
		$this->backgroundImages = $backgroundImages;
		return $this;
	}
	
	public function build($width = 120, $height = 40, $font = null)
	{
		if ($font === null) {
			$font = __DIR__ . '/Font/captcha' . $this->rand(0, 5) . '.ttf';
		}
		
		if (empty($this->backgroundImages)) {
			$image = imagecreatetruecolor($width, $height);
			
			if ($this->backgroundColor == null) {
				$bg    = imagecolorallocate($image, $this->rand(200, 255), $this->rand(200, 255), $this->rand(200, 255));
			} else {
				$color = $this->backgroundColor;
				$bg    = imagecolorallocate($image, $color[0], $color[1], $color[2]);
			}
			
			$this->background = $bg;
			imagefill($image, 0, 0, $bg);
		} else {
			$randomBackgroundImage = $this->backgroundImages[rand(0, count($this->backgroundImages) - 1)];
			$imageType             = $this->validateBackgroundImage($randomBackgroundImage);
			$image                 = $this->createBackgroundImageFromType($randomBackgroundImage, $imageType);
		}
		
		if (!$this->ignoreAllEffects) {
			$square  = $width * $height;
			$effects = $this->rand($square / 3000, $square / 2000);
			
			if ($this->maxBehindLines != null && $this->maxBehindLines > 0) {
				$effects = min($this->maxBehindLines, $effects);
			}
			
			if ($this->maxBehindLines !== 0) {
				for ($e = 0; $e < $effects; $e++) {
					$this->drawLine($image, $width, $height);
				}
			}
		}
		
		$color = $this->writePhrase($image, $this->phrase, $font, $width, $height);
		
		if (!$this->ignoreAllEffects) {
			$square  = $width * $height;
			$effects = $this->rand($square / 3000, $square / 2000);
			
			if ($this->maxFrontLines != null && $this->maxFrontLines > 0) {
				$effects = min($this->maxFrontLines, $effects);
			}
			
			if ($this->maxFrontLines !== 0) {
				for ($e = 0; $e < $effects; $e++) {
					$this->drawLine($image, $width, $height, $color);
				}
			}
			
			if ($this->distortion) {
				$image = $this->distort($image, $width, $height, $bg);
			}
			
			$this->postEffect($image);
		}
		
		$this->contents = $image;
		return $this;
	}
	
	public function save($filename, $quality = 90)
	{
		imagejpeg($this->contents, $filename, $quality);
	}
	
	public function output($quality = 90)
	{
		imagejpeg($this->contents, null, $quality);
	}
	
	public function get($quality = 90)
	{
		ob_start();
		$this->output($quality);
		
		return ob_get_clean();
	}
	
	public function inline($quality = 90)
	{
		return 'data:image/jpeg;base64,' . base64_encode($this->get($quality));
	}
	
	protected function drawLine($image, $width, $height, $tcol = null)
	{
		if ($tcol === null) {
			$tcol = imagecolorallocate($image, $this->rand(100, 255), $this->rand(100, 255), $this->rand(100, 255));
		}
		
		if ($this->rand(0, 1)) {
			$Xa = $this->rand(0, $width / 2);
			$Ya = $this->rand(0, $height);
			$Xb = $this->rand($width / 2, $width);
			$Yb = $this->rand(0, $height);
		} else {
			$Xa = $this->rand(0, $width);
			$Ya = $this->rand(0, $height / 2);
			$Xb = $this->rand(0, $width);
			$Yb = $this->rand($height / 2, $height);
		}
		
		imagesetthickness($image, $this->rand(1, 3));
		imageline($image, $Xa, $Ya, $Xb, $Yb, $tcol);
	}
	
	protected function distort($image, $width, $height, $bg)
	{
		$contents = imagecreatetruecolor($width, $height);
		
		$X        = $this->rand(0, $width);
		$Y        = $this->rand(0, $height);
		$phase    = $this->rand(0, 10);
		$scale    = 1.1 + $this->rand(0, 10000) / 30000;
		
		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				$Vx = $x - $X;
				$Vy = $y - $Y;
				$Vn = sqrt($Vx * $Vx + $Vy * $Vy);
				
				if ($Vn != 0) {
					$Vn2 = $Vn + 4 * sin($Vn / 30);
					$nX  = $X + ($Vx * $Vn2 / $Vn);
					$nY  = $Y + ($Vy * $Vn2 / $Vn);
				} else {
					$nX = $X;
					$nY = $Y;
				}
				$nY = $nY + $scale * sin($phase + $nX * 0.2);
				
				if ($this->interpolation) {
					$p = $this->interpolate(
						$nX - floor($nX),
						$nY - floor($nY),
						$this->getCol($image, floor($nX), floor($nY), $bg),
						$this->getCol($image, ceil($nX), floor($nY), $bg),
						$this->getCol($image, floor($nX), ceil($nY), $bg),
						$this->getCol($image, ceil($nX), ceil($nY), $bg)
					);
				} else {
					$p = $this->getCol($image, round($nX), round($nY), $bg);
				}
				
				if ($p == 0) {
					$p = $bg;
				}
				
				imagesetpixel($contents, $x, $y, $p);
			}
		}
		return $contents;
	}
	
	protected function postEffect($image)
	{
		if (!function_exists('imagefilter')) {
			return;
		}
		
		if ($this->backgroundColor != null || $this->textColor != null) {
			return;
		}
		
		if ($this->rand(0, 1) == 0) {
			imagefilter($image, IMG_FILTER_NEGATE);
		}
		
		if ($this->rand(0, 10) == 0) {
			imagefilter($image, IMG_FILTER_EDGEDETECT);
		}
		
		imagefilter($image, IMG_FILTER_CONTRAST, $this->rand(-50, 10));
		
		if ($this->rand(0, 5) == 0) {
			imagefilter($image, IMG_FILTER_COLORIZE, $this->rand(-80, 50), $this->rand(-80, 50), $this->rand(-80, 50));
		}
	}
	
	public static function getPhrase($length = 4)
	{
		$charset = 'abcdefghijkmnpqrstuvwxy23456789';
		$chars   = str_split($charset);
		
		$phrase  = '';
		for ($i = 0; $i < $length; $i++) {
			$phrase .= $chars[array_rand($chars)];
		}
		
		return $phrase;
	}
	
	protected function writePhrase($image, $phrase, $font, $width, $height)
	{
		$length = mb_strlen($phrase);
		
		if ($length === 0) {
			return imagecolorallocate($image, 0, 0, 0);
		}
		
		$size       = $width / $length - $this->rand(0, 3) - 1;
		$box        = imagettfbbox($size, 0, $font, $phrase);
		
		$textWidth  = $box[2] - $box[0];
		$textHeight = $box[1] - $box[7];
		
		$x          = ($width - $textWidth) / 2;
		$y          = ($height - $textHeight) / 2 + $size;
		
		if (!$this->textColor) {
			$textColor = [$this->rand(0, 150), $this->rand(0, 150), $this->rand(0, 150)];
		} else {
			$textColor = $this->textColor;
		}
		
		$col = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
		
		for ($i = 0; $i < $length; $i++) {
			$symbol = mb_substr($phrase, $i, 1);
			$box    = imagettfbbox($size, 0, $font, $symbol);
			$w      = $box[2] - $box[0];
			
			$angle  = $this->rand(-$this->maxAngle, $this->maxAngle);
			$offset = $this->rand(-$this->maxOffset, $this->maxOffset);
			
			imagettftext($image, $size, $angle, $x, $y + $offset, $col, $font, $symbol);
			$x += $w;
		}
		
		return $col;
	}
	
	protected function rand($min, $max)
	{
		return mt_rand($min, $max);
	}
	
	protected function interpolate($x, $y, $nw, $ne, $sw, $se)
	{
		list($r0, $g0, $b0) = $this->getRGB($nw);
		list($r1, $g1, $b1) = $this->getRGB($ne);
		list($r2, $g2, $b2) = $this->getRGB($sw);
		list($r3, $g3, $b3) = $this->getRGB($se);
		
		$cx = 1.0 - $x;
		$cy = 1.0 - $y;
		
		$m0 = $cx * $r0 + $x * $r1;
		$m1 = $cx * $r2 + $x * $r3;
		$r  = (int) ($cy * $m0 + $y * $m1);
		
		$m0 = $cx * $g0 + $x * $g1;
		$m1 = $cx * $g2 + $x * $g3;
		$g  = (int) ($cy * $m0 + $y * $m1);
		
		$m0 = $cx * $b0 + $x * $b1;
		$m1 = $cx * $b2 + $x * $b3;
		$b  = (int) ($cy * $m0 + $y * $m1);
		
		return ($r << 16) | ($g << 8) | $b;
	}
	
	protected function getCol($image, $x, $y, $background)
	{
		$L = imagesx($image);
		$H = imagesy($image);
		
		if ($x < 0 || $x >= $L || $y < 0 || $y >= $H) {
			return $background;
		}
		
		return imagecolorat($image, $x, $y);
	}
	
	protected function getRGB($col)
	{
		return [
			(int) ($col >> 16) & 0xff,
			(int) ($col >> 8) & 0xff,
			(int) ($col) & 0xff
		];
	}
	
	protected function validateBackgroundImage($backgroundImage)
	{
		if (!is_file($backgroundImage)) {
			$backgroundImageExploded = explode('/', $backgroundImage);
			$imageFileName           = count($backgroundImageExploded) > 1 ? $backgroundImageExploded[count($backgroundImageExploded) - 1] : $backgroundImage;
			
			throw new \Exception('Invalid background image: ' . $imageFileName);
		}
		
		$imageType = \SM\Util\File::mime($backgroundImage);
		
		if (!in_array($imageType, $this->allowedBgImageTypes)) {
			throw new \Exception('Invalid background image type! Allowed types are: ' . join(', ', $this->allowedBgImageTypes));
		}
		
		return $imageType;
	}
	
	protected function createBackgroundImageFromType($backgroundImage, $imageType)
	{
		switch ($imageType) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg($backgroundImage);
			break;
			
			case 'image/png':
				$image = imagecreatefrompng($backgroundImage);
			break;
			
			case 'image/gif':
				$image = imagecreatefromgif($backgroundImage);
			break;
			
			default:
				throw new \Exception('Not supported file type for background image!');
		}
		return $image;
	}
}
