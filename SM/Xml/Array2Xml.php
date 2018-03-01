<?php
namespace SM\Xml;

class Array2Xml
{
	private $document;
	
	public function __construct($nodeName, array $array = [], $version = '1.0', $encoding = 'UTF-8', $formatOutput = true)
	{
		$this->document = new \DOMDocument($version, $encoding);
		$this->document->formatOutput = $formatOutput;
		
		$this->document->appendChild($this->convert($nodeName, $array));
	}
	
	public static function createXML($nodeName, array $array = [], $version = '1.0', $encoding = 'UTF-8', $formatOutput = true)
	{
		$nodeName  = $nodeName ?: 'root';
		
		$converter = new static($nodeName, $array, $version, $encoding, $formatOutput);
		return $converter->toXml();
	}
	
	public function toXml()
	{
		return $this->document->saveXML();
	}
	
	public function toDom()
	{
		return $this->document;
	}
	
	private function convert($nodeName, $array)
	{
		$node = $this->document->createElement($nodeName);
		
		if (is_array($array)) {
			if (isset($array['@attributes'])) {
				foreach ($array['@attributes'] as $key => $value) {
					if (!static::isValidTagName($key)) {
						throw new \Exception('Invalid attr name: ' . $key . ' in node: ' . $nodeName);
					}
					$node->setAttribute($key, $this->bool2Str($value));
				}
				unset($array['@attributes']);
			}
			
			if (isset($array['@value'])) {
				$node->nodeValue = \SM\Util\Str::htmlEscape($array['@value']);
				return $node;
			} elseif (isset($array['@cdata'])) {
				$node->appendChild($this->createChild($array['@cdata']));
				return $node;
			}
		}
		
		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (!$this->isValidTagName($key)) {
					throw new \Exception('Invalid tag name: ' . $key . ' in node: ' . $nodeName);
				}
				
				if (is_array($value) && is_numeric(key($value))) {
					foreach ($value as $k => $v) {
						$node->appendChild($this->convert($key, $v));
					}
				} else {
					$node->appendChild($this->convert($key, $value));
				}
				unset($array[$key]);
			}
		}
		
		if (!is_array($array)) {
			$node->appendChild($this->createChild($array));
		}
		return $node;
	}
	
	private function createChild($value)
	{
		if (preg_match('/[\<\>\&\'\"\[\]]/', $value)) {
			$func = 'createCDATASection';
		} else {
			$func = 'createTextNode';
		}
		
		return $this->document->$func($this->bool2Str($value));
	}
	
	private function bool2Str($v)
	{
		$v = $v === true ? 'true' : $v;
		$v = $v === false ? 'false' : $v;
		
		return $v;
	}
	
	private function isValidTagName($tag)
	{
		$pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
		return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
	}
}
