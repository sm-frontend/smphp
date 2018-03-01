<?php
namespace SM\Image;

use SM\Util\File;
use SM\Request\Curl;
use SM\Validator\Validator;

abstract class ImageAbstract
{
	public $data;
	public $mime;
	public $image;
	
	abstract protected function checkAvailable();
	
	abstract protected function initFromPath($path);
	abstract protected function initFromBinary($data);
	abstract protected function initFromGdResource($resource);
	abstract protected function initFromImagick(\Imagick $object);
	
	abstract protected function processJpeg($quality);
	abstract protected function processPng();
	abstract protected function processGif();
	
	public function init($data)
	{
		$this->data = $data;
		
		switch (true) {
			case $this->isGdResource():
				return $this->initFromGdResource($this->data);
			
			case $this->isImagick():
				return $this->initFromImagick($this->data);
				
			case $this->isSmImage():
				return $this->initFromSmImage($this->data);
			
			case $this->isUpload():
				return $this->initFromUpload($this->data);
			
			case $this->isBinary():
				return $this->initFromBinary($this->data);
			
			case $this->isUrl():
				return $this->initFromUrl($this->data);
			
			case $this->isDataUrl():
				return $this->initFromBinary($this->decodeDataUrl($this->data));
			
			case $this->isFilePath():
				return $this->initFromPath($this->data);
			
			case $this->isBase64():
				return $this->initFromBinary(base64_decode($this->data));
			
			default:
				throw new \Exception('Image source not readable.');
		}
	}
	
	public function save($path = null, $quality = null)
	{
		if (is_null($path)) {
			throw new \Exception("Can't write to undefined path.");
		}
		
		$result = $this->process(File::extension($path), $quality);
		File::put($path, $result);
	}
	
	public function output($format = null, $quality = null)
	{
		$result = $this->process($format, $quality);
		
		header('Content-Type: ' . File::mime($result, 0));
		header('Content-Length: ' . strlen($result));
		
		return $result;
	}
	
	public function width()
	{
		return $this->getSize()->width;
	}
	
	public function height()
	{
		return $this->getSize()->height;
	}
	
	public function mime()
	{
		return $this->mime;
	}
	
	private function decodeDataUrl($dataUrl)
	{
		if (!is_string($dataUrl)) {
			return null;
		}
		
		$pattern = '/^data:(?:image\/[a-zA-Z\-\.]+)(?:charset=".+")?;base64,(?P<data>.+)$/';
		preg_match($pattern, $dataUrl, $matches);
		
		if (is_array($matches) && isset($matches['data'])) {
			return base64_decode($matches['data']);
		}
		return null;
	}
	
	protected function initFromSmImage($object)
	{
		return $object;
	}
	
	protected function initFromUpload($field)
	{
		return $this->initFromPath($_FILES[$field]['tmp_name']);
	}
	
	protected function initFromUrl($url)
	{
		$curl = new Curl();
		$curl->get($url, null, [CURLOPT_TIMEOUT => 300, CURLOPT_HEADER => 0]);
		
		if ($data = $curl->execute()) {
			return $this->initFromBinary($data);
		}
		
		throw new \Exception('Unable to init from given url (' . $url . ').');
	}
	
	protected function isGdResource()
	{
		if (is_resource($this->data)) {
			return (get_resource_type($this->data) == 'gd');
		}
		return false;
	}
	
	protected function isImagick()
	{
		return $this->data instanceof \Imagick;
	}
	
	protected function isSmImage()
	{
		return $this->data instanceof self;
	}
	
	protected function isUpload()
	{
		return isset($_FILES[$this->data]) && isset($_FILES[$this->data]['tmp_name']);
	}
	
	protected function isFilePath()
	{
		if (is_string($this->data)) {
			return is_file($this->data);
		}
		return false;
	}
	
	protected function isBinary()
	{
		if (is_string($this->data)) {
			$mime = File::mime($this->data, 0);
			return (substr($mime, 0, 4) != 'text' && $mime != 'application/x-empty');
		}
		return false;
	}
	
	protected function isBase64()
	{
		return Validator::base64($this->data);
	}
	
	protected function isUrl()
	{
		return Validator::url($this->data);
	}
	
	public function isDataUrl()
	{
		return is_null($this->decodeDataUrl($this->data)) ? false : true;
	}
	
	protected function process($format = null, $quality = null)
	{
		$format  = $this->setFormat($format);
		$quality = $this->setQuality($quality);
		
		switch ($format) {
			case 'data-url':
				$result = $this->processDataUrl($quality);
			break;
			
			case 'jpg':
			case 'jpeg':
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/pjpeg':
				$result = $this->processJpeg($quality);
			break;
			
			case 'png':
			case 'image/png':
			case 'image/x-png':
				$result = $this->processPng();
			break;
			
			case 'gif':
			case 'image/gif':
				$result = $this->processGif();
			break;
			
			default:
				throw new \Exception("Image format ({$format}) is not supported.");
		}
		
		return $result;
	}
	
	protected function processDataUrl($quality)
	{
		$mime = $this->mime ?: 'image/png';
		return sprintf('data:%s;base64,%s', $mime, base64_encode($this->process($mime, $quality)));
	}
	
	protected function setFormat($format = null)
	{
		if (is_null($format)) {
			$format = $this->mime;
		}
		return $format ? strtolower($format) : 'jpg';
	}
	
	protected function setQuality($quality = null)
	{
		$quality = is_null($quality) ? 90 : $quality;
		$quality = $quality === 0 ? 1 : $quality;
		
		if ($quality < 0 || $quality > 100) {
			throw new \Exception('Quality must range from 0 to 100.');
		}
		
		return intval($quality);
	}
}
