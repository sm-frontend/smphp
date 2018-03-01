<?php
namespace SM\View\Resource;

use SM\Util\Dir;
use SM\Util\File as FileUtil;
use SM\View\ResourceInterface;

class File implements ResourceInterface
{
	protected $_templateDir;
	protected $_suffix;
	
	public function __construct($templateDir = 'tpl/', $suffix = '.html')
	{
		$this->_templateDir = Dir::normalizePath($templateDir);
		$this->_suffix      = $suffix;
	}
	
	public function getTemplateId($tpl)
	{
		return $tpl . '.php';
	}
	
	public function getTemplate($tpl)
	{
		$rawTpl = $this->getRawTpl($tpl);
		
		if (false !== ($tplData = FileUtil::get($rawTpl))) {
			return $tplData;
		} else {
			throw new \Exception('Template "' . $tpl . '" (' . $rawTpl . ') does not exist.');
		}
	}
	
	public function getTimestamp($tpl)
	{
		return FileUtil::lastModified($this->getRawTpl($tpl));
	}
	
	protected function getRawTpl($tpl)
	{
		return $this->_templateDir . DIRECTORY_SEPARATOR . $tpl . $this->_suffix;
	}
}
