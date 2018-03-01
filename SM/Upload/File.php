<?php
namespace SM\Upload;

class File extends UploadAbstract
{
	protected static $errorCodeMessages = [
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder',
		7 => 'Failed to write file to disk',
		8 => 'A PHP extension stopped the file upload'
	];
	
	public function __construct($key, StorageInterface $storage)
	{
		if (!isset($_FILES[$key])) {
			throw new \Exception("Cann't find uploaded file(s) identified by key: $key");
		}
		
		if (is_array($_FILES[$key]['tmp_name'])) {
			foreach ($_FILES[$key]['tmp_name'] as $index => $tmpName) {
				if ($_FILES[$key]['error'][$index] !== UPLOAD_ERR_OK) {
					$this->errors[] = sprintf(
						'%s: %s',
						$_FILES[$key]['name'][$index],
						static::$errorCodeMessages[$_FILES[$key]['error'][$index]]
					);
					continue;
				}
				
				$this->objects[] = FileInfo::createFromFactory($tmpName, $_FILES[$key]['name'][$index]);
			}
		} else {
			if ($_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
				$this->errors[] = sprintf(
					'%s: %s',
					$_FILES[$key]['name'],
					static::$errorCodeMessages[$_FILES[$key]['error']]
				);
			} else {
				$this->objects[] = FileInfo::createFromFactory($_FILES[$key]['tmp_name'], $_FILES[$key]['name']);
			}
		}
		
		$this->storage = $storage;
	}
}
