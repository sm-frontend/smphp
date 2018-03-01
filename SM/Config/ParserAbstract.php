<?php
namespace SM\Config;

use SM\Util\File;

abstract class ParserAbstract implements ParserInterface
{
	protected $path;
	protected $policy;
	protected $cached = true;
	
	public function __construct($path, array $policy)
	{
		$this->path   = $path;
		$this->policy = $policy;
	}
	
	public function getData()
	{
		if ($this->cached && $this->policy['cache']) {
			
			$cachePath = $this->getCachePath();
			$timestamp = File::lastModified($this->path);
			
			if (File::isFile($cachePath) && File::lastModified($cachePath) === $timestamp) {
				return require $cachePath;
			}
			
			$data = (array) $this->parse();
			
			if (!empty($data) && File::write($cachePath, $this->getCacheData($data))) {
				touch($cachePath, $timestamp);
				
				if (function_exists('opcache_invalidate')) {
					opcache_invalidate($cachePath, true);
				}
			}
			return $data;
		}
		
		return (array) $this->parse();
	}
	
	public function getCacheData($data)
	{
		return '<?php return ' . var_export($data, true) . ';';
	}
	
	public function getCachePath()
	{
		return sprintf('%s%s%s.php', $this->policy['cache_path'], DIRECTORY_SEPARATOR, md5($this->path));
	}
}
