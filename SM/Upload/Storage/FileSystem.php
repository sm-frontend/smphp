<?php
namespace SM\Upload\Storage;

use SM\Util\Dir;
use SM\Upload\StorageInterface;
use SM\Upload\FileInfoInterface;

class FileSystem implements StorageInterface
{
	protected $directory;
	protected $overwrite;
	
	public function __construct($directory, $overwrite = false)
	{
		$this->directory = Dir::normalizePath($directory);
		
		if (!is_dir($directory)) {
			throw new \Exception('Directory is not exist.');
		}
		
		if (!is_writable($directory)) {
			throw new \Exception('Directory is not writable.');
		}
		
		$this->overwrite = (bool) $overwrite;
	}
	
	public function upload(FileInfoInterface $fileInfo)
	{
		$destFile = $this->getDestFile($fileInfo);
		
		if (!$this->overwrite && is_file($destFile)) {
			throw new \Exception('File already exists.');
		}
		
		if (!$this->moveUploadedFile($fileInfo->getBase64(), $fileInfo->getPathname(), $destFile)) {
			throw new \Exception('File could not be moved to final destination.');
		}
	}
	
	protected function getDestFile($fileInfo)
	{
		return $this->directory . DIRECTORY_SEPARATOR . $fileInfo->getNameWithExtension();
	}
	
	protected function moveUploadedFile($base64, $source, $dest)
	{
		if (!$base64) {
			return move_uploaded_file($source, $dest);
		} else {
			return copy($source, $dest);
		}
	}
}
