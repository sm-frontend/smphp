<?php
namespace SM\Util;

class Arr
{
	public static function has($array, $key, $delimiter = '.')
	{
		if (isset($array[$key])) {
			return true;
		}
		
		foreach (explode($delimiter, $key) as $segment) {
			if (is_array($array) && isset($array[$segment])) {
				$array = $array[$segment];
			} else {
				return false;
			}
		}
		return true;
	}
	
	public static function forget(&$array, $key, $delimiter = '.')
	{
		if (isset($array[$key])) {
			unset($array[$key]);
			return;
		}
		
		$keys = explode($delimiter, $key);
		
		while (count($keys) > 1) {
			$key = array_shift($keys);
			
			if (isset($array[$key]) && is_array($array[$key])) {
				$array = &$array[$key];
			} else {
				return;
			}
		}
		
		unset($array[array_shift($keys)]);
	}
	
	public static function setValue(&$array, $key, $value, $delimiter = '.')
	{
		$keys = explode($delimiter, $key);
		
		while (count($keys) > 1) {
			$key = array_shift($keys);
			
			if (!isset($array[$key]) || !is_array($array[$key])) {
				$array[$key] = [];
			}
			$array = &$array[$key];
		}
		
		$array[array_shift($keys)] = $value;
	}
	
	public static function getValue($array, $key, $delimiter = '.', $default = null)
	{
		if (!is_array($array)) {
			$array = [$array];
		}
		
		if (isset($array[$key])) {
			return $array[$key];
		}
		
		foreach (explode($delimiter, $key) as $segment) {
			if (is_array($array) && isset($array[$segment])) {
				$array = $array[$segment];
			} else {
				return $default;
			}
		}
		return $array;
	}
	
	public static function keyExists($key, $array, $caseSensitive = true)
	{
		if ($caseSensitive) {
			return array_key_exists($key, $array);
		} else {
			foreach (array_keys($array) as $k) {
				if (strcasecmp($key, $k) === 0) {
					return true;
				}
			}
			return false;
		}
	}
	
	public static function toArray($data)
	{
		if (is_array($data)) {
			if (!isset($data[0])) {
				$data = [$data];
			}
		} else {
			$data = [$data];
		}
		return $data;
	}
	
	public static function mapValue($array, $fieldMap = [])
	{
		$ret = [];
		foreach ($fieldMap as $k => $v) {
			if (isset($array[$v])) {
				$ret[is_string($k) ? $k : $v] = trim($array[$v]);
			}
		}
		return $ret;
	}
	
	public static function removeEmpty(array &$array, $trim = true)
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				static::removeEmpty($array[$key]);
			} else {
				$value = trim($value);
				if ($value == '') {
					unset($array[$key]);
				} elseif ($trim) {
					$array[$key] = $value;
				}
			}
		}
	}
	
	public static function getCols($array, $col)
	{
		$ret = [];
		foreach ($array as $row) {
			if (is_array($col)) {
				$arr = [];
				foreach ($col as $v) {
					if (isset($row[$v])) {
						$arr[$v] = $row[$v];
					}
				}
				$ret[] = $arr;
			} else {
				if (isset($row[$col])) {
					$ret[] = $row[$col];
				}
			}
		}
		return $ret;
	}
	
	public static function toHashmap($array, $keyField, $valueField = null)
	{
		$ret = [];
		foreach ($array as $row) {
			$ret[$row[$keyField]] = $valueField ? $row[$valueField] : $row;
		}
		return $ret;
	}
	
	public static function groupBy($array, $keyField, $valueField = null)
	{
		$ret = [];
		foreach ($array as $row) {
			$ret[$row[$keyField]][] = $valueField ? $row[$valueField] : $row;
		}
		return $ret;
	}
	
	public static function sortByCol($array, $keyname, $dir = SORT_ASC)
	{
		return static::sortByMultiCols($array, [$keyname => $dir]);
	}
	
	public static function sortByMultiCols($rowset, $args)
	{
		$sort = [];
		if (is_array($rowset) && is_array($args)) {
			foreach ($args as $sortField => $sortDir) {
				$sort[] = static::getCols($rowset, $sortField);
				$sort[] = $sortDir;
			}
		}
		
		$sort[] = &$rowset;
		call_user_func_array('array_multisort', $sort);
		
		return array_pop($sort);
	}
}
