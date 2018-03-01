<?php
namespace SM\Transform\Driver;

class Xml
{
	public function encode($data, $node_name = 'root')
	{
		return \SM\Xml\Array2Xml::createXML($node_name, $data);
	}
	
	public function decode($data, $root = false)
	{
		return \SM\Xml\Xml2Array::createArray($data, $root);
	}
}
