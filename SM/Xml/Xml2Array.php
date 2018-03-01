<?php
namespace SM\Xml;

class Xml2Array
{
	private $xml;
	private $root;
	
	public function __construct($xml, $root = false, $nonchar = false, $namespace = false)
	{
		$this->xml  = $xml;
		$this->root = $root;
		
		if ($nonchar) {
			$this->removeNonchar();
		}
		
		if ($namespace) {
			$this->removeNamespace();
		}
	}
	
	public static function createArray($xml, $root = false, $nonchar = false, $namespace = false)
	{
		$converter = new static($xml, $root, $nonchar, $namespace);
		return $converter->toArray();
	}
	
	public function toArray()
	{
		$xml = $this->toXml();
		return is_object($xml) ? $this->convert($xml, $this->root) : [];
	}
	
	public function toXml()
	{
		return @simplexml_load_string($this->xml);
	}
	
	private function convert($xml, $root)
	{
		if (!$xml->children()) {
			return $xml->__toString();
		}
		
		$array = [];
		
		foreach ($xml->children() as $element => $node) {
			if ($attributes = $node->attributes()) {
				$data = ($node->count() > 0) ? $this->convert($node, false) : $node->__toString();
				
				if (!is_array($data)) {
					$data = ['value' => $data];
				}
				
				foreach ($attributes as $attr => $value) {
					$data[$attr] = $value->__toString();
				}
			} else {
				$data = $this->convert($node, false);
			}
			
			$this->parseData($array[$element], $data);
		}
		
		return $root ? [$xml->getName() => $array] : $array;
	}
	
	private function parseData(&$current, $data)
	{
		if (!isset($current)) {
			$current = $data;
		} elseif (is_array($current) && isset($current[0])) {
			$current[] = $data;
		} else {
			$current = [$current, $data];
		}
	}
	
	private function removeNonchar()
	{
		$this->xml = \SM\Util\Str::clean($this->xml);
	}
	
	private function removeNamespace()
	{
		$this->xml = preg_replace('/<(\/)?(\w+):(\w+)/', '<${1}${2}_${3}', $this->xml);
		$this->xml = preg_replace('/(\w+):(\w+)="(.*?)"/', '${1}_${2}="${3}"', $this->xml);
	}
}
