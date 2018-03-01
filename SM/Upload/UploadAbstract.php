<?php
namespace SM\Upload;

abstract class UploadAbstract
{
	protected $storage;
	
	protected $errors      = [];
	protected $objects     = [];
	protected $validations = [];
	
	public function addValidations(array $validations)
	{
		foreach ($validations as $validation) {
			$this->addValidation($validation);
		}
	}
	
	public function addValidation(ValidationInterface $validation)
	{
		$this->validations[] = $validation;
	}
	
	public function getValidations()
	{
		return $this->validations;
	}
	
	public function getErrors()
	{
		return $this->errors;
	}
	
	public function isValid()
	{
		foreach ($this->objects as $fileInfo) {
			if (!$fileInfo->isUploadedFile()) {
				$this->errors[] = sprintf('%s: %s', $fileInfo->getNameWithExtension(), 'Is not an uploaded file');
				continue;
			}
			
			foreach ($this->validations as $validation) {
				try {
					$validation->validate($fileInfo);
				} catch (\Exception $e) {
					$this->errors[] = sprintf('%s: %s', $fileInfo->getNameWithExtension(), $e->getMessage());
				}
			}
		}
		
		return empty($this->errors);
	}
	
	public function upload()
	{
		if (!$this->isValid()) {
			throw new \Exception('File validation failed!');
		}
		
		foreach ($this->objects as $fileInfo) {
			try {
				$this->storage->upload($fileInfo);
			} catch (\Exception $e) {
				$this->errors[] = sprintf('%s: %s', $fileInfo->getNameWithExtension(), $e->getMessage());
			}
		}
		
		if (!empty($this->errors)) {
			throw new \Exception('Have File upload failed.');
		}
	}
	
	public function __call($name, $args)
	{
		$result = null;
		$count  = count($this->objects);
		
		if ($count) {
			if ($count > 1) {
				$result = [];
				foreach ($this->objects as $object) {
					$result[] = call_user_func_array([$object, $name], $args);
				}
			} else {
				$result = call_user_func_array([$this->objects[0], $name], $args);
			}
		}
		return $result;
	}
}
