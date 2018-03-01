<?php
namespace SM\Upload\Validation;

use SM\Transform\Transform;
use SM\Upload\FileInfoInterface;
use SM\Upload\ValidationInterface;

class Size implements ValidationInterface
{
	protected $minSize;
	protected $maxSize;
	
	public function __construct($maxSize, $minSize = 0)
	{
		if (is_string($maxSize)) {
			$maxSize = Transform::byteDecode($maxSize);
		}
		
		if (is_string($minSize)) {
			$minSize = Transform::byteDecode($minSize);
		}
		
		$this->minSize = $minSize;
		$this->maxSize = $maxSize;
	}
	
	public function validate(FileInfoInterface $fileInfo)
	{
		$fileSize = $fileInfo->getSize();
		
		if ($fileSize < $this->minSize) {
			throw new \Exception(sprintf('File size is too small. Must be greater than or equal to: %s', Transform::byteEncode($this->minSize)));
		}
		
		if ($fileSize > $this->maxSize) {
			throw new \Exception(sprintf('File size is too large. Must be less than: %s', Transform::byteEncode($this->maxSize)));
		}
	}
}
