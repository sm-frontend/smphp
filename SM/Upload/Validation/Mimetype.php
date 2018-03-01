<?php
namespace SM\Upload\Validation;

use SM\Upload\FileInfoInterface;
use SM\Upload\ValidationInterface;

class Mimetype implements ValidationInterface
{
	protected $mimetypes;
	
	public function __construct($mimetypes)
	{
		if (is_string($mimetypes)) {
			$mimetypes = [$mimetypes];
		}
		
		$this->mimetypes = $mimetypes;
	}
	
	public function validate(FileInfoInterface $fileInfo)
	{
		if (!in_array($fileInfo->getMimetype(), $this->mimetypes)) {
			throw new \Exception(sprintf('Invalid mimetype. Must be one of: %s', implode(', ', $this->mimetypes)));
		}
	}
}
