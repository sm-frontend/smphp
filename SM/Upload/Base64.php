<?php
namespace SM\Upload;

use SM\Util\Arr;
use SM\Util\Dir;
use SM\Util\File as FileUtil;
use SM\Validator\Validator;

class Base64 extends UploadAbstract
{
	protected $tmpFile = [];
	
	public function __construct(array $data, StorageInterface $storage)
	{
		$data = Arr::toArray($data);
		
		foreach ($data as $v) {
			if (empty($v['base64'])) {
				throw new \Exception('Base64 data has missed.');
			}
			$this->objects[] = FileInfo::createFromFactory($this->base64ToFile($v), null, true);
		}
		
		$this->storage = $storage;
	}
	
	protected function base64ToFile($data)
	{
		$pattern = '/^data:(?:image\/[a-zA-Z\-\.]+)(?:charset=".+")?;base64,(?P<data>.+)$/';
		preg_match($pattern, $data['base64'], $matches);
		
		if (is_array($matches) && isset($matches['data'])) {
			$data['base64'] = $matches['data'];
		}
		
		if (!Validator::base64($data['base64'])) {
			throw new \Exception('Base64 data decode failed.');
		}
		
		$tmpFile = tempnam(Dir::getTempDir(), 'base64_');
		
		if (!empty($data['ext'])) {
			$newFile = $tmpFile . '.' . $data['ext'];
			rename($tmpFile, $newFile);
			
			$tmpFile = $newFile;
		}
		
		FileUtil::put($tmpFile, base64_decode($data['base64']));
		$this->tmpFile[] = $tmpFile;
		
		return $tmpFile;
	}
	
	public function __destruct()
	{
		foreach ($this->tmpFile as $v) {
			FileUtil::delete($v);
		}
	}
}
