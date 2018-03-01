<?php
namespace SM\Upload\Validation;

use SM\Upload\FileInfoInterface;
use SM\Upload\ValidationInterface;

class Extension implements ValidationInterface
{
	protected $allowedExtensions;
	
	public function __construct($allowedExtensions)
	{
		if (is_string($allowedExtensions)) {
			$allowedExtensions = [$allowedExtensions];
		}
		
		$this->allowedExtensions = array_map('strtolower', $allowedExtensions);
	}
	
	public function validate(FileInfoInterface $fileInfo)
	{
		$fileExtension = strtolower($fileInfo->getExtension());
		
		if (!in_array($fileExtension, $this->allowedExtensions)) {
			throw new \Exception(sprintf('Invalid file extension. Must be one of: %s', implode(', ', $this->allowedExtensions)));
		}
	}
}
