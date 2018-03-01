<?php
namespace SM\Upload;

use SM\Util\File as FileUtil;

class FileInfo extends \SplFileInfo implements FileInfoInterface
{
	protected $url;
	protected $name;
	protected $extension;
	protected $mimetype;
	protected $isBase64;
	
	public function __construct($filePathname, $newName = null, $base64 = false)
	{
		$expectName = is_null($newName) ? $filePathname : $newName;
		
		$this->setName(FileUtil::filename($expectName));
		$this->setExtension(FileUtil::extension($expectName));
		
		$this->setBase64($base64);
		
		parent::__construct($filePathname);
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$name       = preg_replace('/([^\w\s\d\-_~,;:\[\]\(\).]|[\.]{2,})/', '', $name);
		$name       = basename($name);
		
		$this->name = $name ?: uniqid();
	}
	
	public function getExtension()
	{
		return $this->extension;
	}
	
	public function setExtension($extension)
	{
		$this->extension = strtolower($extension);
	}
	
	public function getUrl()
	{
		return $this->url;
	}
	
	public function setUrl($url)
	{
		$this->url = $url;
	}
	
	public function setBase64($base64)
	{
		$this->isBase64 = (bool) $base64;
	}
	
	public function getBase64()
	{
		return $this->isBase64;
	}
	
	public function getNameWithExtension()
	{
		return $this->extension === '' ? $this->name : sprintf('%s.%s', $this->name, $this->extension);
	}
	
	public function getMimetype()
	{
		if (!isset($this->mimetype)) {
			$this->mimetype = FileUtil::mime($this->getPathname());
		}
		
		return $this->mimetype;
	}
	
	public function getMd5()
	{
		return md5_file($this->getPathname());
	}
	
	public function getHash($algorithm = 'md5')
	{
		return hash_file($algorithm, $this->getPathname());
	}
	
	public function getContents()
	{
		return FileUtil::get($this->getPathname());
	}
	
	public function isUploadedFile()
	{
		if (!$this->isBase64) {
			return is_uploaded_file($this->getPathname());
		}
		return true;
	}
	
	public static function createFromFactory($tmpName, $name = null, $base64 = false)
	{
		return new static($tmpName, $name, $base64);
	}
}
